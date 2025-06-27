<?php

namespace App\Domain\Entities;

use DateTime;

class Message
{
    public function __construct(
        public readonly int $id,
        public readonly string $content,
        public readonly string $senderType,
        public readonly int $senderId,
        public readonly string $receiverType,
        public readonly int $receiverId,
        public readonly bool $isRead,
        public readonly ?DateTime $readAt = null,
        public readonly ?DateTime $createdAt = null,
        public readonly ?DateTime $updatedAt = null
    ) {}

    public function isFromUser(): bool
    {
        return $this->senderType === 'user';
    }

    public function isFromAdmin(): bool
    {
        return $this->senderType === 'admin';
    }

    public function isToUser(): bool
    {
        return $this->receiverType === 'user';
    }

    public function isToAdmin(): bool
    {
        return $this->receiverType === 'admin';
    }

    public function isUnread(): bool
    {
        return !$this->isRead;
    }
} 