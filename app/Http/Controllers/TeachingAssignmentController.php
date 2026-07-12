<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\Timetable;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeachingAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $this->effectiveSchoolYearId($request);
        $selectedSemesterId = $this->selectedSemesterId($request);
        $filters = [
            'class_id' => $request->query('class_id', 'all'),
            'teacher_id' => $request->query('teacher_id', 'all'),
            'role' => $request->query('role', 'all'),
            'status' => $request->query('status', 'all'),
        ];
        $readOnly = $this->isHistoricalReadOnly();

        $assignments = TeachingAssignment::with(['teacher.primarySubject', 'classRoom', 'subject', 'schoolYear', 'semester'])
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
            ->when($filters['class_id'] !== 'all', fn ($query) => $query->where('class_id', $filters['class_id']))
            ->when($filters['teacher_id'] !== 'all', fn ($query) => $query->where('teacher_id', $filters['teacher_id']))
            ->when($filters['role'] !== 'all', fn ($query) => $query->where('role', $filters['role']))
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->orderBy('school_year_id')
            ->orderBy('semester_id')
            ->orderBy('class_id')
            ->get();

        $deleteChecks = $assignments->mapWithKeys(fn (TeachingAssignment $assignment) => [
            (string) $assignment->getKey() => $this->deleteCheck($assignment),
        ]);

        return view('assignments.index', [
            'assignments' => $assignments,
            'teachers' => Teacher::with('primarySubject')->where('work_status', Teacher::STATUS_WORKING)->orderBy('name')->get(),
            'classes' => SchoolClass::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->orderBy('name')
                ->get(),
            'selectedYearId' => $selectedYearId,
            'selectedSemesterId' => $selectedSemesterId,
            'filters' => $filters,
            'readOnly' => $readOnly,
            'deleteChecks' => $deleteChecks,
        ]);
    }

    public function create()
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('assignments.index')->withErrors([
                'assignment' => 'Đang xem dữ liệu lịch sử, không thể thêm phân công.',
            ]);
        }

        return view('assignments.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->denyHistoricalWrite();
        $data = $this->validatedData($request);

        $assignment = TeachingAssignment::create($data);

        AuditLogger::log('teaching_assignment_created', TeachingAssignment::class, (string) $assignment->getKey(), 'Tạo phân công giảng dạy ' . $assignment->roleLabel());

        return redirect()
            ->route('assignments.index', ['school_year_id' => $assignment->school_year_id])
            ->with('success', 'Đã phân công giảng dạy.');
    }

    public function edit(TeachingAssignment $assignment)
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('assignments.index')->withErrors([
                'assignment' => 'Đang xem dữ liệu lịch sử, không thể chỉnh sửa phân công.',
            ]);
        }

        $assignment->load(['teacher', 'classRoom', 'subject', 'schoolYear', 'semester']);

        return view('assignments.edit', $this->formData($assignment) + [
            'assignment' => $assignment,
        ]);
    }

    public function update(Request $request, TeachingAssignment $assignment)
    {
        $this->denyHistoricalWrite();
        $assignment->load(['teacher', 'classRoom', 'subject', 'schoolYear', 'semester']);

        $oldTeacherId = $assignment->teacher_id;
        $oldRole = $assignment->roleLabel();
        $oldStatus = $assignment->status;
        $data = $this->validatedData($request, $assignment);

        $assignment->update($data);

        AuditLogger::log('teaching_assignment_updated', TeachingAssignment::class, (string) $assignment->getKey(), 'Sửa phân công giảng dạy');

        if ((string) $oldTeacherId !== (string) $assignment->teacher_id) {
            AuditLogger::log('teaching_assignment_teacher_changed', TeachingAssignment::class, (string) $assignment->getKey(), 'Đổi giáo viên phân công');
        }

        if ($oldRole !== $assignment->roleLabel()) {
            AuditLogger::log('teaching_assignment_role_changed', TeachingAssignment::class, (string) $assignment->getKey(), 'Đổi vai trò phân công sang ' . $assignment->roleLabel());
        }

        if ($oldStatus !== $assignment->status) {
            AuditLogger::log('teaching_assignment_status_changed', TeachingAssignment::class, (string) $assignment->getKey(), 'Đổi trạng thái phân công sang ' . $assignment->statusLabel());
        }

        return redirect()
            ->route('assignments.index', ['school_year_id' => $assignment->school_year_id])
            ->with('success', 'Đã cập nhật phân công.');
    }

    public function destroy(TeachingAssignment $assignment)
    {
        $this->denyHistoricalWrite();
        $deleteCheck = $this->deleteCheck($assignment);

        if (! $deleteCheck['allowed']) {
            return back()->withErrors(['assignment' => $deleteCheck['message']]);
        }

        $assignmentId = (string) $assignment->getKey();
        $assignment->delete();

        AuditLogger::log('teaching_assignment_deleted', TeachingAssignment::class, $assignmentId, 'Xóa phân công giảng dạy');

        return redirect()->route('assignments.index')->with('success', 'Đã xóa phân công.');
    }

    private function formData(?TeachingAssignment $assignment = null): array
    {
        $selectedYearId = $assignment?->school_year_id ?: $this->selectedSchoolYearId(request());
        $selectedSemesterId = $assignment?->semester_id ?: $this->selectedSemesterId(request());
        $activeYear = $selectedYearId ? SchoolYear::find($selectedYearId) : null;
        $activeSemester = $selectedSemesterId ? Semester::find($selectedSemesterId) : null;

        if ($activeSemester && $activeYear && (string) $activeSemester->school_year_id !== (string) $activeYear->getKey()) {
            $activeSemester = null;
        }

        $currentTeacherId = $assignment?->teacher_id;

        return [
            'years' => $activeYear ? collect([$activeYear]) : collect(),
            'semesters' => $activeSemester ? collect([$activeSemester]) : collect(),
            'classes' => SchoolClass::query()
                ->when($activeYear, fn ($query) => $query->where('school_year_id', $activeYear->getKey()))
                ->when($activeSemester, fn ($query) => $query->where('semester_id', $activeSemester->getKey()))
                ->where('status', SchoolClass::STATUS_ACTIVE)
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get(),
            'teachers' => Teacher::with('primarySubject')
                ->where(function ($query) use ($currentTeacherId) {
                    $query->where('work_status', Teacher::STATUS_WORKING)
                        ->when($currentTeacherId, fn ($teacherQuery) => $teacherQuery->orWhere('id', $currentTeacherId));
                })
                ->whereNotNull('primary_subject_id')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function validatedData(Request $request, ?TeachingAssignment $assignment = null): array
    {
        $rules = [
            'teacher_id' => ['required', 'exists:teachers,id'],
            'role' => ['required', Rule::in(array_keys(TeachingAssignment::ROLES))],
            'custom_role' => ['nullable', 'string', 'max:255', 'required_if:role,' . TeachingAssignment::ROLE_OTHER],
            'weekly_periods' => ['nullable', 'integer', 'min:1', 'max:20'],
            'note' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_keys(TeachingAssignment::STATUSES))],
        ];

        if (! $assignment) {
            $rules += [
                'school_year_id' => ['required', 'exists:school_years,id'],
                'semester_id' => ['required', 'exists:semesters,id'],
                'class_id' => ['required', 'exists:classes,id'],
            ];
        }

        $validated = $request->validate($rules, [
            'custom_role.required_if' => 'Vui lòng nhập vai trò khi chọn Khác.',
        ]);

        $teacher = Teacher::with('primarySubject')->findOrFail($validated['teacher_id']);
        if (! $teacher->primary_subject_id) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Giáo viên này chưa được cấu hình môn chính.',
            ]);
        }

        $data = $assignment
            ? array_merge($assignment->only(['school_year_id', 'semester_id', 'class_id']), $validated)
            : $validated;

        $data['subject_id'] = $teacher->primary_subject_id;
        $data['weekly_periods'] = $data['weekly_periods'] ?? null;

        $data['custom_role'] = $data['role'] === TeachingAssignment::ROLE_OTHER
            ? trim((string) ($data['custom_role'] ?? ''))
            : null;

        $this->validateBusinessRules($data, $assignment);

        if ($data['status'] === TeachingAssignment::STATUS_ARCHIVED) {
            $data['archived_at'] = $assignment?->archived_at ?? now();
        } else {
            $data['archived_at'] = null;
        }

        return $data;
    }

    private function validateBusinessRules(array $data, ?TeachingAssignment $assignment = null): void
    {
        $year = SchoolYear::findOrFail($data['school_year_id']);
        $semester = Semester::findOrFail($data['semester_id']);
        $class = SchoolClass::findOrFail($data['class_id']);
        $subject = Subject::findOrFail($data['subject_id']);
        $teacher = Teacher::findOrFail($data['teacher_id']);

        if (! $year->is_active || $year->isArchived()) {
            throw ValidationException::withMessages(['school_year_id' => 'Chỉ được phân công trong năm học đang hoạt động.']);
        }

        if (! $semester->isActive() || (string) $semester->school_year_id !== (string) $year->getKey()) {
            throw ValidationException::withMessages(['semester_id' => 'Chỉ được phân công trong học kỳ đang hoạt động của năm học hiện tại.']);
        }

        if (! $class->isActive() || $class->isArchived()) {
            throw ValidationException::withMessages(['class_id' => 'Chỉ được chọn lớp đang hoạt động.']);
        }

        if ((string) $class->school_year_id !== (string) $year->getKey() || (string) $class->semester_id !== (string) $semester->getKey()) {
            throw ValidationException::withMessages(['class_id' => 'Lớp không thuộc năm học và học kỳ đã chọn.']);
        }

        if ($subject->status !== Subject::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['subject_id' => 'Chỉ được chọn môn học đang hoạt động.']);
        }

        if (! $teacher->isWorking()) {
            throw ValidationException::withMessages(['teacher_id' => 'Chỉ được chọn giáo viên đang công tác.']);
        }

        if ((string) $teacher->primary_subject_id !== (string) $subject->getKey()) {
            throw ValidationException::withMessages(['teacher_id' => 'Giáo viên chỉ được phân công đúng môn chính đã cấu hình.']);
        }

        $duplicateQuery = TeachingAssignment::where('school_year_id', $data['school_year_id'])
            ->where('semester_id', $data['semester_id'])
            ->where('class_id', $data['class_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('teacher_id', $data['teacher_id'])
            ->where('role', $data['role'])
            ->where(function ($query) use ($data) {
                if ($data['role'] === TeachingAssignment::ROLE_OTHER) {
                    $query->where('custom_role', $data['custom_role']);
                } else {
                    $query->whereNull('custom_role')->orWhere('custom_role', '');
                }
            });

        if ($assignment) {
            $duplicateQuery->whereKeyNot($assignment->getKey());
        }

        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Phân công này đã tồn tại trong cùng năm học, học kỳ, lớp, môn, giáo viên và vai trò.',
            ]);
        }

        if ($data['role'] === TeachingAssignment::ROLE_PRIMARY) {
            $primaryQuery = TeachingAssignment::where('school_year_id', $data['school_year_id'])
                ->where('semester_id', $data['semester_id'])
                ->where('class_id', $data['class_id'])
                ->where('subject_id', $data['subject_id'])
                ->where('role', TeachingAssignment::ROLE_PRIMARY)
                ->where('status', '!=', TeachingAssignment::STATUS_ARCHIVED);

            if ($assignment) {
                $primaryQuery->whereKeyNot($assignment->getKey());
            }

            if ($primaryQuery->exists()) {
                throw ValidationException::withMessages([
                    'role' => 'Trong cùng năm học, học kỳ, lớp và môn chỉ được có một giáo viên giảng dạy chính.',
                ]);
            }
        }
    }

    private function deleteCheck(TeachingAssignment $assignment): array
    {
        if ($reason = $this->businessDataBlockReason($assignment)) {
            return [
                'allowed' => false,
                'message' => 'Không thể xóa phân công vì đã phát sinh ' . $reason . '.',
            ];
        }

        return [
            'allowed' => true,
            'message' => null,
        ];
    }

    private function businessDataBlockReason(TeachingAssignment $assignment): ?string
    {
        if ($this->hasTimetableData($assignment)) {
            return 'thời khóa biểu';
        }

        if ($this->hasScoreData($assignment)) {
            return 'điểm số';
        }

        if ($this->hasAttendanceData($assignment)) {
            return 'điểm danh';
        }

        return null;
    }

    private function hasTimetableData(TeachingAssignment $assignment): bool
    {
        if (! Schema::hasTable('timetables') || ! Schema::hasTable('timetable_entries')) {
            return false;
        }

        $timetableIds = Timetable::where('school_year_id', $assignment->school_year_id)
            ->where('semester_id', $assignment->semester_id)
            ->where('class_id', $assignment->class_id)
            ->pluck('id');

        return $timetableIds->isNotEmpty()
            && \App\Models\TimetableEntry::whereIn('timetable_id', $timetableIds)
                ->where('subject_id', $assignment->subject_id)
                ->exists();
    }

    private function hasScoreData(TeachingAssignment $assignment): bool
    {
        if (! Schema::hasTable('score_headers')) {
            return false;
        }

        $studentIds = Student::where('class_id', $assignment->class_id)->pluck('id');

        return $studentIds->isNotEmpty()
            && ScoreHeader::whereIn('student_id', $studentIds)
                ->where('school_year_id', $assignment->school_year_id)
                ->where('semester_id', $assignment->semester_id)
                ->where('subject_id', $assignment->subject_id)
                ->exists();
    }

    private function hasAttendanceData(TeachingAssignment $assignment): bool
    {
        return Schema::hasTable('attendance_records')
            && AttendanceRecord::where('class_id', $assignment->class_id)
                ->where('semester_id', $assignment->semester_id)
                ->exists();
    }

    private function effectiveSchoolYearId(Request $request): ?string
    {
        return $this->selectedSchoolYearId($request);
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi phân công giảng dạy.',
            ]);
        }
    }
}
