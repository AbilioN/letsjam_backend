<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Repositories\MessageRepositoryInterface;
use App\Events\MessageSent;
use App\Models\Message as MessageModel;

class SendMessageToChatUseCase
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository
    ) {}

    public function execute(int $chatId, string $content, string $senderType, int $senderId): array
    {
        // Cria a mensagem no chat específico
        $message = $this->messageRepository->create(
            $chatId,
            $content,
            $senderType,
            $senderId
        );

        // Buscar o modelo Eloquent para o evento
        $messageModel = MessageModel::find($message->id);
        
        // Disparar evento para broadcast
        MessageSent::dispatch($messageModel);

        return [
            'message' => [
                'id' => $message->id,
                'chat_id' => $message->chatId,
                'content' => $message->content,
                'sender_type' => $message->senderType,
                'sender_id' => $message->senderId,
                'is_read' => $message->isRead,
                'created_at' => $message->createdAt?->format('Y-m-d H:i:s')
            ]
        ];
    }
} 