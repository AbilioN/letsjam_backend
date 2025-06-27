<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Repositories\ChatRepositoryInterface;

class CreatePrivateChatUseCase
{
    public function __construct(private ChatRepositoryInterface $chatRepository) {}

    public function execute(int $userId, string $userType, int $otherUserId, string $otherUserType): array
    {
        $chat = $this->chatRepository->findOrCreatePrivateChat($userId, $userType, $otherUserId, $otherUserType);
        return [
            'chat' => [
                'id' => $chat->id,
                'type' => $chat->type,
                'name' => $chat->name,
                'description' => $chat->description,
            ]
        ];
    }
} 