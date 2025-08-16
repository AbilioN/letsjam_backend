<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Application\UseCases\Chat\SendMessageToChatUseCase;
use App\Domain\Entities\ChatUserFactory;
use App\Models\Chat;
use Exception;

class ProcessMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60; // 1 minute timeout
    public $tries = 3; // Retry 3 times if fails
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $chatId,
        private int $userId,
        private string $content,
        private string $messageType,
        private ?array $metadata,
        private string $queueName = 'message_processing'
    ) {
        $this->onQueue($this->queueName);
    }

    /**
     * Execute the job.
     */
    public function handle(SendMessageToChatUseCase $useCase): void
    {
        try {
            Log::info('Processing message job started', [
                'chat_id' => $this->chatId,
                'user_id' => $this->userId,
                'message_type' => $this->messageType,
                'queue' => $this->queueName
            ]);

            // Verifica se o chat ainda existe
            $chat = Chat::find($this->chatId);
            if (!$chat) {
                throw new Exception('Chat not found');
            }

            // Cria o ChatUser a partir do ID do usuário
            $chatUser = ChatUserFactory::createFromChatUserData(
                $this->userId,
                'user' // Assumindo que é sempre um usuário normal
            );

            // Verifica se o usuário ainda é participante do chat
            if (!$chat->hasParticipant($chatUser)) {
                throw new Exception('User is no longer a participant of this chat');
            }

            // Processa a mensagem usando o caso de uso
            $message = $useCase->execute(
                $this->chatId,
                $this->content,
                $chatUser,
                $this->messageType,
                $this->metadata
            );

            Log::info('Message processed successfully', [
                'chat_id' => $this->chatId,
                'user_id' => $this->userId,
                'message_id' => $message->id,
                'message_type' => $this->messageType
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process message job', [
                'chat_id' => $this->chatId,
                'user_id' => $this->userId,
                'message_type' => $this->messageType,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Se for a última tentativa, cria uma mensagem de erro no chat
            if ($this->attempts() >= $this->tries) {
                $this->createErrorMessage();
            }

            throw $e; // Re-throw para acionar o mecanismo de retry
        }
    }

    /**
     * Create error message in the chat
     */
    private function createErrorMessage(): void
    {
        try {
            $chat = Chat::find($this->chatId);
            if ($chat) {
                $chat->messages()->create([
                    'sender_id' => $this->userId,
                    'user_id' => $this->userId,
                    'user_type' => 'system',
                    'content' => 'Desculpe, houve um erro ao processar sua mensagem. Tente novamente.',
                    'type' => 'text',
                    'is_read' => false
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to create error message', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Message processing job failed permanently', [
            'chat_id' => $this->chatId,
            'user_id' => $this->userId,
            'message_type' => $this->messageType,
            'error' => $exception->getMessage()
        ]);

        // Cria mensagem de erro final
        $this->createErrorMessage();
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'message_processing',
            "chat:{$this->chatId}",
            "user:{$this->userId}",
            "type:{$this->messageType}"
        ];
    }
}
