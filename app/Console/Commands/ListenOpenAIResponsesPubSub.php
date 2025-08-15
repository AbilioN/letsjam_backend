<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessOpenAIResponse;
use Exception;

class ListenOpenAIResponsesPubSub extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listen:openai-responses-pubsub';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to OpenAI responses using Redis Pub/Sub (more efficient)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🎧 Starting OpenAI response listener (Pub/Sub)...");
        $this->info("Listening to channel: openai_responses");
        $this->info("Press Ctrl+C to stop");

        while (true) {
            try {
                $this->info("🔄 Connecting to Redis...");
                
                // Subscribe to the responses channel with connection retry
                $redis = Redis::connection();
                $redis->subscribe(['openai_responses'], function ($message, $channel) {
                    $this->processResponse($message);
                });
                
            } catch (Exception $e) {
                $this->error("❌ Redis connection error: " . $e->getMessage());
                Log::error('OpenAI response listener Redis error', ['error' => $e->getMessage()]);
                
                $this->info("⏳ Waiting 5 seconds before reconnecting...");
                sleep(5);
                
                // Continue the loop to retry
                continue;
            }
        }
    }

    /**
     * Process a response message from Redis Pub/Sub
     */
    private function processResponse(string $message): void
    {
        try {
            $responseData = json_decode($message, true);
            
            if ($responseData && isset($responseData['id'], $responseData['chat_id'], $responseData['user_id'])) {
                $this->info("📨 New response received: {$responseData['id']}");
                
                // Dispatch job to process the response
                ProcessOpenAIResponse::dispatch(
                    $responseData['id'],
                    $responseData['chat_id'],
                    $responseData['user_id']
                );
                
                $this->info("✅ Job dispatched for response: {$responseData['id']}");
                
                Log::info('OpenAI response received via Pub/Sub', [
                    'request_id' => $responseData['id'],
                    'chat_id' => $responseData['chat_id']
                ]);
            } else {
                $this->warn("⚠️ Invalid response format received");
                Log::warning('Invalid OpenAI response format received', ['message' => $message]);
            }
            
        } catch (Exception $e) {
            Log::error('Error processing OpenAI response', ['error' => $e->getMessage()]);
            $this->error("Error processing response: " . $e->getMessage());
        }
    }
}
