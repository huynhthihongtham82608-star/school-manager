<?php

namespace App\Http\Controllers;

use App\Models\Conduct;
use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
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
        $absencePolicy = [
            'semester_unexcused_limit' => self::SEMESTER_UNEXCUSED_ABSENCE_LIMIT,
            'school_year_unexcused_limit' => self::SCHOOL_YEAR_UNEXCUSED_ABSENCE_LIMIT,
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
            $attendanceSummaries = $this->conductAttendanceSummaries($students, $selectedSemester, $selectedClass);
        }

        return view('conduct.index', compact('classes', 'semesters', 'selectedClass', 'selectedSemester', 'students', 'records', 'selectedYearId', 'canEditConduct', 'attendanceSummaries', 'absencePolicy'));
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
        $attendanceSummaries = $this->conductAttendanceSummaries($students, $semester, $class);

        DB::transaction(function () use ($students, $request, $class, $semester, $attendanceSummaries) {
            foreach ($students as $student) {
                $entry = $request->input("conduct.{$student->id}", []);
                $attendance = $attendanceSummaries->get($student->id, $this->emptyAttendanceSummary());
                $conductLevel = $entry['conduct_level'] ?? Conduct::LEVEL_GOOD;
                $comment = trim((string) ($entry['comment'] ?? ''));

                if ($attendance['force_weak']) {
                    $conductLevel = Conduct::LEVEL_NOT_PASS;
                    if ($comment === '') {
                        $comment = 'Hệ thống cảnh báo: Học sinh vắng quá số buổi quy định.';
                    }
                }

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

    private function conductAttendanceSummaries($students, Semester $semester, SchoolClass $class)
    {
        $studentIds = $students->pluck('id')->values();
        $summary = $students->mapWithKeys(fn ($student) => [$student->id => $this->emptyAttendanceSummary()]);

        if ($students->isEmpty() || ! Schema::hasTable('attendance_records')) {
            return $summary;
        }

        $semesterCounts = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->where('class_id', $class->id)
            ->where('semester_id', $semester->id)
            ->select('student_id', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('student_id', 'status')
            ->get();

        foreach ($semesterCounts as $row) {
            $studentSummary = $summary->get($row->student_id, $this->emptyAttendanceSummary());
            $studentSummary[$row->status] = (int) $row->total;
            $studentSummary['semester_unexcused_absent'] = $studentSummary['absent'];
            $summary->put($row->student_id, $studentSummary);
        }

        $schoolYearSemesterIds = Semester::where('school_year_id', $semester->school_year_id)->pluck('id');
        $yearAbsentCounts = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->whereIn('semester_id', $schoolYearSemesterIds)
            ->where('status', 'absent')
            ->select('student_id', DB::raw('COUNT(*) as total'))
            ->groupBy('student_id')
            ->get()
            ->pluck('total', 'student_id');

        return $summary->map(function (array $studentSummary, string $studentId) use ($yearAbsentCounts) {
            $studentSummary['school_year_unexcused_absent'] = (int) ($yearAbsentCounts[$studentId] ?? 0);
            $studentSummary['force_weak'] = $studentSummary['semester_unexcused_absent'] > self::SEMESTER_UNEXCUSED_ABSENCE_LIMIT
                || $studentSummary['school_year_unexcused_absent'] > self::SCHOOL_YEAR_UNEXCUSED_ABSENCE_LIMIT;

            return $studentSummary;
        });
    }

    private function emptyAttendanceSummary(): array
    {
        return [
            'present' => 0,
            'late' => 0,
            'excused' => 0,
            'absent' => 0,
            'semester_unexcused_absent' => 0,
            'school_year_unexcused_absent' => 0,
            'force_weak' => false,
        ];
    }
}
