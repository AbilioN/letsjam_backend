<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

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
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->getChannelName()),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'sender_type' => $this->message->sender_type,
                'sender_id' => $this->message->sender_id,
                'sender_name' => $this->message->sender->name,
                'receiver_type' => $this->message->receiver_type,
                'receiver_id' => $this->message->receiver_id,
                'is_read' => $this->message->is_read,
                'created_at' => $this->message->created_at->format('Y-m-d H:i:s'),
            ]
        ];
    }

    /**
     * Get the channel name for the chat.
     */
    private function getChannelName(): string
    {
        $participants = [$this->message->sender_id, $this->message->receiver_id];
        sort($participants);
        return implode('-', $participants);
    }
}
