<?php

namespace App\Services\Chatbot;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GeminiIntentClassifier
{
    public const INTENTS = [
        'count_students',
        'count_teachers',
        'count_classes',
        'student_count_by_class',
        'teacher_count_by_department',
        'teachers_by_class',
        'students_by_class',
        'largest_class',
        'smallest_class',
        'class_count_by_teacher',
        'my_class',
        'student_score',
        'student_average',
        'student_attendance',
        'student_conduct',
        'student_timetable',
        'student_exam_schedule',
        'student_tuition',
        'student_documents',
        'student_announcements',
        'child_class',
        'child_score',
        'child_average',
        'child_attendance',
        'child_conduct',
        'child_timetable',
        'child_exam_schedule',
        'child_tuition',
        'child_teachers',
        'child_homeroom_teacher',
        'teacher_classes',
        'teacher_student_count',
        'teacher_timetable',
        'teacher_subjects',
        'class_student_count',
        'class_students',
        'class_average_score',
        'students_missing_score',
        'students_low_score',
        'homeroom_class',
        'homeroom_student_count',
        'homeroom_attendance',
        'homeroom_conduct',
        'homeroom_low_score_students',
        'homeroom_tuition_status',
        'homeroom_leave_requests',
        'announcements',
        'events',
        'documents',
        'department_detail',
        'general',
        'unknown',
    ];

    private const ENTITY_KEYS = [
        'student',
        'class',
        'subject',
        'semester',
        'school_year',
        'date',
        'department',
        'teacher',
        'student_scope',
        'needs_clarification',
        'clarification_question',
    ];

    public function classify(User $user, string $question, Collection $history): ?ParsedChatbotIntent
    {
        if (Cache::get('chatbot:gemini_nlu_unavailable')) {
            return null;
        }

        $apiKey = trim((string) config('services.gemini.key'));

        if ($apiKey === '') {
            return null;
        }

        $prompt = $this->prompt($user, $question, $history);

        foreach ($this->modelCandidates() as $model) {
            $url = $this->generateContentUrl($model) . '?key=' . rawurlencode($apiKey);
            $startedAt = microtime(true);

            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->withOptions(['verify' => $this->verifyOption()])
                    ->connectTimeout((int) config('services.gemini.connect_timeout', 10))
                    ->timeout((int) config('services.gemini.timeout', 30))
                    ->post($url, [
                        'contents' => [[
                            'role' => 'user',
                            'parts' => [['text' => $prompt]],
                        ]],
                        'generationConfig' => [
                            'temperature' => 0,
                            'topP' => 0.1,
                            'maxOutputTokens' => 160,
                        ],
                    ]);

                $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

                if (! $response->successful()) {
                    $this->logHttpFailure($model, $response->status(), $durationMs, $response->json() ?: []);

                    if ($this->hasFallbackAfter($model)) {
                        continue;
                    }

                    Cache::put('chatbot:gemini_nlu_unavailable', true, now()->addSeconds(45));

                    return null;
                }

                return $this->parse($this->extractText($response->json() ?: []), $model, $durationMs);
            } catch (ConnectionException $exception) {
                $this->logException('connection', $model, $startedAt, $exception);

                if ($this->hasFallbackAfter($model)) {
                    continue;
                }

                Cache::put('chatbot:gemini_nlu_unavailable', true, now()->addSeconds(45));
            } catch (Throwable $exception) {
                $this->logException('exception', $model, $startedAt, $exception);

                return null;
            }
        }

