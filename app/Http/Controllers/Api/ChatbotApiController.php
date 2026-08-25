<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Models\TuitionFee;
use App\Models\User;
use App\Services\AcademicEvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ChatbotApiController extends Controller
{
    public function handleQuery(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1200'],
            'user_id' => ['required', 'string', 'max:100'],
        ]);

        $user = $this->resolveUser((string) $data['user_id']);

        if (! $user) {
            return $this->errorReply('Dạ, hệ thống chưa tìm thấy tài khoản đang trò chuyện. Vui lòng đăng nhập lại rồi thử tiếp.');
        }

        $apiKey = (string) config('services.gemini.key');

        if ($apiKey === '') {
            return $this->errorReply('Dạ, hệ thống chưa cấu hình GEMINI_API_KEY nên Trợ lý Học vụ AI chưa thể phản hồi lúc này.');
        }

        $message = trim((string) $data['message']);
        $context = $this->buildUserContext($user);
        $systemPromptWithData = $this->buildPrompt($user, $message, $context);

        try {
            if (! function_exists('curl_init')) {
                Log::error('Lỗi kết nối cURL Gemini: PHP curl extension chưa được bật.');

                return $this->errorReply('Dạ, hệ thống chưa bật cURL nên chưa kết nối được Gemini AI.');
            }

            $url = $this->geminiGenerateContentUrl() . '?key=' . rawurlencode($apiKey);
            $payload = json_encode([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPromptWithData . "\n\nNgười dùng hỏi: " . $message],
                        ],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $ch = curl_init($url);

            if ($ch === false) {
                Log::error('Lỗi kết nối cURL Gemini: không khởi tạo được curl handle.', [
                    'url' => $this->redactedGeminiUrl(),
                ]);

                return $this->errorReply('Dạ, hệ thống chưa kết nối được Gemini AI. Vui lòng kiểm tra cấu hình cURL.');
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
            curl_setopt($ch, CURLOPT_TIMEOUT, 35);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $output = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($output === false || $curlError !== '') {
                Log::error('Lỗi kết nối cURL Gemini: ' . $curlError, [
                    'http_code' => $httpCode,
                    'url' => $this->redactedGeminiUrl(),
                ]);

                return $this->errorReply('Dạ, hệ thống chưa kết nối được Gemini AI. Vui lòng kiểm tra mạng hoặc API key.');
            }

            if ($httpCode === 200) {
                $result = json_decode((string) $output, true);

                if (! is_array($result)) {
                    Log::error('Lỗi giải mã JSON Gemini: ' . json_last_error_msg(), [
                        'raw_body' => $output,
                    ]);

                    return $this->errorReply('Dạ, Trợ lý AI đang gặp chút gián đoạn khi phân tích câu trả lời.');
                }

                $reply = $result['candidates'][0]['content']['parts'][0]['text']
                    ?? $this->extractGeminiText($result)
                    ?: 'Dạ, Trợ lý AI đang gặp chút gián đoạn khi phân tích câu trả lời.';

                return $this->successReply(trim((string) $reply));
            }

            Log::error('Lỗi kết nối cURL Gemini: ' . (string) $output, [
                'http_code' => $httpCode,
                'url' => $this->redactedGeminiUrl(),
            ]);

            return $this->errorReply('Dạ, hệ thống chưa kết nối được Gemini AI. Vui lòng kiểm tra mạng hoặc API key.');
        } catch (\Throwable $exception) {
            Log::error('Gemini chatbot exception.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->errorReply('Dạ, hệ thống chưa kết nối được Gemini AI. Vui lòng kiểm tra mạng hoặc API key.');
        }
    }

    private function resolveUser(string $identifier): ?User
    {
        return User::with([
            'student.classRoom',
            'parentProfile.students.classRoom',
            'teacher.primarySubject',
            'teacher.homeroomClasses',
        ])
            ->where('id', $identifier)
            ->orWhere('username', $identifier)
            ->first();
    }

    private function buildUserContext(User $user): array
    {
        if ($user->isStudent() && $user->student) {
            return [
                'students' => [$this->studentSnapshot($user->student)],
            ];
        }

        if ($user->isParent() && $user->parentProfile) {
            return [
                'students' => $user->parentProfile->students
                    ->sortBy('student_code')
                    ->take(4)
                    ->map(fn (Student $student) => $this->studentSnapshot($student))
                    ->values()
                    ->all(),
            ];
        }

        if ($user->isTeacher() && $user->teacher) {
            return $this->teacherSnapshot($user);
        }

        return [
            'summary' => 'Tài khoản quản trị hoặc nhân sự hệ thống. Chỉ trả lời theo phạm vi dữ liệu được cung cấp.',
        ];
    }

    private function studentSnapshot(Student $student): array
    {
        $student->loadMissing('classRoom');

        return [
            'ma_hoc_sinh' => $student->student_code,
            'ho_ten' => $student->name,
            'lop' => $student->classRoom?->name,
            'diem_so' => $this->gradeSnapshot($student),
            'hoc_phi' => $this->tuitionSnapshot($student),
            'chuyen_can' => $this->attendanceSnapshot($student),
        ];
    }

    private function teacherSnapshot(User $user): array
    {
        $teacher = $user->teacher;
        $assignments = collect();

        if ($teacher && Schema::hasTable('teaching_assignments')) {
            $query = TeachingAssignment::with(['classRoom', 'subject'])
                ->where('teacher_id', $teacher->id);

            if (Schema::hasColumn('teaching_assignments', 'status')) {
                $query->where('status', TeachingAssignment::STATUS_ACTIVE);
            }

            $assignments = $query
                ->orderBy('class_id')
                ->get()
                ->map(fn (TeachingAssignment $assignment) => [
                    'lop' => $assignment->classRoom?->name,
                    'mon' => $assignment->subject?->name,
                    'so_tiet_tuan' => $assignment->weekly_periods,
                ])
                ->filter(fn (array $row) => $row['lop'] || $row['mon'])
                ->unique(fn (array $row) => ($row['lop'] ?? '') . '|' . ($row['mon'] ?? ''))
                ->values();
        }

        return [
            'giao_vien' => [
                'ma_giao_vien' => $teacher?->teacher_code,
                'ho_ten' => $teacher?->name ?: $user->display_name,
                'mon_chinh' => $teacher?->primarySubjectName(),
            ],
            'lop_dang_day' => $assignments->all(),
            'lop_chu_nhiem' => $teacher?->homeroomClasses
                ? $teacher->homeroomClasses->map(fn ($class) => [
                    'lop' => $class->name,
                    'si_so' => $class->students()->count(),
                ])->values()->all()
                : [],
        ];
    }

    private function gradeSnapshot(Student $student): array
    {
        $headers = $this->scoreHeadersForStudent($student);

        if ($headers->isEmpty()) {
            return [
                'ghi_chu' => 'Chưa có dữ liệu điểm số.',
                'mon_hoc' => [],
                'gpa' => null,
                'xep_loai' => null,
            ];
        }

        $subjects = $headers
            ->filter(fn ($header) => $header->average !== null && $header->subject)
            ->map(fn ($header) => [
                'mon' => $header->subject->name,
                'diem_trung_binh' => round((float) $header->average, 1),
            ])
            ->values();

        $numericHeaders = $headers
            ->filter(fn ($header) => $header->average !== null && $header->subject?->usesNumericAssessment());

        $gpa = $numericHeaders->isNotEmpty()
            ? round((float) $numericHeaders->avg('average'), 2)
            : null;

        $classification = app(AcademicEvaluationService::class)
            ->classifyFromScoreHeaders($gpa, $headers);

        return [
            'mon_hoc' => $subjects->all(),
            'gpa' => $gpa,
            'xep_loai' => $classification['label'] ?? null,
        ];
    }

    private function scoreHeadersForStudent(Student $student): Collection
    {
        if (Schema::hasTable('score_headers')) {
            $query = ScoreHeader::with(['subject', 'semester'])
                ->where('student_id', $student->id)
                ->whereNotNull('average');

            $semesterId = $this->currentSemesterId();
            $currentCount = $semesterId
                ? (clone $query)->where('semester_id', $semesterId)->count()
                : 0;

            if ($semesterId && $currentCount > 0) {
                $query->where('semester_id', $semesterId);
            } else {
                $latestSemesterId = (clone $query)
                    ->orderByDesc('updated_at')
                    ->value('semester_id');

                if ($latestSemesterId) {
                    $query->where('semester_id', $latestSemesterId);
                }
            }

            return $query->orderBy('subject_id')->get();
        }

        if (Schema::hasTable('student_grades')) {
            return DB::table('student_grades')
                ->where('student_id', $student->id)
                ->get()
                ->map(function ($grade) {
                    $header = new ScoreHeader();
                    $header->average = $grade->average ?? $grade->gpa ?? null;
                    $header->setRelation('subject', new class((string) ($grade->subject_name ?? $grade->subject ?? 'Môn học')) {
                        public function __construct(public string $name)
                        {
                        }

                        public function usesNumericAssessment(): bool
                        {
                            return true;
                        }

                        public function usesPassFailAssessment(): bool
                        {
                            return false;
                        }
                    });

                    return $header;
                });
        }

        return collect();
    }

    private function tuitionSnapshot(Student $student): array
    {
        if (! Schema::hasTable('tuition_fees')) {
            return [
                'ghi_chu' => 'Chưa có bảng học phí.',
                'tong_tien' => 0,
                'con_no' => 0,
                'trang_thai' => 'Chưa có dữ liệu',
                'ma_qr' => $this->tuitionQrImageUrl(),
            ];
        }

        $query = TuitionFee::query()
            ->where('student_id', $student->id)
            ->orderByDesc('updated_at');

        if ($semesterId = $this->currentSemesterId()) {
            $current = (clone $query)->where('semester_id', $semesterId)->first();
            $fee = $current ?: $query->first();
        } else {
            $fee = $query->first();
        }

        $items = $fee ? $fee->normalizedFeeItems() : TuitionFee::configuredFeeItems();
        $total = collect($items)->sum(fn (array $item) => (float) ($item['amount'] ?? 0));
        $remaining = collect($items)
            ->filter(fn (array $item) => ($item['status'] ?? TuitionFee::STATUS_UNPAID) !== TuitionFee::STATUS_PAID)
            ->sum(fn (array $item) => (float) ($item['amount'] ?? 0));

        return [
            'khoan_thu' => collect($items)->map(fn (array $item) => [
                'ten' => $item['label'] ?? 'Khoản thu',
                'so_tien' => $this->formatMoney((float) ($item['amount'] ?? 0)),
                'trang_thai' => ($item['status'] ?? TuitionFee::STATUS_UNPAID) === TuitionFee::STATUS_PAID ? 'Đã đóng' : 'Chưa đóng',
                'mien_giam' => $item['exemption_label'] ?? '',
            ])->values()->all(),
            'tong_tien' => $this->formatMoney($total),
            'con_no' => $this->formatMoney($remaining),
            'trang_thai' => $remaining <= 0 ? 'Đã đóng' : 'Chưa đóng',
            'ma_qr' => $this->tuitionQrImageUrl(),
        ];
    }

    private function attendanceSnapshot(Student $student): array
    {
        if (! Schema::hasTable('attendance_records')) {
            return [
                'ghi_chu' => 'Chưa có bảng điểm danh.',
                'di_muon' => 0,
                'vang_khong_phep' => 0,
                'vang_co_phep' => 0,
            ];
        }

        $query = AttendanceRecord::query()->where('student_id', $student->id);

        if ($semesterId = $this->currentSemesterId()) {
            $hasCurrentSemesterRecords = (clone $query)
                ->where('semester_id', $semesterId)
                ->exists();

            if ($hasCurrentSemesterRecords) {
                $query->where('semester_id', $semesterId);
            }
        }

        return [
            'di_muon' => (clone $query)->where('status', AttendanceRecord::STATUS_LATE)->count(),
            'vang_khong_phep' => (clone $query)
                ->whereIn('status', [AttendanceRecord::STATUS_ABSENT, AttendanceRecord::STATUS_UNEXCUSED_ABSENT])
                ->count(),
            'vang_co_phep' => (clone $query)
                ->whereIn('status', [AttendanceRecord::STATUS_EXCUSED, AttendanceRecord::STATUS_PERMITTED_ABSENT])
                ->count(),
            'vang_tiet_bo_mon' => (clone $query)
                ->where('session_type', AttendanceRecord::SESSION_PERIOD)
                ->whereIn('status', [AttendanceRecord::STATUS_ABSENT, AttendanceRecord::STATUS_UNEXCUSED_ABSENT])
                ->count(),
        ];
    }

    private function currentSemesterId(): ?string
    {
        if (! Schema::hasTable('semesters')) {
            return null;
        }

        return Semester::query()
            ->where('status', Semester::STATUS_ACTIVE)
            ->value('id')
            ?: Semester::query()->orderByDesc('updated_at')->value('id');
    }

    private function tuitionQrImageUrl(): string
    {
        if (! Schema::hasTable('settings')) {
            return '';
        }

        $path = (string) Setting::valueOf('tuition_qr_image', '');

        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return url(Storage::url($path));
    }

    private function buildPrompt(User $user, string $message, array $context): string
    {
        $role = match ($user->role) {
            'student' => 'Học sinh',
            'parent' => 'Phụ huynh',
            'teacher' => 'Giáo viên',
            'admin' => 'Quản trị viên',
            default => 'Nhân sự nhà trường',
        };

        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return <<<PROMPT
Bạn là Trợ lý Học vụ GenAI thông minh của trường THPT.
Người đang trò chuyện có vai trò là {$role} và có dữ liệu hệ thống thật sau:
{$contextJson}

Câu hỏi của người dùng: "{$message}"

Yêu cầu trả lời:
- Kết hợp câu hỏi với dữ liệu thật bên trên để tư vấn sư phạm linh hoạt, thông minh.
- Nếu người dùng tra cứu điểm, học lực, học phí hoặc chuyên cần, bắt buộc dùng đúng con số trong dữ liệu đã cấp, tuyệt đối không tự bịa số liệu.
- Nếu dữ liệu chưa có, nói rõ là hệ thống chưa có dữ liệu cho mục đó.
- Phản hồi bằng tiếng Việt, ngắn gọn dưới 4 dòng, chữ rõ nghĩa, không dùng Markdown bảng.
PROMPT;
    }

    private function geminiGenerateContentUrl(): string
    {
        $endpoint = rtrim((string) config('services.gemini.endpoint'), '/');
        $model = trim((string) config('services.gemini.model'), '/');

        return "{$endpoint}/models/{$model}:generateContent";
    }

    private function redactedGeminiUrl(): string
    {
        return $this->geminiGenerateContentUrl() . '?key=[redacted]';
    }

    private function extractGeminiText(array $payload): string
    {
        $parts = data_get($payload, 'candidates.0.content.parts', []);

        if (! is_array($parts)) {
            return '';
        }

        return collect($parts)
            ->pluck('text')
            ->filter(fn ($text) => is_string($text) && trim($text) !== '')
            ->map(fn (string $text) => trim($text))
            ->join("\n");
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . 'đ';
    }

    private function successReply(string $message): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'reply' => $message,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function errorReply(string $message): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'reply' => $message,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
