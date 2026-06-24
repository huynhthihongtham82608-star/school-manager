<?php

namespace App\Http\Controllers;

use App\Models\ChatbotMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ChatbotController extends Controller
{
    public function index(Request $request)
    {
        $messages = Schema::hasTable('chatbot_messages')
            ? ChatbotMessage::where('user_id', $request->user()->id)->latest('created_at')->limit(20)->get()->reverse()
            : collect();

        return view('chatbot.index', compact('messages'));
    }

    public function ask(Request $request)
    {
        if (! Schema::hasTable('chatbot_messages')) {
            return back()->with('error', 'Chưa có bảng chatbot_messages. Vui lòng chạy migration trước.');
        }

        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        $answer = $this->answer($data['question']);

        ChatbotMessage::create([
            'user_id' => $request->user()->id,
            'question' => $data['question'],
            'answer' => $answer,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Chatbot đã phản hồi câu hỏi của bạn.');
    }

    private function answer(string $question): string
    {
        $text = mb_strtolower($question);

        return match (true) {
            str_contains($text, 'điểm') => 'Bạn có thể xem kết quả học tập trong Dashboard hoặc mục nhập điểm nếu là giáo viên.',
            str_contains($text, 'thời khóa biểu') || str_contains($text, 'tkb') => 'Mục Thời khóa biểu hiển thị lịch học, lịch dạy theo lớp và học kỳ hiện tại.',
            str_contains($text, 'lịch thi') => 'Bạn có thể mở mục Lịch thi để xem môn thi, ngày thi, phòng thi và ghi chú liên quan.',
            str_contains($text, 'tài liệu') => 'Mục Tài liệu học tập chứa các tài liệu do nhà trường hoặc giáo viên cung cấp.',
            str_contains($text, 'điểm danh') => 'Mục Điểm danh giúp theo dõi trạng thái có mặt, vắng, đi muộn hoặc nghỉ có phép.',
            default => 'Tôi có thể hỗ trợ tra cứu nhanh về điểm, thời khóa biểu, lịch thi, điểm danh, tài liệu học tập và thông báo của nhà trường.',
        };
    }
}
