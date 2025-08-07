<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Chat;
use App\Domain\Entities\ChatUser;
use App\Domain\Repositories\ChatRepositoryInterface;
use App\Models\Chat as ChatModel;

class ChatRepository implements ChatRepositoryInterface
{
    public function findOrCreatePrivateChat(ChatUser $user1, ChatUser $user2): Chat
    {
        $chatModel = ChatModel::findOrCreatePrivateChat($user1, $user2);
        return $chatModel->toEntityFromUser($user2);
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

    public function getUserChats(ChatUser $user, int $page = 1, int $perPage = 20): array
    {
        $paginator = ChatModel::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->getId())->where('user_type', $user->getType());
        })
        ->with(['messages' => function ($query) {
            $query->latest()->limit(1);
        }, 'users'])
        ->orderBy('updated_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

        $chats = $paginator->items();
        $chatEntities = array_map(function ($chatModel) use ($user) {
            $lastMessage = $chatModel->messages->first();
            $unreadCount = $chatModel->messages()
                ->where('sender_id', '!=', $user->getId())
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

    public function createGroupChat(string $name, string $description, ChatUser $createdBy): Chat
    {
        $chatModel = ChatModel::create([
            'name' => $name,
            'type' => 'group',
            'description' => $description,
            'created_by' => $createdBy->getId(),
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

    public function addParticipantToChat(int $chatId, ChatUser $user): void
    {
        $chatModel = ChatModel::find($chatId);
        if ($chatModel) {
            $chatModel->addParticipant($user);
        }
    }

    public function removeParticipantFromChat(int $chatId, ChatUser $user): void
    {
        $chatModel = ChatModel::find($chatId);
        if ($chatModel) {
            $chatModel->removeParticipant($user);
        }
    }

    public function markChatAsReadForUser(int $chatId, ChatUser $user): void
    {
        $chatModel = ChatModel::find($chatId);
        if ($chatModel) {
            $chatModel->markAsReadForChatUser($user);
        }
    }

    public function getUnreadCount(ChatUser $user): int
    {
        return ChatModel::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->getId())->where('user_type', $user->getType());
        })
        ->whereHas('messages', function ($query) use ($user) {
            $query->where('sender_id', '!=', $user->getId())->where('is_read', false);
        })
        ->count();
    }
} 