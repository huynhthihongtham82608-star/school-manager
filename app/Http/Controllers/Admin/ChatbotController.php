<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Chatbot\SchoolChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotController extends Controller
{
    public function history(Request $request, SchoolChatbotService $chatbot): JsonResponse
    {
        return response()->json(
            $chatbot->historyPayload($request->user(), 30),
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function handleChat(Request $request, SchoolChatbotService $chatbot): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1200'],
        ]);

        try {
            return response()->json(
                $chatbot->answer($request->user(), $data['message']),
                200,
                [],
                JSON_UNESCAPED_UNICODE
            );
        } catch (Throwable $exception) {
            Log::error('Floating chatbot request failed.', [
                'user_id' => $request->user()?->getKey(),
                'message_length' => mb_strlen($data['message']),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'reply' => 'Trợ lý hiện chưa thể phản hồi. Vui lòng thử lại sau.',
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
