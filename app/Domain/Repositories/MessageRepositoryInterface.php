<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Message;

interface MessageRepositoryInterface
{
    public function create(int $chatId, string $content, string $senderType, int $senderId): Message;
    public function findById(int $id): ?Message;
    public function getChatMessages(int $chatId, int $page = 1, int $perPage = 50): array;
    public function markAsRead(int $messageId): void;
    public function getUnreadCount(int $chatId, int $userId, string $userType): int;
} 