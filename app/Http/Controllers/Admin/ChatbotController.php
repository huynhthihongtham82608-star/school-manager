<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function handleChat(Request $request)
    {
        $userMessage = $request->input('message');

        if (empty($userMessage)) {
            return response()->json(['reply' => 'Nội dung tin nhắn không được để trống.']);
        }

        $apiKey = trim((string) config('services.gemini.key'));
        if ($apiKey === '') {
            return response()->json(['reply' => 'Hệ thống chưa cấu hình GEMINI_API_KEY nên chatbot chưa thể phản hồi lúc này.']);
        }

        try {
            $endpoint = rtrim((string) config('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta'), '/');
            $model = trim((string) config('services.gemini.model', 'gemini-1.5-flash'), '/');
            $url = $endpoint . '/models/' . $model . ':generateContent?key=' . rawurlencode($apiKey);

            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->timeout(35)
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $userMessage]
                            ]
                        ]
                    ],
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => "Bạn là một chú Robot trợ lý học đường thông minh, vui vẻ của một trường THPT tại Việt Nam. Toàn bộ câu trả lời bắt buộc phải viết bằng Tiếng Việt thuần túy, chữ thường mảnh dẻ, ngắn gọn dứt khoát dưới 3 câu, căn lề trái ngăn nắp."]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // Bóc tách chuẩn xác dải dữ liệu JSON từ Google Cloud
                $botReply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Hệ thống AI đang bận xử lý dữ liệu, bạn thử lại nhé!';
                return response()->json(['reply' => trim($botReply)]);
            }

            Log::error('Gemini API Error Status: ' . $response->status() . ' | Body: ' . $response->body());
            return response()->json(['reply' => 'Không thể kết nối với dịch vụ AI. Vui lòng kiểm tra lại.']);

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            return response()->json(['reply' => 'Hệ thống kết nối mạng máy local đang bị khựng, bạn bấm thử lại phát nữa nhé!']);
        }
    }
}
