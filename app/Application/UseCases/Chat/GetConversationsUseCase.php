<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Repositories\MessageRepositoryInterface;

class GetConversationsUseCase
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository
    ) {}

    public function execute(int $userId, string $userType): array
    {
        return [
            'conversations' => $this->messageRepository->getConversations($userId, $userType)
        ];
    }
} 