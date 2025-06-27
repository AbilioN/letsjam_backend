<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'content',
        'sender_type',
        'sender_id',
        'receiver_type',
        'receiver_id',
        'is_read',
        'read_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo($this->sender_type === 'admin' ? Admin::class : User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo($this->receiver_type === 'admin' ? Admin::class : User::class, 'receiver_id');
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeBetweenUsers($query, $user1Id, $user1Type, $user2Id, $user2Type)
    {
        return $query->where(function ($q) use ($user1Id, $user1Type, $user2Id, $user2Type) {
            $q->where(function ($subQ) use ($user1Id, $user1Type, $user2Id, $user2Type) {
                $subQ->where('sender_id', $user1Id)
                    ->where('sender_type', $user1Type)
                    ->where('receiver_id', $user2Id)
                    ->where('receiver_type', $user2Type);
            })->orWhere(function ($subQ) use ($user1Id, $user1Type, $user2Id, $user2Type) {
                $subQ->where('sender_id', $user2Id)
                    ->where('sender_type', $user2Type)
                    ->where('receiver_id', $user1Id)
                    ->where('receiver_type', $user1Type);
            });
        });
    }
}
