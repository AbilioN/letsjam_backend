<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessOpenAIResponse;
use Exception;

class ListenOpenAIResponses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listen:openai-responses {--timeout=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen continuously to OpenAI responses from Redis and process them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = $this->option('timeout');
        
        $this->info("🎧 Starting OpenAI response listener (Polling)...");
        $this->info("Checking for responses every {$timeout} seconds");
        $this->info("Press Ctrl+C to stop");

        while (true) {
            try {
                // Check for new responses in Redis
                $this->checkForResponses();
                
                // Wait before next check
                sleep($timeout);
                
            } catch (Exception $e) {
                $this->error("❌ Error in listener: " . $e->getMessage());
                Log::error('OpenAI response listener error', ['error' => $e->getMessage()]);
                
                $this->info("⏳ Waiting 5 seconds before continuing...");
                sleep(5);
                
                // Continue the loop
                continue;
            }
        }
    }

    /**
     * Check for new OpenAI responses in Redis
     */
    private function checkForResponses(): void
    {
        try {
            // Check the specific queue that Python is using
            // Note: Python sends to 'lestjam_database_openai_responses' but Laravel adds prefix
            $queueName = 'openai_responses';
            $queueLength = Redis::llen($queueName);
            
            if ($queueLength == 0) {
                $this->info("🔍 No new responses in queue: {$queueName}");
                return;
            }
            
            $this->info("📨 Found {$queueLength} response(s) in queue: {$queueName}");
            
            // Process all responses in the queue
            while ($queueLength > 0) {
                try {
                    // Get response from the queue (FIFO)
                    $response = Redis::rpop($queueName);
                    
                    if ($response) {
                        $responseData = json_decode($response, true);
                        
                        if ($responseData && isset($responseData['id'])) {
                            $this->info("📨 Processing response: {$responseData['id']}");
                            
                            // Extract chat_id and user_id from the response
                            // Since Python doesn't send these, we'll need to get them from the original request
                            $chatId = $this->getChatIdFromRequest($responseData['id']);
                            $userId = $this->getUserIdFromRequest($responseData['id']);
                            
                            if ($chatId && $userId) {
                                // Dispatch job to process the response
                                ProcessOpenAIResponse::dispatch(
                                    $responseData['id'],
                                    $chatId,
                                    $userId
                                );
                                
                                $this->info("✅ Job dispatched for response: {$responseData['id']}");
                            } else {
                                $this->warn("⚠️ Could not find chat_id or user_id for response: {$responseData['id']}");
                            }
                        } else {
                            $this->warn("⚠️ Invalid response format");
                        }
                    }
                    
                    $queueLength--;
                    
                } catch (Exception $e) {
                    $this->error("❌ Error processing response: " . $e->getMessage());
                    Log::error('Error processing individual response', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
        } catch (Exception $e) {
            Log::error('Error checking for OpenAI responses', ['error' => $e->getMessage()]);
            $this->error("❌ Error checking responses: " . $e->getMessage());
        }
    }

    /**
     * Get chat_id from the original request (stored in Redis)
     */
    private function getChatIdFromRequest(string $requestId): ?int
    {
        try {
            // Try to get from the original request data
            $requestData = Redis::get("openai_request:{$requestId}");
            if ($requestData) {
                $data = json_decode($requestData, true);
                return $data['chat_id'] ?? null;
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get user_id from the original request (stored in Redis)
     */
    private function getUserIdFromRequest(string $requestId): ?int
    {
        try {
            // Try to get from the original request data
            $requestData = Redis::get("openai_request:{$requestId}");
            if ($requestData) {
                $data = json_decode($requestData, true);
                return $data['user_id'] ?? null;
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
