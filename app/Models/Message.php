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
        'message_type',
        'metadata',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'metadata' => 'array',
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
     * Relacionamento com o remetente (pode ser User ou Admin)
     */
    public function sender(): BelongsTo
    {
        // Primeiro tenta encontrar como User
        $user = User::find($this->sender_id);
        if ($user) {
            return $this->belongsTo(User::class, 'sender_id');
        }
        
        // Se não encontrar, tenta como Admin
        return $this->belongsTo(Admin::class, 'sender_id');
    }

    /**
     * Obtém o tipo do remetente baseado na tabela chat_user
     */
    public function getSenderTypeAttribute(): ?string
    {
        return \Illuminate\Support\Facades\DB::table('chat_user')
            ->where('chat_id', $this->chat_id)
            ->where('user_id', $this->sender_id)
            ->value('user_type');
    }

    /**
     * Escopo para buscar mensagens entre dois usuários
     */
    public function scopeBetweenUsers($query, int $user1Id, int $user2Id)
    {
        return $query->whereHas('chat', function ($q) use ($user1Id, $user2Id) {
            $q->where('type', 'private')
                ->whereHas('users', function ($subQ) use ($user1Id) {
                    $subQ->where('user_id', $user1Id);
                })
                ->whereHas('users', function ($subQ) use ($user2Id) {
                    $subQ->where('user_id', $user2Id);
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

    /**
     * Escopo para mensagens não lidas
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Escopo para mensagens por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('message_type', $type);
    }
}
