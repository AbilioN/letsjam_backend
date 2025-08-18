<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Entities\ChatUser;
use App\Domain\Entities\Message;
use App\Domain\Repositories\ChatRepositoryInterface;
use App\Domain\Repositories\MessageRepositoryInterface;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class SendMessageToChatUseCase
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository,
        private ChatRepositoryInterface $chatRepository
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
        if($this->chatRepository->hasAssistant($chatId)) {
            $this->dispatchOpenAIRequest($chatId, $sender->getId(), $content);
        }
        return $message;
    }

    private function dispatchOpenAIRequest(int $chatId, int $userId, string $content): void
    {
        try {
            \App\Jobs\ProcessOpenAIRequest::dispatch($chatId, $userId, $content);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch OpenAI request', ['error' => $e->getMessage()]);
        }
    }
} 