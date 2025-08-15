<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
use Exception;

class ProcessOpenAIResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30; // 30 seconds timeout
    public $tries = 3; // Retry 3 times if fails

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $requestId,
        private int $chatId,
        private int $userId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing OpenAI response', [
                'request_id' => $this->requestId,
                'chat_id' => $this->chatId,
                'user_id' => $this->userId
            ]);

            // Get response from Redis
            $responseKey = "openai_response:{$this->requestId}";
            $response = Redis::get($responseKey);

            if (!$response) {
                throw new Exception('Response not found in Redis');
            }

            $responseData = json_decode($response, true);

            if (!$responseData || !isset($responseData['response'])) {
                throw new Exception('Invalid response format');
            }

            // Create AI response message in the chat
            $this->createAIMessage($responseData['response']);

            // Clean up response from Redis
            Redis::del($responseKey);

            Log::info('OpenAI response processed successfully', [
                'request_id' => $this->requestId,
                'response' => $responseData['response']
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process OpenAI response', [
                'request_id' => $this->requestId,
                'chat_id' => $this->chatId,
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Create AI response message in the chat
     */
    private function createAIMessage(string $content): void
    {
        Message::create([
            'chat_id' => $this->chatId,
            'sender_id' => $this->userId,
            'user_id' => $this->userId,
            'user_type' => 'ai',
            'content' => $content,
            'type' => 'text'
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('OpenAI response job failed permanently', [
            'request_id' => $this->requestId,
            'chat_id' => $this->chatId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage()
        ]);
    }
}
