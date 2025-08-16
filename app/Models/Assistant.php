<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assistant extends Model
{
    protected $fillable = [
        'name',
        'description',
        'avatar',
        'capabilities',
        'is_active'
    ];

    protected $casts = [
        'capabilities' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get the messages sent by this assistant
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id')->where('user_type', 'assistant');
    }

    /**
     * Check if the assistant is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get assistant capabilities
     */
    public function getCapabilities(): array
    {
        return $this->capabilities ?? [];
    }

    /**
     * Check if assistant has a specific capability
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->getCapabilities());
    }
}
