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
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SchoolYearController extends Controller
{
    private const INITIALIZE_OPTIONS = [
        'subjects' => 'Danh sách môn học',
        'teachers' => 'Danh sách giáo viên',
        'rooms' => 'Danh sách phòng học',
        'students' => 'Hồ sơ học sinh',
        'documents' => 'Tài liệu học tập',
        'promote_students' => 'Thăng lớp học sinh',
        'graduate_grade_12' => 'Đánh dấu học sinh lớp 12 đã tốt nghiệp',
    ];

    public function index(Request $request)
    {
        if ($request->session()->get('viewing_mode') === 'archive' || $request->session()->has('history_school_year_id')) {
            $this->clearHistoryContext($request);
        }

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
        if ($schoolYear->isArchived()) {
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

        DB::transaction(function () use ($data) {
            if ($data['is_active']) {
                SchoolYear::where('is_active', true)->update(['is_active' => false]);
            }

            $schoolYear = SchoolYear::create($data);
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

        DB::transaction(function () use ($schoolYear, $data) {
            if (! $schoolYear->is_active && $data['is_active']) {
                SchoolYear::where('is_active', true)->whereKeyNot($schoolYear->getKey())->update(['is_active' => false]);
            }

            $schoolYear->update($data);
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

        DB::transaction(function () use ($schoolYear) {
            SchoolYear::where('is_active', true)->whereKeyNot($schoolYear->getKey())->update(['is_active' => false]);
            $schoolYear->update(['is_active' => true, 'archived_at' => null]);
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

        $schoolYear->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);
        AuditLogger::log('school_year_archived', SchoolYear::class, (string) $schoolYear->getKey(), 'Lưu trữ năm học ' . $schoolYear->name);

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

        return redirect()->route('dashboard')->with('success', 'Đã quay về năm học hiện tại.');
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
            $copiedDocuments = 0;

            if (in_array('promote_students', $data['options'], true)) {
                [$classMap, $createdClasses] = $this->createPromotionClasses($sourceYear, $targetYear);
                $promotedStudents = $this->promoteStudents($classMap, $targetYear);
            }

            if (in_array('graduate_grade_12', $data['options'], true)) {
                $graduatedStudents = $this->graduateGrade12Students($sourceYear);
            }

            if (in_array('documents', $data['options'], true)) {
                $copiedDocuments = $this->copyLearningDocuments($sourceYear, $classMap);
            }

            $summary = [
                'target_year_id' => $targetYear->getKey(),
                'target_year_name' => $targetYear->name,
                'source_year_name' => $sourceYear->name,
                'created_classes' => $createdClasses,
                'promoted_students' => $promotedStudents,
                'graduated_students' => $graduatedStudents,
                'copied_documents' => $copiedDocuments,
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
                    'documents_copied' => $copiedDocuments,
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

        return [$sourceYear, $validated];
    }

    private function buildInitializationPreview(SchoolYear $sourceYear, string $targetName, array $selectedOptions): array
    {
        $counts = [
            'subjects' => in_array('subjects', $selectedOptions, true) ? Subject::count() : 0,
            'teachers' => in_array('teachers', $selectedOptions, true) ? Teacher::count() : 0,
            'rooms' => in_array('rooms', $selectedOptions, true) ? $this->roomCount($sourceYear) : 0,
            'students' => in_array('students', $selectedOptions, true)
                ? Student::where('school_year_id', $sourceYear->getKey())->count()
                : 0,
            'documents' => in_array('documents', $selectedOptions, true) ? $this->documentCount($sourceYear) : 0,
            'promote_students' => in_array('promote_students', $selectedOptions, true) ? $this->promotableStudentCount($sourceYear) : 0,
            'graduate_grade_12' => in_array('graduate_grade_12', $selectedOptions, true) ? $this->graduatableStudentCount($sourceYear) : 0,
        ];

        return [
            'source_year' => $sourceYear,
            'target_name' => $targetName,
            'selected_options' => $selectedOptions,
            'counts' => $counts,
        ];
    }

    private function createPromotionClasses(SchoolYear $sourceYear, SchoolYear $targetYear): array
    {
        $classMap = [];
        $created = 0;

        SchoolClass::where('school_year_id', $sourceYear->getKey())
            ->whereIn('grade_level', [10, 11])
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

    private function promoteStudents(array $classMap, SchoolYear $targetYear): int
    {
        if (! $classMap) {
            return 0;
        }

        $students = Student::whereIn('class_id', array_keys($classMap))
            ->where('status', 'studying')
            ->get();

        $students->each(function (Student $student) use ($classMap, $targetYear) {
            $student->update([
                'class_id' => $classMap[$student->class_id],
                'school_year_id' => $targetYear->getKey(),
                'status' => 'studying',
            ]);
        });

        return $students->count();
    }

    private function graduateGrade12Students(SchoolYear $sourceYear): int
    {
        $classIds = SchoolClass::where('school_year_id', $sourceYear->getKey())
            ->where('grade_level', 12)
            ->pluck('id');

        if ($classIds->isEmpty()) {
            return 0;
        }

        return Student::whereIn('class_id', $classIds)
            ->where('status', 'studying')
            ->update(['status' => 'graduated']);
    }

    private function copyLearningDocuments(SchoolYear $sourceYear, array $classMap): int
    {
        if (! Schema::hasTable('learning_documents') || ! $classMap) {
            return 0;
        }

        $copied = 0;

        LearningDocument::whereIn('class_id', array_keys($classMap))
            ->orderBy('created_at')
            ->get()
            ->each(function (LearningDocument $document) use ($classMap, &$copied) {
                $targetClassId = $classMap[$document->class_id] ?? null;

                if (! $targetClassId) {
                    return;
                }

                $exists = LearningDocument::where('title', $document->title)
                    ->where('file_url', $document->file_url)
                    ->where('class_id', $targetClassId)
                    ->exists();

                if ($exists) {
                    return;
                }

                LearningDocument::create([
                    'title' => $document->title,
                    'description' => $document->getRawOriginal('description'),
                    'category' => $document->category,
                    'file_url' => $document->file_url,
                    'subject_id' => $document->subject_id,
                    'class_id' => $targetClassId,
                    'uploaded_by' => $document->uploaded_by,
                    'is_published' => $document->is_published,
                ]);

                $copied++;
            });

        return $copied;
    }

    private function roomCount(SchoolYear $sourceYear): int
    {
        if (! Schema::hasTable('timetable_entries') || ! Schema::hasTable('timetables')) {
            return 0;
        }

        return TimetableEntry::whereHas('timetable', function ($query) use ($sourceYear) {
                $query->where('school_year_id', $sourceYear->getKey());
            })
            ->whereNotNull('room')
            ->where('room', '!=', '')
            ->distinct()
            ->count('room');
    }

    private function documentCount(SchoolYear $sourceYear): int
    {
        if (! Schema::hasTable('learning_documents')) {
            return 0;
        }

        $classIds = SchoolClass::where('school_year_id', $sourceYear->getKey())->pluck('id');

        if ($classIds->isEmpty()) {
            return 0;
        }

        return LearningDocument::whereIn('class_id', $classIds)->count();
    }

    private function promotableStudentCount(SchoolYear $sourceYear): int
    {
        $classIds = SchoolClass::where('school_year_id', $sourceYear->getKey())
            ->whereIn('grade_level', [10, 11])
            ->pluck('id');

        if ($classIds->isEmpty()) {
            return 0;
        }

        return Student::whereIn('class_id', $classIds)->where('status', 'studying')->count();
    }

    private function graduatableStudentCount(SchoolYear $sourceYear): int
    {
        $classIds = SchoolClass::where('school_year_id', $sourceYear->getKey())
            ->where('grade_level', 12)
            ->pluck('id');

        if ($classIds->isEmpty()) {
            return 0;
        }

        return Student::whereIn('class_id', $classIds)->where('status', 'studying')->count();
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
                'label' => 'Lịch thi',
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
            'Lịch thi' => fn () => $this->examSchedulesExistForYear($id, $classIds, $semesterIds),
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

        if ($classIds->isNotEmpty() && Schema::hasTable('learning_documents')) {
            LearningDocument::whereIn('class_id', $classIds)->delete();
        }

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
        $request->session()->put([
            'history_school_year_id' => $schoolYear->id,
            'viewing_mode' => 'archive',
            'viewing_school_year_id' => $schoolYear->id,
            'viewing_school_year_name' => $schoolYear->name,
        ]);
    }

    private function clearHistoryContext(Request $request): void
    {
        $request->session()->forget([
            'history_school_year_id',
            'viewing_mode',
            'viewing_school_year_id',
            'viewing_school_year_name',
        ]);
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
