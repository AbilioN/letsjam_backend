<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Application\UseCases\Chat\SendMessageUseCase;
use App\Application\UseCases\Chat\GetConversationUseCase;
use App\Application\UseCases\Chat\GetConversationsUseCase;
use App\Application\UseCases\Chat\CreatePrivateChatUseCase;
use App\Application\UseCases\Chat\CreateGroupChatUseCase;
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
        $senderType = $user instanceof \App\Models\Admin ? 'admin' : 'user';

        $result = $sendMessageUseCase->execute(
            $request->content,
            $senderType,
            $user->id,
            $request->receiver_type,
            $request->receiver_id
        );

        return response()->json($result, 201);
    }

    public function sendMessageToChat(Request $request, $chatId): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'message_type' => 'required|in:text,image,file',
            'metadata' => 'nullable|array'
        ]);

        $user = $request->user();
        $userId = $user->id;

        // Verifica se o usuário é participante do chat
        $chat = Chat::findOrFail($chatId);
        if (!$chat->hasParticipant($userId)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Cria a mensagem
        $message = Message::create([
            'chat_id' => $chatId,
            'content' => $request->content,
            'sender_id' => $userId,
            'message_type' => $request->message_type,
            'metadata' => $request->metadata,
            'is_read' => false
        ]);

        // Obtém o tipo do remetente da tabela chat_user
        $senderType = DB::table('chat_user')
            ->where('chat_id', $chatId)
            ->where('user_id', $userId)
            ->value('user_type');

        $messageData = [
            'id' => $message->id,
            'chat_id' => $message->chat_id,
            'content' => $message->content,
            'sender_id' => $message->sender_id,
            'sender_type' => $senderType,
            'message_type' => $message->message_type,
            'metadata' => $message->metadata,
            'is_read' => $message->is_read,
            'created_at' => $message->created_at
        ];

        return response()->json([
            'success' => true,
            'data' => ['message' => $messageData]
        ], 201);
    }

    public function getChatMessages(Request $request, $chatId): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        // Verifica se o usuário é participante do chat
        $chat = Chat::findOrFail($chatId);
        if (!$chat->hasParticipant($userId)) {
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
        $userId = $user->id;

        // Verifica se o usuário é participante do chat
        $chat = Chat::findOrFail($chatId);
        if (!$chat->hasParticipant($userId)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Marca mensagens como lidas
        $updatedCount = $chat->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        // Atualiza last_read_at na tabela chat_user
        DB::table('chat_user')
            ->where('chat_id', $chatId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => ['updated_count' => $updatedCount]
        ], 200);
    }

    public function getUnreadCount(Request $request, $chatId): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        // Verifica se o usuário é participante do chat
        $chat = Chat::findOrFail($chatId);
        if (!$chat->hasParticipant($userId)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Conta mensagens não lidas
        $unreadCount = $chat->messages()
            ->where('sender_id', '!=', $userId)
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
        $userType = $user instanceof \App\Models\Admin ? 'admin' : 'user';

        $result = $getConversationUseCase->execute(
            $user->id,
            $userType,
            $request->other_user_id,
            $request->other_user_type,
            $request->get('page', 1),
            $request->get('per_page', 50)
        );

        return response()->json($result, 200);
    }

    public function getConversations(Request $request, GetConversationsUseCase $getConversationsUseCase): JsonResponse
    {
        $user = $request->user();
        $userType = $user instanceof \App\Models\Admin ? 'admin' : 'user';

        $result = $getConversationsUseCase->execute($user->id, $userType);

        return response()->json($result, 200);
    }

    public function createPrivateChat(Request $request, CreatePrivateChatUseCase $useCase): JsonResponse
    {
        $request->validate([
            'other_user_id' => 'required|integer',
            'other_user_type' => 'required|in:user,admin'
        ]);
        $user = $request->user();

        $userType = $user instanceof \App\Models\Admin ? 'admin' : 'user';
        // dd($user->id, $userType, $request->other_user_id, $request->other_user_type);
        $result = $useCase->execute($user->id, $userType, $request->other_user_id, $request->other_user_type);
        return response()->json(['success' => true, 'data' => $result], 201);
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
        $userType = $user instanceof \App\Models\Admin ? 'admin' : 'user';
        $result = $useCase->execute(
            $user->id,
            $userType,
            $request->name,
            $request->description,
            $request->participants
        );
        return response()->json(['success' => true, 'data' => $result], 201);
    }
}
