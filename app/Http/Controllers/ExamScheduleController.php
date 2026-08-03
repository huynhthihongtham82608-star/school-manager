<?php

namespace App\Http\Controllers;

use App\Models\ExamSchedule;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreColumn;
use App\Models\ScoreDetail;
use App\Models\ScoreHeader;
use App\Models\ScoreSetting;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExamScheduleController extends Controller
{
    private const SCORE_PATTERN = '/^(10(\.0)?|[0-9](\.[0-9])?)$/';

    public function index(Request $request)
    {
        $user = $request->user();
        $selectedYearId = $this->selectedSchoolYearId($request);
        $selectedSemesterId = $this->selectedSemesterId($request);
        $query = Schema::hasTable('exam_schedules')
            ? ExamSchedule::with(['classRoom.students', 'subject', 'semester.schoolYear'])
            : null;

        if ($query && ! ($user->isAdmin() || $user->isStaff())) {
            $query->where(function ($query) {
                $query->whereNull('note')
                    ->orWhere(function ($query) {
                        $query->where('note', 'not like', '%"status":"draft"%')
                            ->where('note', 'not like', '%"status":"canceled"%');
                    });
            });
        }

        if ($query && $user->isStudent() && $user->student) {
            $query->where('class_id', $user->student->class_id);
        }

        if ($query && $user->isParent() && $user->parentProfile) {
            $students = $user->parentProfile->students()->orderBy('student_code')->get(['students.id', 'students.class_id']);
            $selected = $students->firstWhere('id', session('selected_parent_student_id')) ?: $students->first();
            $classIds = collect([$selected?->class_id])->filter();
            $query->whereIn('class_id', $classIds);
        }

        if ($query && $user->isTeacher() && $user->teacher && ! ($user->isAdmin() || $user->isStaff())) {
            $teacher = $user->teacher;
            $assignedPairs = $teacher->assignments()
                ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->where('status', \App\Models\TeachingAssignment::STATUS_ACTIVE)
                ->get(['class_id', 'subject_id']);
            $homeroomClassIds = $teacher->homeroomClasses()
                ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->pluck('id');

            $query->where(function ($query) use ($assignedPairs, $homeroomClassIds) {
                $hasCondition = false;

                if ($homeroomClassIds->isNotEmpty()) {
                    $query->whereIn('class_id', $homeroomClassIds);
                    $hasCondition = true;
                }

                foreach ($assignedPairs as $pair) {
                    $method = $hasCondition ? 'orWhere' : 'where';
                    $query->{$method}(function ($query) use ($pair) {
                        $query->where('class_id', $pair->class_id)
                            ->where('subject_id', $pair->subject_id);
                    });
                    $hasCondition = true;
                }

                if (! $hasCondition) {
                    $query->whereRaw('1 = 0');
                }
            });
        }

        if ($query && $selectedYearId) {
            $semesterIds = Schema::hasTable('semesters')
                ? Semester::where('school_year_id', $selectedYearId)->pluck('id')
                : collect();

            $query->where(function ($yearQuery) use ($selectedYearId, $semesterIds) {
                $yearQuery->where('note', 'like', '%"school_year_id":"' . $selectedYearId . '"%');

                if ($semesterIds->isNotEmpty()) {
                    $yearQuery->orWhereIn('semester_id', $semesterIds);
                }
            });
        }

        if ($query && $selectedSemesterId) {
            $query->where('semester_id', $selectedSemesterId);
        }

        $schedules = $query ? $query->orderBy('exam_date')->orderBy('start_time')->paginate(12) : collect();
        $classes = Schema::hasTable('classes')
            ? SchoolClass::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->orderBy('name')->get()
            : collect();
        $subjects = Schema::hasTable('subjects')
            ? Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
                ->where('status', Subject::STATUS_ACTIVE)
                ->withEvaluatedAssessment()
                ->orderBy('name')
                ->get()
            : collect();
        $semesters = Schema::hasTable('semesters')
            ? Semester::with('schoolYear')->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->orderByDesc('created_at')->get()
            : collect();
        $years = Schema::hasTable('school_years') ? SchoolYear::orderByDesc('start_date')->get() : collect();
        $examTypes = ExamSchedule::EXAM_TYPES;

        return view('exam_schedules.index', compact('schedules', 'classes', 'subjects', 'semesters', 'years', 'examTypes', 'selectedYearId', 'selectedSemesterId'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isStaff(), 403);

        if (! Schema::hasTable('exam_schedules')) {
            return back()->with('error', 'Chưa có bảng exam_schedules. Vui lòng chạy migration trước.');
        }

        $data = $request->validate($this->rules());
        $this->ensureSemesterWritable($data['semester_id']);
        $this->ensureValidScheduleWindow($data);
        $this->ensureNoConflicts($data);
        $examTypeData = $this->resolveExamTypeData($data);

        $meta = [
            'school_year_id' => $data['school_year_id'],
            'status' => $data['status'] ?? 'draft',
        ];
        unset($data['school_year_id'], $data['status'], $data['custom_display_name']);

        $schedule = ExamSchedule::create([
            ...$data,
            ...$examTypeData,
            'note' => ExamSchedule::withMeta($data['note'] ?? null, $meta),
        ]);

        AuditLogger::log('exam_schedule_created', ExamSchedule::class, $schedule->id, 'Tạo lịch kiểm tra');

        return back()->with('success', 'Đã thêm lịch kiểm tra.');
    }

    public function update(Request $request, ExamSchedule $examSchedule)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isStaff(), 403);

        if (! Schema::hasTable('exam_schedules')) {
            return back()->with('error', 'Chưa có bảng exam_schedules. Vui lòng chạy migration trước.');
        }

        $data = $request->validate($this->rules());
        $this->ensureSemesterWritable($data['semester_id']);
        $this->ensureValidScheduleWindow($data);
        $this->ensureNoConflicts($data, $examSchedule);
        $examTypeData = $this->resolveExamTypeData($data);

        $meta = [
            'school_year_id' => $data['school_year_id'],
            'status' => $data['status'] ?? 'draft',
        ];
        unset($data['school_year_id'], $data['status'], $data['custom_display_name']);

        $examSchedule->update([
            ...$data,
            ...$examTypeData,
            'note' => ExamSchedule::withMeta($data['note'] ?? null, $meta),
        ]);

        AuditLogger::log('exam_schedule_updated', ExamSchedule::class, $examSchedule->id, 'Cập nhật lịch kiểm tra');

        return back()->with('success', 'Đã cập nhật lịch kiểm tra.');
    }

    public function destroy(Request $request, ExamSchedule $examSchedule)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isStaff(), 403);

        if (! Schema::hasTable('exam_schedules')) {
            return back()->with('error', 'Chưa có bảng exam_schedules. Vui lòng chạy migration trước.');
        }

        if ($examSchedule->semester?->isArchived()) {
            abort(403, 'Học kỳ đã lưu trữ chỉ được xem, không thể xóa lịch kiểm tra.');
        }

        $scheduleId = $examSchedule->id;
        $examSchedule->delete();

        AuditLogger::log('exam_schedule_deleted', ExamSchedule::class, $scheduleId, 'Xóa lịch kiểm tra');

        return back()->with('success', 'Đã xóa lịch kiểm tra.');
    }

    public function storeScores(Request $request, ExamSchedule $examSchedule)
    {
        $this->authorizeExamScoreEntry($request, $examSchedule);

        if (! $examSchedule->isPublished()) {
            abort(403, 'Chỉ nhập điểm cho lịch kiểm tra đã công bố.');
        }

        if (! ($request->user()->isAdmin() || $request->user()->isStaff()) && ! $examSchedule->isScoreInputOpen()) {
            abort(403, 'Lịch kiểm tra chưa mở hoặc đã khóa nhập điểm.');
        }

        $scoreColumnType = $this->scoreColumnTypeForExam($examSchedule);
        if (! $scoreColumnType) {
            abort(422, 'Chỉ tự động đồng bộ điểm cho bài kiểm tra Giữa kỳ hoặc Cuối kỳ.');
        }

        $data = $request->validate([
            'scores' => ['array'],
            'scores.*' => ['nullable', 'regex:' . self::SCORE_PATTERN],
            'is_retest' => ['nullable', 'boolean'],
        ], [], [
            'scores.*' => 'điểm bài kiểm tra',
        ]);

        $students = Student::where('class_id', $examSchedule->class_id)
            ->where('status', Student::STATUS_STUDYING)
            ->orderBy('student_code')
            ->get()
            ->keyBy('id');
        $scoreSetting = ScoreSetting::current();
        $scoreColumn = $this->scoreColumnForExam($examSchedule, $scoreColumnType, $scoreSetting);
        $markAsRetest = (bool) ($data['is_retest'] ?? false) || $this->looksLikeRetestSchedule($examSchedule);
        $updatedCount = 0;

        DB::transaction(function () use ($students, $examSchedule, $scoreColumn, $scoreColumnType, $scoreSetting, $request, $markAsRetest, &$updatedCount) {
            foreach ($students as $student) {
                $rawValue = trim((string) $request->input("scores.{$student->id}", ''));

                $header = ScoreHeader::firstOrCreate([
                    'student_id' => $student->id,
                    'subject_id' => $examSchedule->subject_id,
                    'semester_id' => $examSchedule->semester_id,
                    'school_year_id' => $examSchedule->semester?->school_year_id ?? $examSchedule->schoolYearId(),
                ]);

                $detail = $header->details()
                    ->where('score_column_id', $scoreColumn->id)
                    ->first();

                if ($rawValue === '') {
                    if ($detail && (string) $detail->exam_schedule_id === (string) $examSchedule->id) {
                        $detail->delete();
                        $updatedCount++;
                    }

                    $this->recalculateAverage($header);
                    continue;
                }

                $value = round((float) $rawValue, 1);
                $payload = [
                    'exam_schedule_id' => $examSchedule->id,
                    'score_column_id' => $scoreColumn->id,
                    'type' => $scoreColumnType,
                    'name' => $scoreColumn->name,
                    'value' => $value,
                    'weight_group' => $scoreSetting->weightForScoreType($scoreColumnType),
                ];

                if ($detail) {
                    $oldValue = $detail->value !== null ? round((float) $detail->value, 1) : null;
                    $isChangedRetest = $markAsRetest && $oldValue !== null && abs($oldValue - $value) > 0.0001;

                    $detail->update([
                        ...$payload,
                        'is_retest' => $isChangedRetest || (bool) $detail->is_retest,
                        'original_value' => $isChangedRetest ? $oldValue : $detail->original_value,
                        'retest_updated_at' => $isChangedRetest ? now() : $detail->retest_updated_at,
                    ]);
                } else {
                    ScoreDetail::create([
                        'score_header_id' => $header->id,
                        ...$payload,
                        'is_retest' => false,
                        'original_value' => null,
                        'retest_updated_at' => null,
                    ]);
                }

                $updatedCount++;
                $this->recalculateAverage($header);
            }
        });

        AuditLogger::log('exam_scores_synced', ExamSchedule::class, $examSchedule->id, 'Đồng bộ điểm bài kiểm tra vào sổ điểm');

        return back()->with('success', "Đã đồng bộ {$updatedCount} điểm bài kiểm tra vào sổ điểm học kỳ.");
    }

    private function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(ExamSchedule::EXAM_TYPES))],
            'custom_display_name' => ['nullable', 'string', 'max:255', Rule::requiredIf(request('type') === ExamSchedule::TYPE_CUSTOM)],
            'school_year_id' => ['required', 'string', 'max:50', 'exists:school_years,id'],
            'class_id' => ['required', 'string', 'max:50', 'exists:classes,id'],
            'subject_id' => ['required', 'string', 'max:50', 'exists:subjects,id'],
            'semester_id' => ['required', 'string', 'max:50', 'exists:semesters,id'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'room' => ['required', 'string', 'max:100'],
            'score_input_opens_at' => ['required', 'date'],
            'score_input_closes_at' => ['required', 'date', 'after_or_equal:score_input_opens_at'],
            'note' => ['nullable', 'string'],
            'status' => ['required', Rule::in(ExamSchedule::MANAGEMENT_STATUSES)],
        ];
    }

    private function resolveExamTypeData(array $data): array
    {
        $type = $data['type'];
        $displayName = $type === ExamSchedule::TYPE_CUSTOM
            ? trim((string) ($data['custom_display_name'] ?? ''))
            : ExamSchedule::EXAM_TYPES[$type];

        if ($type === ExamSchedule::TYPE_CUSTOM && $displayName === '') {
            throw ValidationException::withMessages([
                'custom_display_name' => 'Vui lòng nhập tên loại kiểm tra khi chọn Khác.',
            ]);
        }

        return [
            'type' => $type,
            'display_name' => $displayName,
            'title' => $displayName,
        ];
    }

    private function ensureValidScheduleWindow(array $data): void
    {
        $subject = Subject::findOrFail($data['subject_id']);
        if (! $subject->isEvaluated()) {
            throw ValidationException::withMessages([
                'subject_id' => 'Môn học này chỉ dùng trong thời khóa biểu, không tạo lịch kiểm tra.',
            ]);
        }

        if ($this->minutes($data['end_time']) <= $this->minutes($data['start_time'])) {
            throw ValidationException::withMessages([
                'end_time' => 'Giờ kết thúc phải lớn hơn giờ bắt đầu.',
            ]);
        }
    }

    private function ensureNoConflicts(array $data, ?ExamSchedule $ignore = null): void
    {
        if (($data['status'] ?? null) === 'canceled') {
            return;
        }

        $query = ExamSchedule::query()
            ->where('exam_date', $data['exam_date'])
            ->where(function ($query) use ($data) {
                $query->where('class_id', $data['class_id'])
                    ->orWhere('room', $data['room']);
            })
            ->get();

        $start = $this->minutes($data['start_time']);
        $end = $this->minutes($data['end_time']);

        foreach ($query as $schedule) {
            if ($ignore && $schedule->id === $ignore->id) {
                continue;
            }

            if ($schedule->isCanceled()) {
                continue;
            }

            if (! $schedule->start_time || ! $schedule->end_time) {
                continue;
            }

            $overlaps = $start < $this->minutes($schedule->end_time)
                && $end > $this->minutes($schedule->start_time);

            if (! $overlaps) {
                continue;
            }

            if ($schedule->class_id === $data['class_id']) {
                throw ValidationException::withMessages([
                    'class_id' => 'Lớp này đã có lịch kiểm tra trùng thời gian.',
                ]);
            }

            if ($schedule->room === $data['room']) {
                throw ValidationException::withMessages([
                    'room' => 'Phòng này đã có lịch kiểm tra trùng thời gian.',
                ]);
            }
        }
    }

    private function ensureSemesterWritable(string $semesterId): void
    {
        $semester = Semester::findOrFail($semesterId);

        if ($semester->isArchived()) {
            abort(403, 'Học kỳ đã lưu trữ chỉ được xem, không thể chỉnh sửa lịch kiểm tra.');
        }
    }

    private function authorizeExamScoreEntry(Request $request, ExamSchedule $examSchedule): void
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isStaff()) {
            return;
        }

        $teacherId = $user->teacher?->id;
        if (! $teacherId) {
            abort(403);
        }

        $isAssigned = TeachingAssignment::where('teacher_id', $teacherId)
            ->where('class_id', $examSchedule->class_id)
            ->where('subject_id', $examSchedule->subject_id)
            ->where('semester_id', $examSchedule->semester_id)
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->exists();

        if (! $isAssigned) {
            abort(403, 'Chỉ giáo viên bộ môn được phân công mới được nhập điểm bài kiểm tra.');
        }
    }

    private function scoreColumnTypeForExam(ExamSchedule $examSchedule): ?string
    {
        return match ($examSchedule->type) {
            ExamSchedule::TYPE_MIDTERM => ScoreColumn::TYPE_MIDTERM,
            ExamSchedule::TYPE_FINAL_TEST => ScoreColumn::TYPE_FINAL,
            default => null,
        };
    }

    private function scoreColumnForExam(ExamSchedule $examSchedule, string $type, ScoreSetting $setting): ScoreColumn
    {
        $gradeLevel = (int) ($examSchedule->classRoom?->grade_level ?? SchoolClass::find($examSchedule->class_id)?->grade_level ?? 0);
        $schoolYearId = $examSchedule->semester?->school_year_id ?? $examSchedule->schoolYearId();
        $name = $type === ScoreColumn::TYPE_MIDTERM ? 'Kiểm tra Giữa kỳ' : 'Kiểm tra Cuối kỳ';
        $sortOrder = $type === ScoreColumn::TYPE_MIDTERM ? 40 : 50;

        return ScoreColumn::firstOrCreate([
            'school_year_id' => $schoolYearId,
            'subject_id' => $examSchedule->subject_id,
            'grade_level' => $gradeLevel,
            'type' => $type,
        ], [
            'name' => $name,
            'weight_group' => $setting->weightForScoreType($type),
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    private function looksLikeRetestSchedule(ExamSchedule $examSchedule): bool
    {
        $text = Str::lower(Str::ascii($examSchedule->displayName() . ' ' . $examSchedule->note));

        return str_contains($text, 'bu')
            || str_contains($text, 'thi lai')
            || str_contains($text, 'kiem tra lai');
    }

    private function recalculateAverage(ScoreHeader $header): void
    {
        $header->loadMissing('subject');

        if ($header->subject?->usesPassFailAssessment() || $header->subject?->isNotEvaluated()) {
            $header->forceFill(['average' => null])->save();
            return;
        }

        $scoreSetting = ScoreSetting::current();
        $details = $header->details()
            ->with('scoreColumn')
            ->get()
            ->reject(fn (ScoreDetail $detail) => $detail->scoreColumn && $this->scoreColumnReportFamily($detail->scoreColumn) === 'one_period');
        $weightedSum = $details->sum(fn (ScoreDetail $detail) => (float) $detail->value * $scoreSetting->weightForScoreType($detail->type));
        $totalWeight = $details->sum(fn (ScoreDetail $detail) => $scoreSetting->weightForScoreType($detail->type));

        $header->forceFill([
            'average' => $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : null,
        ])->save();
    }

    private function scoreColumnReportFamily(ScoreColumn $column): string
    {
        if ($column->type === ScoreColumn::TYPE_MIDTERM) {
            return 'midterm';
        }

        if ($column->type === ScoreColumn::TYPE_FINAL) {
            return 'final';
        }

        $name = Str::lower(Str::ascii((string) $column->name));

        if (str_contains($name, 'mieng') || str_contains($name, 'oral')) {
            return 'oral';
        }

        if (str_contains($name, '15')) {
            return 'fifteen';
        }

        return 'one_period';
    }

    private function minutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $hour * 60 + $minute;
    }
}
