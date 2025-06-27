<?php

namespace App\Domain\Entities;

use DateTime;

class Message
{
    public function __construct(
        public readonly int $id,
        public readonly int $chatId,
        public readonly string $content,
        public readonly string $senderType,
        public readonly int $senderId,
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

    public function isUnread(): bool
    {
        return !$this->isRead;
    }

    public function markAsRead(): self
    {
        return new self(
            id: $this->id,
            chatId: $this->chatId,
            content: $this->content,
            senderType: $this->senderType,
            senderId: $this->senderId,
            isRead: true,
            readAt: new DateTime(),
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt
        );
    }
} 