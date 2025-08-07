<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Entities\ChatUser;
use App\Domain\Entities\Message;
use App\Domain\Repositories\MessageRepositoryInterface;
use App\Events\MessageSent;
use App\Models\Message as MessageModel;

class SendMessageToChatUseCase
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository
    ) {}

    public function execute(int $chatId, string $content, ChatUser $sender, string $messageType = 'text', ?array $metadata = null): Message
    {
        // Cria a mensagem no chat específico
        $message = $this->messageRepository->create(
            $chatId,
            $content,
            $sender,
            $messageType,
            $metadata
        );

        // Buscar o modelo Eloquent para o evento
        $messageModel = MessageModel::find($message->id);
        
        // Disparar evento para broadcast
        MessageSent::dispatch($messageModel);

        return $message;
    }
} 