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

    /**
     * Gets the list of chats for a user with pagination
     * 
     * @param ChatUser $user The user to get chats for
     * @param int $page Page number (default: 1)
     * @param int $perPage Items per page (default: 20)
     * @return \App\Application\DTOs\ChatListResponseDto Returns a DTO containing:
     *   - chats: array<\App\Application\DTOs\ChatListItemDto> List of chats
     *   - pagination: \App\Application\DTOs\PaginationDto Pagination information
     * 
     * @example
     * $response = $repository->getUserChats($user, 1, 20);
     * $chats = $response->chats; // ChatListItemDto[]
     * $pagination = $response->pagination; // PaginationDto
     * $array = $response->toArray(); // array for JSON
     */
    public function getUserChats(ChatUser $user, int $page = 1, int $perPage = 20): \App\Application\DTOs\ChatListResponseDto
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

            $lastMessageDto = $lastMessage ? new \App\Application\DTOs\LastMessageDto(
                id: $lastMessage->id,
                content: $lastMessage->content,
                senderType: $lastMessage->sender_type,
                senderId: $lastMessage->sender_id,
                createdAt: $lastMessage->created_at->format('Y-m-d H:i:s')
            ) : null;

            return new \App\Application\DTOs\ChatListItemDto(
                id: $chatModel->id,
                name: $chatModel->name ?? $chatModel->users->first()->name,
                type: $chatModel->type,
                description: $chatModel->description ?? '',
                lastMessage: $lastMessageDto,
                unreadCount: $unreadCount,
                participantsCount: $chatModel->users->count(),
                createdAt: $chatModel->created_at->format('Y-m-d H:i:s'),
                updatedAt: $chatModel->updated_at->format('Y-m-d H:i:s')
            );
        }, $chats);

        $paginationDto = new \App\Application\DTOs\PaginationDto(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
            from: $paginator->firstItem(),
            to: $paginator->lastItem()
        );

        return new \App\Application\DTOs\ChatListResponseDto(
            chats: $chatEntities,
            pagination: $paginationDto
        );
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