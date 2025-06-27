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
        'created_by_type',
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
            ->wherePivot('user_type', 'user')
            ->withPivot(['joined_at', 'last_read_at', 'is_active'])
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
    public static function findOrCreatePrivateChat(int $user1Id, string $user1Type, int $user2Id, string $user2Type): self
    {
        // Busca um chat privado que contenha exatamente esses dois usuários
        $chat = self::where('type', 'private')
            ->whereHas('users', function ($query) use ($user1Id, $user1Type, $user2Id, $user2Type) {
                $query->where(function ($q) use ($user1Id, $user1Type) {
                    $q->where('user_id', $user1Id)->where('user_type', $user1Type);
                })->where(function ($q) use ($user2Id, $user2Type) {
                    $q->where('user_id', $user2Id)->where('user_type', $user2Type);
                });
            })
            ->whereDoesntHave('users', function ($query) use ($user1Id, $user1Type, $user2Id, $user2Type) {
                $query->whereNotIn('user_id', [$user1Id, $user2Id]);
            })
            ->first();

        if (!$chat) {
            // Cria um novo chat privado
            $chat = self::create([
                'type' => 'private',
                'created_by' => $user1Id,
                'created_by_type' => $user1Type,
            ]);

            // Adiciona os participantes
            $chat->users()->attach($user1Id, ['user_type' => $user1Type]);
            $chat->users()->attach($user2Id, ['user_type' => $user2Type]);
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
    public function removeParticipant(int $userId, string $userType): void
    {
        $this->users()->detach($userId);
    }

    /**
     * Marca mensagens como lidas para um usuário
     */
    public function markAsReadForUser(int $userId, string $userType): void
    {
        $this->users()->updateExistingPivot($userId, [
            'last_read_at' => now(),
            'user_type' => $userType
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
}
