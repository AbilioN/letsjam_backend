<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Chat;

interface ChatRepositoryInterface
{
    public function findOrCreatePrivateChat(int $user1Id, string $user1Type, int $user2Id, string $user2Type): Chat;
    
    public function findById(int $id): ?Chat;
    
    public function getUserChats(int $userId, string $userType, int $page = 1, int $perPage = 20): array;
    
    public function createGroupChat(string $name, string $description, int $createdBy, string $createdByType): Chat;
    
    public function addParticipantToChat(int $chatId, int $userId, string $userType): void;
    
    public function removeParticipantFromChat(int $chatId, int $userId, string $userType): void;
    
    public function markChatAsReadForUser(int $chatId, int $userId, string $userType): void;
    
    public function getUnreadCount(int $userId, string $userType): int;
} 