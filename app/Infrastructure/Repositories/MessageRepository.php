<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Message;
use App\Domain\Repositories\MessageRepositoryInterface;
use App\Models\Message as MessageModel;

class MessageRepository implements MessageRepositoryInterface
{
    public function create(string $content, string $senderType, int $senderId, string $receiverType, int $receiverId): Message
    {
        $messageModel = MessageModel::create([
            'content' => $content,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'receiver_type' => $receiverType,
            'receiver_id' => $receiverId,
        ]);

        return new Message(
            id: $messageModel->id,
            content: $messageModel->content,
            senderType: $messageModel->sender_type,
            senderId: $messageModel->sender_id,
            receiverType: $messageModel->receiver_type,
            receiverId: $messageModel->receiver_id,
            isRead: (bool) $messageModel->is_read,
            readAt: $messageModel->read_at,
            createdAt: $messageModel->created_at,
            updatedAt: $messageModel->updated_at
        );
    }

    public function findById(int $id): ?Message
    {
        $messageModel = MessageModel::find($id);
        
        if (!$messageModel) {
            return null;
        }

        return new Message(
            id: $messageModel->id,
            content: $messageModel->content,
            senderType: $messageModel->sender_type,
            senderId: $messageModel->sender_id,
            receiverType: $messageModel->receiver_type,
            receiverId: $messageModel->receiver_id,
            isRead: (bool) $messageModel->is_read,
            readAt: $messageModel->read_at,
            createdAt: $messageModel->created_at,
            updatedAt: $messageModel->updated_at
        );
    }

    public function findConversation(int $user1Id, string $user1Type, int $user2Id, string $user2Type, int $page = 1, int $perPage = 50): array
    {
        $paginator = MessageModel::with(['sender', 'receiver'])
            ->betweenUsers($user1Id, $user1Type, $user2Id, $user2Type)
            ->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        $messages = $paginator->items();
        $messageEntities = array_map(function ($messageModel) {
            return [
                'id' => $messageModel->id,
                'content' => $messageModel->content,
                'sender_type' => $messageModel->sender_type,
                'sender_id' => $messageModel->sender_id,
                'sender_name' => $messageModel->sender->name,
                'receiver_type' => $messageModel->receiver_type,
                'receiver_id' => $messageModel->receiver_id,
                'is_read' => (bool) $messageModel->is_read,
                'read_at' => $messageModel->read_at?->format('Y-m-d H:i:s'),
                'created_at' => $messageModel->created_at->format('Y-m-d H:i:s'),
            ];
        }, $messages);

        return [
            'messages' => $messageEntities,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem()
            ]
        ];
    }

    public function markAsRead(int $messageId): void
    {
        MessageModel::where('id', $messageId)->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    public function getUnreadCount(int $userId, string $userType): int
    {
        return MessageModel::where('receiver_id', $userId)
            ->where('receiver_type', $userType)
            ->where('is_read', false)
            ->count();
    }

    public function getConversations(int $userId, string $userType): array
    {
        $conversations = MessageModel::selectRaw('
                CASE 
                    WHEN sender_id = ? AND sender_type = ? THEN receiver_id
                    ELSE sender_id
                END as other_user_id,
                CASE 
                    WHEN sender_id = ? AND sender_type = ? THEN receiver_type
                    ELSE sender_type
                END as other_user_type,
                MAX(created_at) as last_message_at,
                COUNT(*) as message_count,
                SUM(CASE WHEN receiver_id = ? AND receiver_type = ? AND is_read = 0 THEN 1 ELSE 0 END) as unread_count
            ', [$userId, $userType, $userId, $userType, $userId, $userType])
            ->where(function ($query) use ($userId, $userType) {
                $query->where('sender_id', $userId)
                    ->where('sender_type', $userType)
                    ->orWhere('receiver_id', $userId)
                    ->where('receiver_type', $userType);
            })
            ->groupBy('other_user_id', 'other_user_type')
            ->orderBy('last_message_at', 'desc')
            ->get();

        return $conversations->toArray();
    }
} 