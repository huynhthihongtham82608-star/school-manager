<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $selectedYearId = $this->selectedSchoolYearId($request);
        $selectedSemesterId = $this->selectedSemesterId($request);
        $selectedClassId = $request->query('class_id');
        $date = $request->query('date', now()->toDateString());

        $schoolYears = Schema::hasTable('school_years')
            ? SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->get()
            : collect();

        if (! $selectedYearId && $schoolYears->isNotEmpty()) {
            $semesterYearIds = Schema::hasTable('semesters')
                ? Semester::query()->distinct()->pluck('school_year_id')
                : collect();
            $classYearIds = Schema::hasTable('classes')
                ? SchoolClass::query()->distinct()->pluck('school_year_id')
                : collect();
            $usableYearIds = $semesterYearIds->intersect($classYearIds)->values();

            $selectedYearId = optional($schoolYears->first(fn ($year) => $year->is_active && $usableYearIds->contains($year->id)))->id
                ?? optional($schoolYears->first(fn ($year) => $usableYearIds->contains($year->id)))->id
                ?? optional($schoolYears->firstWhere('is_active', true))->id
                ?? $schoolYears->first()->id;
        }

        $semesters = Schema::hasTable('semesters')
            ? Semester::with('schoolYear')
                ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->orderBy('name')
                ->get()
            : collect();

        $classesQuery = Schema::hasTable('classes')
            ? SchoolClass::with(['students', 'schoolYear'])->orderBy('name')
            : null;

        if ($classesQuery && $selectedYearId) {
            $classesQuery->where('school_year_id', $selectedYearId);
        }

        if ($classesQuery && $user->isTeacher() && ! $user->isAdmin()) {
            $classesQuery->whereIn('id', $this->teacherAttendanceClassIds($user));
        }

        $classes = $classesQuery ? $classesQuery->get() : collect();
        $students = collect();
        $existingRecords = collect();
        $selectedClass = null;
        $selectedSemester = null;
        $isEditingSession = false;

        if ($selectedClassId && $selectedSemesterId && $date && Schema::hasTable('students')) {
            $selectedClass = $classes->firstWhere('id', $selectedClassId);
            $selectedSemester = $semesters->firstWhere('id', $selectedSemesterId);

            if ($selectedClass && $selectedSemester) {
                $students = $selectedClass->students->sortBy('student_code')->values();

                if (Schema::hasTable('attendance_records')) {
                    $existingRecords = AttendanceRecord::where('class_id', $selectedClass->id)
                        ->where('semester_id', $selectedSemester->id)
                        ->whereDate('attendance_date', $date)
                        ->get()
                        ->keyBy('student_id');
                    $isEditingSession = $existingRecords->isNotEmpty();
                }
            }
        }

        $recordsQuery = Schema::hasTable('attendance_records')
            ? AttendanceRecord::with(['student', 'classRoom.schoolYear', 'semester.schoolYear'])->latest('attendance_date')->latest()
            : null;

        if ($recordsQuery) {
            if ($user->isStudent() && $user->student) {
                $recordsQuery->where('student_id', $user->student->id);
            } elseif ($user->isParent() && $user->parentProfile) {
                $studentIds = $this->selectedParentStudentIds($user);
                $recordsQuery->whereIn('student_id', $studentIds);
            } elseif ($user->isTeacher() && ! $user->isAdmin()) {
                $recordsQuery->whereIn('class_id', $this->teacherAttendanceClassIds($user));
            }

            if ($selectedYearId) {
                $semesterIds = Semester::where('school_year_id', $selectedYearId)->pluck('id');
                $recordsQuery->where(function ($query) use ($semesterIds, $selectedYearId) {
                    $query->whereIn('semester_id', $semesterIds)
                        ->orWhereHas('classRoom', fn ($classQuery) => $classQuery->where('school_year_id', $selectedYearId));
                });
            }

            if ($selectedSemesterId) {
                $recordsQuery->where('semester_id', $selectedSemesterId);
            }

            if ($selectedClassId) {
                $recordsQuery->where('class_id', $selectedClassId);
            }
        }

        $attendanceSessions = $recordsQuery
            ? $this->paginateSessions($recordsQuery->get(), $request)
            : collect();

        return view('attendance.index', compact(
            'schoolYears',
            'classes',
            'students',
            'existingRecords',
            'attendanceSessions',
            'semesters',
            'selectedYearId',
            'selectedSemesterId',
            'selectedClassId',
            'selectedClass',
            'selectedSemester',
            'isEditingSession',
            'date'
        ));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isTeacher(), 403);

        if (! Schema::hasTable('attendance_records')) {
            return back()->with('error', 'Chưa có bảng attendance_records. Vui lòng chạy migration trước.');
        }

        $data = $request->validate([
            'school_year_id' => ['required', 'string', 'max:50', 'exists:school_years,id'],
            'class_id' => ['required', 'string', 'max:50', 'exists:classes,id'],
            'semester_id' => ['required', 'string', 'max:50', 'exists:semesters,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'array'],
            'status.*' => ['required', 'in:present,late,excused,absent'],
            'note' => ['nullable', 'array'],
            'note.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        $semester = Semester::findOrFail($data['semester_id']);

        $this->authorizeAttendanceClass($class, $data['attendance_date']);
        $this->ensureSelectionMatchesYear($data['school_year_id'], $class, $semester);

        if (! $semester->isActive()) {
            abort(403, 'Học kỳ không ở trạng thái Hoạt động nên không thể nhập hoặc chỉnh sửa điểm danh.');
        }

        $students = Student::where('class_id', $class->id)->orderBy('student_code')->get();

        foreach ($students as $student) {
            $status = $data['status'][$student->id] ?? null;

            if (! $status) {
                continue;
            }

            AttendanceRecord::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'attendance_date' => $data['attendance_date'],
                ],
                [
                    'class_id' => $data['class_id'],
                    'semester_id' => $data['semester_id'],
                    'status' => $status,
                    'note' => $data['note'][$student->id] ?? null,
                    'recorded_by' => $request->user()->id,
                ]
            );
        }

        AuditLogger::log('attendance_updated', AttendanceRecord::class, null, 'Cập nhật điểm danh lớp');

        return redirect()
            ->route('attendance.index', [
                'school_year_id' => $data['school_year_id'],
                'semester_id' => $data['semester_id'],
                'class_id' => $data['class_id'],
                'date' => $data['attendance_date'],
            ])
            ->with('success', 'Đã lưu điểm danh.');
    }

    private function authorizeAttendanceClass(SchoolClass $class, string $attendanceDate): void
    {
        $user = request()->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isHomeroom() && optional($user->teacher)->id === $class->homeroom_teacher_id) {
            return;
        }

        if ($user->isTeacher() && $user->teacher && $this->teacherHasScheduledClassOnDate($user->teacher->id, $class->id, $attendanceDate)) {
            return;
        }

        abort(403, 'Giáo viên chỉ được điểm danh lớp mình dạy đúng ngày có tiết học.');
    }

    private function teacherAttendanceClassIds($user)
    {
        if (! $user->teacher) {
            return collect();
        }

        return $user->teacher->assignments()
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->pluck('class_id')
            ->merge(SchoolClass::where('homeroom_teacher_id', $user->teacher->id)->pluck('id'))
            ->filter()
            ->unique()
            ->values();
    }

    private function selectedParentStudentIds($user)
    {
        $students = $user->parentProfile->students()->orderBy('student_code')->get(['students.id']);

        if ($students->isEmpty()) {
            return collect();
        }

        $selectedId = session('selected_parent_student_id');
        $selected = $students->firstWhere('id', $selectedId) ?: $students->first();

        return collect([$selected->id]);
    }

    private function teacherHasScheduledClassOnDate(string $teacherId, string $classId, string $attendanceDate): bool
    {
        $dayOfWeek = \Illuminate\Support\Carbon::parse($attendanceDate)->isoWeekday();

        if ($dayOfWeek < 1 || $dayOfWeek > 6) {
            return false;
        }

        $timetableIds = Timetable::where('class_id', $classId)->pluck('id');

        return TimetableEntry::whereIn('timetable_id', $timetableIds)
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', TimetableEntry::STATUS_ACTIVE)
            ->exists();
    }

    private function ensureSelectionMatchesYear(string $schoolYearId, SchoolClass $class, Semester $semester): void
    {
        $errors = [];

        if ((string) $class->school_year_id !== (string) $schoolYearId) {
            $errors['class_id'] = 'Lớp không thuộc năm học đã chọn.';
        }

        if ((string) $semester->school_year_id !== (string) $schoolYearId) {
            $errors['semester_id'] = 'Học kỳ không thuộc năm học đã chọn.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function paginateSessions($records, Request $request): LengthAwarePaginator
    {
        $sessions = $records
            ->groupBy(fn (AttendanceRecord $record) => implode('|', [
                $record->class_id,
                $record->semester_id ?: 'none',
                optional($record->attendance_date)->toDateString(),
            ]))
            ->map(function ($items, $key) {
                $first = $items->first();
                $counts = $items->countBy('status');

                return (object) [
                    'key' => md5($key),
                    'class_id' => $first->class_id,
                    'semester_id' => $first->semester_id,
                    'school_year_id' => $first->semester?->school_year_id ?? $first->classRoom?->school_year_id,
                    'date' => $first->attendance_date,
                    'class_name' => $first->classRoom->name ?? 'Không rõ',
                    'semester_name' => $first->semester->name ?? 'Không rõ',
                    'school_year_name' => $first->semester?->schoolYear?->name ?? $first->classRoom?->schoolYear?->name ?? 'Không rõ',
                    'total' => $items->count(),
                    'present' => $counts->get('present', 0),
                    'late' => $counts->get('late', 0),
                    'excused' => $counts->get('excused', 0),
                    'absent' => $counts->get('absent', 0),
                    'records' => $items
                        ->sortBy(fn ($record) => $record->student->student_code ?? $record->student->name ?? '')
                        ->values(),
                ];
            })
            ->values();

        $page = max((int) $request->query('page', 1), 1);
        $perPage = 10;

        return new LengthAwarePaginator(
            $sessions->forPage($page, $perPage)->values(),
            $sessions->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
