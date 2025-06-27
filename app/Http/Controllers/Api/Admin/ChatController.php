<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Application\UseCases\Chat\GetConversationsUseCase;
use App\Application\UseCases\Chat\GetConversationUseCase;
use App\Application\UseCases\Chat\SendMessageUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function getConversations(Request $request, GetConversationsUseCase $getConversationsUseCase): JsonResponse
    {
        $user = $request->user();
        
        $result = $getConversationsUseCase->execute($user->id, 'admin');

        return response()->json($result, 200);
    }

    public function getConversationWithUser(Request $request, GetConversationUseCase $getConversationUseCase): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|min:1',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100'
        ]);

        $admin = $request->user();

        $result = $getConversationUseCase->execute(
            $admin->id,
            'admin',
            $request->user_id,
            'user',
            $request->get('page', 1),
            $request->get('per_page', 50)
        );

        return response()->json($result, 200);
    }

    public function sendMessageToUser(Request $request, SendMessageUseCase $sendMessageUseCase): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'user_id' => 'required|integer|min:1'
        ]);

        $admin = $request->user();

        $result = $sendMessageUseCase->execute(
            $request->content,
            'admin',
            $admin->id,
            'user',
            $request->user_id
        );

        return response()->json($result, 201);
    }
}
