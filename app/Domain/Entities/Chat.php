<?php

namespace App\Domain\Entities;

use DateTime;

class Chat
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly string $type,
        public readonly ?string $description,
        public readonly ?int $createdBy,
        public readonly ?string $createdByType,
        public readonly ?DateTime $createdAt = null,
        public readonly ?DateTime $updatedAt = null
    ) {}

    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function hasName(): bool
    {
        return !empty($this->name);
    }

    public function getDisplayName(): string
    {
        return $this->name ?? 'Chat Privado';
    }
} 