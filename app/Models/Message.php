<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'content',
        'sender_id',
        'sender_type',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com o chat
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Relacionamento com o remetente (usuário)
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id')
            ->where('sender_type', 'user');
    }

    /**
     * Relacionamento com o remetente (admin)
     */
    public function senderAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sender_id')
            ->where('sender_type', 'admin');
    }

    /**
     * Escopo para buscar mensagens entre dois usuários
     */
    public function scopeBetweenUsers($query, int $user1Id, string $user1Type, int $user2Id, string $user2Type)
    {
        return $query->whereHas('chat', function ($q) use ($user1Id, $user1Type, $user2Id, $user2Type) {
            $q->where('type', 'private')
                ->whereHas('users', function ($subQ) use ($user1Id, $user1Type) {
                    $subQ->where('user_id', $user1Id)->where('user_type', $user1Type);
                })
                ->whereHas('users', function ($subQ) use ($user2Id, $user2Type) {
                    $subQ->where('user_id', $user2Id)->where('user_type', $user2Type);
                });
        });
    }

    /**
     * Verifica se a mensagem é de um usuário
     */
    public function isFromUser(): bool
    {
        return $this->sender_type === 'user';
    }

    /**
     * Verifica se a mensagem é de um admin
     */
    public function isFromAdmin(): bool
    {
        return $this->sender_type === 'admin';
    }

    /**
     * Marca a mensagem como lida
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }
}
