<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ChatbotController as WebChatbotController;
use App\Http\Controllers\Api\ChatbotApiController;
use App\Models\User;
use App\Services\Chatbot\GeminiClient;
use App\Services\Chatbot\SchoolChatbotService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class ChatbotRequestFlowTest extends TestCase
{
    public function test_web_chatbot_request_uses_orchestrator_flow_without_missing_store_method(): void
    {
        $this->bindGeminiReply('Xin chào, tôi đang sẵn sàng hỗ trợ.');

        $response = app(WebChatbotController::class)->handleChat(
            $this->chatRequest('/chatbot/send', 'xin chào', $this->chatUser()),
            app(SchoolChatbotService::class)
        );

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $payload['status']);
        $this->assertTrue($payload['success']);
        $this->assertSame('Xin chào, tôi đang sẵn sàng hỗ trợ.', $payload['reply']);
    }

    public function test_api_chatbot_request_uses_same_orchestrator_flow_without_missing_store_method(): void
    {
        $this->bindGeminiReply('Xin chào, API chatbot đã sẵn sàng.');

        $response = app(ChatbotApiController::class)->handleQuery(
            $this->chatRequest('/api/chatbot/query', 'xin chào', $this->chatUser()),
            app(SchoolChatbotService::class)
        );

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $payload['status']);
        $this->assertTrue($payload['success']);
        $this->assertSame('Xin chào, API chatbot đã sẵn sàng.', $payload['reply']);
    }

    private function bindGeminiReply(string $reply): void
    {
        $gemini = Mockery::mock(GeminiClient::class);
        $gemini->shouldReceive('chooseTool')
            ->once()
            ->andReturn([
                'success' => true,
                'model' => 'fake-gemini',
                'text' => $reply,
                'function_call' => null,
            ]);
        $gemini->shouldNotReceive('finalAnswer');

        $this->app->instance(GeminiClient::class, $gemini);
    }

    private function chatRequest(string $uri, string $message, User $user): Request
    {
        $request = Request::create($uri, 'POST', ['message' => $message]);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function chatUser(): User
    {
        return new User([
            'role' => 'student',
            'role_type' => 'student',
            'is_active' => true,
            'login_status' => true,
        ]);
    }
}
