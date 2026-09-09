<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ChatbotOrchestrator
{
    private const FRIENDLY_ERROR = 'Trợ lý AI tạm thời chưa phản hồi được. Vui lòng thử lại sau.';

    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly ChatbotToolRegistry $registry,
        private readonly ChatbotToolExecutor $executor,
        private readonly UserScopeService $scope,
    ) {
    }

    public function recentMessages(User $user, int $limit = 20): Collection
    {
        if (! $user->getKey() || ! Schema::hasTable('chatbot_messages')) {
            return collect();
        }

        return ChatbotMessage::query()
            ->where('user_id', $user->getKey())
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function historyPayload(User $user, int $limit = 30): array
    {
        return [
            'status' => 'success',
            'messages' => $this->recentMessages($user, $limit)
                ->flatMap(fn (ChatbotMessage $message) => [
                    [
                        'type' => 'user',
                        'text' => (string) $message->question,
                        'created_at' => optional($message->created_at)->toIso8601String(),
                    ],
                    [
                        'type' => 'bot',
                        'text' => $this->normalizeReply((string) $message->answer),
                        'created_at' => optional($message->created_at)->toIso8601String(),
                    ],
                ])
                ->values()
                ->all(),
        ];
    }

    public function clearHistory(User $user): void
    {
        if (Schema::hasTable('chatbot_messages')) {
            ChatbotMessage::query()->where('user_id', $user->getKey())->delete();
        }
    }

    public function answer(User $user, string $question): array
    {
        $startedAt = microtime(true);
        $question = trim($question);

        if ($question === '') {
            return $this->payload('error', 'Vui lòng nhập nội dung cần hỏi.');
        }

        if (! $this->scope->canUseChatbot($user)) {
            return $this->storeAndPayload($user, $question, 'Tài khoản của bạn chưa được phép sử dụng trợ lý lúc này.', 'error', [
                'latency_ms' => $this->duration($startedAt),
                'error' => 'user_not_allowed',
            ]);
        }

        $history = $this->recentMessages($user, 4);
        $systemInstruction = $this->systemInstruction($user);
        $toolDeclarations = $this->registry->declarations();
        $choice = $this->gemini->chooseTool($systemInstruction, $history, $question, $toolDeclarations);

        if (! ($choice['success'] ?? false)) {
            return $this->storeAndPayload($user, $question, self::FRIENDLY_ERROR, 'error', [
                'model' => $choice['model'] ?? null,
                'latency_ms' => $this->duration($startedAt),
                'error' => $choice['error_type'] ?? $choice['message'] ?? 'gemini_choice_failed',
            ]);
        }

        $functionCall = $choice['function_call'] ?? null;

        if (! $functionCall) {
            $reply = $this->normalizeReply((string) ($choice['text'] ?? ''));
            if ($reply === '') {
                $reply = 'Bạn muốn tra cứu điểm số, thời khóa biểu, học phí, điểm danh hay thông tin nào khác?';
            }

            return $this->storeAndPayload($user, $question, $reply, 'success', [
                'model' => $choice['model'] ?? null,
                'latency_ms' => $this->duration($startedAt),
            ]);
        }

        $toolName = (string) ($functionCall['name'] ?? '');
        $toolArgs = is_array($functionCall['args'] ?? null) ? $functionCall['args'] : [];
        $toolArgs = $this->enrichToolArgsFromHistory($user, $toolName, $toolArgs, $history);
        $functionCall['args'] = $toolArgs;

        $toolResult = $this->executor->execute($user, $toolName, $toolArgs);
        $final = $this->gemini->finalAnswer($systemInstruction, $history, $question, $functionCall, $toolResult, $toolDeclarations);
        $reply = $this->normalizeReply((string) ($final['text'] ?? ''));
        $status = 'success';
        $error = null;

        if (! ($final['success'] ?? false) || $reply === '') {
            $status = ($toolResult['success'] ?? false) ? 'success' : 'error';
            $reply = $this->fallbackReplyForToolResult($toolName, $toolResult);
            $error = $final['error_type'] ?? $final['message'] ?? 'gemini_final_failed';
        }

        return $this->storeAndPayload($user, $question, $reply, $status, [
            'intent' => $toolName,
            'entities' => array_merge($toolArgs, $this->extractEntityMetadata($toolResult)),
            'tool_name' => $toolName,
            'tool_args' => $toolArgs,
            'tool_result_summary' => $this->summarizeToolResult($toolResult),
            'model' => $final['model'] ?? $choice['model'] ?? null,
            'latency_ms' => $this->duration($startedAt),
            'gemini_choice_ms' => $choice['duration_ms'] ?? null,
            'tool_duration_ms' => $toolResult['duration_ms'] ?? null,
            'gemini_final_ms' => $final['duration_ms'] ?? null,
            'tool_call_count' => 1,
            'gemini_rounds' => 2,
            'error' => $error,
        ]);
    }

    private function systemInstruction(User $user): string
    {
        return implode("\n", [
            'Bạn là trợ lý AI của hệ thống quản lý trường THPT.',
            'Trả lời bằng tiếng Việt thuần văn bản, không Markdown, không **in đậm**, không backtick, không JSON, không tên tool, không UUID và không chi tiết kỹ thuật nội bộ.',
            'Trả lời ngắn gọn, tự nhiên, tối đa 4 đoạn ngắn, chỉ dùng dữ liệu đã được tool Laravel trả về.',
            'Khi câu hỏi cần dữ liệu trường học, điều hướng chức năng hoặc cách tính điểm, bắt buộc gọi tool phù hợp trước khi trả lời.',
            'Không tự đoán số liệu, không tự tạo menu, không dùng kiến thức chung thay cho cấu hình thật của hệ thống.',
            'Không được suy diễn teaching_assignments.role=primary là giáo viên chủ nhiệm; lớp chủ nhiệm chỉ lấy từ dữ liệu lớp học canonical.',
            'Nếu phụ huynh có nhiều con và câu hỏi chưa xác định học sinh, hãy hỏi chọn học sinh trước, không tự chọn học sinh đầu tiên.',
            'Nếu thiếu tên lớp, học sinh, môn học hoặc ngày cần thiết, hãy hỏi lại ngắn gọn.',
            'Vai trò người dùng hiện tại: ' . $this->scope->roleLabel($user) . '.',
        ]);
    }

    private function enrichToolArgsFromHistory(User $user, string $toolName, array $toolArgs, Collection $history): array
    {
        if ($this->missingStudentIdentifier($toolArgs) && $this->isStudentScopedTool($toolName)) {
            $activeChildId = $this->latestEntity($history, 'active_child_id');
            if ($activeChildId) {
                $toolArgs['student_id'] = $activeChildId;
            }
        }

        if ($this->missingClassIdentifier($toolArgs) && $this->isClassScopedTool($toolName)) {
            $activeClassId = $this->latestEntity($history, 'active_class_id');
            if ($activeClassId) {
                $toolArgs['class_id'] = $activeClassId;
            }
        }

        return $toolArgs;
    }

    private function isStudentScopedTool(string $toolName): bool
    {
        return in_array($toolName, [
            'get_student_scores',
            'get_student_timetable',
            'get_student_attendance',
            'get_student_conduct',
            'get_child_tuition',
            'get_student_exam_schedule',
            'get_child_subject_teachers',
        ], true);
    }

    private function isClassScopedTool(string $toolName): bool
    {
        return in_array($toolName, [
            'get_class_student_count',
            'get_class_students',
            'get_class_subject_teachers',
            'get_class_homeroom_teacher',
            'get_student_exam_schedule',
        ], true);
    }

    private function missingStudentIdentifier(array $args): bool
    {
        return trim((string) ($args['student_id'] ?? '')) === ''
            && trim((string) ($args['student_name'] ?? $args['student'] ?? '')) === ''
            && trim((string) ($args['student_code'] ?? '')) === '';
    }

    private function missingClassIdentifier(array $args): bool
    {
        return trim((string) ($args['class_id'] ?? '')) === ''
            && trim((string) ($args['class_name'] ?? $args['class'] ?? '')) === '';
    }

    private function latestEntity(Collection $history, string $key): ?string
    {
        return $history->reverse()
            ->map(function ($message) use ($key) {
                $entities = $message->entities;
                if (is_string($entities)) {
                    $entities = json_decode($entities, true) ?: [];
                }

                return is_array($entities) ? (string) ($entities[$key] ?? '') : '';
            })
            ->first(fn ($value) => trim($value) !== '') ?: null;
    }

    private function extractEntityMetadata(array $toolResult): array
    {
        $data = $toolResult['data'] ?? [];
        if (! is_array($data)) {
            return [];
        }

        $entities = [];

        foreach (['student' => 'active_child_id', 'class' => 'active_class_id'] as $sourceKey => $entityKey) {
            $row = $data[$sourceKey] ?? null;
            if (is_array($row) && ! empty($row['id'])) {
                $entities[$entityKey] = (string) $row['id'];
            }
        }

        if (! empty($data['subject_filter'])) {
            $entities['active_subject'] = (string) $data['subject_filter'];
        }

        return $entities;
    }

    private function fallbackReplyForToolResult(string $toolName, array $toolResult): string
    {
        if (! ($toolResult['success'] ?? false)) {
            return $this->normalizeReply((string) ($toolResult['message'] ?? self::FRIENDLY_ERROR));
        }

        $data = $toolResult['data'] ?? [];
        if (! is_array($data)) {
            return 'Tôi đã truy vấn được dữ liệu, nhưng chưa thể diễn đạt đầy đủ lúc này.';
        }

        $reply = match ($toolName) {
            'get_class_student_count' => $this->formatClassStudentCount($data),
            'get_class_students' => $this->formatClassStudents($data),
            'get_teacher_classes' => $this->formatTeacherClasses($data),
            'get_teacher_homeroom_class' => $this->formatTeacherHomeroom($data),
            'get_class_subject_teachers', 'get_child_subject_teachers' => $this->formatClassSubjectTeachers($data),
            'get_class_homeroom_teacher' => $this->formatClassHomeroomTeacher($data),
            'get_student_scores' => $this->formatStudentScores($data),
            'get_student_attendance' => $this->formatStudentAttendance($data),
            'get_student_conduct' => $this->formatStudentConduct($data),
            'get_child_tuition' => $this->formatChildTuition($data),
            'get_score_calculation_rules' => $this->formatScoreRules($data),
            'get_system_navigation' => $this->formatNavigation($data),
            default => 'Tôi đã truy vấn được dữ liệu phù hợp. Bạn có thể hỏi cụ thể hơn để tôi tóm tắt ngắn gọn.',
        };

        return $this->normalizeReply($reply);
    }

    private function formatClassStudentCount(array $data): string
    {
        if (($data['mode'] ?? '') === 'single') {
            return 'Lớp ' . ($data['class']['name'] ?? '') . ' có ' . (int) ($data['student_count'] ?? 0) . ' học sinh.';
        }

        return collect($data['classes'] ?? [])
            ->map(fn ($class) => ($class->name ?? $class['name'] ?? '') . ': ' . (int) ($class->student_count ?? $class['student_count'] ?? 0) . ' học sinh')
            ->filter()
            ->join("\n") ?: 'Chưa tìm thấy dữ liệu sĩ số phù hợp.';
    }

    private function formatClassStudents(array $data): string
    {
        $class = $data['class']['name'] ?? '';
        $students = collect($data['students'] ?? [])->map(fn ($student) => trim(($student['code'] ?? '') . ' - ' . ($student['name'] ?? ''), ' -'))->filter();

        if ($students->isEmpty()) {
            return 'Lớp ' . $class . ' hiện chưa có học sinh.';
        }

        return 'Lớp ' . $class . ' có ' . (int) ($data['student_count'] ?? $students->count()) . " học sinh:\n" . $students->take(30)->join("\n");
    }

    private function formatTeacherClasses(array $data): string
    {
        $teacher = $data['teacher']['name'] ?? 'Giáo viên';
        $assignments = collect($data['teaching_assignments'] ?? [])->map(fn ($row) => ($row->class_name ?? $row['class_name'] ?? '') . ' - ' . ($row->subject_name ?? $row['subject_name'] ?? ''))->filter();
        $homeroom = $data['homeroom_class']['name'] ?? null;
        $lines = [$teacher . ' đang dạy: ' . ($assignments->isNotEmpty() ? $assignments->join(', ') : 'chưa có phân công dạy.')];
        $lines[] = 'Lớp chủ nhiệm: ' . ($homeroom ?: 'chưa cập nhật.');
        if (! empty($data['homeroom_data_warning'])) {
            $lines[] = $data['homeroom_data_warning'];
        }

        return implode("\n", $lines);
    }

    private function formatTeacherHomeroom(array $data): string
    {
        $teacher = $data['teacher']['name'] ?? 'Giáo viên';
        $class = $data['homeroom_class']['name'] ?? null;
        $reply = $class ? $teacher . ' đang chủ nhiệm lớp ' . $class . '.' : $teacher . ' hiện chưa có lớp chủ nhiệm.';

        return $reply . (! empty($data['data_warning']) ? "\n" . $data['data_warning'] : '');
    }

    private function formatClassSubjectTeachers(array $data): string
    {
        $class = $data['class']['name'] ?? '';
        $teachers = collect($data['teachers'] ?? [])->map(fn ($row) => ($row['teacher'] ?? '') . (($row['subject'] ?? '') ? ' - ' . $row['subject'] : ''))->filter();

        if ($teachers->isEmpty()) {
            return 'Chưa tìm thấy giáo viên đang dạy lớp ' . $class . '.';
        }

        return 'Lớp ' . $class . ' có ' . (int) ($data['distinct_teacher_count'] ?? $teachers->count()) . " giáo viên đang dạy:\n" . $teachers->join("\n");
    }

    private function formatClassHomeroomTeacher(array $data): string
    {
        return ! empty($data['homeroom_teacher'])
            ? 'Giáo viên chủ nhiệm lớp ' . ($data['class']['name'] ?? '') . ' là ' . ($data['homeroom_teacher']['name'] ?? '') . '.'
            : 'Lớp ' . ($data['class']['name'] ?? '') . ' hiện chưa cập nhật giáo viên chủ nhiệm.';
    }

    private function formatStudentScores(array $data): string
    {
        $student = $data['student']['name'] ?? 'Học sinh';
        $scores = collect($data['scores'] ?? [])->map(fn ($row) => ($row['subject'] ?? '') . ': ' . (($row['average'] ?? null) !== null ? $row['average'] : 'chưa có điểm'))->filter();

        return $scores->isEmpty()
            ? $student . ' hiện chưa có điểm được ghi nhận.'
            : 'Điểm trung bình của ' . $student . ":\n" . $scores->join(', ');
    }

    private function formatStudentAttendance(array $data): string
    {
        $student = $data['student']['name'] ?? 'Học sinh';
        $summary = $data['summary'] ?? [];

        return 'Lịch sử nề nếp của ' . $student . ': đi muộn ' . (int) ($summary['late'] ?? 0)
            . ' lần, nghỉ có phép ' . (int) ($summary['permitted_absent'] ?? 0)
            . ' buổi, vắng không phép ' . (int) ($summary['unexcused_absent'] ?? 0) . ' buổi.';
    }

    private function formatStudentConduct(array $data): string
    {
        $student = $data['student']['name'] ?? 'Học sinh';
        $latest = collect($data['conducts'] ?? [])->first();
        $level = is_array($latest) ? ($latest['conduct_level'] ?? null) : ($latest->conduct_level ?? null);

        return $level ? 'Hạnh kiểm hiện ghi nhận của ' . $student . ': ' . $level . '.' : $student . ' hiện chưa có dữ liệu hạnh kiểm.';
    }

    private function formatChildTuition(array $data): string
    {
        $student = $data['student']['name'] ?? 'Học sinh';

        return 'Học phí của ' . $student . ': tổng ' . ($data['total_amount'] ?? '0đ')
            . ', đã đóng ' . (int) ($data['paid_records'] ?? 0)
            . ' khoản, chưa đóng ' . (int) ($data['unpaid_records'] ?? 0) . ' khoản.'
            . (! empty($data['qr_image_url']) ? "\nMã QR: " . $data['qr_image_url'] : '');
    }

    private function formatScoreRules(array $data): string
    {
        $weights = collect($data['weights'] ?? [])->map(fn ($value, $label) => $label . ' hệ số ' . $value)->join(', ');

        return 'Cách tính điểm đang cấu hình trong hệ thống: ' . $weights . ".\n" . ($data['formula'] ?? '');
    }

    private function formatNavigation(array $data): string
    {
        $items = collect($data['items'] ?? []);
        if ($items->isEmpty()) {
            return 'Chưa tìm thấy chức năng phù hợp trong menu hiện tại.';
        }

        return $items->take(5)->map(fn ($item) => ($item['label'] ?? '') . ': ' . ($item['section'] ?? '') . ' > ' . ($item['group'] ?? ''))->filter()->join("\n");
    }

    private function storeAndPayload(User $user, string $question, string $reply, string $status = 'success', array $metadata = []): array
    {
        $reply = $this->normalizeReply($reply);

        if ($reply === '') {
            $reply = self::FRIENDLY_ERROR;
            $status = 'error';
        }

        $metadata['entities'] = is_array($metadata['entities'] ?? null) ? $metadata['entities'] : [];
        $this->rememberActiveEntities($metadata['entities']);
        $this->storeMessage($user, $question, $reply, $metadata);

        return $this->payload($status, $reply, [
            'intent' => $metadata['intent'] ?? null,
            'model' => $metadata['model'] ?? null,
            'latency_ms' => $metadata['latency_ms'] ?? null,
        ]);
    }

    private function payload(string $status, string $reply, array $metadata = []): array
    {
        $reply = $this->normalizeReply($reply);

        return array_filter([
            'status' => $status,
            'success' => $status === 'success',
            'reply' => $reply !== '' ? $reply : self::FRIENDLY_ERROR,
            'intent' => $metadata['intent'] ?? null,
            'model' => $metadata['model'] ?? null,
            'latency_ms' => $metadata['latency_ms'] ?? null,
        ], fn ($value) => $value !== null);
    }

    private function storeMessage(User $user, string $question, string $reply, array $metadata): void
    {
        if (! $user->getKey() || ! Schema::hasTable('chatbot_messages')) {
            return;
        }

        $row = [
            'user_id' => (string) $user->getKey(),
            'question' => $question,
            'answer' => $reply,
            'created_at' => now(),
        ];

        foreach ([
            'intent',
            'entities',
            'tool_name',
            'tool_args',
            'tool_result_summary',
            'model',
            'latency_ms',
            'error',
        ] as $column) {
            if (Schema::hasColumn('chatbot_messages', $column) && array_key_exists($column, $metadata)) {
                $row[$column] = $metadata[$column];
            }
        }

        try {
            ChatbotMessage::query()->create($row);
        } catch (\Throwable $exception) {
            Log::warning('Unable to store chatbot message.', [
                'user_id' => $user->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function rememberActiveEntities(array $entities): void
    {
        if (! function_exists('session')) {
            return;
        }

        if (! empty($entities['active_child_id'])) {
            session(['selected_parent_student_id' => (string) $entities['active_child_id']]);
        }

        if (! empty($entities['active_class_id'])) {
            session(['chatbot_active_class_id' => (string) $entities['active_class_id']]);
        }
    }

    private function summarizeToolResult(array $toolResult): array
    {
        $data = is_array($toolResult['data'] ?? null) ? $toolResult['data'] : [];

        return array_filter([
            'success' => (bool) ($toolResult['success'] ?? false),
            'error_type' => $toolResult['error_type'] ?? null,
            'message' => isset($toolResult['message']) ? Str::limit((string) $toolResult['message'], 180, '') : null,
            'data_keys' => array_slice(array_keys($data), 0, 12),
            'record_counts' => $this->collectionCounts($data),
            'duration_ms' => $toolResult['duration_ms'] ?? null,
        ], fn ($value) => $value !== null && $value !== []);
    }

    private function collectionCounts(array $data): array
    {
        $counts = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $counts[$key] = count($value);
                continue;
            }

            if ($value instanceof Collection) {
                $counts[$key] = $value->count();
            }
        }

        return $counts;
    }

    private function duration(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function normalizeReply(string $reply): string
    {
        $reply = str_replace(["\u{FFFC}", "\xEF\xBF\xBC"], '', $reply);
        $reply = str_replace(['\\_', '`'], ['_', ''], $reply);
        $reply = (string) preg_replace('/\*\*(.*?)\*\*/us', '$1', $reply);
        $reply = str_replace('**', '', $reply);
        $reply = (string) preg_replace('/\bget_[a-z0-9_]+\b/i', '', $reply);
        $reply = (string) preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', '', $reply);
        $reply = collect(preg_split('/\R/u', $reply) ?: [])
            ->map(fn ($line) => trim((string) preg_replace('/[ \t]+/u', ' ', $line)))
            ->filter(fn ($line) => $line !== '')
            ->join("\n");

        if (preg_match('/^\s*[\{\[]/u', $reply)) {
            return 'Tôi đã truy vấn được dữ liệu, nhưng chưa thể diễn đạt đầy đủ lúc này.';
        }

        return trim($reply);
    }
}
