<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\ChatUser;

interface ChatRepositoryInterface
{
    public function findOrCreatePrivateChat(ChatUser $user1, ChatUser $user2): array;
    
    public function findById(int $id): ?array;
    
    public function getUserChats(ChatUser $user, int $page = 1, int $perPage = 20): array;
    
    public function createGroupChat(string $name, string $description, ChatUser $createdBy): array;
    
    public function addParticipantToChat(int $chatId, ChatUser $user): void;
    
    public function removeParticipantFromChat(int $chatId, ChatUser $user): void;
    
    public function markChatAsReadForUser(int $chatId, ChatUser $user): void;
    
    public function getUnreadCount(ChatUser $user): int;
} 