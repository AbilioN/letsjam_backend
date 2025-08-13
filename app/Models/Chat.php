<?php

namespace App\Models;

use App\Domain\Entities\ChatUser;
use App\Domain\Entities\ChatUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Entities\Chat as ChatEntity;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function toEntity(): ChatEntity
    {
        return new ChatEntity(
            id: $this->id,
            name: $this->name,
            type: $this->type,
            description: $this->description,
            createdBy: $this->created_by,
            createdByType: $this->created_by_type,
            createdAt: $this->created_at,
            updatedAt: $this->updated_at
        );
    }

    public function toEntityFromReciever(ChatUser $reciever): ChatEntity
    {
        return new ChatEntity(
            id: $this->id,
            name: $reciever->getName(),
            type: $this->type,
            description: $this->description,
            createdBy: $reciever->getId(),
            createdByType: $reciever->getType(),
            createdAt: $this->created_at,
            updatedAt: $this->updated_at
        );
    }

    /**
     * Relacionamento com usuários através da tabela pivot
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_user', 'chat_id', 'user_id')
            ->withPivot(['user_type', 'joined_at', 'last_read_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Relacionamento com admins através da tabela pivot
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'chat_user', 'chat_id', 'user_id')
            ->wherePivot('user_type', 'admin')
            ->withPivot(['joined_at', 'last_read_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Relacionamento com mensagens
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Última mensagem do chat
     */
    public function lastMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    /**
     * Obtém o tipo do criador baseado na tabela chat_user
     */
    public function getCreatedByTypeAttribute(): ?string
    {
        return \Illuminate\Support\Facades\DB::table('chat_user')
            ->where('chat_id', $this->id)
            ->where('user_id', $this->created_by)
            ->value('user_type');
    }

    /**
     * Obtém o ChatUser que criou o chat
     */
    public function getCreatedByChatUser(): ?ChatUser
    {
        if (!$this->created_by) {
            return null;
        }

        $userType = $this->created_by_type;
        if (!$userType) {
            return null;
        }

        return ChatUserFactory::createFromChatUserData($this->created_by, $userType);
    }

    /**
     * Verifica se é um chat privado
     */
    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    /**
     * Verifica se é um chat em grupo
     */
    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /**
     * Busca ou cria um chat privado entre dois ChatUsers
     */
    public static function findOrCreatePrivateChat(ChatUser $user1, ChatUser $user2): self
    {
        // Busca um chat privado que contenha exatamente esses dois usuários
        $chat = self::where('type', 'private')
            ->whereHas('users', function ($query) use ($user1) {
                $query->where('user_id', $user1->getId());
            })
            ->whereHas('users', function ($query) use ($user2) {
                $query->where('user_id', $user2->getId());
            })
            ->whereDoesntHave('users', function ($query) use ($user1, $user2) {
                $query->whereNotIn('user_id', [$user1->getId(), $user2->getId()]);
            })
            ->first();

        if (!$chat) {
            // Cria um novo chat privado
            $chat = self::create([
                'type' => 'private',
                'created_by' => $user1->getId(),
            ]);

            // Adiciona os participantes
            $chat->addParticipant($user1);
            $chat->addParticipant($user2);
            $chat->name = $user2->getName();
            $chat->description = '';
            $chat->save();
            $chat->refresh();
        }

        return $chat;
    }

    /**
     * Adiciona um ChatUser ao chat
     */
    public function addParticipant(ChatUser $chatUser): void
    {
        $this->users()->attach($chatUser->getId(), [
            'user_type' => $chatUser->getType(),
            'joined_at' => now(),
            'is_active' => true
        ]);
    }

    /**
     * Remove um ChatUser do chat
     */
    public function removeParticipant(ChatUser $chatUser): void
    {
        $this->users()->detach($chatUser->getId());
    }

    /**
     * Verifica se um ChatUser é participante do chat
     */
    public function hasParticipant(ChatUser $chatUser): bool
    {
        return $this->users()->where('user_id', $chatUser->getId())->exists();
    }

    /**
     * Verifica se um usuário é participante do chat (método de compatibilidade)
     */
    public function hasParticipantById(int $userId): bool
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    /**
     * Marca mensagens como lidas para um ChatUser
     */
    public function markAsReadForChatUser(ChatUser $chatUser): void
    {
        $this->users()->updateExistingPivot($chatUser->getId(), [
            'last_read_at' => now()
        ]);

        // Marca mensagens não lidas como lidas
        $this->messages()
            ->where('sender_id', '!=', $chatUser->getId())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
    }

    /**
     * Marca mensagens como lidas para um usuário (método de compatibilidade)
     */
    public function markAsReadForUser(int $userId): void
    {
        $this->users()->updateExistingPivot($userId, [
            'last_read_at' => now()
        ]);

        // Marca mensagens não lidas como lidas
        $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
    }

    /**
     * Obtém participantes ativos como ChatUsers
     */
    public function getActiveChatUsers(): array
    {
        $participants = \Illuminate\Support\Facades\DB::table('chat_user')
            ->where('chat_id', $this->id)
            ->where('is_active', true)
            ->get();

        $chatUsers = [];
        foreach ($participants as $participant) {
            $chatUsers[] = ChatUserFactory::createFromChatUserData(
                $participant->user_id,
                $participant->user_type
            );
        }

        return $chatUsers;
    }

    /**
     * Obtém participantes ativos
     */
    public function activeParticipants()
    {
        return $this->users()->wherePivot('is_active', true);
    }


}
