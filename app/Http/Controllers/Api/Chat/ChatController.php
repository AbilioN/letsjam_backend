<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Application\UseCases\Chat\SendMessageUseCase;
use App\Application\UseCases\Chat\GetConversationUseCase;
use App\Application\UseCases\Chat\GetConversationsUseCase;
use App\Application\UseCases\Chat\CreatePrivateChatUseCase;
use App\Application\UseCases\Chat\CreateGroupChatUseCase;
use App\Application\UseCases\Chat\SendMessageToChatUseCase;
use App\Domain\Entities\ChatUser;
use App\Domain\Entities\ChatUserFactory;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function sendMessage(Request $request, SendMessageUseCase $sendMessageUseCase): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'receiver_type' => 'required|in:user,admin',
            'receiver_id' => 'required|integer|min:1'
        ]);

        $user = $request->user();
        $sender = ChatUserFactory::createFromModel($user);

        $receiver = ChatUserFactory::createFromChatUserData(
            $request->receiver_id,
            $request->receiver_type
        );

        $result = $sendMessageUseCase->execute(
            $request->content,
            $sender,
            $receiver
        );

        return response()->json($result, 201);
    }

    public function sendMessageToChat(Request $request, $chatId, SendMessageToChatUseCase $useCase): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'message_type' => 'required|in:text,image,file',
            'metadata' => 'nullable|array'
        ]);

        $user = $request->user();
        $chatUser = ChatUserFactory::createFromModel($user);

        // Verifica se o usuário é participante do chat
        $chat = Chat::findOrFail($chatId);
        if (!$chat->hasParticipant($chatUser)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Cria a mensagem usando o caso de uso
        $message = $useCase->execute(
            $chatId,
            $request->content,
            $chatUser,
            $request->message_type,
            $request->metadata
        );

        return response()->json([
            'success' => true,
            'data' => ['message' => $message->toDto()->toArray()]
        ], 201);
    }

    public function getChatMessages(Request $request, $chatId): JsonResponse
    {
        $user = $request->user();
        $chatUser = ChatUserFactory::createFromModel($user);

        // Verifica se o usuário é participante do chat
        $chat = Chat::findOrFail($chatId);
        if (!$chat->hasParticipant($chatUser)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $messages = $chat->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        // Enriquece as mensagens com sender_type
        $enrichedMessages = $messages->map(function ($message) use ($chatId) {
            $senderType = DB::table('chat_user')
                ->where('chat_id', $chatId)
                ->where('user_id', $message->sender_id)
                ->value('user_type');

            return [
                'id' => $message->id,
                'chat_id' => $message->chat_id,
                'content' => $message->content,
                'sender_id' => $message->sender_id,
                'message_type' => $message->message_type,
                'metadata' => $message->metadata,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $enrichedMessages,
                'from_cache' => false,
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $messages->count(),
                    'total' => $messages->count()
                ]
            ]
        ], 200);
    }

    public function markMessagesAsRead(Request $request, $chatId): JsonResponse
    {
        $user = $request->user();
        $chatUser = ChatUserFactory::createFromModel($user);

        // Verifica se o usuário é participante do chat
        $chat = Chat::findOrFail($chatId);
        if (!$chat->hasParticipant($chatUser)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Marca mensagens como lidas usando a abstração ChatUser
        $chat->markAsReadForChatUser($chatUser);

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Messages marked as read']
        ], 200);
    }

    public function getUnreadCount(Request $request, $chatId): JsonResponse
    {
        $user = $request->user();
        $chatUser = ChatUserFactory::createFromModel($user);

        // Verifica se o usuário é participante do chat
        $chat = Chat::findOrFail($chatId);
        if (!$chat->hasParticipant($chatUser)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Conta mensagens não lidas
        $unreadCount = $chat->messages()
            ->where('sender_id', '!=', $chatUser->getId())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $unreadCount]
        ], 200);
    }

    public function getConversation(Request $request, GetConversationUseCase $getConversationUseCase): JsonResponse
    {
        $request->validate([
            'other_user_type' => 'required|in:user,admin',
            'other_user_id' => 'required|integer|min:1',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100'
        ]);

        $user = $request->user();
        $chatUser = ChatUserFactory::createFromModel($user);
        $otherChatUser = ChatUserFactory::createFromChatUserData(
            $request->other_user_id,
            $request->other_user_type
        );
        $result = $getConversationUseCase->execute(
            $chatUser,
            $otherChatUser,
            $request->get('page', 1),
            $request->get('per_page', 50)
        );

        return response()->json($result, 200);
    }

    public function getConversations(Request $request, GetConversationsUseCase $getConversationsUseCase): JsonResponse
    {
        $user = $request->user();
        $chatUser = ChatUserFactory::createFromModel($user);

        $result = $getConversationsUseCase->execute($chatUser);

        return response()->json($result, 200);
    }

    public function createPrivateChat(Request $request, CreatePrivateChatUseCase $useCase): JsonResponse
    {
        $request->validate([
            'other_user_id' => 'required|integer',
            'other_user_type' => 'required|in:user,admin'
        ]);

        $user = $request->user();
        $chatUser = ChatUserFactory::createFromModel($user);

        // Cria ChatUser para o outro usuário
        $otherChatUser = ChatUserFactory::createFromChatUserData(
            $request->other_user_id,
            $request->other_user_type
        );

        $chat = $useCase->execute($chatUser, $otherChatUser);
        
        return response()->json([
            'success' => true, 
            'data' => $chat->toDto()->toArray()
        ], 201);
    }

    public function createGroupChat(Request $request, CreateGroupChatUseCase $useCase): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'participants' => 'required|array|min:1',
            'participants.*.user_id' => 'required|integer',
            'participants.*.user_type' => 'required|in:user,admin'
        ]);

        $user = $request->user();
        $chatUser = ChatUserFactory::createFromModel($user);
        // Converte participantes para ChatUsers
        $participants = collect($request->participants)->map(function ($participant) {
            return ChatUserFactory::createFromChatUserData(
                $participant['user_id'],
                $participant['user_type']
            );
        })->toArray();

        $chat = $useCase->execute(
            $chatUser,
            $request->name,
            $request->description,
            $participants
        );

        return response()->json([
            'success' => true, 
            'data' => $chat->toDto()->toArray()
        ], 201);
    }
}
