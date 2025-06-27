<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Message;

interface MessageRepositoryInterface
{
    public function create(string $content, string $senderType, int $senderId, string $receiverType, int $receiverId): Message;
    public function findById(int $id): ?Message;
    public function findConversation(int $user1Id, string $user1Type, int $user2Id, string $user2Type, int $page = 1, int $perPage = 50): array;
    public function markAsRead(int $messageId): void;
    public function getUnreadCount(int $userId, string $userType): int;
    public function getConversations(int $userId, string $userType): array;
} 