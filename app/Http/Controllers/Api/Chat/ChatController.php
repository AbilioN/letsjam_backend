<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Application\UseCases\Chat\SendMessageUseCase;
use App\Application\UseCases\Chat\GetConversationUseCase;
use App\Application\UseCases\Chat\GetConversationsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
