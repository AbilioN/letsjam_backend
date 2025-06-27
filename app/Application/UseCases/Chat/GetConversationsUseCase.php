<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Repositories\ChatRepositoryInterface;

class GetConversationsUseCase
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository
    ) {}

    public function execute(int $userId, string $userType, int $page = 1, int $perPage = 20): array
    {
        return $this->chatRepository->getUserChats($userId, $userType, $page, $perPage);
    }
} 