        return null;
    }

    private function prompt(User $user, string $question, Collection $history): string
    {
        $previousQuestions = $history
            ->take(-3)
            ->pluck('question')
            ->filter()
            ->values()
            ->map(fn ($item) => '- ' . Str::limit((string) $item, 120))
            ->join("\n");

        return implode("\n", array_filter([
            'Bạn là bộ phân tích NLU cho chatbot trường THPT. Chỉ trả JSON hợp lệ, không markdown.',
            'Không trả số liệu, không đoán dữ liệu, không viết SQL.',
            'Intent hợp lệ: ' . implode(', ', self::INTENTS),
            'Entity hợp lệ: student, class, subject, semester, school_year, date, department, teacher, student_scope.',
            'student_scope dùng một trong: self, my_child, named_student, class_scope, school_scope.',
            'date dùng một trong: today, tomorrow, this_week, this_month hoặc YYYY-MM-DD nếu câu hỏi có ngày rõ.',
            'Nếu câu hỏi mơ hồ, đặt intent unknown, confidence dưới 0.65 và thêm clarification_question.',
            'Phân biệt kỹ: "có bao nhiêu lớp" là count_classes; "mỗi/từng lớp có bao nhiêu học sinh" là student_count_by_class; "lớp 10A1 có bao nhiêu học sinh" là class_student_count.',
            'Role hiện tại: ' . $this->roleLabel($user),
            $previousQuestions !== '' ? "Câu hỏi gần đây:\n{$previousQuestions}" : '',
            'Câu hỏi hiện tại: ' . $question,
            'JSON mẫu: {"intent":"student_count_by_class","entities":{"class":null,"subject":null,"semester":null,"date":null,"student_scope":"school_scope"},"confidence":0.96}',
        ]));
    }

    private function parse(string $text, string $model, int $durationMs): ?ParsedChatbotIntent
    {
        $json = trim((string) preg_replace('/^```(?:json)?|```$/mi', '', trim($text)));

        if (preg_match('/\{.*\}/s', $json, $matches)) {
            $json = $matches[0];
        }

        $payload = json_decode($json, true);

        if (! is_array($payload)) {
            Log::warning('Gemini NLU returned invalid JSON.', [
                'model' => $model,
                'duration_ms' => $durationMs,
                'body' => Str::limit($text, 500),
            ]);

            return null;
        }

        $intent = (string) ($payload['intent'] ?? 'unknown');
        $confidence = max(0.0, min(1.0, (float) ($payload['confidence'] ?? 0)));

        if (! in_array($intent, self::INTENTS, true)) {
            return null;
        }

        $entities = collect((array) ($payload['entities'] ?? []))
            ->only(self::ENTITY_KEYS)
            ->filter(fn ($value) => is_null($value) || is_scalar($value))
            ->map(fn ($value) => is_string($value) ? trim(Str::limit($value, 120, '')) : $value)
            ->all();

        $clarification = null;
        if ($confidence < 0.65 || $intent === 'unknown' || ($entities['needs_clarification'] ?? false)) {
            $clarification = (string) ($entities['clarification_question'] ?? 'Bạn muốn xem thông tin nào: sĩ số, điểm, điểm danh, học phí hay thời khóa biểu?');
            $intent = 'unknown';
        }

        Log::info('Gemini NLU classified chatbot question.', [
            'model' => $model,
            'intent' => $intent,
            'confidence' => $confidence,
            'duration_ms' => $durationMs,
        ]);

        return new ParsedChatbotIntent($intent, $entities, $confidence, 'gemini', $clarification ?: null);
    }

    private function extractText(array $payload): string
    {
        $parts = data_get($payload, 'candidates.0.content.parts', []);

        return collect(is_array($parts) ? $parts : [])
            ->pluck('text')
            ->filter(fn ($text) => is_string($text) && trim($text) !== '')
            ->join("\n");
    }

    private function modelCandidates(): array
    {
        return collect([
            trim((string) config('services.gemini.model_primary', config('services.gemini.model')), '/'),
            trim((string) config('services.gemini.model_fallback'), '/'),
        ])->filter()->unique()->take(2)->values()->all();
    }

    private function hasFallbackAfter(string $model): bool
    {
        $models = $this->modelCandidates();

        return array_search($model, $models, true) === 0 && count($models) > 1;
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

    private function logHttpFailure(string $model, int $status, int $durationMs, array $payload): void
    {
        $error = data_get($payload, 'error', []);

        Log::warning('Gemini NLU request failed.', [
            'model' => $model,
            'http_status' => $status,
            'duration_ms' => $durationMs,
            'error_code' => data_get($error, 'code'),
            'error_status' => data_get($error, 'status'),
            'error_message' => Str::limit((string) data_get($error, 'message', ''), 800),
        ]);
    }

    private function logException(string $type, string $model, float $startedAt, Throwable $exception): void
    {
        Log::warning('Gemini NLU request exception.', [
            'type' => $type,
            'model' => $model,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'error_message' => Str::limit((string) preg_replace('/([?&]key=)[^\s)"]+/i', '$1[redacted]', $exception->getMessage()), 800),
        ]);
    }

    private function roleLabel(User $user): string
    {
        return match (true) {
            $user->isSuperAdmin() || $user->role === 'admin' => 'admin',
            $user->isStaff() => 'staff',
            $user->isTeacher() => 'teacher',
            $user->isParent() => 'parent',
            $user->isStudent() => 'student',
            default => (string) $user->role,
        };
    }
}
