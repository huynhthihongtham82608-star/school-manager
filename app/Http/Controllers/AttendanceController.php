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
        $attendanceEditDeadline = $this->attendanceEditDeadline($date);
        $attendanceEditWindowOpen = $this->attendanceEditWindowOpen($date);

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
                ->orderBy('order')
                ->orderBy('name')
                ->get()
            : collect();

        if (! $selectedSemesterId && $semesters->isNotEmpty()) {
            $selectedSemesterId = optional($semesters->first(fn ($semester) => $semester->isActive()))->id
                ?? $semesters->first()->id;
        }

        $classesQuery = Schema::hasTable('classes')
            ? SchoolClass::with(['students', 'schoolYear'])->orderBy('name')
            : null;

        if ($classesQuery && $selectedYearId) {
            $classesQuery->where('school_year_id', $selectedYearId);
        }

        if ($classesQuery && $user->isTeacher() && ! $user->isAdmin() && ! $user->isStaff()) {
            $classesQuery->whereIn('id', $this->teacherAttendanceClassIds($user));
        }

        $classes = $classesQuery ? $classesQuery->get() : collect();
        $isSubjectTeacherOnlyAttendance = $user->isTeacher()
            && ! $user->isAdmin()
            && ! $user->isStaff()
            && ! $user->isHomeroom();

        if (! $selectedClassId && $user->isHomeroom() && ! $user->isAdmin() && ! $user->isStaff() && $classes->isNotEmpty()) {
            $selectedClassId = $classes->first()->id;
        }

        if ($isSubjectTeacherOnlyAttendance) {
            $selectedSessionType = AttendanceRecord::SESSION_PERIOD;

            if ($selectedTimetableEntryId) {
                $selectedEntryClassId = TimetableEntry::with('timetable')->find($selectedTimetableEntryId)?->timetable?->class_id;
                if ($selectedEntryClassId && $classes->contains('id', $selectedEntryClassId)) {
                    $selectedClassId = $selectedEntryClassId;
                }
            }

            if (! $selectedClassId && $selectedSemesterId && $date) {
                $firstEntry = $this->teacherTimetableEntriesForDate($user, $selectedSemesterId, $date, $classes->pluck('id'))->first();
                if ($firstEntry?->timetable?->class_id) {
                    $selectedClassId = $firstEntry->timetable->class_id;
                    $selectedTimetableEntryId = $selectedTimetableEntryId ?: $firstEntry->id;
                } elseif ($classes->isNotEmpty()) {
                    $selectedClassId = $classes->first()->id;
                }
            }
        }

        $students = collect();
        $existingRecords = collect();
        $selectedClass = null;
        $selectedSemester = null;
        $selectedTimetableEntry = null;
        $availableTimetableEntries = collect();
        $approvedLeaveRequests = collect();
        $approvedLeaveStudentIds = collect();
        $allowedSessionTypes = $this->mainAttendanceSessionTypes();
        $isEditingSession = false;
        $parentLeaveChildren = collect();
        $selectedParentStudent = null;

        if ($user->isParent() && $user->parentProfile) {
            $parentLeaveChildren = $user->parentProfile->students()
                ->with('classRoom.homeroomTeacher')
                ->orderBy('student_code')
                ->get();
            $selectedParentStudent = $this->selectedParentStudent($parentLeaveChildren);
        }

        if ($selectedClassId && $selectedSemesterId && $date && Schema::hasTable('students')) {
            $selectedClass = $classes->firstWhere('id', $selectedClassId);
            $selectedSemester = $semesters->firstWhere('id', $selectedSemesterId);

            if ($selectedClass && $selectedSemester) {
                $allowedSessionTypes = $this->allowedSessionTypes($user, $selectedClass);
                if (! array_key_exists((string) $selectedSessionType, $allowedSessionTypes)) {
                    $selectedSessionType = array_key_first($allowedSessionTypes) ?: AttendanceRecord::SESSION_DAILY;
                }

                $availableTimetableEntries = $isSubjectTeacherOnlyAttendance
                    ? $this->teacherTimetableEntriesForDate(
                        $user,
                        $selectedSemester->id,
                        $date,
                        $selectedClassId ? collect([$selectedClassId]) : $classes->pluck('id')
                    )
                    : $this->availableTimetableEntries($user, $selectedClass, $selectedSemester->id, $date);
                if ($selectedSessionType === AttendanceRecord::SESSION_PERIOD) {
                    $selectedTimetableEntry = $availableTimetableEntries->firstWhere('id', $selectedTimetableEntryId)
                        ?: $availableTimetableEntries->first();
                    $selectedTimetableEntryId = $selectedTimetableEntry?->id;
                    if ($isSubjectTeacherOnlyAttendance && $selectedTimetableEntry?->timetable?->class_id) {
                        $selectedClass = $classes->firstWhere('id', $selectedTimetableEntry->timetable->class_id) ?: $selectedTimetableEntry->timetable->classRoom;
                        $selectedClassId = $selectedClass?->id;
                    }
                    if ($selectedTimetableEntryId) {
                        $this->authorizePeriodAttendanceView($user, $selectedClass, $selectedSemester, $date, $selectedTimetableEntryId);
                    }
                }

                if ($selectedSessionType !== AttendanceRecord::SESSION_PERIOD || $selectedTimetableEntry) {
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
            }
        } else {
            $selectedSessionType = $selectedSessionType ?: AttendanceRecord::SESSION_MORNING;
        }

        $isSubjectTeacherPeriodScope = $user->isTeacher()
            && ! $user->isAdmin()
            && ! $user->isStaff()
            && $selectedClass
            && ! ($user->isHomeroom() && $user->teacher && (string) $selectedClass->homeroom_teacher_id === (string) $user->teacher->id);

        $recordsQuery = Schema::hasTable('attendance_records')
            ? AttendanceRecord::with(['student', 'classRoom.schoolYear', 'semester.schoolYear', 'timetableEntry.subject', 'timetableEntry.teacher'])->latest('attendance_date')->latest()
            : null;

        if ($recordsQuery) {
            if ($user->isStudent() && $user->student) {
                $recordsQuery->where('student_id', $user->student->id);
            } elseif ($user->isParent() && $user->parentProfile) {
                $studentIds = $this->selectedParentStudentIds($user);
                $recordsQuery->whereIn('student_id', $studentIds);
            } elseif ($user->isTeacher() && ! $user->isAdmin() && ! $user->isStaff()) {
                if ($user->isHomeroom()) {
                    $recordsQuery->whereIn('class_id', $this->teacherAttendanceClassIds($user));
                } elseif ($user->teacher) {
                    $recordsQuery->whereIn('class_id', $this->teacherAttendanceClassIds($user));
                } else {
                    $recordsQuery->whereRaw('1 = 0');
                }
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

            if (! $user->isStudent() && ! $user->isParent()) {
                if ($isSubjectTeacherPeriodScope && $user->teacher) {
                    $teacherTimetableEntryIds = TimetableEntry::where('teacher_id', $user->teacher->id)->pluck('id');
                    $recordsQuery->where(function ($query) use ($teacherTimetableEntryIds) {
                        $query->where('session_type', AttendanceRecord::SESSION_PERIOD)
                            ->orWhere('session_key', 'like', 'period:%');
                    });

                    if ($teacherTimetableEntryIds->isNotEmpty()) {
                        $recordsQuery->whereIn('timetable_entry_id', $teacherTimetableEntryIds);
                    } else {
                        $recordsQuery->whereRaw('1 = 0');
                    }
                } else {
                    $recordsQuery->where(function ($query) {
                        $query->whereIn('session_type', [AttendanceRecord::SESSION_MORNING, AttendanceRecord::SESSION_AFTERNOON])
                            ->orWhereIn('session_key', [AttendanceRecord::SESSION_MORNING, AttendanceRecord::SESSION_AFTERNOON]);
                    });
                }
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
            ? $this->paginateSessions($attendanceRecords, $request, $isSubjectTeacherPeriodScope)
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
            'attendanceEditDeadline',
            'attendanceEditWindowOpen',
            'attendanceSummary',
            'attendanceDetailRows',
            'approvedLeaveRequests',
            'approvedLeaveStudentIds',
            'weeklyMatrix',
            'pendingLeaveRequests',
            'parentLeaveChildren',
            'selectedParentStudent'
        ));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isTeacher() && ! $request->user()->isAdmin() && ! $request->user()->isStaff(), 403);

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
        $this->authorizeAttendanceMutation($request->user(), $class, $semester, $data['attendance_date'], $data['attendance_type'], $data['timetable_entry_id'] ?? null);

        if (! $semester->isActive()) {
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

                if ($data['attendance_type'] !== AttendanceRecord::SESSION_PERIOD && $approvedLeaveStudentIds->contains($student->id)) {
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

    private function authorizeAttendanceMutation($user, SchoolClass $class, Semester $semester, string $attendanceDate, string $sessionType, ?string $timetableEntryId): void
    {
        if ($sessionType === AttendanceRecord::SESSION_PERIOD) {
            $this->authorizePeriodAttendanceView($user, $class, $semester, $attendanceDate, $timetableEntryId);

            if (! $this->attendanceEditWindowOpen($attendanceDate)) {
                abort(403, 'Đã quá 24 giờ kể từ ngày điểm danh. Sổ điểm danh đã chuyển sang chế độ chỉ xem.');
            }

            return;
        }

        if (! ($user->isHomeroom() && optional($user->teacher)->id === $class->homeroom_teacher_id)) {
            abort(403, 'Chỉ giáo viên chủ nhiệm của lớp mới được lưu sổ điểm danh ngày.');
        }

        if (! in_array($sessionType, [AttendanceRecord::SESSION_MORNING, AttendanceRecord::SESSION_AFTERNOON], true)) {
            abort(403, 'Giáo viên bộ môn chỉ được xem điểm danh theo tiết, không được lưu sổ tổng hợp lớp.');
        }

        if (! $this->attendanceEditWindowOpen($attendanceDate)) {
            abort(403, 'Đã quá 24 giờ kể từ ngày điểm danh. Sổ điểm danh đã chuyển sang chế độ chỉ xem.');
        }
    }

    private function attendanceEditDeadline(string $attendanceDate): Carbon
    {
        return Carbon::parse($attendanceDate)
            ->startOfDay()
            ->addHours(24);
    }

    private function attendanceEditWindowOpen(string $attendanceDate): bool
    {
        return now()->lte($this->attendanceEditDeadline($attendanceDate));
    }

    private function authorizePeriodAttendanceView($user, SchoolClass $class, Semester $semester, string $attendanceDate, ?string $timetableEntryId): void
    {
        if ($user->isAdmin() || $user->isStaff()) {
            return;
        }

        if (! $timetableEntryId) {
            if ($user->isHomeroom() && optional($user->teacher)->id === $class->homeroom_teacher_id) {
                return;
            }

            abort(403, 'Giáo viên bộ môn chỉ được xem điểm danh khi chọn đúng tiết học được phân công.');
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

        if ($user->isHomeroom() && optional($user->teacher)->id === $class->homeroom_teacher_id) {
            return;
        }

        if ($user->isTeacher() && $user->teacher && (string) $entry->teacher_id === (string) $user->teacher->id) {
            return;
        }

        abort(403, 'Giáo viên bộ môn chỉ được xem điểm danh đúng tiết học mình được phân công.');
    }

    private function allowedSessionTypes($user, ?SchoolClass $class): array
    {
        if ($user->isAdmin() || $user->isStaff()) {
            return $this->mainAttendanceSessionTypes();
        }

        $types = [];

        if ($class && $user->isHomeroom() && optional($user->teacher)->id === $class->homeroom_teacher_id) {
            $types[AttendanceRecord::SESSION_MORNING] = AttendanceRecord::SESSION_TYPES[AttendanceRecord::SESSION_MORNING];
            $types[AttendanceRecord::SESSION_AFTERNOON] = AttendanceRecord::SESSION_TYPES[AttendanceRecord::SESSION_AFTERNOON];
        }

        if ($user->isTeacher() && ! ($class && $user->isHomeroom() && optional($user->teacher)->id === $class->homeroom_teacher_id)) {
            return [
                AttendanceRecord::SESSION_PERIOD => AttendanceRecord::SESSION_TYPES[AttendanceRecord::SESSION_PERIOD] ?? 'Theo tiết học',
            ];
        }

        return $types ?: $this->mainAttendanceSessionTypes();
    }

    private function mainAttendanceSessionTypes(): array
    {
        return [
            AttendanceRecord::SESSION_MORNING => AttendanceRecord::SESSION_TYPES[AttendanceRecord::SESSION_MORNING],
            AttendanceRecord::SESSION_AFTERNOON => AttendanceRecord::SESSION_TYPES[AttendanceRecord::SESSION_AFTERNOON],
        ];
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
                $subjectAbsent = $items
                    ->filter(fn (AttendanceRecord $record) => $record->status === 'absent'
                        && ($record->session_type === AttendanceRecord::SESSION_PERIOD || str_starts_with((string) $record->session_key, 'period:')))
                    ->count();
                $totalAbsentPeriods += $excused + $absent;
                $totalLate += $late;

                return [$day->toDateString() => [
                    'excused' => $excused,
                    'absent' => $absent,
                    'late' => $late,
                    'present' => $items->where('status', 'present')->count(),
                    'subject_absent' => $subjectAbsent,
                    'total' => $items->count(),
                ]];
            });

            return [
                'student' => $student,
                'cells' => $cells,
                'total_absent_periods' => $totalAbsentPeriods,
                'total_subject_absent_periods' => $cells->sum('subject_absent'),
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

        $homeroomClassIds = SchoolClass::where('homeroom_teacher_id', $user->teacher->id)
            ->pluck('id');

        if ($user->isHomeroom()) {
            return $homeroomClassIds
                ->filter()
                ->unique()
                ->values();
        }

        return $user->teacher->assignments()
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->pluck('class_id')
            ->merge($homeroomClassIds)
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

    private function selectedParentStudent($children): ?Student
    {
        if ($children->isEmpty()) {
            return null;
        }

        return $children->firstWhere('id', session('selected_parent_student_id')) ?: $children->first();
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

    private function teacherTimetableEntriesForDate($user, string $semesterId, string $attendanceDate, $classIds)
    {
        $dayOfWeek = \Illuminate\Support\Carbon::parse($attendanceDate)->isoWeekday();

        if ($dayOfWeek < 1 || $dayOfWeek > 6 || ! $user->teacher) {
            return collect();
        }

        $classIds = collect($classIds)->filter()->values();

        return TimetableEntry::with(['timetable.classRoom', 'subject', 'teacher', 'roomInfo'])
            ->where('teacher_id', $user->teacher->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', TimetableEntry::STATUS_ACTIVE)
            ->whereHas('timetable', function ($query) use ($semesterId, $classIds) {
                $query->where('semester_id', $semesterId)
                    ->when($classIds->isNotEmpty(), fn ($inner) => $inner->whereIn('class_id', $classIds));
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

    private function paginateSessions($records, Request $request, bool $periodMode = false): LengthAwarePaginator
    {
        $mainRecords = $records
            ->filter(fn (AttendanceRecord $record) => $periodMode
                ? ($record->session_type === AttendanceRecord::SESSION_PERIOD || str_starts_with((string) $record->session_key, 'period:'))
                : $this->normalizedMainSessionType($record) !== null)
            ->values();

        $rosters = $mainRecords->isEmpty()
            ? collect()
            : Student::query()
                ->whereIn('class_id', $mainRecords->pluck('class_id')->filter()->unique())
                ->where('status', Student::STATUS_STUDYING)
                ->orderBy('student_code')
                ->get()
                ->groupBy('class_id');
        $rosterCounts = $rosters->map->count();

        $sessions = $mainRecords
            ->groupBy(fn (AttendanceRecord $record) => implode('|', [
                $record->class_id,
                $record->semester_id ?: 'none',
                optional($record->attendance_date)->toDateString(),
                $periodMode ? ($record->session_key ?: 'period:' . $record->timetable_entry_id) : $this->normalizedMainSessionType($record),
            ]))
            ->map(function ($items, $key) use ($rosters, $rosterCounts, $periodMode) {
                $first = $items->first();
                $sessionType = $periodMode
                    ? AttendanceRecord::SESSION_PERIOD
                    : ($this->normalizedMainSessionType($first) ?: AttendanceRecord::SESSION_MORNING);
                $uniqueRecords = $items
                    ->sortByDesc(fn (AttendanceRecord $record) => optional($record->updated_at)->timestamp ?? optional($record->created_at)->timestamp ?? 0)
                    ->unique('student_id')
                    ->values();
                $counts = $uniqueRecords->countBy('status');
                $rosterTotal = (int) ($rosterCounts->get($first->class_id) ?: $uniqueRecords->count());
                $recordedTotal = $uniqueRecords->count();
                $recordsByStudent = $uniqueRecords->keyBy('student_id');
                $displayRecords = collect($rosters->get($first->class_id, collect()))
                    ->map(function (Student $student) use ($recordsByStudent, $first, $sessionType, $periodMode) {
                        $record = $recordsByStudent->get($student->id);
                        if ($record) {
                            return $record;
                        }

                        $record = new AttendanceRecord([
                            'student_id' => $student->id,
                            'class_id' => $first->class_id,
                            'semester_id' => $first->semester_id,
                            'attendance_date' => $first->attendance_date?->toDateString(),
                            'session_type' => $sessionType,
                            'session_key' => $periodMode ? ($first->session_key ?: 'period:' . $first->timetable_entry_id) : $sessionType,
                            'session_label' => $periodMode ? ($first->session_label ?: $first->displaySessionLabel()) : (AttendanceRecord::SESSION_TYPES[$sessionType] ?? ''),
                            'session_order' => $periodMode ? (int) ($first->session_order ?: 0) : ($sessionType === AttendanceRecord::SESSION_AFTERNOON ? 2 : 1),
                            'status' => 'present',
                        ]);
                        $record->setRelation('student', $student);

                        return $record;
                    })
                    ->values();

                return (object) [
                    'key' => md5($key),
                    'class_id' => $first->class_id,
                    'semester_id' => $first->semester_id,
                    'school_year_id' => $first->semester?->school_year_id ?? $first->classRoom?->school_year_id,
                    'date' => $first->attendance_date,
                    'session_type' => $sessionType,
                    'session_label' => $periodMode ? ($first->session_label ?: $first->displaySessionLabel()) : (AttendanceRecord::SESSION_TYPES[$sessionType] ?? $first->displaySessionLabel()),
                    'session_order' => $periodMode ? (int) ($first->session_order ?: 0) : ($sessionType === AttendanceRecord::SESSION_AFTERNOON ? 2 : 1),
                    'timetable_entry_id' => $periodMode ? $first->timetable_entry_id : null,
                    'class_name' => $first->classRoom->name ?? 'Không rõ',
                    'semester_name' => $first->semester->name ?? 'Không rõ',
                    'school_year_name' => $first->semester?->schoolYear?->name ?? $first->classRoom?->schoolYear?->name ?? 'Không rõ',
                    'total' => $rosterTotal,
                    'recorded_total' => $recordedTotal,
                    'present' => $counts->get('present', 0),
                    'late' => $counts->get('late', 0),
                    'excused' => $counts->get('excused', 0),
                    'absent' => $counts->get('absent', 0),
                    'is_completed' => $rosterTotal > 0 && $recordedTotal >= $rosterTotal,
                    'records' => $displayRecords->isNotEmpty() ? $displayRecords : $uniqueRecords
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

    private function normalizedMainSessionType(AttendanceRecord $record): ?string
    {
        foreach ([AttendanceRecord::SESSION_MORNING, AttendanceRecord::SESSION_AFTERNOON] as $sessionType) {
            if ($record->session_type === $sessionType || $record->session_key === $sessionType) {
                return $sessionType;
            }
        }

        return null;
    }

    private function sessionKey(string $sessionType, ?string $timetableEntryId = null): string
    {
        if ($sessionType === AttendanceRecord::SESSION_PERIOD && $timetableEntryId) {
            return 'period:' . $timetableEntryId;
        }

        if (in_array($sessionType, [AttendanceRecord::SESSION_MORNING, AttendanceRecord::SESSION_AFTERNOON], true)) {
            return $sessionType;
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

        if ($sessionType === AttendanceRecord::SESSION_MORNING) {
            return 'Điểm danh Buổi Sáng';
        }

        if ($sessionType === AttendanceRecord::SESSION_AFTERNOON) {
            return 'Điểm danh Buổi Chiều';
        }

        return 'Điểm danh theo ngày';
    }

    private function sessionOrder(string $sessionType, ?TimetableEntry $entry = null): int
    {
        if ($sessionType === AttendanceRecord::SESSION_MORNING) {
            return 1;
        }

        if ($sessionType === AttendanceRecord::SESSION_AFTERNOON) {
            return 2;
        }

        return $sessionType === AttendanceRecord::SESSION_PERIOD && $entry
            ? (int) $entry->period
            : 0;
    }
}
