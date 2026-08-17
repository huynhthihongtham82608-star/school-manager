<?php

namespace App\Http\Controllers;

use App\Models\Conduct;
use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Services\AcademicEvaluationService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ConductController extends Controller
{
    private const SEMESTER_UNEXCUSED_ABSENCE_LIMIT = 22;
    private const SCHOOL_YEAR_UNEXCUSED_ABSENCE_LIMIT = 45;

    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedYearId = $this->selectedSchoolYearId($request);
        $selectedSemesterId = $this->selectedSemesterId($request);
        $viewStudent = null;
        $studentConductRecords = collect();

        if ($user->isStudent() && $user->student) {
            $viewStudent = $user->student->load('classRoom');
        } elseif ($user->isParent() && $user->parentProfile) {
            $children = $user->parentProfile->students()->with('classRoom')->orderBy('student_code')->get();
            $viewStudent = $children->firstWhere('id', session('selected_parent_student_id')) ?: $children->first();
        }

        if ($viewStudent) {
            $studentConductRecords = Conduct::with(['classRoom', 'semester.schoolYear'])
                ->where('student_id', $viewStudent->id)
                ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->latest()
                ->get();

            return view('conduct.index', [
                'classes' => collect(),
                'semesters' => Semester::with('schoolYear')->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->get(),
                'selectedClass' => null,
                'selectedSemester' => null,
                'students' => collect(),
                'records' => collect(),
                'selectedYearId' => $selectedYearId,
                'viewStudent' => $viewStudent,
                'studentConductRecords' => $studentConductRecords,
            ]);
        }

        $classesQuery = SchoolClass::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId));

        if ($user->isTeacher() && $user->teacher && ! ($user->isAdmin() || $user->isStaff())) {
            $homeroomClassIds = $user->teacher->homeroomClasses()
                ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->pluck('id');

            $classesQuery->whereIn('id', $homeroomClassIds->unique()->values());
        }

        $classes = $classesQuery->orderBy('grade_level')->orderBy('name')->get();

        $semesters = Semester::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        if (! $selectedSemesterId && $semesters->isNotEmpty()) {
            $selectedSemesterId = optional($semesters->first(fn ($semester) => $semester->isActive()))->id
                ?? $semesters->first()->id;
        }

        if (! $request->filled('class_id') && $user->isHomeroom() && $classes->isNotEmpty()) {
            $request->merge(['class_id' => $classes->first()->id]);
        }

        $selectedClass = null;
        $selectedSemester = $selectedSemesterId ? $semesters->firstWhere('id', $selectedSemesterId) : null;
        $students = collect();
        $records = collect();
        $attendanceSummaries = collect();
        $conductSuggestions = collect();
        $evaluationRules = $this->evaluationRules();
        $absencePolicy = [
            'semester_unexcused_limit' => (int) $evaluationRules['conduct_unexcused_absence_limit'],
            'school_year_unexcused_limit' => max(self::SCHOOL_YEAR_UNEXCUSED_ABSENCE_LIMIT, (int) $evaluationRules['conduct_unexcused_absence_limit'] * 2),
        ];
        $canEditConduct = false;

        if ($request->filled('class_id') && $selectedSemesterId) {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
            ]);

            $selectedClass = SchoolClass::find($request->input('class_id'));
            $selectedSemester = Semester::find($selectedSemesterId);
            $this->authorizeConductView($selectedClass);
            $canEditConduct = $this->canEditConduct($selectedClass)
                && $selectedSemester?->isActive()
                && ! $this->isHistoricalReadOnly();

            $students = Student::where('class_id', $selectedClass->id)->orderBy('student_code')->get();
            $records = Conduct::where('class_id', $selectedClass->id)
                ->where('semester_id', $selectedSemester->id)
                ->get()
                ->keyBy('student_id');
            $attendanceSummaries = $this->conductAttendanceSummaries($students, $selectedSemester, $selectedClass, $evaluationRules);
            $conductSuggestions = $this->conductSystemSuggestions($students, $attendanceSummaries, $selectedSemester, $evaluationRules);
        }

        return view('conduct.index', compact('classes', 'semesters', 'selectedClass', 'selectedSemester', 'students', 'records', 'selectedYearId', 'canEditConduct', 'attendanceSummaries', 'conductSuggestions', 'absencePolicy'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'semester_id' => 'required|exists:semesters,id',
            'conduct' => 'array',
            'conduct.*.conduct_level' => ['nullable', Rule::in(array_keys(Conduct::LEVELS))],
            'conduct.*.comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        $semester = Semester::findOrFail($data['semester_id']);
        $this->authorizeConductEdit($class);

        if (! $semester->isActive()) {
            abort(403, 'Học kỳ không ở trạng thái Hoạt động nên không thể nhập hoặc chỉnh sửa hạnh kiểm.');
        }

        if ($this->isHistoricalReadOnly()) {
            abort(403, 'Đang xem dữ liệu năm học cũ, chỉ được xem hạnh kiểm.');
        }

        $students = Student::where('class_id', $class->id)->orderBy('student_code')->get();
        $attendanceSummaries = $this->conductAttendanceSummaries($students, $semester, $class, $this->evaluationRules());

        DB::transaction(function () use ($students, $request, $class, $semester, $attendanceSummaries) {
            foreach ($students as $student) {
                $entry = $request->input("conduct.{$student->id}", []);
                $attendance = $attendanceSummaries->get($student->id, $this->emptyAttendanceSummary());
                $conductLevel = $entry['conduct_level'] ?? Conduct::LEVEL_GOOD;
                $comment = trim((string) ($entry['comment'] ?? ''));

                Conduct::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'semester_id' => $semester->id,
                    ],
                    [
                        'school_year_id' => $semester->school_year_id,
                        'class_id' => $class->id,
                        'conduct_level' => $conductLevel ?: Conduct::LEVEL_GOOD,
                        'comment' => $comment,
                    ]
                );
            }

            AuditLogger::log('conduct_updated', Conduct::class, null, 'Cập nhật sổ hạnh kiểm học kỳ');
        });

        return back()->with('success', 'Đã lưu hạnh kiểm');
    }

    protected function authorizeConductView(SchoolClass $class): void
    {
        $user = Auth::user();

        if ($user->isAdmin() || $user->isStaff()) {
            return;
        }

        if ($user->isTeacher() && $user->teacher) {
            $teacherId = $user->teacher->id;

            if ((string) $teacherId === (string) $class->homeroom_teacher_id) {
                return;
            }

            if ($class->assignments()->where('teacher_id', $teacherId)->exists()) {
                return;
            }
        }

        abort(403, 'Không có quyền xem hạnh kiểm của lớp này.');
    }

    protected function authorizeConductEdit(SchoolClass $class): void
    {
        if ($this->canEditConduct($class)) {
            return;
        }

        abort(403, 'Chỉ giáo viên chủ nhiệm của lớp mới được nhập hoặc chỉnh sửa hạnh kiểm.');
    }

    private function canEditConduct(SchoolClass $class): bool
    {
        $user = Auth::user();

        return $user->isHomeroom()
            && $user->teacher
            && (string) $user->teacher->id === (string) $class->homeroom_teacher_id;
    }

    private function conductAttendanceSummaries($students, Semester $semester, SchoolClass $class, array $evaluationRules)
    {
        $studentIds = $students->pluck('id')->values();
        $summary = $students->mapWithKeys(fn ($student) => [$student->id => $this->emptyAttendanceSummary()]);

        if ($students->isEmpty() || ! Schema::hasTable('attendance_records')) {
            return $summary;
        }

        $hasSessionType = Schema::hasColumn('attendance_records', 'session_type');
        $semesterCountsQuery = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->where('class_id', $class->id)
            ->where('semester_id', $semester->id)
            ->select('student_id', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('student_id', 'status');

        if ($hasSessionType) {
            $semesterCountsQuery->addSelect('session_type')->groupBy('session_type');
        }

        $semesterCounts = $semesterCountsQuery->get();

        foreach ($semesterCounts as $row) {
            $studentSummary = $summary->get($row->student_id, $this->emptyAttendanceSummary());
            if (in_array($row->status, [AttendanceRecord::STATUS_EXCUSED, AttendanceRecord::STATUS_PERMITTED_ABSENT], true)) {
                $studentSummary['excused'] += (int) $row->total;
            } elseif (in_array($row->status, [AttendanceRecord::STATUS_ABSENT, AttendanceRecord::STATUS_UNEXCUSED_ABSENT], true)) {
                if ($hasSessionType && ($row->session_type ?? null) === AttendanceRecord::SESSION_PERIOD) {
                    $studentSummary['period_absent'] += (int) $row->total;
                } else {
                    $studentSummary['absent'] += (int) $row->total;
                }
            } else {
                $studentSummary[$row->status] = (int) $row->total;
            }
            $studentSummary['semester_unexcused_absent'] = $studentSummary['absent'];
            $summary->put($row->student_id, $studentSummary);
        }

        $schoolYearSemesterIds = Semester::where('school_year_id', $semester->school_year_id)->pluck('id');
        $yearAbsentCountsQuery = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->whereIn('semester_id', $schoolYearSemesterIds)
            ->whereIn('status', [AttendanceRecord::STATUS_ABSENT, AttendanceRecord::STATUS_UNEXCUSED_ABSENT]);

        if ($hasSessionType) {
            $yearAbsentCountsQuery->where('session_type', '!=', AttendanceRecord::SESSION_PERIOD);
        }

        $yearAbsentCounts = $yearAbsentCountsQuery
            ->select('student_id', DB::raw('COUNT(*) as total'))
            ->groupBy('student_id')
            ->get()
            ->pluck('total', 'student_id');

        return $summary->map(function (array $studentSummary, string $studentId) use ($yearAbsentCounts, $evaluationRules) {
            $studentSummary['school_year_unexcused_absent'] = (int) ($yearAbsentCounts[$studentId] ?? 0);
            $semesterLimit = collect(app(AcademicEvaluationService::class)->conductLevels())
                ->max(fn (array $level) => (int) $level['max_unexcused_absence']) ?: (int) $evaluationRules['conduct_unexcused_absence_limit'];
            $yearLimit = max(self::SCHOOL_YEAR_UNEXCUSED_ABSENCE_LIMIT, $semesterLimit * 2);
            $studentSummary['force_weak'] = $studentSummary['semester_unexcused_absent'] > $semesterLimit
                || $studentSummary['school_year_unexcused_absent'] > $yearLimit;

            return $studentSummary;
        });
    }

    private function conductSystemSuggestions($students, $attendanceSummaries, Semester $semester, array $evaluationRules)
    {
        if ($students->isEmpty() || ! Schema::hasTable('score_headers')) {
            return collect();
        }

        $scoreAverages = ScoreHeader::query()
            ->whereIn('student_id', $students->pluck('id')->values())
            ->where('semester_id', $semester->id)
            ->whereNotNull('average')
            ->get(['student_id', 'average'])
            ->groupBy('student_id')
            ->map(fn ($scores) => round((float) $scores->avg('average'), 2));

        return $students->mapWithKeys(function (Student $student) use ($attendanceSummaries, $scoreAverages, $evaluationRules) {
            $attendance = $attendanceSummaries->get($student->id, $this->emptyAttendanceSummary());
            $unexcusedAbsence = (int) ($attendance['semester_unexcused_absent'] ?? 0);
            $periodAbsence = (int) ($attendance['period_absent'] ?? 0);
            $late = (int) ($attendance['late'] ?? 0);
            $excused = (int) ($attendance['excused'] ?? 0);
            $average = $scoreAverages->get($student->id);
            $topAcademicLevel = app(AcademicEvaluationService::class)->levels()['level_1'];
            $excellentMin = (float) $topAcademicLevel['gpa_min'];
            $conductSuggestion = app(AcademicEvaluationService::class)->suggestConductLabel($unexcusedAbsence, $periodAbsence, $late);

            $suggestion = null;
            if ($unexcusedAbsence > 0 || $late > 0) {
                $suggestion = 'Gợi ý: ' . $conductSuggestion;
            } elseif ($unexcusedAbsence === 0 && $late === 0 && $excused === 0 && $average !== null && $average >= $excellentMin) {
                $suggestion = 'Gợi ý: ' . $topAcademicLevel['label'];
            }

            return [$student->id => [
                'text' => $suggestion,
                'average' => $average,
            ]];
        });
    }

    private function emptyAttendanceSummary(): array
    {
        return [
            'present' => 0,
            'late' => 0,
            'excused' => 0,
            'absent' => 0,
            'period_absent' => 0,
            'semester_unexcused_absent' => 0,
            'school_year_unexcused_absent' => 0,
            'force_weak' => false,
        ];
    }

    private function evaluationRules(): array
    {
        return Setting::valuesFor(array_merge(AcademicEvaluationService::defaults(), [
            'conduct_unexcused_absence_limit' => self::SEMESTER_UNEXCUSED_ABSENCE_LIMIT,
        ]));
    }
}
