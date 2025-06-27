<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Repositories\MessageRepositoryInterface;
use App\Events\MessageSent;
use App\Models\Message as MessageModel;

class SendMessageUseCase
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository
    ) {}

    public function execute(string $content, string $senderType, int $senderId, string $receiverType, int $receiverId): array
    {
        $message = $this->messageRepository->create(
            $content,
            $senderType,
            $senderId,
            $receiverType,
            $receiverId
        );

        // Buscar o modelo Eloquent para o evento
        $messageModel = MessageModel::find($message->id);
        
        // Disparar evento para broadcast
        MessageSent::dispatch($messageModel);

        return [
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'sender_type' => $message->senderType,
                'sender_id' => $message->senderId,
                'receiver_type' => $message->receiverType,
                'receiver_id' => $message->receiverId,
                'is_read' => $message->isRead,
                'created_at' => $message->createdAt?->format('Y-m-d H:i:s')
            ]
        ];
    }
} 