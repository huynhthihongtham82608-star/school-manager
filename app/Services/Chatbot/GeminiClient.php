<?php

namespace App\Services\Chatbot;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GeminiClient
{
    public function chooseTool(string $systemInstruction, Collection $history, string $message, array $toolDeclarations): array
    {
        return $this->requestWithFallback(function (string $model) use ($systemInstruction, $history, $message, $toolDeclarations) {
            return $this->postGenerateContent($model, [
                'systemInstruction' => [
                    'parts' => [['text' => $systemInstruction]],
                ],
                'contents' => $this->contentsFromHistory($history, $message),
                'tools' => [
                    ['functionDeclarations' => $toolDeclarations],
                ],
                'toolConfig' => [
                    'functionCallingConfig' => [
                        'mode' => 'AUTO',
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.15,
                    'topP' => 0.85,
                    'maxOutputTokens' => 700,
                ],
            ]);
        });
    }

    public function finalAnswer(
        string $systemInstruction,
        Collection $history,
        string $message,
        array $functionCall,
        array $toolResult,
        array $toolDeclarations,
    ): array {
        return $this->requestWithFallback(function (string $model) use ($systemInstruction, $history, $message, $functionCall, $toolResult, $toolDeclarations) {
            $contents = $this->contentsFromHistory($history, $message);
            $modelPart = [
                'functionCall' => [
                    'name' => (string) ($functionCall['name'] ?? ''),
                    'args' => (object) ($functionCall['args'] ?? []),
                ],
            ];

            if (! empty($functionCall['id'])) {
                $modelPart['functionCall']['id'] = (string) $functionCall['id'];
            }

            if (! empty($functionCall['thought_signature'])) {
                $modelPart['thoughtSignature'] = (string) $functionCall['thought_signature'];
            }

            $functionResponse = [
                'name' => (string) ($functionCall['name'] ?? ''),
                'response' => $toolResult,
            ];

            if (! empty($functionCall['id'])) {
                $functionResponse['id'] = (string) $functionCall['id'];
            }

            $contents[] = [
                'role' => 'model',
                'parts' => [$modelPart],
            ];
            $contents[] = [
                'role' => 'user',
                'parts' => [[
                    'functionResponse' => $functionResponse,
                ]],
            ];

            return $this->postGenerateContent($model, [
                'systemInstruction' => [
                    'parts' => [['text' => $systemInstruction]],
                ],
                'contents' => $contents,
                'tools' => [
                    ['functionDeclarations' => $toolDeclarations],
                ],
                'generationConfig' => [
                    'temperature' => 0.25,
                    'topP' => 0.9,
                    'maxOutputTokens' => 800,
                ],
            ]);
        });
    }

    public function liveSmokeTest(string $message = 'Hãy trả lời ngắn gọn: kết nối Gemini đã sẵn sàng.'): array
    {
        return $this->requestWithFallback(fn (string $model) => $this->postGenerateContent($model, [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $message]],
            ]],
            'generationConfig' => [
                'temperature' => 0,
                'maxOutputTokens' => 40,
            ],
        ]));
    }

    private function requestWithFallback(callable $sender): array
    {
        $apiKey = trim((string) config('services.gemini.key'));

        if ($apiKey === '') {
            return [
                'success' => false,
                'error_type' => 'missing_api_key',
                'message' => 'Gemini API key chưa được cấu hình.',
                'model' => null,
                'http_status' => null,
                'duration_ms' => 0,
            ];
        }

        $lastFailure = null;

        foreach ($this->modelCandidates() as $model) {
            $startedAt = microtime(true);

            try {
                $response = $sender($model);
                $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

                if (! $response->successful()) {
                    $lastFailure = [
                        'success' => false,
                        'error_type' => 'http_error',
                        'message' => $this->responseErrorMessage($response->json() ?: [], $response->body()),
                        'model' => $model,
                        'http_status' => $response->status(),
                        'duration_ms' => $durationMs,
                    ];

                    Log::warning('Gemini chatbot request failed.', [
                        'model' => $model,
                        'http_status' => $response->status(),
                        'duration_ms' => $durationMs,
                        'error_message' => Str::limit($lastFailure['message'], 800),
                    ]);

                    continue;
                }

                $payload = $response->json() ?: [];

                return [
                    'success' => true,
                    'model' => $model,
                    'http_status' => $response->status(),
                    'duration_ms' => $durationMs,
                    'text' => $this->extractText($payload),
                    'function_call' => $this->extractFunctionCall($payload),
                    'raw' => $payload,
                ];
            } catch (ConnectionException $exception) {
                $lastFailure = $this->exceptionFailure('connection', $model, $startedAt, $exception);
            } catch (Throwable $exception) {
                $lastFailure = $this->exceptionFailure('exception', $model, $startedAt, $exception);
            }
        }

        return $lastFailure ?: [
            'success' => false,
            'error_type' => 'unknown',
            'message' => 'Không nhận được phản hồi từ Gemini.',
            'model' => null,
            'http_status' => null,
            'duration_ms' => 0,
        ];
    }

    private function postGenerateContent(string $model, array $payload)
    {
        return Http::acceptJson()
            ->asJson()
            ->withOptions(['verify' => $this->verifyOption()])
            ->connectTimeout((int) config('services.gemini.connect_timeout', 10))
            ->timeout((int) config('services.gemini.timeout', 30))
            ->post($this->generateContentUrl($model) . '?key=' . rawurlencode((string) config('services.gemini.key')), $payload);
    }

    private function contentsFromHistory(Collection $history, string $message): array
    {
        $contents = [];

        foreach ($history->take(-6) as $item) {
            $question = trim((string) ($item->question ?? ''));
            $answer = trim((string) ($item->answer ?? ''));

            if ($question !== '') {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => Str::limit($question, 1000, '')]],
                ];
            }

            if ($answer !== '') {
                $contents[] = [
                    'role' => 'model',
                    'parts' => [['text' => Str::limit($answer, 1200, '')]],
                ];
            }
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        return $contents;
    }

    private function extractFunctionCall(array $payload): ?array
    {
        $parts = data_get($payload, 'candidates.0.content.parts', []);

        if (! is_array($parts)) {
            return null;
        }

        foreach ($parts as $part) {
            $call = $part['functionCall'] ?? null;

            if (! is_array($call) || empty($call['name'])) {
                continue;
            }

            return [
                'name' => (string) $call['name'],
                'args' => is_array($call['args'] ?? null) ? $call['args'] : [],
                'id' => (string) ($call['id'] ?? ''),
                'thought_signature' => (string) ($part['thoughtSignature'] ?? ''),
            ];
        }

        return null;
    }

    private function extractText(array $payload): string
    {
        $parts = data_get($payload, 'candidates.0.content.parts', []);

        if (! is_array($parts)) {
            return '';
        }

        return collect($parts)
            ->pluck('text')
            ->filter(fn ($text) => is_string($text) && trim($text) !== '')
            ->map(fn ($text) => trim($text))
            ->join("\n");
    }

    private function responseErrorMessage(array $payload, string $body): string
    {
        $message = (string) data_get($payload, 'error.message', '');

        if ($message === '') {
            $message = Str::limit($body, 800, '');
        }

        return $this->redactSecrets($message);
    }

    private function exceptionFailure(string $type, string $model, float $startedAt, Throwable $exception): array
    {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $message = $this->redactSecrets($exception->getMessage());

        Log::warning('Gemini chatbot request exception.', [
            'type' => $type,
            'model' => $model,
            'duration_ms' => $durationMs,
            'error_message' => Str::limit($message, 800),
        ]);

        return [
            'success' => false,
            'error_type' => $type,
            'message' => $message,
            'model' => $model,
            'http_status' => null,
            'duration_ms' => $durationMs,
        ];
    }

    private function modelCandidates(): array
    {
        return collect([
            trim((string) config('services.gemini.model_primary', config('services.gemini.model')), '/'),
            trim((string) config('services.gemini.model_fallback'), '/'),
            trim((string) config('services.gemini.model'), '/'),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function generateContentUrl(string $model): string
    {
        $endpoint = rtrim((string) config('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        return "{$endpoint}/models/{$model}:generateContent";
    }

    private function verifyOption(): bool|string
    {
        $caBundle = trim((string) config('services.gemini.ca_bundle'));

        if ($caBundle === '') {
            return true;
        }

        return is_file($caBundle) ? $caBundle : str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caBundle);
    }

    private function redactSecrets(string $message): string
    {
        return (string) preg_replace('/([?&]key=)[^\s)"]+/i', '$1[redacted]', $message);
    }
}
