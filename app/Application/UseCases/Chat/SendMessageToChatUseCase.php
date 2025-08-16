<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Entities\ChatUser;
use App\Domain\Entities\Message;
use App\Domain\Repositories\MessageRepositoryInterface;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class SendMessageToChatUseCase
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository
    ) {}

    public function execute(int $chatId, string $content, ChatUser $sender, string $messageType = 'text', ?array $metadata = null): Message
    {
        $message = $this->messageRepository->create(
            $chatId,
            $content,
            $sender,
            $messageType,
            $metadata
        );
        Log::info('Dispatching MessageSent event for message ID: ' . $message->id);
        MessageSent::dispatch($message);
        return $message;
    }
} 