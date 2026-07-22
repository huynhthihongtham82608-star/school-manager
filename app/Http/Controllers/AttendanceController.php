<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ParentLeaveRequest;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
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
        $readOnly = $this->isHistoricalReadOnly();
        $selectedSessionType = $request->query('attendance_type');
        $selectedTimetableEntryId = $request->query('timetable_entry_id');

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
        $selectedTimetableEntry = null;
        $availableTimetableEntries = collect();
        $approvedLeaveRequests = collect();
        $approvedLeaveStudentIds = collect();
        $allowedSessionTypes = AttendanceRecord::SESSION_TYPES;
        $isEditingSession = false;

        if ($selectedClassId && $selectedSemesterId && $date && Schema::hasTable('students')) {
            $selectedClass = $classes->firstWhere('id', $selectedClassId);
            $selectedSemester = $semesters->firstWhere('id', $selectedSemesterId);

            if ($selectedClass && $selectedSemester) {
                $allowedSessionTypes = $this->allowedSessionTypes($user, $selectedClass);
                if (! array_key_exists((string) $selectedSessionType, $allowedSessionTypes)) {
                    $selectedSessionType = array_key_first($allowedSessionTypes) ?: AttendanceRecord::SESSION_DAILY;
                }

                $availableTimetableEntries = $this->availableTimetableEntries($user, $selectedClass, $selectedSemester->id, $date);
                if ($selectedSessionType === AttendanceRecord::SESSION_PERIOD) {
                    $selectedTimetableEntry = $availableTimetableEntries->firstWhere('id', $selectedTimetableEntryId)
                        ?: $availableTimetableEntries->first();
                    $selectedTimetableEntryId = $selectedTimetableEntry?->id;
                }

                $students = $selectedClass->students()
                    ->where('status', Student::STATUS_STUDYING)
                    ->orderBy('student_code')
                    ->get();

                if (Schema::hasTable('attendance_records')) {
                    $sessionKey = $this->sessionKey($selectedSessionType, $selectedTimetableEntryId);
                    $existingRecords = AttendanceRecord::where('class_id', $selectedClass->id)
                        ->where('semester_id', $selectedSemester->id)
                        ->whereDate('attendance_date', $date)
                        ->where('session_key', $sessionKey)
                        ->get()
                        ->keyBy('student_id');
                    $isEditingSession = $existingRecords->isNotEmpty();
                }

                $approvedLeaveRequests = $this->approvedLeaveRequestsFor($selectedClass, $date);
                $approvedLeaveStudentIds = $approvedLeaveRequests->pluck('student_id')->unique()->values();
            }
        } else {
            $selectedSessionType = $selectedSessionType ?: AttendanceRecord::SESSION_DAILY;
        }

        $recordsQuery = Schema::hasTable('attendance_records')
            ? AttendanceRecord::with(['student', 'classRoom.schoolYear', 'semester.schoolYear', 'timetableEntry.subject', 'timetableEntry.teacher'])->latest('attendance_date')->latest()
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

        $attendanceRecords = $recordsQuery ? $recordsQuery->get() : collect();
        $attendanceSummary = [
            'total' => $attendanceRecords->count(),
            'present' => $attendanceRecords->where('status', 'present')->count(),
            'late' => $attendanceRecords->where('status', 'late')->count(),
            'excused' => $attendanceRecords->where('status', 'excused')->count(),
            'absent' => $attendanceRecords->where('status', 'absent')->count(),
        ];
        $attendanceDetailRows = $this->attendanceDetailRows($attendanceRecords);
        $weeklyMatrix = $this->weeklyMatrix($request->user(), $selectedClass, $selectedSemesterId, $date);
        $pendingLeaveRequests = $this->pendingLeaveRequestsFor($request->user(), $selectedClass);

        $attendanceSessions = $recordsQuery
            ? $this->paginateSessions($attendanceRecords, $request)
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
            'selectedSessionType',
            'selectedTimetableEntryId',
            'selectedTimetableEntry',
            'availableTimetableEntries',
            'allowedSessionTypes',
            'isEditingSession',
            'date',
            'readOnly',
            'attendanceSummary',
            'attendanceDetailRows',
            'approvedLeaveRequests',
            'approvedLeaveStudentIds',
            'weeklyMatrix',
            'pendingLeaveRequests'
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
            'attendance_type' => ['required', 'in:' . implode(',', array_keys(AttendanceRecord::SESSION_TYPES))],
            'timetable_entry_id' => ['nullable', 'string', 'max:50', 'exists:timetable_entries,id'],
            'status' => ['required', 'array'],
            'status.*' => ['required', 'in:present,late,excused,absent'],
            'note' => ['nullable', 'array'],
            'note.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        $semester = Semester::findOrFail($data['semester_id']);
        $timetableEntry = null;

        $this->ensureSelectionMatchesYear($data['school_year_id'], $class, $semester);
        $this->authorizeAttendanceSession($request->user(), $class, $semester, $data['attendance_date'], $data['attendance_type'], $data['timetable_entry_id'] ?? null);

        if (! $semester->isActive() && ! $request->user()->isAdmin()) {
            abort(403, 'Học kỳ không ở trạng thái Hoạt động nên không thể nhập hoặc chỉnh sửa điểm danh.');
        }

        if ($data['attendance_type'] === AttendanceRecord::SESSION_PERIOD) {
            $timetableEntry = TimetableEntry::with(['subject', 'teacher'])->findOrFail($data['timetable_entry_id']);
        }

        $sessionKey = $this->sessionKey($data['attendance_type'], $data['timetable_entry_id'] ?? null);
        $sessionLabel = $this->sessionLabel($data['attendance_type'], $timetableEntry);
        $sessionOrder = $this->sessionOrder($data['attendance_type'], $timetableEntry);

        $students = Student::where('class_id', $class->id)
            ->where('status', Student::STATUS_STUDYING)
            ->orderBy('student_code')
            ->get();

        $approvedLeaveRequests = $this->approvedLeaveRequestsFor($class, $data['attendance_date']);
        $approvedLeaveStudentIds = $approvedLeaveRequests->pluck('student_id')->unique();

        DB::transaction(function () use ($students, $data, $sessionKey, $sessionLabel, $sessionOrder, $request, $approvedLeaveStudentIds, $approvedLeaveRequests) {
            foreach ($students as $student) {
                $status = $data['status'][$student->id] ?? null;
                $note = $data['note'][$student->id] ?? null;

                if (
                    ! $request->user()->isAdmin()
                    && $data['attendance_type'] === AttendanceRecord::SESSION_PERIOD
                    && $approvedLeaveStudentIds->contains($student->id)
                ) {
                    $status = 'excused';
                    $leaveReason = optional($approvedLeaveRequests->firstWhere('student_id', $student->id))->reason;
                    $note = 'Đã duyệt đơn xin nghỉ học của phụ huynh.' . ($leaveReason ? ' Lý do: ' . $leaveReason : '');
                }

                if (! $status) {
                    continue;
                }

                AttendanceRecord::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'attendance_date' => $data['attendance_date'],
                        'session_key' => $sessionKey,
                    ],
                    [
                        'class_id' => $data['class_id'],
                        'semester_id' => $data['semester_id'],
                        'session_type' => $data['attendance_type'],
                        'timetable_entry_id' => $data['attendance_type'] === AttendanceRecord::SESSION_PERIOD ? $data['timetable_entry_id'] : null,
                        'session_label' => $sessionLabel,
                        'session_order' => $sessionOrder,
                        'status' => $status,
                        'note' => $note,
                        'recorded_by' => $request->user()->id,
                    ]
                );
            }

            AuditLogger::log('attendance_updated', AttendanceRecord::class, null, 'Cập nhật điểm danh lớp');
        });

        return redirect()
            ->route('attendance.index', [
                'school_year_id' => $data['school_year_id'],
                'semester_id' => $data['semester_id'],
                'class_id' => $data['class_id'],
                'date' => $data['attendance_date'],
                'attendance_type' => $data['attendance_type'],
                'timetable_entry_id' => $data['timetable_entry_id'] ?? null,
            ])
            ->with('success', 'Đã lưu điểm danh.');
    }

    private function authorizeAttendanceSession($user, SchoolClass $class, Semester $semester, string $attendanceDate, string $sessionType, ?string $timetableEntryId): void
    {
        if ($user->isAdmin() && $sessionType === AttendanceRecord::SESSION_DAILY) {
            return;
        }

        if ($sessionType === AttendanceRecord::SESSION_DAILY) {
            if ($user->isHomeroom() && optional($user->teacher)->id === $class->homeroom_teacher_id) {
                return;
            }

            abort(403, 'Chỉ Admin hoặc giáo viên chủ nhiệm của lớp mới được điểm danh theo ngày.');
        }

        if (! $timetableEntryId) {
            abort(403, 'Vui lòng chọn tiết học cần điểm danh.');
        }

        $entry = TimetableEntry::with('timetable')->findOrFail($timetableEntryId);
        $dayOfWeek = \Illuminate\Support\Carbon::parse($attendanceDate)->isoWeekday();

        if (
            ! $entry->timetable
            || (string) $entry->timetable->class_id !== (string) $class->id
            || (string) $entry->timetable->semester_id !== (string) $semester->id
            || (int) $entry->day_of_week !== (int) $dayOfWeek
            || $entry->status !== TimetableEntry::STATUS_ACTIVE
        ) {
            abort(403, 'Tiết học không phù hợp với lớp, học kỳ hoặc ngày điểm danh đã chọn.');
        }

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTeacher() && $user->teacher && (string) $entry->teacher_id === (string) $user->teacher->id) {
            return;
        }

        abort(403, 'Giáo viên chỉ được điểm danh đúng tiết học mình được phân công.');
    }

    private function allowedSessionTypes($user, ?SchoolClass $class): array
    {
        if ($user->isAdmin()) {
            return AttendanceRecord::SESSION_TYPES;
        }

        $types = [];

        if ($class && $user->isHomeroom() && optional($user->teacher)->id === $class->homeroom_teacher_id) {
            $types[AttendanceRecord::SESSION_DAILY] = AttendanceRecord::SESSION_TYPES[AttendanceRecord::SESSION_DAILY];
        }

        if ($user->isTeacher()) {
            $types[AttendanceRecord::SESSION_PERIOD] = AttendanceRecord::SESSION_TYPES[AttendanceRecord::SESSION_PERIOD];
        }

        return $types ?: [AttendanceRecord::SESSION_PERIOD => AttendanceRecord::SESSION_TYPES[AttendanceRecord::SESSION_PERIOD]];
    }

    private function approvedLeaveRequestsFor(?SchoolClass $class, ?string $date)
    {
        if (! $class || ! $date || ! Schema::hasTable('parent_leave_requests')) {
            return collect();
        }

        return ParentLeaveRequest::where('class_id', $class->id)
            ->whereDate('leave_date', $date)
            ->where('status', ParentLeaveRequest::STATUS_APPROVED)
            ->get()
            ->keyBy('student_id');
    }

    private function pendingLeaveRequestsFor($user, ?SchoolClass $class)
    {
        if (! $class || ! Schema::hasTable('parent_leave_requests')) {
            return collect();
        }

        if (! ($user->isHomeroom() && $user->teacher && (string) $class->homeroom_teacher_id === (string) $user->teacher->id)) {
            return collect();
        }

        return ParentLeaveRequest::with(['parent', 'student', 'classRoom'])
            ->where('class_id', $class->id)
            ->where('status', ParentLeaveRequest::STATUS_PENDING)
            ->latest('leave_date')
            ->latest()
            ->limit(8)
            ->get();
    }

    private function attendanceDetailRows($attendanceRecords)
    {
        return $attendanceRecords
            ->sort(function (AttendanceRecord $left, AttendanceRecord $right) {
                $dateCompare = (optional($right->attendance_date)->timestamp ?? 0) <=> (optional($left->attendance_date)->timestamp ?? 0);

                return $dateCompare !== 0
                    ? $dateCompare
                    : ((int) ($left->session_order ?: 0) <=> (int) ($right->session_order ?: 0));
            })
            ->values();
    }

    private function weeklyMatrix($user, ?SchoolClass $class, ?string $semesterId, ?string $date): array
    {
        if (! $class || ! $semesterId || ! $date || ! Schema::hasTable('attendance_records')) {
            return ['enabled' => false, 'days' => collect(), 'rows' => collect()];
        }

        $canViewMatrix = $user->isAdmin()
            || ($user->isHomeroom() && $user->teacher && (string) $class->homeroom_teacher_id === (string) $user->teacher->id);

        if (! $canViewMatrix) {
            return ['enabled' => false, 'days' => collect(), 'rows' => collect()];
        }

        $start = Carbon::parse($date)->startOfWeek(Carbon::MONDAY);
        $days = collect(range(0, 5))->map(fn ($offset) => $start->copy()->addDays($offset));
        $students = $class->students()
            ->where('status', Student::STATUS_STUDYING)
            ->orderBy('student_code')
            ->get();

        $records = AttendanceRecord::where('class_id', $class->id)
            ->where('semester_id', $semesterId)
            ->whereBetween('attendance_date', [$days->first()->toDateString(), $days->last()->toDateString()])
            ->get()
            ->groupBy(['student_id', fn (AttendanceRecord $record) => $record->attendance_date?->toDateString()]);

        $rows = $students->map(function (Student $student) use ($days, $records) {
            $studentRecords = $records->get($student->id, collect());
            $totalAbsentPeriods = 0;
            $totalLate = 0;

            $cells = $days->mapWithKeys(function (Carbon $day) use ($studentRecords, &$totalAbsentPeriods, &$totalLate) {
                $items = collect($studentRecords->get($day->toDateString(), collect()));
                $excused = $items->where('status', 'excused')->count();
                $absent = $items->where('status', 'absent')->count();
                $late = $items->where('status', 'late')->count();
                $totalAbsentPeriods += $excused + $absent;
                $totalLate += $late;

                return [$day->toDateString() => [
                    'excused' => $excused,
                    'absent' => $absent,
                    'late' => $late,
                    'present' => $items->where('status', 'present')->count(),
                    'total' => $items->count(),
                ]];
            });

            return [
                'student' => $student,
                'cells' => $cells,
                'total_absent_periods' => $totalAbsentPeriods,
                'total_late' => $totalLate,
            ];
        });

        return ['enabled' => true, 'days' => $days, 'rows' => $rows];
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

    private function availableTimetableEntries($user, SchoolClass $class, string $semesterId, string $attendanceDate)
    {
        $dayOfWeek = \Illuminate\Support\Carbon::parse($attendanceDate)->isoWeekday();

        if ($dayOfWeek < 1 || $dayOfWeek > 6) {
            return collect();
        }

        $timetableIds = Timetable::where('class_id', $class->id)
            ->where('semester_id', $semesterId)
            ->pluck('id');

        if ($timetableIds->isEmpty()) {
            return collect();
        }

        return TimetableEntry::with(['subject', 'teacher', 'roomInfo'])
            ->whereIn('timetable_id', $timetableIds)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', TimetableEntry::STATUS_ACTIVE)
            ->when(! $user->isAdmin(), function ($query) use ($user) {
                if ($user->isTeacher() && $user->teacher) {
                    $query->where('teacher_id', $user->teacher->id);
                }
            })
            ->orderBy('period')
            ->get();
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
                $record->session_key ?: 'daily',
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
                    'session_type' => $first->session_type ?: AttendanceRecord::SESSION_DAILY,
                    'session_label' => $first->displaySessionLabel(),
                    'session_order' => (int) ($first->session_order ?: 0),
                    'timetable_entry_id' => $first->timetable_entry_id,
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
            ->sort(function ($left, $right) {
                $dateCompare = (optional($right->date)->timestamp ?? 0) <=> (optional($left->date)->timestamp ?? 0);

                return $dateCompare !== 0
                    ? $dateCompare
                    : ((int) $left->session_order <=> (int) $right->session_order);
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

    private function sessionKey(string $sessionType, ?string $timetableEntryId = null): string
    {
        if ($sessionType === AttendanceRecord::SESSION_PERIOD && $timetableEntryId) {
            return 'period:' . $timetableEntryId;
        }

        return 'daily';
    }

    private function sessionLabel(string $sessionType, ?TimetableEntry $entry = null): string
    {
        if ($sessionType === AttendanceRecord::SESSION_PERIOD && $entry) {
            $subject = $entry->subject?->name ?: 'Môn học';
            $teacher = $entry->teacher?->name;
            $parts = [$entry->displayPeriod(), $subject];

            if ($teacher) {
                $parts[] = $teacher;
            }

            return implode(' - ', $parts);
        }

        return 'Điểm danh theo ngày';
    }

    private function sessionOrder(string $sessionType, ?TimetableEntry $entry = null): int
    {
        return $sessionType === AttendanceRecord::SESSION_PERIOD && $entry
            ? (int) $entry->period
            : 0;
    }
}
