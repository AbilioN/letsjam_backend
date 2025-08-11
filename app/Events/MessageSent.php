<?php

namespace App\Events;

use App\Models\Message;
use App\Services\PusherApiService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        Log::info('MessageSent event broadcasting on channel: chat.' . $this->message->chat_id);
        
        try {
            // Usar API REST do Pusher diretamente
            $pusherService = app(PusherApiService::class);
            Log::info('PusherApiService instanciado com sucesso');
            
            $channel = 'private-chat.' . $this->message->chat_id;
            
            $data = [
                'id' => $this->message->id,
                'chat_id' => $this->message->chat_id,
                'content' => $this->message->content,
                'sender_type' => $this->message->sender_type,
                'sender_id' => $this->message->sender_id,
                'is_read' => $this->message->is_read,
                'created_at' => $this->message->created_at->format('Y-m-d H:i:s')
            ];
            
            Log::info('Tentando enviar evento para Pusher', ['channel' => $channel, 'data' => $data]);
            $result = $pusherService->trigger($channel, 'MessageSent', $data);
            Log::info('Resultado do envio: ' . ($result ? 'sucesso' : 'falha'));
            
        } catch (\Exception $e) {
            Log::error('Erro ao usar PusherApiService: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        // Retornar canal vazio para não usar broadcasting padrão
        return new PrivateChannel('chat.' . $this->message->chat_id);
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'chat_id' => $this->message->chat_id,
            'content' => $this->message->content,
            'sender_type' => $this->message->sender_type,
            'sender_id' => $this->message->sender_id,
            'is_read' => $this->message->is_read,
            'created_at' => $this->message->created_at->format('Y-m-d H:i:s')
        ];
    }
}
