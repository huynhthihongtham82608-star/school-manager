<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\TeachingAssignment;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TimetableController extends Controller
{
    private const DAYS = [
        1 => 'Thứ 2',
        2 => 'Thứ 3',
        3 => 'Thứ 4',
        4 => 'Thứ 5',
        5 => 'Thứ 6',
        6 => 'Thứ 7',
    ];

    private const PERIODS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    private const PERIOD_GROUPS = [
        'morning' => [
            'label' => 'Buổi sáng',
            'periods' => [
                1 => 'Tiết 1',
                2 => 'Tiết 2',
                3 => 'Tiết 3',
                4 => 'Tiết 4',
                5 => 'Tiết 5',
            ],
        ],
        'afternoon' => [
            'label' => 'Buổi chiều',
            'periods' => [
                6 => 'Tiết 1',
                7 => 'Tiết 2',
                8 => 'Tiết 3',
                9 => 'Tiết 4',
                10 => 'Tiết 5',
            ],
        ],
    ];

    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedYearId = $this->effectiveSchoolYearId($request);
        $selectedSemesterId = $this->selectedSemesterId($request);
        $classesQuery = SchoolClass::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderBy('name');

        if ($user->isStudent() && $user->student?->class_id) {
            $classesQuery->whereKey($user->student->class_id);
        } elseif ($user->isParent() && $user->parentProfile) {
            $children = $user->parentProfile->students()->with('classRoom')->orderBy('student_code')->get();
            $child = $children->firstWhere('id', session('selected_parent_student_id')) ?: $children->first();
            $classesQuery->whereKey($child?->class_id);
        }

        $classes = $classesQuery->get();
        $semesters = Semester::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderBy('name')
            ->get();

        $selectedClass = null;
        $selectedSemester = null;
        $timetable = null;
        $entries = collect();

        if ($user->isStudent() && $user->student) {
            $selectedClass = $user->student->classRoom;
        } elseif ($user->isParent() && $user->parentProfile) {
            $children = $user->parentProfile->students()->with('classRoom')->orderBy('student_code')->get();
            $child = $children->firstWhere('id', session('selected_parent_student_id')) ?: $children->first();
            $selectedClass = $child?->classRoom;
        } elseif ($user->isTeacher() && $user->teacher) {
            return $this->teacherView();
        } elseif ($request->filled('class_id')) {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
            ]);

            $selectedClass = SchoolClass::find($request->input('class_id'));
        }

        if ($selectedClass && $selectedSemesterId) {
            $selectedSemester = Semester::find($selectedSemesterId);

            if ($selectedSemester) {
                $timetable = Timetable::where('class_id', $selectedClass->id)
                    ->where('semester_id', $selectedSemester->id)
                    ->first();

                if ($timetable) {
                    $entries = TimetableEntry::where('timetable_id', $timetable->id)
                        ->where('status', '!=', TimetableEntry::STATUS_ARCHIVED)
                        ->with(['assignment.subject', 'assignment.teacher', 'subject', 'teacher', 'roomInfo'])
                        ->get()
                        ->keyBy(fn ($entry) => $entry->day_of_week . '-' . $entry->period);
                }
            }
        }

        return view('timetables.index', [
            'classes' => $classes,
            'semesters' => $semesters,
            'selectedClass' => $selectedClass,
            'selectedSemester' => $selectedSemester,
            'timetable' => $timetable,
            'entries' => $entries,
            'days' => self::DAYS,
            'periods' => self::PERIODS,
            'periodGroups' => self::PERIOD_GROUPS,
            'selectedYearId' => $selectedYearId,
            'selectedSemesterId' => $selectedSemesterId,
        ]);
    }

    public function manage(Request $request)
    {
        $selectedYearId = $this->effectiveSchoolYearId($request);
        $selectedSemesterId = $this->selectedSemesterId($request);
        $readOnly = $this->isHistoricalReadOnly();
        $years = $readOnly
            ? SchoolYear::whereKey($selectedYearId)->get()
            : SchoolYear::where('is_active', true)->whereNull('archived_at')->orderByDesc('start_date')->get();
        $semesters = Semester::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when(! $readOnly, fn ($query) => $query->where('status', '!=', Semester::STATUS_ARCHIVED))
            ->when(! $readOnly, fn ($query) => $query->whereNull('archived_at'))
            ->orderBy('name')
            ->get();
        $classes = SchoolClass::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when(! $readOnly, fn ($query) => $query->where('status', SchoolClass::STATUS_ACTIVE))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $selectedClass = null;
        $selectedSemester = null;
        $timetable = null;
        $entries = collect();
        $assignments = collect();
        $cloneTargetSemesters = collect();
        $rooms = $readOnly ? Room::orderBy('name')->get() : Room::where('status', Room::STATUS_ACTIVE)->orderBy('name')->get();

        if ($request->filled('class_id') && $selectedSemesterId) {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
            ]);

            $selectedClass = SchoolClass::find($request->input('class_id'));
            $selectedSemester = Semester::find($selectedSemesterId);
            $this->validateSelection($selectedClass, $selectedSemester, $readOnly);

            $timetableQuery = Timetable::where('class_id', $selectedClass->id)
                ->where('semester_id', $selectedSemester->id);

            $timetable = $readOnly
                ? $timetableQuery->first()
                : Timetable::firstOrCreate([
                    'class_id' => $selectedClass->id,
                    'semester_id' => $selectedSemester->id,
                ], [
                    'school_year_id' => $selectedSemester->school_year_id,
                ]);

            $assignments = $this->activeAssignmentsFor($selectedClass, $selectedSemester);

            if ($timetable) {
                $entries = TimetableEntry::where('timetable_id', $timetable->id)
                    ->with(['assignment.subject.periodNorms', 'assignment.teacher', 'subject', 'teacher', 'roomInfo'])
                    ->get()
                    ->keyBy(fn ($entry) => $entry->day_of_week . '-' . $entry->period);
            }

            $cloneTargetSemesters = $semesters->filter(function (Semester $semester) use ($selectedSemester) {
                return $selectedSemester
                    && $this->semesterTermIndex($selectedSemester) === 1
                    && $this->semesterTermIndex($semester) === 2
                    && (string) $semester->school_year_id === (string) $selectedSemester->school_year_id
                    && ! $semester->isLocked()
                    && ! $semester->isArchived();
            });
        }

        return view('timetables.manage', [
            'years' => $years,
            'classes' => $classes,
            'semesters' => $semesters,
            'selectedClass' => $selectedClass,
            'selectedSemester' => $selectedSemester,
            'timetable' => $timetable,
            'entries' => $entries,
            'assignments' => $assignments,
            'selectedYearId' => $selectedYearId,
            'selectedSemesterId' => $selectedSemesterId,
            'readOnly' => $readOnly,
            'days' => self::DAYS,
            'periods' => self::PERIODS,
            'periodGroups' => self::PERIOD_GROUPS,
            'statuses' => TimetableEntry::STATUSES,
            'cloneTargetSemesters' => $cloneTargetSemesters,
            'rooms' => $rooms,
        ]);
    }

    public function saveEntries(Request $request)
    {
        $this->denyHistoricalWrite();

        $data = $request->validate([
            'timetable_id' => 'required|exists:timetables,id',
            'entries' => 'array',
        ]);

        $timetable = Timetable::with(['semester', 'classRoom'])->findOrFail($data['timetable_id']);
        $this->ensureTimetableEditable($timetable);
        $slots = $this->validatedSlotsForTimetable($request, $timetable);

        DB::transaction(function () use ($slots, $timetable) {
            foreach ($slots as $slot) {
                $existing = $slot['existing'];

                if (! $slot['assignment']) {
                    if ($existing) {
                        $this->deleteEntryOrFail($existing);
                    }

                    continue;
                }

                $assignment = $slot['assignment'];
                $payload = [
                    'assignment_id' => $assignment->id,
                    'subject_id' => $assignment->subject_id,
                    'teacher_id' => $assignment->teacher_id,
                    'room' => $slot['room']?->name,
                    'room_id' => $slot['room']?->id,
                    'note' => $assignment->roleLabel(),
                    'status' => $slot['status'],
                    'archived_at' => $slot['status'] === TimetableEntry::STATUS_ARCHIVED ? ($existing?->archived_at ?? now()) : null,
                ];

                $entry = TimetableEntry::updateOrCreate([
                    'timetable_id' => $timetable->id,
                    'day_of_week' => $slot['day'],
                    'period' => $slot['period'],
                ], $payload);

                $action = $entry->wasRecentlyCreated ? 'timetable_entry_created' : 'timetable_entry_updated';
                $description = ($entry->wasRecentlyCreated ? 'Tạo' : 'Sửa')
                    . ' ' . self::periodDisplayLabel((int) $slot['period'])
                    . ' ' . (self::DAYS[$slot['day']] ?? $slot['day'])
                    . ' lớp ' . ($timetable->classRoom->name ?? '');

                AuditLogger::log($action, TimetableEntry::class, (string) $entry->getKey(), $description);
            }
        });

        return back()->with('success', 'Đã lưu thời khóa biểu.');
    }

    public function clone(Request $request)
    {
        $this->denyHistoricalWrite();

        $data = $request->validate([
            'source_class_id' => ['required', 'exists:classes,id'],
            'source_semester_id' => ['required', 'exists:semesters,id'],
            'target_semester_id' => ['required', 'exists:semesters,id'],
        ]);

        $sourceClass = SchoolClass::findOrFail($data['source_class_id']);
        $sourceSemester = Semester::findOrFail($data['source_semester_id']);
        $targetSemester = Semester::findOrFail($data['target_semester_id']);

        $this->validateSemesterClone($sourceClass, $sourceSemester, $targetSemester);

        $sourceTimetable = Timetable::where('class_id', $sourceClass->id)
            ->where('semester_id', $sourceSemester->id)
            ->with(['entries.assignment.subject.periodNorms', 'entries.roomInfo'])
            ->first();

        if (! $sourceTimetable) {
            return back()->withErrors(['clone' => 'Không tìm thấy thời khóa biểu nguồn để sao chép.']);
        }

        $targetTimetable = Timetable::firstOrCreate([
            'class_id' => $sourceClass->id,
            'semester_id' => $targetSemester->id,
        ], [
            'school_year_id' => $targetSemester->school_year_id,
        ]);

        $created = DB::transaction(function () use ($sourceTimetable, $targetTimetable, $sourceClass, $targetSemester) {
            $count = 0;
            $assignmentCounts = [];
            $pendingEntries = [];
            $pendingSlotKeys = [];

            foreach ($sourceTimetable->entries as $sourceEntry) {
                if ($sourceEntry->isArchived() || ! $sourceEntry->assignment) {
                    continue;
                }

                $targetAssignment = TeachingAssignment::with(['subject.periodNorms', 'teacher'])
                    ->where('school_year_id', $targetSemester->school_year_id)
                    ->where('semester_id', $targetSemester->id)
                    ->where('class_id', $sourceClass->id)
                    ->where('subject_id', $sourceEntry->assignment->subject_id)
                    ->where('role', $sourceEntry->assignment->role)
                    ->where('status', TeachingAssignment::STATUS_ACTIVE)
                    ->whereHas('teacher', fn ($query) => $query->where('work_status', \App\Models\Teacher::STATUS_WORKING))
                    ->first();

                if (! $targetAssignment) {
                    continue;
                }

                $this->ensureSubjectPeriodNormConfigured($targetAssignment, $sourceClass);
                $existingTargetEntry = TimetableEntry::where('timetable_id', $targetTimetable->id)
                    ->where('day_of_week', $sourceEntry->day_of_week)
                    ->where('period', $sourceEntry->period)
                    ->first();

                if ($sourceEntry->status === TimetableEntry::STATUS_ACTIVE) {
                    $this->ensureTeacherAvailable($targetAssignment, $targetTimetable, (int) $sourceEntry->day_of_week, (int) $sourceEntry->period, $existingTargetEntry);
                    $this->ensureRoomAvailable($sourceEntry->roomInfo?->isActive() ? $sourceEntry->roomInfo : null, $targetTimetable, (int) $sourceEntry->day_of_week, (int) $sourceEntry->period, $existingTargetEntry);
                }

                $pendingEntries[] = [$sourceEntry, $targetAssignment];
                $pendingSlotKeys[] = $sourceEntry->day_of_week . '-' . $sourceEntry->period;
            }

            TimetableEntry::where('timetable_id', $targetTimetable->id)
                ->where('status', '!=', TimetableEntry::STATUS_ARCHIVED)
                ->get()
                ->each(function (TimetableEntry $entry) use (&$assignmentCounts, $pendingSlotKeys) {
                    $slotKey = $entry->day_of_week . '-' . $entry->period;
                    if (in_array($slotKey, $pendingSlotKeys, true)) {
                        return;
                    }

                    if ($entry->assignment_id) {
                        $assignmentCounts[$entry->assignment_id] = ($assignmentCounts[$entry->assignment_id] ?? 0) + 1;
                    }
                });

            foreach ($pendingEntries as [$sourceEntry, $targetAssignment]) {
                if ($sourceEntry->status !== TimetableEntry::STATUS_ARCHIVED) {
                    $assignmentCounts[$targetAssignment->id] = ($assignmentCounts[$targetAssignment->id] ?? 0) + 1;
                }
            }

            $this->ensureAssignmentWeeklyPeriodLimits($assignmentCounts);

            foreach ($pendingEntries as [$sourceEntry, $targetAssignment]) {
                TimetableEntry::updateOrCreate([
                    'timetable_id' => $targetTimetable->id,
                    'day_of_week' => $sourceEntry->day_of_week,
                    'period' => $sourceEntry->period,
                ], [
                    'assignment_id' => $targetAssignment->id,
                    'subject_id' => $targetAssignment->subject_id,
                    'teacher_id' => $targetAssignment->teacher_id,
                    'room' => $sourceEntry->roomInfo?->isActive() ? $sourceEntry->roomInfo->name : null,
                    'room_id' => $sourceEntry->roomInfo?->isActive() ? $sourceEntry->roomInfo->id : null,
                    'note' => $targetAssignment->roleLabel(),
                    'status' => $sourceEntry->status,
                    'archived_at' => null,
                ]);

                $count++;
            }

            AuditLogger::log('timetable_cloned', Timetable::class, (string) $targetTimetable->getKey(), 'Clone thời khóa biểu HK1 sang HK2 cùng lớp: ' . $count . ' tiết');

            return $count;
        });

        return redirect()
            ->route('timetable.manage', ['class_id' => $sourceClass->id, 'semester_id' => $targetSemester->id, 'school_year_id' => $targetSemester->school_year_id])
            ->with('success', 'Đã clone ' . $created . ' tiết học sang học kỳ 2.');
    }

    protected function teacherView()
    {
        $teacherId = optional(Auth::user()->teacher)->id;
        if (! $teacherId) {
            abort(403);
        }

        $entries = TimetableEntry::with(['timetable.classRoom', 'assignment.subject', 'subject', 'roomInfo'])
            ->where('teacher_id', $teacherId)
            ->where('status', TimetableEntry::STATUS_ACTIVE)
            ->get()
            ->sortBy(fn ($entry) => sprintf('%d-%02d', (int) $entry->day_of_week, (int) $entry->period))
            ->values();

        return view('timetables.teacher', [
            'entries' => $entries,
            'dayMap' => self::DAYS + [7 => 'CN'],
        ]);
    }

    public static function periodSessionLabel(int $period): string
    {
        return $period <= 5 ? 'Buổi sáng' : 'Buổi chiều';
    }

    public static function periodNumberInSession(int $period): int
    {
        return $period <= 5 ? $period : $period - 5;
    }

    public static function periodDisplayLabel(int $period): string
    {
        return self::periodSessionLabel($period) . ' - Tiết ' . self::periodNumberInSession($period);
    }

    private function activeAssignmentsFor(SchoolClass $class, Semester $semester): Collection
    {
        return TeachingAssignment::with(['subject.periodNorms', 'teacher', 'classRoom'])
            ->where('school_year_id', $semester->school_year_id)
            ->where('semester_id', $semester->id)
            ->where('class_id', $class->id)
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->whereHas('teacher', fn ($query) => $query->where('work_status', \App\Models\Teacher::STATUS_WORKING))
            ->orderBy('role')
            ->get();
    }

    private function validateSelection(SchoolClass $class, Semester $semester, bool $readOnly): void
    {
        if ((string) $class->school_year_id !== (string) $semester->school_year_id) {
            throw ValidationException::withMessages([
                'class_id' => 'Lớp không thuộc năm học của học kỳ đã chọn.',
            ]);
        }

        if ($readOnly) {
            return;
        }

        if ($semester->isLocked() || $semester->isArchived()) {
            throw ValidationException::withMessages([
                'semester_id' => 'Không thể quản lý thời khóa biểu trong học kỳ đã khóa hoặc lưu trữ.',
            ]);
        }

        if (! $class->isActive() || $class->isArchived()) {
            throw ValidationException::withMessages([
                'class_id' => 'Chỉ được quản lý thời khóa biểu cho lớp đang hoạt động.',
            ]);
        }
    }

    private function ensureTimetableEditable(Timetable $timetable): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'timetable' => 'Đang xem dữ liệu lịch sử, không thể thay đổi thời khóa biểu.',
            ]);
        }

        if ($timetable->semester?->isLocked() || $timetable->semester?->isArchived()) {
            throw ValidationException::withMessages([
                'timetable' => 'Không thể chỉnh sửa thời khóa biểu trong học kỳ đã khóa hoặc lưu trữ.',
            ]);
        }
    }

    private function validatedSlotsForTimetable(Request $request, Timetable $timetable): array
    {
        $slots = [];
        $assignmentCounts = [];

        foreach (self::DAYS as $day => $dayLabel) {
            foreach (self::PERIODS as $period) {
                $slot = $request->input("entries.$day.$period", []);
                $assignmentId = $slot['assignment_id'] ?? null;
                $roomId = $slot['room_id'] ?? null;
                $status = $slot['status'] ?? TimetableEntry::STATUS_ACTIVE;

                if (! array_key_exists($status, TimetableEntry::STATUSES)) {
                    throw ValidationException::withMessages([
                        "entries.$day.$period.status" => 'Trạng thái tiết học không hợp lệ.',
                    ]);
                }

                $existing = TimetableEntry::where('timetable_id', $timetable->id)
                    ->where('day_of_week', $day)
                    ->where('period', $period)
                    ->first();

                $assignment = null;
                $room = null;
                if ($assignmentId) {
                    $assignment = $this->validatedAssignmentForTimetable($assignmentId, $timetable);
                    $room = $this->validatedRoomForTimetable($roomId);

                    if ($status !== TimetableEntry::STATUS_ARCHIVED) {
                        $assignmentCounts[$assignment->id] = ($assignmentCounts[$assignment->id] ?? 0) + 1;
                    }

                    if ($status === TimetableEntry::STATUS_ACTIVE) {
                        $this->ensureTeacherAvailable($assignment, $timetable, $day, $period, $existing);
                        $this->ensureRoomAvailable($room, $timetable, $day, $period, $existing);
                    }
                }

                $slots[] = [
                    'day' => $day,
                    'period' => $period,
                    'room' => $room,
                    'status' => $status,
                    'existing' => $existing,
                    'assignment' => $assignment,
                ];
            }
        }

        $this->ensureAssignmentWeeklyPeriodLimits($assignmentCounts);

        return $slots;
    }

    private function validatedAssignmentForTimetable(string $assignmentId, Timetable $timetable): TeachingAssignment
    {
        $assignment = TeachingAssignment::with(['subject.periodNorms', 'teacher'])->findOrFail($assignmentId);

        if (
            (string) $assignment->school_year_id !== (string) $timetable->school_year_id
            || (string) $assignment->semester_id !== (string) $timetable->semester_id
            || (string) $assignment->class_id !== (string) $timetable->class_id
            || $assignment->status !== TeachingAssignment::STATUS_ACTIVE
            || ! $assignment->teacher?->isWorking()
        ) {
            throw ValidationException::withMessages([
                'assignment_id' => 'Phân công không hợp lệ hoặc không còn hoạt động.',
            ]);
        }

        $timetable->loadMissing('classRoom');
        $this->ensureSubjectPeriodNormConfigured($assignment, $timetable->classRoom);

        return $assignment;
    }

    private function validatedRoomForTimetable(?string $roomId): ?Room
    {
        if (! $roomId) {
            return null;
        }

        $room = Room::findOrFail($roomId);

        if (! $room->isActive()) {
            throw ValidationException::withMessages([
                'room_id' => 'Chỉ được chọn phòng học đang hoạt động.',
            ]);
        }

        return $room;
    }

    private function ensureSubjectPeriodNormConfigured(TeachingAssignment $assignment, SchoolClass $class): void
    {
        $assignment->loadMissing('subject.periodNorms');

        if ((int) ($assignment->effectiveWeeklyPeriods() ?: 0) > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'assignment_id' => 'Môn học này chưa cấu hình định mức tiết/tuần cho khối ' . $class->grade_level . ', hoặc phân công chưa có giá trị điều chỉnh.',
        ]);
    }

    private function ensureAssignmentWeeklyPeriodLimits(array $assignmentCounts): void
    {
        if (! $assignmentCounts) {
            return;
        }

        $assignments = TeachingAssignment::with(['subject.periodNorms', 'classRoom'])
            ->whereIn('id', array_keys($assignmentCounts))
            ->get()
            ->keyBy('id');

        foreach ($assignmentCounts as $assignmentId => $count) {
            $assignment = $assignments[$assignmentId] ?? null;
            $limit = (int) ($assignment?->effectiveWeeklyPeriods() ?: 0);

            if ($limit <= 0) {
                $assignment?->loadMissing(['subject', 'classRoom']);

                throw ValidationException::withMessages([
                    'assignment_id' => 'Phân công ' . ($assignment?->subject?->name ?? 'môn học') . ' lớp ' . ($assignment?->classRoom?->name ?? '') . ' chưa có định mức tiết/tuần.',
                ]);
            }

            if ($count > $limit) {
                throw ValidationException::withMessages([
                    'assignment_id' => 'Phân công ' . ($assignment?->subject?->name ?? 'môn học') . ' lớp ' . ($assignment?->classRoom?->name ?? '') . ' chỉ được xếp tối đa ' . $limit . ' tiết/tuần. Hiện đang xếp ' . $count . '/' . $limit . ' tiết.',
                ]);
            }
        }
    }

    private function ensureTeacherAvailable(TeachingAssignment $assignment, Timetable $timetable, int $day, int $period, ?TimetableEntry $currentEntry): void
    {
        $conflict = TimetableEntry::where('teacher_id', $assignment->teacher_id)
            ->where('day_of_week', $day)
            ->where('period', $period)
            ->where('status', TimetableEntry::STATUS_ACTIVE)
            ->whereHas('timetable', function ($query) use ($timetable) {
                $query->where('school_year_id', $timetable->school_year_id)
                    ->where('semester_id', $timetable->semester_id);
            })
            ->when($currentEntry, fn ($query) => $query->whereKeyNot($currentEntry->getKey()))
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'teacher_conflict' => 'Giáo viên ' . ($assignment->teacher->name ?? '') . ' đã có lịch dạy ở ' . (self::DAYS[$day] ?? $day) . ', ' . self::periodDisplayLabel($period) . '.',
            ]);
        }
    }

    private function ensureRoomAvailable(?Room $room, Timetable $timetable, int $day, int $period, ?TimetableEntry $currentEntry): void
    {
        if (! $room) {
            return;
        }

        $conflict = TimetableEntry::where('room_id', $room->id)
            ->where('day_of_week', $day)
            ->where('period', $period)
            ->where('status', TimetableEntry::STATUS_ACTIVE)
            ->whereHas('timetable', function ($query) use ($timetable) {
                $query->where('school_year_id', $timetable->school_year_id)
                    ->where('semester_id', $timetable->semester_id);
            })
            ->when($currentEntry, fn ($query) => $query->whereKeyNot($currentEntry->getKey()))
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'room_id' => 'Phòng ' . $room->name . ' đã có lịch học ở ' . (self::DAYS[$day] ?? $day) . ', ' . self::periodDisplayLabel($period) . '.',
            ]);
        }
    }

    private function validateSemesterClone(SchoolClass $class, Semester $sourceSemester, Semester $targetSemester): void
    {
        if ((string) $sourceSemester->school_year_id !== (string) $targetSemester->school_year_id) {
            throw ValidationException::withMessages([
                'target_semester_id' => 'Chỉ được clone thời khóa biểu trong cùng một năm học.',
            ]);
        }

        if ((string) $class->school_year_id !== (string) $sourceSemester->school_year_id) {
            throw ValidationException::withMessages([
                'source_class_id' => 'Lớp không thuộc năm học của học kỳ nguồn.',
            ]);
        }

        if ($this->semesterTermIndex($sourceSemester) !== 1 || $this->semesterTermIndex($targetSemester) !== 2) {
            throw ValidationException::withMessages([
                'target_semester_id' => 'Chỉ hỗ trợ clone thời khóa biểu từ Học kỳ 1 sang Học kỳ 2 cùng lớp.',
            ]);
        }

        if ($targetSemester->isLocked() || $targetSemester->isArchived()) {
            throw ValidationException::withMessages([
                'target_semester_id' => 'Không thể clone vào học kỳ đã khóa hoặc lưu trữ.',
            ]);
        }

        if (! $class->isActive() || $class->isArchived()) {
            throw ValidationException::withMessages([
                'source_class_id' => 'Chỉ được clone thời khóa biểu cho lớp đang hoạt động.',
            ]);
        }
    }

    private function semesterTermIndex(Semester $semester): int
    {
        $name = mb_strtolower((string) $semester->name);

        if (str_contains($name, '1') || (int) $semester->order === 1) {
            return 1;
        }

        return 2;
    }

    private function deleteEntryOrFail(TimetableEntry $entry): void
    {
        if ($reason = $this->entryBusinessDataBlockReason($entry)) {
            throw ValidationException::withMessages([
                'delete_entry' => 'Không thể xóa tiết học vì đã phát sinh ' . $reason . '.',
            ]);
        }

        $entryId = (string) $entry->getKey();
        $entry->delete();
        AuditLogger::log('timetable_entry_deleted', TimetableEntry::class, $entryId, 'Xóa tiết học khỏi thời khóa biểu');
    }

    private function entryBusinessDataBlockReason(TimetableEntry $entry): ?string
    {
        $entry->loadMissing('timetable');

        if ($this->hasAttendanceData($entry)) {
            return 'điểm danh';
        }

        if ($this->hasLearningLogData($entry)) {
            return 'nhật ký học tập';
        }

        return null;
    }

    private function hasAttendanceData(TimetableEntry $entry): bool
    {
        return Schema::hasTable('attendance_records')
            && AttendanceRecord::where('class_id', $entry->timetable->class_id)
                ->where('semester_id', $entry->timetable->semester_id)
                ->exists();
    }

    private function hasLearningLogData(TimetableEntry $entry): bool
    {
        if (! Schema::hasTable('learning_logs')) {
            return false;
        }

        if (Schema::hasColumn('learning_logs', 'timetable_entry_id')) {
            return DB::table('learning_logs')->where('timetable_entry_id', $entry->id)->exists();
        }

        if (
            Schema::hasColumn('learning_logs', 'class_id')
            && Schema::hasColumn('learning_logs', 'semester_id')
            && Schema::hasColumn('learning_logs', 'subject_id')
        ) {
            return DB::table('learning_logs')
                ->where('class_id', $entry->timetable->class_id)
                ->where('semester_id', $entry->timetable->semester_id)
                ->where('subject_id', $entry->subject_id)
                ->exists();
        }

        return false;
    }

    private function effectiveSchoolYearId(Request $request): ?string
    {
        return $this->selectedSchoolYearId($request);
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi thời khóa biểu.',
            ]);
        }
    }
}
