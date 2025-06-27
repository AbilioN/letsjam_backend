<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Repositories\ChatRepositoryInterface;
use App\Domain\Repositories\MessageRepositoryInterface;

class GetConversationUseCase
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository,
        private MessageRepositoryInterface $messageRepository
    ) {}

    public function execute(int $userId, string $userType, int $otherUserId, string $otherUserType, int $page = 1, int $perPage = 50): array
    {
        // Busca ou cria o chat privado entre os dois usuários
        $chat = $this->chatRepository->findOrCreatePrivateChat(
            $userId,
            $userType,
            $otherUserId,
            $otherUserType
        );

        // Busca as mensagens do chat
        $result = $this->messageRepository->getChatMessages($chat->id, $page, $perPage);

        return [
            'chat' => [
                'id' => $chat->id,
                'type' => $chat->type,
                'name' => $chat->name,
                'description' => $chat->description,
            ],
            'messages' => $result['messages'],
            'pagination' => $result['pagination']
        ];
    }
} 