<?php

namespace Tests\Unit;

use App\Services\Chatbot\GeminiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiClientErrorHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'fake-secret-for-test',
            'services.gemini.endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'services.gemini.model_primary' => 'gemini-1.5-flash',
            'services.gemini.model_fallback' => 'gemini-1.5-flash-8b',
            'services.gemini.timeout' => 1,
            'services.gemini.connect_timeout' => 1,
        ]);
    }

    public function test_quota_error_stops_after_primary_model_and_redacts_secret(): void
    {
        Http::fake(fn () => Http::response([
            'error' => ['message' => 'Quota exceeded for ?key=fake-secret-for-test'],
        ], 429));

        $result = app(GeminiClient::class)->chooseTool('system', collect(), 'xin chao', []);

        $this->assertFalse($result['success']);
        $this->assertSame('http_error', $result['error_type']);
        $this->assertSame(429, $result['http_status']);
        $this->assertStringNotContainsString('fake-secret-for-test', $result['message']);
        $this->assertStringContainsString('[redacted]', $result['message']);
        Http::assertSentCount(1);
    }

    public function test_server_error_does_not_retry_fallback_or_expose_stack_trace(): void
    {
        Http::fake(fn () => Http::response([
            'error' => ['message' => 'Service unavailable'],
        ], 503));

        $result = app(GeminiClient::class)->chooseTool('system', collect(), 'xin chao', []);

        $this->assertFalse($result['success']);
        $this->assertSame('http_error', $result['error_type']);
        $this->assertSame(503, $result['http_status']);
        $this->assertStringNotContainsString('Exception', $result['message']);
        Http::assertSentCount(1);
    }

    public function test_timeout_returns_sanitized_connection_failure_without_retry_loop(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 1000 milliseconds'));

        $result = app(GeminiClient::class)->chooseTool('system', collect(), 'xin chao', []);

        $this->assertFalse($result['success']);
        $this->assertSame('connection', $result['error_type']);
        $this->assertNull($result['http_status']);
        $this->assertStringNotContainsString('fake-secret-for-test', $result['message']);
        $this->assertSame('gemini-1.5-flash', $result['model']);
    }

    public function test_network_error_returns_sanitized_connection_failure_without_retry_loop(): void
    {
        Http::fake(fn () => throw new ConnectionException('DNS lookup failed for generativelanguage.googleapis.com'));

        $result = app(GeminiClient::class)->chooseTool('system', collect(), 'xin chao', []);

        $this->assertFalse($result['success']);
        $this->assertSame('connection', $result['error_type']);
        $this->assertNull($result['http_status']);
        $this->assertStringNotContainsString('fake-secret-for-test', $result['message']);
        $this->assertSame('gemini-1.5-flash', $result['model']);
    }

    public function test_missing_api_key_fails_before_http_request(): void
    {
        config(['services.gemini.key' => '']);
        Http::fake();

        $result = app(GeminiClient::class)->chooseTool('system', collect(), 'xin chao', []);

        $this->assertFalse($result['success']);
        $this->assertSame('missing_api_key', $result['error_type']);
        $this->assertNull($result['http_status']);
        Http::assertNothingSent();
    }
}
