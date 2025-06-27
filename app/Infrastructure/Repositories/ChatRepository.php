<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Chat;
use App\Domain\Repositories\ChatRepositoryInterface;
use App\Models\Chat as ChatModel;

class ChatRepository implements ChatRepositoryInterface
{
    public function findOrCreatePrivateChat(int $user1Id, string $user1Type, int $user2Id, string $user2Type): Chat
    {
        $chatModel = ChatModel::findOrCreatePrivateChat($user1Id, $user1Type, $user2Id, $user2Type);
        
        return new Chat(
            id: $chatModel->id,
            name: $chatModel->name,
            type: $chatModel->type,
            description: $chatModel->description,
            createdBy: $chatModel->created_by,
            createdByType: $chatModel->created_by_type,
            createdAt: $chatModel->created_at,
            updatedAt: $chatModel->updated_at
        );
    }

    public function findById(int $id): ?Chat
    {
        $chatModel = ChatModel::find($id);
        
        if (!$chatModel) {
            return null;
        }

        return new Chat(
            id: $chatModel->id,
            name: $chatModel->name,
            type: $chatModel->type,
            description: $chatModel->description,
            createdBy: $chatModel->created_by,
            createdByType: $chatModel->created_by_type,
            createdAt: $chatModel->created_at,
            updatedAt: $chatModel->updated_at
        );
    }

    public function getUserChats(int $userId, string $userType, int $page = 1, int $perPage = 20): array
    {
        $paginator = ChatModel::whereHas('users', function ($query) use ($userId, $userType) {
            $query->where('user_id', $userId)->where('user_type', $userType);
        })
        ->with(['messages' => function ($query) {
            $query->latest()->limit(1);
        }, 'users'])
        ->orderBy('updated_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

        $chats = $paginator->items();
        $chatEntities = array_map(function ($chatModel) use ($userId, $userType) {
            $lastMessage = $chatModel->messages->first();
            $unreadCount = $chatModel->messages()
                ->where('sender_id', '!=', $userId)
                ->where('is_read', false)
                ->count();

            return [
                'id' => $chatModel->id,
                'name' => $chatModel->name,
                'type' => $chatModel->type,
                'description' => $chatModel->description,
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'content' => $lastMessage->content,
                    'sender_type' => $lastMessage->sender_type,
                    'sender_id' => $lastMessage->sender_id,
                    'created_at' => $lastMessage->created_at->format('Y-m-d H:i:s'),
                ] : null,
                'unread_count' => $unreadCount,
                'participants_count' => $chatModel->users->count(),
                'created_at' => $chatModel->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $chatModel->updated_at->format('Y-m-d H:i:s'),
            ];
        }, $chats);

        return [
            'chats' => $chatEntities,
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

    public function createGroupChat(string $name, string $description, int $createdBy, string $createdByType): Chat
    {
        $chatModel = ChatModel::create([
            'name' => $name,
            'type' => 'group',
            'description' => $description,
            'created_by' => $createdBy,
            'created_by_type' => $createdByType,
        ]);

        return new Chat(
            id: $chatModel->id,
            name: $chatModel->name,
            type: $chatModel->type,
            description: $chatModel->description,
            createdBy: $chatModel->created_by,
            createdByType: $chatModel->created_by_type,
            createdAt: $chatModel->created_at,
            updatedAt: $chatModel->updated_at
        );
    }

    public function addParticipantToChat(int $chatId, int $userId, string $userType): void
    {
        $chatModel = ChatModel::find($chatId);
        if ($chatModel) {
            $chatModel->addParticipant($userId, $userType);
        }
    }

    public function removeParticipantFromChat(int $chatId, int $userId, string $userType): void
    {
        $chatModel = ChatModel::find($chatId);
        if ($chatModel) {
            $chatModel->removeParticipant($userId, $userType);
        }
    }

    public function markChatAsReadForUser(int $chatId, int $userId, string $userType): void
    {
        $chatModel = ChatModel::find($chatId);
        if ($chatModel) {
            $chatModel->markAsReadForUser($userId, $userType);
        }
    }

    public function getUnreadCount(int $userId, string $userType): int
    {
        return ChatModel::whereHas('users', function ($query) use ($userId, $userType) {
            $query->where('user_id', $userId)->where('user_type', $userType);
        })
        ->whereHas('messages', function ($query) use ($userId) {
            $query->where('sender_id', '!=', $userId)->where('is_read', false);
        })
        ->count();
    }
} 