<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\SchoolChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotController extends Controller
{
    public function index(Request $request, SchoolChatbotService $chatbot)
    {
        return view('chatbot.index', [
            'messages' => $chatbot->recentMessages($request->user(), 30),
            'suggestions' => $this->suggestionsFor($request->user()),
        ]);
    }

    public function ask(Request $request, SchoolChatbotService $chatbot)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1200'],
        ]);

        try {
            $result = $chatbot->answer($request->user(), $data['question']);
        } catch (Throwable $exception) {
            Log::error('Chatbot page request failed.', [
                'user_id' => $request->user()?->getKey(),
                'message_length' => mb_strlen($data['question']),
                'error' => $exception->getMessage(),
            ]);

            $result = [
                'status' => 'error',
                'reply' => 'Trợ lý hiện chưa thể phản hồi. Vui lòng thử lại sau.',
            ];
        }

        return back()
            ->with($result['status'] === 'success' ? 'success' : 'error', $result['reply'])
            ->with('chatbot_open', true);
    }

    private function suggestionsFor($user): array
    {
        if ($user->isStudent()) {
            return ['Xem điểm của tôi', 'Hôm nay tôi học gì?', 'Lịch kiểm tra sắp tới', 'Tôi đã nghỉ bao nhiêu buổi?'];
        }

        if ($user->isParent()) {
            return ['Kết quả học tập của con', 'Học phí còn bao nhiêu?', 'Con đã nghỉ bao nhiêu buổi?', 'Lịch kiểm tra của con'];
        }

        if ($user->isTeacher()) {
            return ['Lịch dạy hôm nay', 'Các lớp tôi đang dạy', 'Tiến độ nhập điểm', 'Lớp chủ nhiệm nghỉ nhiều nhất'];
        }

        return ['Tổng số học sinh', 'Thống kê điểm danh', 'Học phí toàn trường', 'Học sinh có nguy cơ'];
    }}

