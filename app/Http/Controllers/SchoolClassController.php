<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Conduct;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentClassAssignment;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolClassController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $this->effectiveSchoolYearId($request);
        $selectedGrade = $request->query('grade_level', 'all');
        $readOnly = $this->isHistoricalReadOnly();

        $classes = SchoolClass::with(['schoolYear', 'semester', 'homeroomTeacher', 'students'])
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when(in_array($selectedGrade, ['10', '11', '12'], true), fn ($query) => $query->where('grade_level', $selectedGrade))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $deleteChecks = $classes->mapWithKeys(fn (SchoolClass $class) => [
            (string) $class->getKey() => $this->deleteCheck($class),
        ]);
        $classStudents = Student::with('classRoom')
            ->whereIn('class_id', $classes->pluck('id'))
            ->orderBy('student_code')
            ->get()
            ->groupBy('class_id');
        $unassignedStudents = $selectedYearId
            ? Student::where('school_year_id', $selectedYearId)
                ->whereDoesntHave('classAssignments', fn ($query) => $query
                    ->where('academic_year_id', $selectedYearId)
                    ->where('status', StudentClassAssignment::STATUS_ACTIVE))
                ->orderBy('student_code')
                ->get()
            : collect();
        $transferClasses = $selectedYearId
            ? SchoolClass::with(['schoolYear', 'semester'])
                ->where('school_year_id', $selectedYearId)
                ->where('status', '!=', SchoolClass::STATUS_ARCHIVED)
                ->where('status', '!=', SchoolClass::STATUS_LOCKED)
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get()
            : collect();

        return view('classes.index', [
            'classes' => $classes,
            'selectedYearId' => $selectedYearId,
            'selectedGrade' => $selectedGrade,
            'readOnly' => $readOnly,
            'deleteChecks' => $deleteChecks,
            'classStudents' => $classStudents,
            'unassignedStudents' => $unassignedStudents,
            'transferClasses' => $transferClasses,
        ]);
    }

    public function create()
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('classes.index')->withErrors([
                'class' => 'Đang xem dữ liệu lịch sử, không thể thêm lớp học.',
            ]);
        }

        return view('classes.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->denyHistoricalWrite();
        $data = $this->validatedData($request);

        $class = SchoolClass::create($data + [
            'status' => SchoolClass::STATUS_DRAFT,
        ]);

        $this->syncHomeroomTeacherFlags($class);

        AuditLogger::log('class_created', SchoolClass::class, (string) $class->getKey(), 'Tạo lớp học ' . $class->name);

        return redirect()
            ->route('classes.index', ['school_year_id' => $class->school_year_id])
            ->with('success', 'Đã tạo lớp học.');
    }

    public function edit(SchoolClass $class)
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('classes.index')->withErrors([
                'class' => 'Đang xem dữ liệu lịch sử, không thể chỉnh sửa lớp học.',
            ]);
        }

        if (! $class->canEdit()) {
            return redirect()->route('classes.index', ['school_year_id' => $class->school_year_id])->withErrors([
                'class' => 'Lớp học đã khóa hoặc lưu trữ, chỉ được xem.',
            ]);
        }

        return view('classes.edit', $this->formData($class) + ['class' => $class]);
    }

    public function update(Request $request, SchoolClass $class)
    {
        $this->denyHistoricalWrite();

        if (! $class->canEdit()) {
            return back()->withErrors(['class' => 'Lớp học đã khóa hoặc lưu trữ, không thể chỉnh sửa.']);
        }

        $oldTeacherId = $class->homeroom_teacher_id;
        $data = $this->validatedData($request, $class);

        if ((int) $data['capacity'] < $class->currentStudentCount()) {
            throw ValidationException::withMessages([
                'capacity' => 'Sức chứa tối đa không được nhỏ hơn sĩ số hiện tại.',
            ]);
        }

        $class->update($data);
        $this->syncHomeroomTeacherFlags($class, $oldTeacherId ? (string) $oldTeacherId : null);

        AuditLogger::log('class_updated', SchoolClass::class, (string) $class->getKey(), 'Chỉnh sửa lớp học ' . $class->name);

        if ((string) $oldTeacherId !== (string) $class->homeroom_teacher_id) {
            AuditLogger::log('class_homeroom_teacher_changed', SchoolClass::class, (string) $class->getKey(), 'Đổi giáo viên chủ nhiệm lớp ' . $class->name);
        }

        return redirect()
            ->route('classes.index', ['school_year_id' => $class->school_year_id])
            ->with('success', 'Đã cập nhật lớp học.');
    }

    public function activate(SchoolClass $class)
    {
        $this->denyHistoricalWrite();

        if ($class->schoolYear?->isArchived() || $class->semester?->isArchived() || $class->semester?->isLocked()) {
            return back()->withErrors(['class' => 'Không thể kích hoạt lớp thuộc năm học hoặc học kỳ đã khóa/lưu trữ.']);
        }

        if ($class->isArchived()) {
            return back()->withErrors(['class' => 'Không thể kích hoạt lớp đã lưu trữ.']);
        }

        $class->update(['status' => SchoolClass::STATUS_ACTIVE]);
        AuditLogger::log('class_activated', SchoolClass::class, (string) $class->getKey(), 'Kích hoạt lớp học ' . $class->name);

        return back()->with('success', 'Đã kích hoạt lớp học.');
    }

    public function lock(SchoolClass $class)
    {
        $this->denyHistoricalWrite();

        if ($class->isArchived()) {
            return back()->withErrors(['class' => 'Lớp đã lưu trữ, không thể khóa.']);
        }

        $class->update([
            'status' => SchoolClass::STATUS_LOCKED,
            'locked_at' => $class->locked_at ?? now(),
        ]);
        AuditLogger::log('class_locked', SchoolClass::class, (string) $class->getKey(), 'Khóa lớp học ' . $class->name);

        return back()->with('success', 'Đã khóa lớp học.');
    }

    public function archive(SchoolClass $class)
    {
        $this->denyHistoricalWrite();
        $this->archiveClass($class, 'class_archived', 'Lưu trữ lớp học ' . $class->name);

        return back()->with('success', 'Đã lưu trữ lớp học.');
    }

    public function destroy(SchoolClass $class)
    {
        $this->denyHistoricalWrite();
        $deleteCheck = $this->deleteCheck($class);

        if (! $deleteCheck['allowed']) {
            return back()->withErrors(['class' => $deleteCheck['message']]);
        }

        $className = $class->name;
        $classId = (string) $class->getKey();
        $oldTeacherId = $class->homeroom_teacher_id ? (string) $class->homeroom_teacher_id : null;
        $class->delete();
        $this->refreshTeacherHomeroomFlag($oldTeacherId);

        AuditLogger::log('class_deleted', SchoolClass::class, $classId, 'Xóa lớp học ' . $className);

        return redirect()->route('classes.index')->with('success', 'Đã xóa lớp học.');
    }

    public function updateStudentAssignments(Request $request, SchoolClass $class)
    {
        $this->denyHistoricalWrite();

        if (! $class->canEdit() || $class->schoolYear?->isArchived() || $class->semester?->isArchived() || $class->semester?->isLocked()) {
            return back()->withErrors(['class' => 'Lớp học đang khóa hoặc lưu trữ, không thể phân học sinh.']);
        }

        $data = $request->validate([
            'action' => ['required', Rule::in(['assign', 'unassign', 'transfer'])],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'string', 'exists:students,id'],
            'target_class_id' => ['nullable', 'string', 'exists:classes,id'],
        ]);

        if ($data['action'] === 'assign') {
            return $this->assignStudentsToClass($class, $data['student_ids']);
        }

        if ($data['action'] === 'transfer') {
            if (empty($data['target_class_id'])) {
                return back()->withErrors(['target_class_id' => 'Vui lòng chọn lớp đích.']);
            }

            return $this->transferStudentsToClass($class, $data['student_ids'], $data['target_class_id']);
        }

        return $this->unassignStudentsFromClass($class, $data['student_ids']);
    }

    private function assignStudentsToClass(SchoolClass $class, array $studentIds)
    {
        $studentIds = array_values(array_unique($studentIds));
        $students = Student::whereIn('id', $studentIds)->get();

        if ($students->count() !== count($studentIds)) {
            return back()->withErrors(['students' => 'Danh sách học sinh không hợp lệ.']);
        }

        if ($students->contains(fn (Student $student) => (string) $student->school_year_id !== (string) $class->school_year_id)) {
            return back()->withErrors(['students' => 'Chỉ được phân học sinh thuộc cùng năm học với lớp.']);
        }

        $alreadyAssigned = StudentClassAssignment::whereIn('student_id', $studentIds)
            ->where('academic_year_id', $class->school_year_id)
            ->where('status', StudentClassAssignment::STATUS_ACTIVE)
            ->where('class_id', '!=', $class->id)
            ->exists();

        if ($alreadyAssigned) {
            return back()->withErrors(['students' => 'Có học sinh đã thuộc lớp khác trong cùng năm học.']);
        }

        if ($class->currentStudentCount() + $students->count() > $class->maxCapacity()) {
            return back()->withErrors(['students' => 'Lớp đã vượt quá sức chứa tối đa 45 học sinh.']);
        }

        DB::transaction(function () use ($class, $students) {
            foreach ($students as $student) {
                StudentClassAssignment::where('student_id', $student->id)
                    ->where('academic_year_id', $class->school_year_id)
                    ->where('status', StudentClassAssignment::STATUS_ACTIVE)
                    ->where('class_id', '!=', $class->id)
                    ->update(['status' => StudentClassAssignment::STATUS_INACTIVE]);

                StudentClassAssignment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'academic_year_id' => $class->school_year_id,
                    ],
                    ['status' => StudentClassAssignment::STATUS_ACTIVE]
                );

                $student->update([
                    'class_id' => $class->id,
                    'school_year_id' => $class->school_year_id,
                ]);
            }
        });

        AuditLogger::log('class_students_assigned', SchoolClass::class, (string) $class->getKey(), 'Phân ' . $students->count() . ' học sinh vào lớp ' . $class->name);

        return back()->with('success', 'Đã phân học sinh vào lớp.');
    }

    private function unassignStudentsFromClass(SchoolClass $class, array $studentIds)
    {
        $studentIds = array_values(array_unique($studentIds));
        $students = Student::whereIn('id', $studentIds)
            ->where('class_id', $class->id)
            ->get();

        if ($students->isEmpty()) {
            return back()->withErrors(['students' => 'Không có học sinh hợp lệ để bỏ khỏi lớp.']);
        }

        DB::transaction(function () use ($class, $students) {
            StudentClassAssignment::whereIn('student_id', $students->pluck('id'))
                ->where('class_id', $class->id)
                ->where('academic_year_id', $class->school_year_id)
                ->where('status', StudentClassAssignment::STATUS_ACTIVE)
                ->update(['status' => StudentClassAssignment::STATUS_INACTIVE]);

            Student::whereIn('id', $students->pluck('id'))
                ->where('class_id', $class->id)
                ->update(['class_id' => null]);
        });

        AuditLogger::log('class_students_unassigned', SchoolClass::class, (string) $class->getKey(), 'Bỏ ' . $students->count() . ' học sinh khỏi lớp ' . $class->name);

        return back()->with('success', 'Đã cập nhật phân lớp học sinh.');
    }

    private function transferStudentsToClass(SchoolClass $class, array $studentIds, string $targetClassId)
    {
        $studentIds = array_values(array_unique($studentIds));
        $targetClass = SchoolClass::findOrFail($targetClassId);

        if ((string) $targetClass->getKey() === (string) $class->getKey()) {
            return back()->withErrors(['target_class_id' => 'Lớp đích phải khác lớp hiện tại.']);
        }

        if ((string) $targetClass->school_year_id !== (string) $class->school_year_id) {
            return back()->withErrors(['target_class_id' => 'Chỉ được chuyển học sinh trong cùng năm học.']);
        }

        if ($targetClass->isReadOnly() || $targetClass->schoolYear?->isArchived() || $targetClass->semester?->isArchived() || $targetClass->semester?->isLocked()) {
            return back()->withErrors(['target_class_id' => 'Lớp đích đang khóa hoặc lưu trữ.']);
        }

        $students = Student::whereIn('id', $studentIds)
            ->where('class_id', $class->id)
            ->get();

        if ($students->count() !== count($studentIds)) {
            return back()->withErrors(['students' => 'Danh sách học sinh cần chuyển không hợp lệ.']);
        }

        if ($targetClass->currentStudentCount() + $students->count() > $targetClass->maxCapacity()) {
            return back()->withErrors(['target_class_id' => 'Lớp đích đã vượt quá sức chứa tối đa 45 học sinh.']);
        }

        DB::transaction(function () use ($class, $targetClass, $students) {
            foreach ($students as $student) {
                $assignment = StudentClassAssignment::where('student_id', $student->id)
                    ->where('academic_year_id', $class->school_year_id)
                    ->where('status', StudentClassAssignment::STATUS_ACTIVE)
                    ->first();

                if ($assignment) {
                    $assignment->update(['class_id' => $targetClass->id]);
                } else {
                    StudentClassAssignment::create([
                        'student_id' => $student->id,
                        'class_id' => $targetClass->id,
                        'academic_year_id' => $class->school_year_id,
                        'status' => StudentClassAssignment::STATUS_ACTIVE,
                    ]);
                }

                $student->update([
                    'class_id' => $targetClass->id,
                    'school_year_id' => $targetClass->school_year_id,
                ]);
            }
        });

        AuditLogger::log('class_students_transferred', SchoolClass::class, (string) $class->getKey(), 'Chuyển ' . $students->count() . ' học sinh từ lớp ' . $class->name . ' sang lớp ' . $targetClass->name);

        return back()->with('success', 'Đã chuyển học sinh sang lớp khác.');
    }

    private function formData(?SchoolClass $class = null): array
    {
        $years = SchoolYear::whereNull('archived_at')
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->get();

        $semesters = Semester::with('schoolYear')
            ->whereIn('school_year_id', $years->pluck('id'))
            ->where('status', '!=', Semester::STATUS_ARCHIVED)
            ->where('status', '!=', Semester::STATUS_LOCKED)
            ->orderBy('school_year_id')
            ->orderBy('name')
            ->get();

        $activeYearId = $this->selectedSchoolYearId(request());
        $activeSemesterId = $this->selectedSemesterId(request());
        $currentTeacherId = $class?->homeroom_teacher_id;
        $usedTeacherIds = SchoolClass::query()
            ->where('school_year_id', $activeYearId)
            ->when($class, fn ($query) => $query->whereKeyNot($class->getKey()))
            ->whereNotNull('homeroom_teacher_id')
            ->pluck('homeroom_teacher_id');

        $teachers = Teacher::query()
            ->where(function ($query) use ($currentTeacherId) {
                $query->where('work_status', Teacher::STATUS_WORKING)
                    ->when($currentTeacherId, fn ($teacherQuery) => $teacherQuery->orWhere('id', $currentTeacherId));
            })
            ->where(function ($query) use ($usedTeacherIds, $currentTeacherId) {
                $query->whereNotIn('id', $usedTeacherIds)
                    ->when($currentTeacherId, fn ($teacherQuery) => $teacherQuery->orWhere('id', $currentTeacherId));
            })
            ->orderBy('name')
            ->get();

        return [
            'teachers' => $teachers,
            'years' => $years,
            'semesters' => $semesters,
            'selectedYearId' => $activeYearId,
            'selectedSemesterId' => $activeSemesterId,
        ];
    }

    private function validatedData(Request $request, ?SchoolClass $class = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'grade_level' => ['required', 'integer', Rule::in([10, 11, 12])],
            'school_year_id' => ['required', 'exists:school_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'homeroom_teacher_id' => ['nullable', 'exists:teachers,id'],
            'capacity' => ['required', 'integer', 'min:1', 'max:45'],
        ]);

        $year = SchoolYear::findOrFail($validated['school_year_id']);
        $semester = Semester::findOrFail($validated['semester_id']);

        if ($year->isArchived()) {
            throw ValidationException::withMessages(['school_year_id' => 'Không thể tạo lớp trong năm học đã lưu trữ.']);
        }

        if ((string) $semester->school_year_id !== (string) $year->id) {
            throw ValidationException::withMessages(['semester_id' => 'Học kỳ không thuộc năm học đã chọn.']);
        }

        if ($semester->isArchived() || $semester->isLocked()) {
            throw ValidationException::withMessages(['semester_id' => 'Không thể tạo hoặc sửa lớp trong học kỳ đã khóa/lưu trữ.']);
        }

        if (! empty($validated['homeroom_teacher_id'])) {
            $teacherCanBeHomeroom = Teacher::whereKey($validated['homeroom_teacher_id'])
                ->where('work_status', Teacher::STATUS_WORKING)
                ->exists();

            if (! $teacherCanBeHomeroom) {
                throw ValidationException::withMessages([
                    'homeroom_teacher_id' => 'Chỉ được chọn giáo viên đang công tác làm giáo viên chủ nhiệm.',
                ]);
            }

            $teacherAlreadyAssigned = SchoolClass::where('school_year_id', $validated['school_year_id'])
                ->where('homeroom_teacher_id', $validated['homeroom_teacher_id'])
                ->when($class, fn ($query) => $query->whereKeyNot($class->getKey()))
                ->exists();

            if ($teacherAlreadyAssigned) {
                throw ValidationException::withMessages([
                    'homeroom_teacher_id' => 'Giáo viên này đã là giáo viên chủ nhiệm của lớp khác trong cùng năm học.',
                ]);
            }
        }

        $exists = SchoolClass::where('school_year_id', $validated['school_year_id'])
            ->where('semester_id', $validated['semester_id'])
            ->where('name', $validated['name'])
            ->when($class, fn ($query) => $query->whereKeyNot($class->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Tên lớp đã tồn tại trong cùng năm học và học kỳ.',
            ]);
        }

        return $validated;
    }

    private function effectiveSchoolYearId(Request $request): ?string
    {
        return $this->selectedSchoolYearId($request);
    }

    private function deleteCheck(SchoolClass $class): array
    {
        if ($class->students()->exists()) {
            return ['allowed' => false, 'message' => 'Không thể xóa lớp vì đã có học sinh.'];
        }

        if ($this->hasScoreData($class)) {
            return ['allowed' => false, 'message' => 'Không thể xóa lớp vì đã phát sinh điểm.'];
        }

        if ($this->modelHasRows(AttendanceRecord::class, 'class_id', (string) $class->getKey())) {
            return ['allowed' => false, 'message' => 'Không thể xóa lớp vì đã phát sinh điểm danh.'];
        }

        if ($this->modelHasRows(Conduct::class, 'class_id', (string) $class->getKey())) {
            return ['allowed' => false, 'message' => 'Không thể xóa lớp vì đã phát sinh hạnh kiểm.'];
        }

        if ($this->modelHasRows(Timetable::class, 'class_id', (string) $class->getKey())) {
            return ['allowed' => false, 'message' => 'Không thể xóa lớp vì đã có thời khóa biểu.'];
        }

        return ['allowed' => true, 'message' => null];
    }

    private function hasScoreData(SchoolClass $class): bool
    {
        if (! Schema::hasTable('score_headers')) {
            return false;
        }

        $studentIds = Student::where('class_id', $class->getKey())->pluck('id');

        if ($studentIds->isEmpty()) {
            return false;
        }

        return ScoreHeader::whereIn('student_id', $studentIds)->exists();
    }

    private function modelHasRows(string $model, string $column, string $value): bool
    {
        $instance = new $model();

        return Schema::hasTable($instance->getTable())
            && Schema::hasColumn($instance->getTable(), $column)
            && $model::where($column, $value)->exists();
    }

    private function archiveClass(SchoolClass $class, string $action, string $description): void
    {
        if ($class->isArchived()) {
            return;
        }

        $oldTeacherId = $class->homeroom_teacher_id ? (string) $class->homeroom_teacher_id : null;

        $class->update([
            'status' => SchoolClass::STATUS_ARCHIVED,
            'archived_at' => $class->archived_at ?? now(),
        ]);
        $this->refreshTeacherHomeroomFlag($oldTeacherId);

        AuditLogger::log($action, SchoolClass::class, (string) $class->getKey(), $description);
    }

    private function syncHomeroomTeacherFlags(SchoolClass $class, ?string $oldTeacherId = null): void
    {
        if ($class->homeroom_teacher_id) {
            Teacher::whereKey($class->homeroom_teacher_id)->update(['is_homeroom' => true]);
        }

        if ($oldTeacherId && (string) $oldTeacherId !== (string) $class->homeroom_teacher_id) {
            $this->refreshTeacherHomeroomFlag($oldTeacherId);
        }
    }

    private function refreshTeacherHomeroomFlag(?string $teacherId): void
    {
        if (! $teacherId) {
            return;
        }

        $hasHomeroomClass = SchoolClass::where('homeroom_teacher_id', $teacherId)
            ->where('status', '!=', SchoolClass::STATUS_ARCHIVED)
            ->whereHas('schoolYear', fn ($query) => $query->whereNull('archived_at'))
            ->exists();

        Teacher::whereKey($teacherId)->update(['is_homeroom' => $hasHomeroomClass]);
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi lớp học.',
            ]);
        }
    }
}
