<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Entities\Chat;
use App\Domain\Entities\ChatUser;
use App\Domain\Repositories\ChatRepositoryInterface;

class CreatePrivateChatUseCase
{
    public function __construct(private ChatRepositoryInterface $chatRepository) {}

    public function execute(ChatUser $user1, ChatUser $user2): Chat
    {
        $chatData = $this->chatRepository->findOrCreatePrivateChat($user1, $user2);
        
        // Converte dados em entidade de domínio
        return new Chat(
            id: $chatData['id'],
            name: $chatData['name'],
            type: $chatData['type'],
            description: $chatData['description'],
            createdBy: $chatData['created_by'],
            createdByType: $chatData['created_by_type'],
            createdAt: $chatData['created_at'] ? new \DateTime($chatData['created_at']) : null,
            updatedAt: $chatData['updated_at'] ? new \DateTime($chatData['updated_at']) : null
        );
    }
} 