<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\SchoolClass;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Conduct;
use App\Services\AcademicEvaluationService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KhenThuongController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAccess();

        $selectedYearId = $this->selectedSchoolYearId($request);
        $selectedSemesterId = $request->query('semester_id') ?: $this->selectedSemesterId($request);
        $selectedClassId = $request->query('class_id', 'all');
        $selectedType = $request->query('reward_type', 'all');

        $classes = SchoolClass::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when($this->isHomeroomOnly(), function ($query) use ($user) {
                $query->where('homeroom_teacher_id', $user->teacher_id);
            })
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        if ($this->isHomeroomOnly()) {
            $selectedClassId = $classes->first()?->id;
        }

        $semesters = Semester::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        if (! $selectedSemesterId && $semesters->isNotEmpty()) {
            $selectedSemesterId = optional($semesters->first(fn ($semester) => $semester->isActive()))->id
                ?? $semesters->first()->id;
        }

        $allowedClassIds = $classes->pluck('id')->map(fn ($id) => (string) $id)->values();
        $students = Student::with('classRoom')
            ->whereIn('class_id', $allowedClassIds)
            ->when($selectedClassId && $selectedClassId !== 'all', fn ($query) => $query->where('class_id', $selectedClassId))
            ->orderBy('student_code')
            ->get();

        $rewards = Reward::with(['student', 'classRoom', 'semester'])
            ->whereIn('class_id', $allowedClassIds)
            ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
            ->when($selectedClassId && $selectedClassId !== 'all', fn ($query) => $query->where('class_id', $selectedClassId))
            ->when($selectedType && $selectedType !== 'all', fn ($query) => $query->where('reward_type', $selectedType))
            ->latest()
            ->get();

        return view('rewards.index', [
            'rewards' => $rewards,
            'classes' => $classes,
            'students' => $students,
            'semesters' => $semesters,
            'rewardTypes' => Reward::typeLabels(),
            'selectedSemesterId' => $selectedSemesterId,
            'selectedClassId' => $selectedClassId ?: 'all',
            'selectedType' => $selectedType,
            'isHomeroomOnly' => $this->isHomeroomOnly(),
            'readOnly' => $this->isHistoricalReadOnly(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        $this->denyHistoricalWrite();

        $data = $this->validatedData($request);
        $student = Student::with('classRoom')->findOrFail($data['student_id']);
        $this->authorizeStudent($student);

        $reward = Reward::create($data + [
            'class_id' => $student->class_id,
            'school_year_id' => $student->school_year_id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::log('reward_created', Reward::class, (string) $reward->getKey(), 'Tạo quyết định khen thưởng cho ' . $student->name);

        return back()->with('success', 'Đã lưu quyết định khen thưởng.');
    }

    public function update(Request $request, Reward $reward)
    {
        $this->authorizeAccess();
        $this->denyHistoricalWrite();
        $this->authorizeReward($reward);

        $data = $this->validatedData($request);
        $student = Student::with('classRoom')->findOrFail($data['student_id']);
        $this->authorizeStudent($student);

        $reward->update($data + [
            'class_id' => $student->class_id,
            'school_year_id' => $student->school_year_id,
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::log('reward_updated', Reward::class, (string) $reward->getKey(), 'Cập nhật quyết định khen thưởng cho ' . $student->name);

        return back()->with('success', 'Đã cập nhật quyết định khen thưởng.');
    }

    public function destroy(Reward $reward)
    {
        $this->authorizeAccess();
        $this->denyHistoricalWrite();
        $this->authorizeReward($reward);

        $rewardId = (string) $reward->getKey();
        $studentName = $reward->student?->name ?: 'học sinh';
        $reward->delete();

        AuditLogger::log('reward_deleted', Reward::class, $rewardId, 'Xóa quyết định khen thưởng của ' . $studentName);

        return back()->with('success', 'Đã xóa quyết định khen thưởng.');
    }

    public function scan(Request $request, AcademicEvaluationService $evaluationService)
    {
        $this->authorizeAccess();
        $this->denyHistoricalWrite();

        $data = $request->validate([
            'semester_id' => ['required', 'string', 'exists:semesters,id'],
            'class_id' => ['nullable', 'string'],
        ]);

        $user = Auth::user();
        $semester = Semester::findOrFail($data['semester_id']);
        $requestedClassId = $data['class_id'] ?? 'all';
        $allowedClasses = SchoolClass::query()
            ->when($this->isHomeroomOnly(), fn ($query) => $query->where('homeroom_teacher_id', $user->teacher_id))
            ->where('school_year_id', $semester->school_year_id)
            ->when($requestedClassId && $requestedClassId !== 'all', fn ($query) => $query->whereKey($requestedClassId))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        if ($allowedClasses->isEmpty()) {
            return response()->json([
                'message' => 'Không có lớp học phù hợp để quét danh hiệu.',
                'created' => 0,
                'updated' => 0,
                'total' => 0,
            ], 422);
        }

        $students = Student::with('classRoom')
            ->whereIn('class_id', $allowedClasses->pluck('id'))
            ->where('status', Student::STATUS_STUDYING)
            ->orderBy('student_code')
            ->get();

        $scoreHeaders = ScoreHeader::with('subject')
            ->where('semester_id', $semester->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');

        $conductRecords = Conduct::where('semester_id', $semester->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $topAcademicKey = array_key_first($evaluationService->levels());
        $created = 0;
        $updated = 0;
        $qualified = 0;

        foreach ($students as $student) {
            $headers = collect($scoreHeaders->get($student->id, []));
            $numericScores = $headers
                ->filter(fn (ScoreHeader $header) => $header->subject?->usesNumericAssessment() && $header->average !== null)
                ->pluck('average')
                ->map(fn ($average) => (float) $average)
                ->values();

            if ($numericScores->isEmpty()) {
                continue;
            }

            $gpa = round((float) $numericScores->avg(), 1);
            $academicRank = $evaluationService->classifyFromScoreHeaders($gpa, $headers);
            $conduct = $conductRecords->get($student->id);

            if (($academicRank['key'] ?? null) !== $topAcademicKey || $conduct?->conduct_level !== Conduct::LEVEL_GOOD) {
                continue;
            }

            $excellentSubjectCount = $numericScores->filter(fn (float $average) => $average >= 9.0)->count();
            $rewardType = $excellentSubjectCount >= 6 ? Reward::TYPE_OUTSTANDING : Reward::TYPE_GOOD;
            $reward = Reward::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'semester_id' => $semester->id,
                ],
                [
                    'class_id' => $student->class_id,
                    'school_year_id' => $semester->school_year_id,
                    'reward_type' => $rewardType,
                    'detail' => $this->automaticRewardDetail($rewardType, $gpa, $excellentSubjectCount),
                    'decision_number' => null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]
            );

            $reward->wasRecentlyCreated ? $created++ : $updated++;
            $qualified++;
        }

        AuditLogger::log('rewards_auto_scanned', Reward::class, null, 'Tự động quét danh hiệu học kỳ ' . $semester->normalizedName());

        return response()->json([
            'message' => "Đã quét {$students->count()} học sinh, ghi nhận {$qualified} danh hiệu.",
            'created' => $created,
            'updated' => $updated,
            'total' => $qualified,
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'student_id' => ['required', 'string', 'exists:students,id'],
            'semester_id' => ['required', 'string', 'exists:semesters,id'],
            'reward_type' => ['required', 'string', Rule::in(array_keys(Reward::typeLabels()))],
            'detail' => ['nullable', 'string', 'max:2000'],
            'decision_number' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function authorizeAccess(): void
    {
        $user = Auth::user();

        if ($user?->isAdmin() || $user?->isStaff() || $user?->isHomeroom()) {
            return;
        }

        abort(403, 'Chỉ Admin hoặc giáo viên chủ nhiệm được quản lý khen thưởng.');
    }

    private function authorizeStudent(Student $student): void
    {
        if (! $this->isHomeroomOnly()) {
            return;
        }

        if ((string) $student->classRoom?->homeroom_teacher_id === (string) Auth::user()->teacher_id) {
            return;
        }

        abort(403, 'Giáo viên chủ nhiệm chỉ được quản lý khen thưởng của lớp mình.');
    }

    private function authorizeReward(Reward $reward): void
    {
        if (! $this->isHomeroomOnly()) {
            return;
        }

        if ((string) $reward->classRoom?->homeroom_teacher_id === (string) Auth::user()->teacher_id) {
            return;
        }

        abort(403, 'Giáo viên chủ nhiệm chỉ được chỉnh sửa khen thưởng của lớp mình.');
    }

    private function isHomeroomOnly(): bool
    {
        $user = Auth::user();

        return (bool) ($user?->isTeacher() && ! $user->isAdmin() && ! $user->isStaff());
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi khen thưởng.',
            ]);
        }
    }

    private function automaticRewardDetail(string $rewardType, float $gpa, int $excellentSubjectCount): string
    {
        if ($rewardType === Reward::TYPE_OUTSTANDING) {
            return "Tự động ghi nhận: học lực Tốt, hạnh kiểm Tốt, {$excellentSubjectCount} môn GPA từ 9.0, GPA chung {$gpa}.";
        }

        return "Tự động ghi nhận: học lực Tốt, hạnh kiểm Tốt, GPA chung {$gpa}.";
    }
}
