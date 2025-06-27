<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Repositories\MessageRepositoryInterface;

class GetConversationUseCase
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository
    ) {}

    public function execute(int $user1Id, string $user1Type, int $user2Id, string $user2Type, int $page = 1, int $perPage = 50): array
    {
        return $this->messageRepository->findConversation($user1Id, $user1Type, $user2Id, $user2Type, $page, $perPage);
    }
} 