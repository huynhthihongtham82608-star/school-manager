<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Conduct;
use App\Models\ExamSchedule;
use App\Models\GradeWindow;
use App\Models\LearningDocument;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentClassAssignment;
use App\Models\TeachingAssignment;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Support\AuditLogger;
use App\Support\CurrentAcademicContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SchoolYearController extends Controller
{
    private const INITIALIZE_OPTIONS = [
        'promote_students' => 'Thăng lớp học sinh',
        'graduate_grade_12' => 'Đánh dấu học sinh lớp 12 đã tốt nghiệp',
    ];

    public function index(Request $request)
    {
        $years = SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $deleteChecks = $years->mapWithKeys(fn (SchoolYear $year) => [
            (string) $year->getKey() => $this->deleteCheck($year),
        ]);

        return view('school_years.index', compact('years', 'deleteChecks'));
    }

    public function create()
    {
        return view('school_years.create', [
            'activeYear' => $this->activeYear(),
        ]);
    }

    public function show(Request $request, SchoolYear $schoolYear)
    {
        $currentYear = app(CurrentAcademicContext::class)->schoolYear();

        if (! $currentYear || (string) $schoolYear->getKey() !== (string) $currentYear->getKey()) {
            $this->rememberHistoryMode($request, $schoolYear);
        }

        $logs = $this->schoolYearLogs($schoolYear);

        return view('school_years.show', [
            'schoolYear' => $schoolYear,
            'yearParts' => $this->splitYearName($schoolYear->name),
            'dataCards' => $this->schoolYearDataCards($schoolYear),
            'logs' => $logs,
            'logSummary' => $this->logSummary($logs),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if ($data['is_active'] && $this->activeYear() && ! $request->boolean('confirm_activation')) {
            return back()
                ->withInput()
                ->withErrors(['is_active' => 'Vui lòng xác nhận trước khi chuyển năm học hoạt động.']);
        }

        $syncedSemester = null;

        DB::transaction(function () use ($data, &$syncedSemester) {
            if ($data['is_active']) {
                SchoolYear::where('is_active', true)->update(['is_active' => false]);
            }

            $schoolYear = SchoolYear::create($data);
            if ($data['is_active']) {
                $syncedSemester = app(CurrentAcademicContext::class)->syncSemesterForCurrentYear($schoolYear);
            }
            AuditLogger::log('school_year_created', SchoolYear::class, (string) $schoolYear->getKey(), 'Tạo năm học ' . $schoolYear->name);
        });

        return redirect()->route('school-years.index')->with('success', 'Đã tạo năm học.');
    }

    public function edit(SchoolYear $schoolYear)
    {
        if ($schoolYear->isArchived()) {
            return redirect()
                ->route('school-years.detail', $schoolYear)
                ->withErrors(['school_year' => 'Năm học đã lưu trữ chỉ được xem chi tiết, không được chỉnh sửa.']);
        }

        [$startYear, $endYear] = $this->splitYearName($schoolYear->name);

        return view('school_years.edit', [
            'schoolYear' => $schoolYear,
            'activeYear' => $this->activeYear(),
            'startYear' => $startYear,
            'endYear' => $endYear,
            'hasDependentData' => $this->hasDependentData($schoolYear),
        ]);
    }

    public function update(Request $request, SchoolYear $schoolYear)
    {
        if ($schoolYear->isArchived()) {
            return redirect()
                ->route('school-years.detail', $schoolYear)
                ->withErrors(['school_year' => 'Không thể chỉnh sửa năm học đã lưu trữ.']);
        }

        $hasDependentData = $this->hasDependentData($schoolYear);
        $data = $this->validatedData($request, $schoolYear, $hasDependentData);

        if ($schoolYear->isArchived()) {
            $data['is_active'] = false;
        }

        if ($schoolYear->is_active) {
            $data['is_active'] = true;
        }

        if (
            ! $schoolYear->is_active
            && $data['is_active']
            && $this->activeYear()
            && ! $request->boolean('confirm_activation')
        ) {
            return back()
                ->withInput()
                ->withErrors(['is_active' => 'Vui lòng xác nhận trước khi chuyển năm học hoạt động.']);
        }

        $syncedSemester = null;

        DB::transaction(function () use ($schoolYear, $data, &$syncedSemester) {
            if (! $schoolYear->is_active && $data['is_active']) {
                SchoolYear::where('is_active', true)->whereKeyNot($schoolYear->getKey())->update(['is_active' => false]);
            }

            $schoolYear->update($data);
            if ($schoolYear->is_active) {
                $syncedSemester = app(CurrentAcademicContext::class)->syncSemesterForCurrentYear($schoolYear);
            }
            AuditLogger::log('school_year_updated', SchoolYear::class, (string) $schoolYear->getKey(), 'Chỉnh sửa năm học ' . $schoolYear->name);
        });

        return redirect()->route('school-years.index')->with('success', 'Đã cập nhật năm học.');
    }

    public function activate(Request $request, SchoolYear $schoolYear)
    {
        if ($schoolYear->isArchived()) {
            return back()->withErrors(['school_year' => 'Không thể kích hoạt năm học đã lưu trữ.']);
        }

        if ($schoolYear->is_active) {
            return back()->with('success', 'Năm học này đang hoạt động.');
        }

        if ($this->activeYear() && ! $request->boolean('confirm_activation')) {
            return back()->withErrors(['school_year' => 'Vui lòng xác nhận trước khi chuyển năm học hoạt động.']);
        }

        $syncedSemester = null;

        DB::transaction(function () use ($schoolYear, &$syncedSemester) {
            SchoolYear::where('is_active', true)->whereKeyNot($schoolYear->getKey())->update(['is_active' => false]);
            $schoolYear->update(['is_active' => true, 'archived_at' => null]);
            $syncedSemester = app(CurrentAcademicContext::class)->syncSemesterForCurrentYear($schoolYear);
            AuditLogger::log('school_year_activated', SchoolYear::class, (string) $schoolYear->getKey(), 'Kích hoạt năm học ' . $schoolYear->name);
        });

        return redirect()->route('school-years.index')->with('success', 'Đã kích hoạt năm học.');
    }

    public function archive(SchoolYear $schoolYear)
    {
        if ($schoolYear->is_active) {
            return back()->withErrors(['school_year' => 'Không thể lưu trữ năm học đang được sử dụng.']);
        }

        if ($schoolYear->isArchived()) {
            return back()->with('success', 'Năm học này đã được lưu trữ.');
        }

        DB::transaction(function () use ($schoolYear) {
            $this->archiveSemestersForSchoolYear($schoolYear);

            $schoolYear->update([
                'is_active' => false,
                'archived_at' => now(),
            ]);

            AuditLogger::log('school_year_archived', SchoolYear::class, (string) $schoolYear->getKey(), 'Lưu trữ năm học ' . $schoolYear->name);
        });

        return redirect()->route('school-years.index')->with('success', 'Đã lưu trữ năm học.');
    }

    public function destroy(SchoolYear $schoolYear)
    {
        $deleteCheck = $this->deleteCheck($schoolYear);

        if (! $deleteCheck['allowed']) {
            return back()->withErrors(['school_year' => $deleteCheck['message']]);
        }

        $schoolYearName = $schoolYear->name;

        DB::transaction(function () use ($schoolYear, $schoolYearName) {
            $this->deleteInitialSchoolYearData($schoolYear);

            $schoolYear->delete();

            AuditLogger::log(
                'school_year_deleted',
                SchoolYear::class,
                (string) $schoolYear->getKey(),
                'Xóa năm học ' . $schoolYearName
            );
        });

        return redirect()->route('school-years.index')->with('success', 'Đã xóa năm học.');
    }

    public function clearHistoryMode(Request $request)
    {
        $this->clearHistoryContext($request);

        return redirect()->route('dashboard')->with('success', 'Đã quay về năm học hiện hành.');
    }

    public function updateWorkingContext(Request $request)
    {
        $data = $request->validate([
            'school_year_id' => ['required', 'exists:school_years,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
        ]);

        $schoolYear = SchoolYear::findOrFail($data['school_year_id']);

        $semester = null;
        if (! empty($data['semester_id'])) {
            $semester = Semester::find($data['semester_id']);
            if ($semester && (string) $semester->school_year_id !== (string) $schoolYear->getKey()) {
                $semester = null;
            }
        }

        $currentYear = app(CurrentAcademicContext::class)->schoolYear();
        $isCurrentYear = $currentYear && (string) $schoolYear->getKey() === (string) $currentYear->getKey();

        $semester ??= app(CurrentAcademicContext::class)->semester($schoolYear);
        $semester ??= Semester::where('school_year_id', $schoolYear->getKey())
            ->when($isCurrentYear, fn ($query) => $query->where('status', '!=', Semester::STATUS_ARCHIVED))
            ->orderByRaw("case when status = 'active' then 0 when status = 'inactive' then 1 else 2 end")
            ->orderBy('order')
            ->orderBy('name')
            ->first();

        if ($isCurrentYear) {
            $this->clearHistoryContext($request);
        } else {
            $request->session()->put('working_school_year_id', $schoolYear->getKey());
            if ($semester) {
                $request->session()->put('working_semester_id', $semester->getKey());
            } else {
                $request->session()->forget('working_semester_id');
            }
            $this->rememberHistoryMode($request, $schoolYear);
        }

        return redirect()
            ->to($this->cleanPreviousUrl($request))
            ->with('success', $isCurrentYear
                ? 'Đã quay về năm học hiện hành.'
                : 'Đã chuyển sang chế độ xem dữ liệu năm học ' . $schoolYear->name . '.');
    }

    public function initializeForm()
    {
        return view('school_years.initialize', [
            'sourceYears' => $this->sourceYears(),
            'options' => self::INITIALIZE_OPTIONS,
        ]);
    }

    public function initializePreview(Request $request)
    {
        [$sourceYear, $data] = $this->validatedInitializationData($request);
        $targetName = $this->formatYearName($data['start_year'], $data['end_year']);
        $preview = $this->buildInitializationPreview($sourceYear, $targetName, $data['options']);

        return view('school_years.initialize', [
            'sourceYears' => $this->sourceYears(),
            'options' => self::INITIALIZE_OPTIONS,
            'preview' => $preview,
            'input' => $data + ['target_name' => $targetName],
        ]);
    }

    public function initializeStore(Request $request)
    {
        [$sourceYear, $data] = $this->validatedInitializationData($request, true);
        $targetName = $this->formatYearName($data['start_year'], $data['end_year']);

        $result = DB::transaction(function () use ($sourceYear, $data, $targetName) {
            $targetYear = SchoolYear::create([
                'name' => $targetName,
                'start_date' => sprintf('%04d-08-01', $data['start_year']),
                'end_date' => sprintf('%04d-05-31', $data['end_year']),
                'is_active' => false,
                'archived_at' => null,
            ]);

            $report = $this->buildInitializationPreview($sourceYear, $targetName, $data['options']);
            $classMap = [];
            $createdClasses = 0;
            $promotedStudents = 0;
            $graduatedStudents = 0;

            if (in_array('promote_students', $data['options'], true)) {
                [$classMap, $createdClasses] = $this->createPromotionClasses(
                    $sourceYear,
                    $targetYear,
                    $data['promote_student_ids']
                );
                $promotedStudents = $this->promoteStudents(
                    $classMap,
                    $targetYear,
                    $data['promote_student_ids']
                );
            }

            if (in_array('graduate_grade_12', $data['options'], true)) {
                $graduatedStudents = $this->graduateGrade12Students(
                    $sourceYear,
                    $data['graduate_student_ids']
                );
            }

            $summary = [
                'target_year_id' => $targetYear->getKey(),
                'target_year_name' => $targetYear->name,
                'source_year_name' => $sourceYear->name,
                'created_classes' => $createdClasses,
                'promoted_students' => $promotedStudents,
                'graduated_students' => $graduatedStudents,
                'counts' => $report['counts'],
            ];

            AuditLogger::log(
                'school_year_initialized',
                SchoolYear::class,
                (string) $targetYear->getKey(),
                json_encode($summary, JSON_UNESCAPED_UNICODE)
            );

            return [
                'targetYear' => $targetYear,
                'sourceYear' => $sourceYear,
                'counts' => array_merge($report['counts'], [
                    'created_classes' => $createdClasses,
                    'promote_students' => $promotedStudents,
                    'graduate_grade_12' => $graduatedStudents,
                ]),
                'selected_options' => $data['options'],
            ];
        });

        return view('school_years.initialize', [
            'sourceYears' => $this->sourceYears(),
            'options' => self::INITIALIZE_OPTIONS,
            'result' => $result,
        ])->with('success', 'Khởi tạo năm học mới thành công.');
    }

    private function validatedData(Request $request, ?SchoolYear $schoolYear = null, bool $lockYearName = false): array
    {
        if ($lockYearName) {
            [$startYear, $endYear] = $this->splitYearName($schoolYear?->name);
            $request->merge([
                'start_year' => $startYear,
                'end_year' => $endYear,
            ]);
        }

        $validated = $request->validate([
            'start_year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'end_year' => ['required', 'integer', 'min:1901', 'max:2101'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'confirm_activation' => ['nullable', 'boolean'],
        ]);

        $name = $this->formatYearName($validated['start_year'], $validated['end_year']);

        if ((int) $validated['end_year'] !== (int) $validated['start_year'] + 1) {
            throw ValidationException::withMessages([
                'end_year' => 'Năm kết thúc phải bằng năm bắt đầu + 1.',
            ]);
        }

        if ($this->yearNameExists($name, $schoolYear)) {
            throw ValidationException::withMessages([
                'start_year' => 'Năm học này đã tồn tại.',
            ]);
        }

        return [
            'name' => $name,
            'start_date' => $validated['start_date'] ?: sprintf('%04d-08-01', $validated['start_year']),
            'end_date' => $validated['end_date'] ?: sprintf('%04d-05-31', $validated['end_year']),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function validatedInitializationData(Request $request, bool $requireConfirm = false): array
    {
        $validated = $request->validate([
            'source_year_id' => ['required', 'exists:school_years,id'],
            'start_year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'end_year' => ['required', 'integer', 'min:1901', 'max:2101'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'in:' . implode(',', array_keys(self::INITIALIZE_OPTIONS))],
            'promote_student_ids' => ['nullable', 'array'],
            'promote_student_ids.*' => ['string', 'exists:students,id'],
            'graduate_student_ids' => ['nullable', 'array'],
            'graduate_student_ids.*' => ['string', 'exists:students,id'],
            'confirm_initialization' => ['nullable', 'boolean'],
        ]);

        if ($requireConfirm && ! $request->boolean('confirm_initialization')) {
            throw ValidationException::withMessages([
                'confirm_initialization' => 'Vui lòng xác nhận trước khi khởi tạo năm học mới.',
            ]);
        }

        $sourceYear = SchoolYear::findOrFail($validated['source_year_id']);

        if ($sourceYear->is_active) {
            throw ValidationException::withMessages([
                'source_year_id' => 'Không thể khởi tạo từ năm học đang hoạt động.',
            ]);
        }

        if (! $sourceYear->isArchived() && (! $sourceYear->end_date || ! $sourceYear->end_date->lt(today()))) {
            throw ValidationException::withMessages([
                'source_year_id' => 'Chỉ có thể khởi tạo từ năm học đã kết thúc hoặc đã lưu trữ.',
            ]);
        }

        if ((int) $validated['end_year'] !== (int) $validated['start_year'] + 1) {
            throw ValidationException::withMessages([
                'end_year' => 'Năm học mới không hợp lệ. Năm kết thúc phải bằng năm bắt đầu + 1.',
            ]);
        }

        $targetName = $this->formatYearName($validated['start_year'], $validated['end_year']);

        if ($this->yearNameExists($targetName)) {
            throw ValidationException::withMessages([
                'start_year' => 'Năm học mới đã tồn tại.',
            ]);
        }

        $validated['options'] = array_values(array_intersect(
            $validated['options'] ?? [],
            array_keys(self::INITIALIZE_OPTIONS)
        ));

        $validated['promote_student_ids'] = array_values(array_unique($validated['promote_student_ids'] ?? []));
        $validated['graduate_student_ids'] = array_values(array_unique($validated['graduate_student_ids'] ?? []));

        if ($requireConfirm) {
            $validated['promote_student_ids'] = in_array('promote_students', $validated['options'], true)
                ? $this->validPromotionStudentIds($sourceYear, $validated['promote_student_ids'])
                : [];

            $validated['graduate_student_ids'] = in_array('graduate_grade_12', $validated['options'], true)
                ? $this->validGraduationStudentIds($sourceYear, $validated['graduate_student_ids'])
                : [];
        }

        return [$sourceYear, $validated];
    }

    private function buildInitializationPreview(SchoolYear $sourceYear, string $targetName, array $selectedOptions): array
    {
        $counts = [
            'promote_students' => in_array('promote_students', $selectedOptions, true) ? $this->promotableStudentCount($sourceYear) : 0,
            'graduate_grade_12' => in_array('graduate_grade_12', $selectedOptions, true) ? $this->graduatableStudentCount($sourceYear) : 0,
        ];

        return [
            'source_year' => $sourceYear,
            'target_name' => $targetName,
            'selected_options' => $selectedOptions,
            'counts' => $counts,
            'promotion_groups' => in_array('promote_students', $selectedOptions, true)
                ? $this->promotionStudentGroups($sourceYear, $targetName)
                : collect(),
            'graduation_groups' => in_array('graduate_grade_12', $selectedOptions, true)
                ? $this->graduationStudentGroups($sourceYear)
                : collect(),
        ];
    }

    private function promotionStudentGroups(SchoolYear $sourceYear, string $targetName)
    {
        return SchoolClass::with(['students' => function ($query) {
            $query->where('status', Student::STATUS_STUDYING)
                ->orderBy('student_code')
                ->orderBy('name');
        }])
            ->where('school_year_id', $sourceYear->getKey())
            ->whereIn('grade_level', [10, 11])
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get()
            ->filter(fn (SchoolClass $class) => $class->students->isNotEmpty())
            ->map(function (SchoolClass $class) use ($targetName) {
                $targetGrade = (int) $class->grade_level + 1;
                $baseTargetName = $this->promotedClassName($class->name, (int) $class->grade_level, $targetGrade);

                return [
                    'source_class' => $class,
                    'target_name' => $this->uniqueClassName($baseTargetName, $targetName),
                    'target_grade' => $targetGrade,
                    'students' => $class->students,
                ];
            })
            ->values();
    }

    private function graduationStudentGroups(SchoolYear $sourceYear)
    {
        return SchoolClass::with(['students' => function ($query) {
            $query->where('status', Student::STATUS_STUDYING)
                ->orderBy('student_code')
                ->orderBy('name');
        }])
            ->where('school_year_id', $sourceYear->getKey())
            ->where('grade_level', 12)
            ->orderBy('name')
            ->get()
            ->filter(fn (SchoolClass $class) => $class->students->isNotEmpty())
            ->map(fn (SchoolClass $class) => [
                'class' => $class,
                'students' => $class->students,
            ])
            ->values();
    }

    private function validPromotionStudentIds(SchoolYear $sourceYear, array $studentIds): array
    {
        if (empty($studentIds)) {
            return [];
        }

        $eligibleIds = $this->promotionEligibleStudentsQuery($sourceYear)
            ->whereIn('id', $studentIds)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (count($eligibleIds) !== count($studentIds)) {
            throw ValidationException::withMessages([
                'promote_student_ids' => 'Danh sách học sinh thăng lớp không hợp lệ hoặc có học sinh không thuộc lớp nguồn.',
            ]);
        }

        return $eligibleIds;
    }

    private function validGraduationStudentIds(SchoolYear $sourceYear, array $studentIds): array
    {
        if (empty($studentIds)) {
            return [];
        }

        $eligibleIds = $this->graduationEligibleStudentsQuery($sourceYear)
            ->whereIn('id', $studentIds)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (count($eligibleIds) !== count($studentIds)) {
            throw ValidationException::withMessages([
                'graduate_student_ids' => 'Danh sách học sinh tốt nghiệp không hợp lệ hoặc có học sinh không thuộc lớp 12 đã chọn.',
            ]);
        }

        return $eligibleIds;
    }

    private function promotionEligibleStudentsQuery(SchoolYear $sourceYear)
    {
        $classIds = SchoolClass::where('school_year_id', $sourceYear->getKey())
            ->whereIn('grade_level', [10, 11])
            ->pluck('id');

        return Student::whereIn('class_id', $classIds)
            ->where('status', Student::STATUS_STUDYING);
    }

    private function graduationEligibleStudentsQuery(SchoolYear $sourceYear)
    {
        $classIds = SchoolClass::where('school_year_id', $sourceYear->getKey())
            ->where('grade_level', 12)
            ->pluck('id');

        return Student::whereIn('class_id', $classIds)
            ->where('status', Student::STATUS_STUDYING);
    }

    private function createPromotionClasses(SchoolYear $sourceYear, SchoolYear $targetYear, array $studentIds): array
    {
        $classMap = [];
        $created = 0;

        if (empty($studentIds)) {
            return [$classMap, $created];
        }

        $sourceClassIds = Student::whereIn('id', $studentIds)
            ->where('status', Student::STATUS_STUDYING)
            ->pluck('class_id')
            ->filter()
            ->unique()
            ->values();

        if ($sourceClassIds->isEmpty()) {
            return [$classMap, $created];
        }

        SchoolClass::where('school_year_id', $sourceYear->getKey())
            ->whereIn('grade_level', [10, 11])
            ->whereIn('id', $sourceClassIds)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get()
            ->each(function (SchoolClass $sourceClass) use ($targetYear, &$classMap, &$created) {
                $targetGrade = (int) $sourceClass->grade_level + 1;
                $targetName = $this->promotedClassName($sourceClass->name, (int) $sourceClass->grade_level, $targetGrade);
                $targetClass = SchoolClass::create([
                    'name' => $this->uniqueClassName($targetName, $targetYear->name),
                    'grade_level' => $targetGrade,
                    'school_year_id' => $targetYear->getKey(),
                    'homeroom_teacher_id' => $sourceClass->homeroom_teacher_id,
                    'capacity' => $sourceClass->capacity,
                ]);

                $classMap[$sourceClass->getKey()] = $targetClass->getKey();
                $created++;
            });

        return [$classMap, $created];
    }

    private function promoteStudents(array $classMap, SchoolYear $targetYear, array $studentIds): int
    {
        if (! $classMap || empty($studentIds)) {
            return 0;
        }

        $students = Student::whereIn('id', $studentIds)
            ->whereIn('class_id', array_keys($classMap))
            ->where('status', Student::STATUS_STUDYING)
            ->get();

        $students->each(function (Student $student) use ($classMap, $targetYear) {
            $targetClassId = $classMap[$student->class_id] ?? null;

            if (! $targetClassId) {
                return;
            }

            $student->update([
                'class_id' => $targetClassId,
                'school_year_id' => $targetYear->getKey(),
                'status' => Student::STATUS_STUDYING,
            ]);

            StudentClassAssignment::updateOrCreate([
                'student_id' => $student->getKey(),
                'class_id' => $targetClassId,
                'academic_year_id' => $targetYear->getKey(),
            ], [
                'status' => StudentClassAssignment::STATUS_ACTIVE,
            ]);
        });

        return $students->count();
    }

    private function graduateGrade12Students(SchoolYear $sourceYear, array $studentIds): int
    {
        if (empty($studentIds)) {
            return 0;
        }

        $classIds = SchoolClass::where('school_year_id', $sourceYear->getKey())
            ->where('grade_level', 12)
            ->pluck('id');

        if ($classIds->isEmpty()) {
            return 0;
        }

        return Student::whereIn('id', $studentIds)
            ->whereIn('class_id', $classIds)
            ->where('status', Student::STATUS_STUDYING)
            ->update(['status' => Student::STATUS_GRADUATED]);
    }

    private function promotableStudentCount(SchoolYear $sourceYear): int
    {
        $classIds = SchoolClass::where('school_year_id', $sourceYear->getKey())
            ->whereIn('grade_level', [10, 11])
            ->pluck('id');

        if ($classIds->isEmpty()) {
            return 0;
        }

        return Student::whereIn('class_id', $classIds)->where('status', Student::STATUS_STUDYING)->count();
    }

    private function graduatableStudentCount(SchoolYear $sourceYear): int
    {
        $classIds = SchoolClass::where('school_year_id', $sourceYear->getKey())
            ->where('grade_level', 12)
            ->pluck('id');

        if ($classIds->isEmpty()) {
            return 0;
        }

        return Student::whereIn('class_id', $classIds)->where('status', Student::STATUS_STUDYING)->count();
    }

    private function sourceYears()
    {
        return SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
    }

    private function schoolYearDataCards(SchoolYear $schoolYear): array
    {
        $id = (string) $schoolYear->getKey();
        $historyParams = $schoolYear->isArchived() ? ['history_school_year_id' => $id] : [];
        $yearParams = array_merge(['school_year_id' => $id], $historyParams);
        $classIds = SchoolClass::where('school_year_id', $id)->pluck('id');
        $semesterIds = Semester::where('school_year_id', $id)->pluck('id');

        $examCount = 0;
        if (Schema::hasTable('exam_schedules')) {
            $examCount = ExamSchedule::where(function ($query) use ($id, $semesterIds) {
                $query->where('note', 'like', '%"school_year_id":"' . $id . '"%');

                if ($semesterIds->isNotEmpty()) {
                    $query->orWhereIn('semester_id', $semesterIds);
                }
            })->count();
        }

        $documentCount = Schema::hasTable('learning_documents') && $classIds->isNotEmpty()
            ? LearningDocument::whereIn('class_id', $classIds)->count()
            : 0;

        $attendanceCount = Schema::hasTable('attendance_records') && ($classIds->isNotEmpty() || $semesterIds->isNotEmpty())
            ? AttendanceRecord::where(function ($query) use ($classIds, $semesterIds) {
                if ($classIds->isNotEmpty()) {
                    $query->whereIn('class_id', $classIds);
                }

                if ($semesterIds->isNotEmpty()) {
                    $method = $classIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('semester_id', $semesterIds);
                }
            })->count()
            : 0;

        return [
            [
                'icon' => 'bi-calendar2-week',
                'label' => 'Học kỳ',
                'count' => Semester::where('school_year_id', $id)->count(),
                'url' => route('semesters.index', $yearParams),
            ],
            [
                'icon' => 'bi-building',
                'label' => 'Lớp học',
                'count' => $classIds->count(),
                'url' => route('classes.index', $yearParams),
            ],
            [
                'icon' => 'bi-book',
                'label' => 'Môn học',
                'count' => TeachingAssignment::where('school_year_id', $id)->distinct('subject_id')->count('subject_id'),
                'url' => route('subjects.index', $yearParams),
            ],
            [
                'icon' => 'bi-person-badge',
                'label' => 'Giáo viên',
                'count' => TeachingAssignment::where('school_year_id', $id)->distinct('teacher_id')->count('teacher_id'),
                'url' => route('teachers.index', $yearParams),
            ],
            [
                'icon' => 'bi-person',
                'label' => 'Học sinh',
                'count' => Student::where('school_year_id', $id)->count(),
                'url' => route('students.index', $yearParams),
            ],
            [
                'icon' => 'bi-diagram-3',
                'label' => 'Phân công giảng dạy',
                'count' => TeachingAssignment::where('school_year_id', $id)->count(),
                'url' => route('assignments.index', $yearParams),
            ],
            [
                'icon' => 'bi-calendar3-week',
                'label' => 'Thời khóa biểu',
                'count' => Timetable::where('school_year_id', $id)->count(),
                'url' => route('timetable.manage', $yearParams),
            ],
            [
                'icon' => 'bi-calendar2-check',
                'label' => 'Lịch kiểm tra',
                'count' => $examCount,
                'url' => route('exam-schedules.index', $yearParams),
            ],
            [
                'icon' => 'bi-table',
                'label' => 'Điểm số',
                'count' => ScoreHeader::where('school_year_id', $id)->count(),
                'url' => route('scores.index', $yearParams),
            ],
            [
                'icon' => 'bi-star',
                'label' => 'Hạnh kiểm',
                'count' => Conduct::where('school_year_id', $id)->count(),
                'url' => route('conduct.index', $yearParams),
            ],
            [
                'icon' => 'bi-person-check',
                'label' => 'Điểm danh',
                'count' => $attendanceCount,
                'url' => route('attendance.index', $yearParams),
            ],
            [
                'icon' => 'bi-journal-bookmark',
                'label' => 'Tài liệu học tập',
                'count' => $documentCount,
                'url' => route('documents.index', $yearParams),
            ],
        ];
    }

    private function schoolYearLogs(SchoolYear $schoolYear)
    {
        if (! Schema::hasTable('audit_logs')) {
            return collect();
        }

        return AuditLog::with('user')
            ->where(function ($query) use ($schoolYear) {
                $query->where(function ($entityQuery) use ($schoolYear) {
                    $entityQuery->where('entity_type', SchoolYear::class)
                        ->where('entity_id', (string) $schoolYear->getKey());
                })
                ->orWhere(function ($sourceQuery) use ($schoolYear) {
                    $sourceQuery->where('action', 'school_year_initialized')
                        ->where('description', 'like', '%"source_year_name":"' . $schoolYear->name . '"%');
                });
            })
            ->latest('created_at')
            ->get();
    }

    private function logSummary($logs): array
    {
        return [
            'created' => $logs->firstWhere('action', 'school_year_created'),
            'updated' => $logs->firstWhere('action', 'school_year_updated'),
            'activated' => $logs->firstWhere('action', 'school_year_activated'),
            'archived' => $logs->firstWhere('action', 'school_year_archived'),
        ];
    }

    private function activeYear(): ?SchoolYear
    {
        return SchoolYear::where('is_active', true)->first();
    }

    private function archiveSemestersForSchoolYear(SchoolYear $schoolYear): void
    {
        $schoolYear->semesters()
            ->orderBy('name')
            ->get()
            ->each(function (Semester $semester) use ($schoolYear) {
                if ($semester->isArchived()) {
                    return;
                }

                if ($semester->isActive()) {
                    $semester->update([
                        'status' => Semester::STATUS_LOCKED,
                        'is_score_input_open' => false,
                        'locked_at' => $semester->locked_at ?? now(),
                    ]);

                    AuditLogger::log(
                        'semester_auto_locked_by_school_year_archive',
                        Semester::class,
                        (string) $semester->getKey(),
                        'Tự động khóa học kỳ ' . $semester->name . ' khi lưu trữ năm học ' . $schoolYear->name
                    );
                }

                $semester->refresh();
                $this->archiveTimetableEntriesForSemester($semester, $schoolYear);
                $this->archiveAssignmentsForSemester($semester, $schoolYear);
                $this->archiveClassesForSemester($semester, $schoolYear);

                $semester->update([
                    'status' => Semester::STATUS_ARCHIVED,
                    'is_score_input_open' => false,
                    'archived_at' => $semester->archived_at ?? now(),
                ]);

                AuditLogger::log(
                    'semester_auto_archived_by_school_year_archive',
                    Semester::class,
                    (string) $semester->getKey(),
                    'Tự động lưu trữ học kỳ ' . $semester->name . ' khi lưu trữ năm học ' . $schoolYear->name
                );
            });

        $this->archiveTimetableEntriesWithoutSemesterForSchoolYear($schoolYear);
        $this->archiveAssignmentsWithoutSemesterForSchoolYear($schoolYear);
        $this->archiveClassesWithoutSemesterForSchoolYear($schoolYear);

        $hasUnarchivedSemester = $schoolYear->semesters()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', Semester::STATUS_ARCHIVED)
                    ->orWhereNull('archived_at');
            })
            ->exists();

        if ($hasUnarchivedSemester) {
            throw new \RuntimeException('Không thể lưu trữ năm học vì vẫn còn học kỳ chưa được lưu trữ.');
        }

        $hasUnarchivedClass = $schoolYear->classes()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', SchoolClass::STATUS_ARCHIVED)
                    ->orWhereNull('archived_at');
            })
            ->exists();

        if ($hasUnarchivedClass) {
            throw new \RuntimeException('Không thể lưu trữ năm học vì vẫn còn lớp học chưa được lưu trữ.');
        }
    }

    private function archiveClassesForSemester(Semester $semester, SchoolYear $schoolYear): void
    {
        SchoolClass::where('semester_id', $semester->getKey())
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', SchoolClass::STATUS_ARCHIVED)
                    ->orWhereNull('archived_at');
            })
            ->get()
            ->each(function (SchoolClass $class) use ($semester, $schoolYear) {
                $class->update([
                    'status' => SchoolClass::STATUS_ARCHIVED,
                    'archived_at' => $class->archived_at ?? now(),
                ]);

                AuditLogger::log(
                    'class_auto_archived_by_school_year_archive',
                    SchoolClass::class,
                    (string) $class->getKey(),
                    'Tự động lưu trữ lớp ' . $class->name . ' khi lưu trữ học kỳ ' . $semester->name . ' của năm học ' . $schoolYear->name
                );
            });
    }

    private function archiveTimetableEntriesForSemester(Semester $semester, SchoolYear $schoolYear): void
    {
        if (! Schema::hasTable('timetables') || ! Schema::hasTable('timetable_entries')) {
            return;
        }

        $timetableIds = Timetable::where('semester_id', $semester->getKey())->pluck('id');

        if ($timetableIds->isEmpty()) {
            return;
        }

        TimetableEntry::whereIn('timetable_id', $timetableIds)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', TimetableEntry::STATUS_ARCHIVED)
                    ->orWhereNull('archived_at');
            })
            ->get()
            ->each(function (TimetableEntry $entry) use ($semester, $schoolYear) {
                $entry->update([
                    'status' => TimetableEntry::STATUS_ARCHIVED,
                    'archived_at' => $entry->archived_at ?? now(),
                ]);

                AuditLogger::log(
                    'timetable_entry_auto_archived_by_school_year_archive',
                    TimetableEntry::class,
                    (string) $entry->getKey(),
                    'Tự động lưu trữ tiết học khi lưu trữ học kỳ ' . $semester->name . ' của năm học ' . $schoolYear->name
                );
            });
    }

    private function archiveAssignmentsForSemester(Semester $semester, SchoolYear $schoolYear): void
    {
        TeachingAssignment::where('semester_id', $semester->getKey())
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', TeachingAssignment::STATUS_ARCHIVED)
                    ->orWhereNull('archived_at');
            })
            ->get()
            ->each(function (TeachingAssignment $assignment) use ($semester, $schoolYear) {
                $assignment->update([
                    'status' => TeachingAssignment::STATUS_ARCHIVED,
                    'archived_at' => $assignment->archived_at ?? now(),
                ]);

                AuditLogger::log(
                    'teaching_assignment_auto_archived_by_school_year_archive',
                    TeachingAssignment::class,
                    (string) $assignment->getKey(),
                    'Tự động lưu trữ phân công khi lưu trữ học kỳ ' . $semester->name . ' của năm học ' . $schoolYear->name
                );
            });
    }

    private function archiveTimetableEntriesWithoutSemesterForSchoolYear(SchoolYear $schoolYear): void
    {
        if (! Schema::hasTable('timetables') || ! Schema::hasTable('timetable_entries')) {
            return;
        }

        $timetableIds = Timetable::where('school_year_id', $schoolYear->getKey())
            ->whereNull('semester_id')
            ->pluck('id');

        if ($timetableIds->isEmpty()) {
            return;
        }

        TimetableEntry::whereIn('timetable_id', $timetableIds)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', TimetableEntry::STATUS_ARCHIVED)
                    ->orWhereNull('archived_at');
            })
            ->get()
            ->each(function (TimetableEntry $entry) use ($schoolYear) {
                $entry->update([
                    'status' => TimetableEntry::STATUS_ARCHIVED,
                    'archived_at' => $entry->archived_at ?? now(),
                ]);

                AuditLogger::log(
                    'timetable_entry_auto_archived_by_school_year_archive',
                    TimetableEntry::class,
                    (string) $entry->getKey(),
                    'Tự động lưu trữ tiết học khi lưu trữ năm học ' . $schoolYear->name
                );
            });
    }

    private function archiveClassesWithoutSemesterForSchoolYear(SchoolYear $schoolYear): void
    {
        $schoolYear->classes()
            ->whereNull('semester_id')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', SchoolClass::STATUS_ARCHIVED)
                    ->orWhereNull('archived_at');
            })
            ->get()
            ->each(function (SchoolClass $class) use ($schoolYear) {
                $class->update([
                    'status' => SchoolClass::STATUS_ARCHIVED,
                    'archived_at' => $class->archived_at ?? now(),
                ]);

                AuditLogger::log(
                    'class_auto_archived_by_school_year_archive',
                    SchoolClass::class,
                    (string) $class->getKey(),
                    'Tự động lưu trữ lớp ' . $class->name . ' khi lưu trữ năm học ' . $schoolYear->name
                );
            });
    }

    private function archiveAssignmentsWithoutSemesterForSchoolYear(SchoolYear $schoolYear): void
    {
        TeachingAssignment::where('school_year_id', $schoolYear->getKey())
            ->whereNull('semester_id')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', TeachingAssignment::STATUS_ARCHIVED)
                    ->orWhereNull('archived_at');
            })
            ->get()
            ->each(function (TeachingAssignment $assignment) use ($schoolYear) {
                $assignment->update([
                    'status' => TeachingAssignment::STATUS_ARCHIVED,
                    'archived_at' => $assignment->archived_at ?? now(),
                ]);

                AuditLogger::log(
                    'teaching_assignment_auto_archived_by_school_year_archive',
                    TeachingAssignment::class,
                    (string) $assignment->getKey(),
                    'Tự động lưu trữ phân công khi lưu trữ năm học ' . $schoolYear->name
                );
            });
    }

    private function deleteCheck(SchoolYear $schoolYear): array
    {
        if ($schoolYear->is_active) {
            return [
                'allowed' => false,
                'message' => 'Không thể xóa năm học đang hoạt động.',
            ];
        }

        if ($schoolYear->isArchived()) {
            return [
                'allowed' => false,
                'message' => 'Không thể xóa năm học đã lưu trữ.',
            ];
        }

        if ($reason = $this->realBusinessDataBlockReason($schoolYear)) {
            return [
                'allowed' => false,
                'message' => 'Không thể xóa năm học vì đã phát sinh dữ liệu nghiệp vụ: ' . $reason . '.',
            ];
        }

        return [
            'allowed' => true,
            'message' => null,
        ];
    }

    private function realBusinessDataBlockReason(SchoolYear $schoolYear): ?string
    {
        $id = (string) $schoolYear->getKey();
        $classIds = $this->idsFor(SchoolClass::class, 'school_year_id', $id);
        $semesterIds = $this->idsFor(Semester::class, 'school_year_id', $id);

        $checks = [
            'Điểm số' => fn () => $this->modelHasRows(ScoreHeader::class, 'school_year_id', $id),
            'Điểm danh' => fn () => $this->modelHasRowsIn(AttendanceRecord::class, 'class_id', $classIds)
                || $this->modelHasRowsIn(AttendanceRecord::class, 'semester_id', $semesterIds),
            'Hạnh kiểm' => fn () => $this->modelHasRows(Conduct::class, 'school_year_id', $id),
            'Lịch kiểm tra' => fn () => $this->examSchedulesExistForYear($id, $classIds, $semesterIds),
            'Thời khóa biểu' => fn () => $this->modelHasRows(Timetable::class, 'school_year_id', $id),
            'Phân công giảng dạy' => fn () => $this->modelHasRows(TeachingAssignment::class, 'school_year_id', $id),
            'Thông báo' => fn () => $this->contentRowsExistForYear('school_posts', 'content', $id),
            'Sự kiện' => fn () => $this->contentRowsExistForYear('school_events', 'description', $id),
        ];

        foreach ($checks as $label => $exists) {
            if ($exists()) {
                return $label;
            }
        }

        return null;
    }

    private function deleteInitialSchoolYearData(SchoolYear $schoolYear): void
    {
        $id = (string) $schoolYear->getKey();
        $sourceYear = $this->initializedSourceYear($schoolYear);
        $classIds = $this->idsFor(SchoolClass::class, 'school_year_id', $id);
        $semesterIds = $this->idsFor(Semester::class, 'school_year_id', $id);

        if (Schema::hasTable('grade_windows')) {
            GradeWindow::where('school_year_id', $id)->delete();
        }

        $this->restorePromotedStudents($schoolYear, $sourceYear);
        $this->deleteRemainingStudentsForYear($id, $classIds);

        if (Schema::hasTable('semesters') && $semesterIds->isNotEmpty()) {
            Semester::whereIn('id', $semesterIds)->delete();
        }

        if (Schema::hasTable('classes') && $classIds->isNotEmpty()) {
            SchoolClass::whereIn('id', $classIds)->delete();
        }
    }

    private function restorePromotedStudents(SchoolYear $targetYear, ?SchoolYear $sourceYear): void
    {
        if (! $sourceYear || ! Schema::hasTable('classes') || ! Schema::hasTable('students')) {
            return;
        }

        $targetClasses = SchoolClass::where('school_year_id', $targetYear->getKey())->get();

        if ($targetClasses->isEmpty()) {
            return;
        }

        SchoolClass::where('school_year_id', $sourceYear->getKey())
            ->whereIn('grade_level', [10, 11])
            ->get()
            ->each(function (SchoolClass $sourceClass) use ($targetYear, $targetClasses) {
                $targetGrade = (int) $sourceClass->grade_level + 1;
                $baseTargetName = $this->promotedClassName($sourceClass->name, (int) $sourceClass->grade_level, $targetGrade);
                $targetClass = $targetClasses->first(function (SchoolClass $class) use ($targetGrade, $baseTargetName, $targetYear) {
                    return (int) $class->grade_level === $targetGrade
                        && $this->isPromotedClassCandidate($class->name, $baseTargetName, $targetYear->name);
                });

                if (! $targetClass) {
                    return;
                }

                Student::where('school_year_id', $targetYear->getKey())
                    ->where('class_id', $targetClass->getKey())
                    ->update([
                        'school_year_id' => $sourceClass->school_year_id,
                        'class_id' => $sourceClass->getKey(),
                    ]);
            });
    }

    private function deleteRemainingStudentsForYear(string $schoolYearId, $classIds): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        $studentIds = Student::where(function ($query) use ($schoolYearId, $classIds) {
            $query->where('school_year_id', $schoolYearId);

            if ($classIds->isNotEmpty()) {
                $query->orWhereIn('class_id', $classIds);
            }
        })->pluck('id');

        if ($studentIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('parent_student')) {
            DB::table('parent_student')->whereIn('student_id', $studentIds)->delete();
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'student_id')) {
            DB::table('users')->whereIn('student_id', $studentIds)->update(['student_id' => null]);
        }

        Student::whereIn('id', $studentIds)->delete();
    }

    private function initializedSourceYear(SchoolYear $targetYear): ?SchoolYear
    {
        if (! Schema::hasTable('audit_logs')) {
            return null;
        }

        $log = AuditLog::where('action', 'school_year_initialized')
            ->where('entity_id', (string) $targetYear->getKey())
            ->latest('created_at')
            ->first();

        $decoded = json_decode((string) $log?->description, true);
        $sourceYearName = is_array($decoded) ? ($decoded['source_year_name'] ?? null) : null;

        return $sourceYearName ? SchoolYear::where('name', $sourceYearName)->first() : null;
    }

    private function idsFor(string $model, string $column, string $value)
    {
        if (! $this->modelHasColumn($model, $column)) {
            return collect();
        }

        return $model::where($column, $value)->pluck('id');
    }

    private function modelHasRows(string $model, string $column, string $value): bool
    {
        return $this->modelHasColumn($model, $column)
            && $model::where($column, $value)->exists();
    }

    private function modelHasRowsIn(string $model, string $column, $values): bool
    {
        return $this->modelHasColumn($model, $column)
            && $values->isNotEmpty()
            && $model::whereIn($column, $values)->exists();
    }

    private function modelHasColumn(string $model, string $column): bool
    {
        $instance = new $model();

        return Schema::hasTable($instance->getTable())
            && Schema::hasColumn($instance->getTable(), $column);
    }

    private function examSchedulesExistForYear(string $schoolYearId, $classIds, $semesterIds): bool
    {
        if (! Schema::hasTable('exam_schedules')) {
            return false;
        }

        $hasSchoolYearColumn = Schema::hasColumn('exam_schedules', 'school_year_id');
        $hasClassColumn = Schema::hasColumn('exam_schedules', 'class_id');
        $hasSemesterColumn = Schema::hasColumn('exam_schedules', 'semester_id');
        $hasNoteColumn = Schema::hasColumn('exam_schedules', 'note');

        if (! $hasSchoolYearColumn && ! $hasClassColumn && ! $hasSemesterColumn && ! $hasNoteColumn) {
            return false;
        }

        return ExamSchedule::where(function ($query) use ($schoolYearId, $classIds, $semesterIds) {
            if (Schema::hasColumn('exam_schedules', 'school_year_id')) {
                $query->orWhere('school_year_id', $schoolYearId);
            }

            if ($classIds->isNotEmpty()) {
                $query->orWhereIn('class_id', $classIds);
            }

            if ($semesterIds->isNotEmpty()) {
                $query->orWhereIn('semester_id', $semesterIds);
            }

            if (Schema::hasColumn('exam_schedules', 'note')) {
                $query->orWhere('note', 'like', '%"school_year_id":"' . $schoolYearId . '"%');
            }
        })->exists();
    }

    private function contentRowsExistForYear(string $table, string $metaColumn, string $schoolYearId): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $hasSchoolYearColumn = Schema::hasColumn($table, 'school_year_id');
        $hasMetaColumn = Schema::hasColumn($table, $metaColumn);

        if (! $hasSchoolYearColumn && ! $hasMetaColumn) {
            return false;
        }

        return DB::table($table)
            ->where(function ($query) use ($table, $metaColumn, $schoolYearId) {
                if (Schema::hasColumn($table, 'school_year_id')) {
                    $query->orWhere('school_year_id', $schoolYearId);
                }

                if (Schema::hasColumn($table, $metaColumn)) {
                    $query->orWhere($metaColumn, 'like', '%"school_year_id":"' . $schoolYearId . '"%');
                }
            })
            ->exists();
    }

    private function isPromotedClassCandidate(string $className, string $baseName, string $targetYearName): bool
    {
        if ($className === $baseName) {
            return true;
        }

        $candidate = $baseName . ' - ' . $targetYearName;

        return $className === $candidate
            || preg_match('/^' . preg_quote($candidate, '/') . ' \(\d+\)$/', $className) === 1;
    }

    private function rememberHistoryMode(Request $request, SchoolYear $schoolYear): void
    {
        $semesterId = $request->session()->get('working_semester_id');
        $semester = $semesterId ? Semester::find($semesterId) : null;

        if (! $semester || (string) $semester->school_year_id !== (string) $schoolYear->getKey()) {
            $semester = Semester::where('school_year_id', $schoolYear->getKey())
                ->orderByRaw("case when status = 'active' then 0 when status = 'inactive' then 1 else 2 end")
                ->orderBy('order')
                ->orderBy('name')
                ->first();
        }

        $request->session()->put([
            'history_school_year_id' => $schoolYear->id,
            'working_school_year_id' => $schoolYear->id,
            'viewing_mode' => 'history',
            'viewing_school_year_id' => $schoolYear->id,
            'viewing_school_year_name' => $schoolYear->name,
        ]);

        if ($semester) {
            $request->session()->put('working_semester_id', $semester->getKey());
        } else {
            $request->session()->forget('working_semester_id');
        }
    }

    private function clearHistoryContext(Request $request): void
    {
        $request->session()->forget([
            'history_school_year_id',
            'working_school_year_id',
            'working_semester_id',
            'viewing_mode',
            'viewing_school_year_id',
            'viewing_school_year_name',
        ]);
    }

    private function cleanPreviousUrl(Request $request): string
    {
        $previous = url()->previous() ?: route('dashboard');
        $parts = parse_url($previous);

        if (! $parts || empty($parts['path'])) {
            return route('dashboard');
        }

        $base = ($parts['scheme'] ?? $request->getScheme()) . '://' . ($parts['host'] ?? $request->getHost());

        if (! empty($parts['port'])) {
            $base .= ':' . $parts['port'];
        }

        return $base . $parts['path'];
    }

    private function formatYearName(int $startYear, int $endYear): string
    {
        return trim($startYear . ' - ' . $endYear);
    }

    private function splitYearName(?string $name): array
    {
        if (preg_match('/(\d{4})\s*-\s*(\d{4})/', (string) $name, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        return [null, null];
    }

    private function yearNameExists(string $name, ?SchoolYear $except = null): bool
    {
        $query = SchoolYear::where('name', $name);

        if ($except) {
            $query->whereKeyNot($except->getKey());
        }

        return $query->exists();
    }

    private function hasDependentData(SchoolYear $schoolYear): bool
    {
        $id = $schoolYear->getKey();

        $checks = [
            [Semester::class, 'school_year_id'],
            [SchoolClass::class, 'school_year_id'],
            [TeachingAssignment::class, 'school_year_id'],
            [Timetable::class, 'school_year_id'],
            [ExamSchedule::class, 'school_year_id'],
            [ScoreHeader::class, 'school_year_id'],
            [Conduct::class, 'school_year_id'],
            [GradeWindow::class, 'school_year_id'],
        ];

        foreach ($checks as [$model, $column]) {
            $instance = new $model();
            if (Schema::hasTable($instance->getTable()) && Schema::hasColumn($instance->getTable(), $column) && $model::where($column, $id)->exists()) {
                return true;
            }
        }

        if (Schema::hasTable('attendance_records')) {
            $classIds = SchoolClass::where('school_year_id', $id)->pluck('id');

            if ($classIds->isNotEmpty() && AttendanceRecord::whereIn('class_id', $classIds)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function promotedClassName(string $sourceName, int $sourceGrade, int $targetGrade): string
    {
        $name = preg_replace('/^' . preg_quote((string) $sourceGrade, '/') . '/', (string) $targetGrade, $sourceName, 1);

        if ($name === $sourceName) {
            return $targetGrade . $sourceName;
        }

        return $name;
    }

    private function uniqueClassName(string $baseName, string $targetYearName): string
    {
        if (! SchoolClass::where('name', $baseName)->exists()) {
            return $baseName;
        }

        $candidate = $baseName . ' - ' . $targetYearName;
        if (! SchoolClass::where('name', $candidate)->exists()) {
            return $candidate;
        }

        $suffix = 2;
        while (SchoolClass::where('name', $candidate . ' (' . $suffix . ')')->exists()) {
            $suffix++;
        }

        return $candidate . ' (' . $suffix . ')';
    }
}
