<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Busca ou cria um chat privado entre dois usuários
     */
    public static function findOrCreatePrivateChat(int $user1Id, int $user2Id): self
    {
        // Busca um chat privado que contenha exatamente esses dois usuários
        $chat = self::where('type', 'private')
            ->whereHas('users', function ($query) use ($user1Id) {
                $query->where('user_id', $user1Id);
            })
            ->whereHas('users', function ($query) use ($user2Id) {
                $query->where('user_id', $user2Id);
            })
            ->whereDoesntHave('users', function ($query) use ($user1Id, $user2Id) {
                $query->whereNotIn('user_id', [$user1Id, $user2Id]);
            })
            ->first();

        if (!$chat) {
            // Cria um novo chat privado
            $chat = self::create([
                'type' => 'private',
                'created_by' => $user1Id,
            ]);

            // Adiciona os participantes
            $chat->users()->attach($user1Id, ['user_type' => 'user']);
            $chat->users()->attach($user2Id, ['user_type' => 'user']);
            $chat->name = $chat->users()->where('user_id', $user2Id)->first()->name;
            $chat->save();
            $chat->refresh();
        }

        return $chat;
    }

    /**
     * Adiciona um usuário ao chat
     */
    public function addParticipant(int $userId, string $userType): void
    {
        $this->users()->attach($userId, ['user_type' => $userType]);
    }

    /**
     * Remove um usuário do chat
     */
    public function removeParticipant(int $userId): void
    {
        $this->users()->detach($userId);
    }

    /**
     * Marca mensagens como lidas para um usuário
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
     * Verifica se um usuário é participante do chat
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    /**
     * Obtém participantes ativos
     */
    public function activeParticipants()
    {
        return $this->users()->wherePivot('is_active', true);
    }
}
