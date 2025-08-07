<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Entities\Chat;
use App\Domain\Entities\ChatUser;
use App\Domain\Entities\Message;
use App\Domain\Repositories\ChatRepositoryInterface;
use App\Domain\Repositories\MessageRepositoryInterface;
use App\Events\MessageSent;
use App\Models\Message as MessageModel;

class SendMessageUseCase
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository,
        private MessageRepositoryInterface $messageRepository
    ) {}

    public function execute(string $content, ChatUser $sender, ChatUser $receiver): array
    {
        // Busca ou cria o chat privado entre os dois usuários
        $chatData = $this->chatRepository->findOrCreatePrivateChat($sender, $receiver);

        // Cria a mensagem
        $message = $this->messageRepository->create(
            $chatData['id'],
            $content,
            $sender
        );

        // Buscar o modelo Eloquent para o evento
        $messageModel = MessageModel::find($message->id);
        
        // Disparar evento para broadcast
        MessageSent::dispatch($messageModel);

        // Converte dados em entidade de domínio
        $chat = new Chat(
            id: $chatData['id'],
            name: $chatData['name'],
            type: $chatData['type'],
            description: $chatData['description'],
            createdBy: $chatData['created_by'],
            createdByType: $chatData['created_by_type'],
            createdAt: $chatData['created_at'] ? new \DateTime($chatData['created_at']) : null,
            updatedAt: $chatData['updated_at'] ? new \DateTime($chatData['updated_at']) : null
        );

        return [
            'chat' => $chat->toDto()->toArray(),
            'message' => $message->toDto()->toArray()
        ];
    }
} 