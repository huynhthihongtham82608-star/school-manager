<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreColumn;
use App\Models\ScoreDetail;
use App\Models\ScoreHeader;
use App\Models\ScoreSetting;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentClassAssignment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ScoreController extends Controller
{
    private const SCORE_PATTERN = '/^(10(\.0)?|[0-9](\.[0-9])?)$/';

    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedYearId = $this->selectedSchoolYearId($request);
        $selectedSemesterId = $this->selectedSemesterId($request);
        $years = SchoolYear::all();
        $semesters = Semester::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->get();
        $subjects = $this->availableSubjectsFor($user, $selectedYearId, $selectedSemesterId);
        $classes = $this->availableClassesFor($user, $selectedYearId, $selectedSemesterId);
        $scoreSetting = ScoreSetting::current();
        $teachers = collect();
        $student = null;
        $studentScores = collect();
        $studentReportRows = collect();
        $studentReportColumnHeaders = collect();
        $studentReportColumnsBySubject = collect();
        $studentReportGlobalGpa = null;
        $studentReportAnnualSummary = [
            'hk1_gpa' => null,
            'hk2_gpa' => null,
            'year_gpa' => null,
        ];
        $scoreColumnConfig = null;
        $adminMatrix = null;

        if ($user->isStudent() || $user->isParent()) {
            if ($user->isStudent()) {
                $student = $user->student?->load('classRoom');
            } elseif ($user->parentProfile) {
                $children = $user->parentProfile->students()
                    ->with('classRoom')
                    ->orderBy('student_code')
                    ->get();
                $student = $children->firstWhere('id', session('selected_parent_student_id')) ?: $children->first();
            }

            if ($student) {
                $reportData = $this->studentReportData($student, $selectedYearId, $selectedSemesterId);
                $years = $reportData['years'];
                $semesters = $reportData['semesters'];
                $selectedYearId = $reportData['selectedYearId'];
                $selectedSemesterId = $reportData['selectedSemesterId'];
                $studentScores = $reportData['scores'];
                $studentReportRows = $reportData['rows'];
                $studentReportColumnHeaders = $reportData['headers'];
                $studentReportColumnsBySubject = $reportData['columnsBySubject'];
                $studentReportGlobalGpa = $reportData['globalGpa'];
                $studentReportAnnualSummary = $reportData['annualSummary'];
            }

            $detailLabels = $this->detailLabels();

            return view('scores.index', compact('years', 'semesters', 'subjects', 'classes', 'selectedYearId', 'selectedSemesterId', 'student', 'studentScores', 'studentReportRows', 'studentReportColumnHeaders', 'studentReportColumnsBySubject', 'studentReportGlobalGpa', 'studentReportAnnualSummary', 'detailLabels', 'scoreSetting'));
        }

        $assignments = collect();
        if ($user->isAdmin() || $user->isStaff()) {
            $teachers = Teacher::orderBy('name')->get();
            $assignments = TeachingAssignment::query()
                ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->whereHas('subject', fn ($query) => $query->withEvaluatedAssessment())
                ->get(['teacher_id', 'class_id', 'subject_id', 'semester_id']);
            $scoreColumnConfig = $this->scoreColumnConfigData($request, $selectedYearId, $scoreSetting);
            $adminMatrix = $this->adminScoreMatrixPayload($request);
        }

        if ($user->isTeacher() && $user->teacher) {
            $assignments = $this->teacherScoreEntryAssignments($user->teacher);
        }

        return view('scores.index', compact('years', 'semesters', 'subjects', 'classes', 'teachers', 'assignments', 'selectedYearId', 'selectedSemesterId', 'scoreSetting', 'scoreColumnConfig', 'adminMatrix'));
    }

    public function reportCard(Request $request)
    {
        $user = Auth::user();

        if (! $user->isStudent() && ! $user->isParent()) {
            abort(403);
        }

        $student = $this->reportStudentForUser($user);
        if (! $student) {
            return response()->json([
                'message' => 'Không tìm thấy học sinh để tra cứu phiếu điểm.',
            ], 404);
        }

        $data = $this->studentReportData(
            $student,
            $request->query('school_year_id'),
            $request->query('semester_id')
        );

        return response()->json($this->serializeStudentReportData($data, $student));
    }

    public function exportReportCard(Request $request)
    {
        $user = Auth::user();

        if (! $user->isStudent() && ! $user->isParent()) {
            abort(403);
        }

        $student = $this->reportStudentForUser($user);
        if (! $student) {
            abort(404, 'Không tìm thấy học sinh để xuất phiếu điểm.');
        }

        $data = $this->studentReportData(
            $student,
            $request->query('school_year_id'),
            $request->query('semester_id')
        );
        $payload = $this->serializeStudentReportData($data, $student);
        $fileName = 'phieu_diem_' . Str::slug($student->student_code ?: $student->name ?: 'hoc_sinh') . '_' . now()->format('Ymd_His') . '.xls';
        $html = $this->reportCardExportHtml($payload);

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    public function cascade(Request $request)
    {
        $this->authorizeScoreAdminStream();
        $user = Auth::user();

        $yearId = $request->query('school_year_id') ?: $this->selectedSchoolYearId($request);
        $semesterId = $request->query('semester_id') ?: $this->selectedSemesterId($request);
        $gradeLevel = $request->query('grade_level');
        $teacherId = $request->query('teacher_id');
        $forcedHomeroomClass = null;

        if ($user->isTeacher() && ! $user->isAdmin() && ! $user->isStaff()) {
            $forcedHomeroomClass = $this->teacherHomeroomClassForScoreMatrix($user->teacher, $yearId);
            abort_unless($forcedHomeroomClass, 403, 'Chỉ giáo viên chủ nhiệm mới được xem ma trận điểm toàn lớp.');

            $yearId = $forcedHomeroomClass->school_year_id;
            $gradeLevel = $forcedHomeroomClass->grade_level;
            $teacherId = null;
            $request->merge([
                'school_year_id' => $yearId,
                'grade_level' => $gradeLevel,
                'class_id' => $forcedHomeroomClass->id,
                'teacher_id' => null,
            ]);
        }

        $class = $request->query('class_id')
            ? SchoolClass::with(['homeroomTeacher', 'students'])->find($request->query('class_id'))
            : null;

        if ($forcedHomeroomClass) {
            $class = $forcedHomeroomClass;
        }

        if ($class && (string) $class->school_year_id !== (string) $yearId) {
            $yearId = $class->school_year_id;
            $gradeLevel = $gradeLevel ?: $class->grade_level;
        }
        $classes = $forcedHomeroomClass
            ? $this->adminClassOptionCollection($forcedHomeroomClass)
            : $this->adminLinkedClasses($yearId, $gradeLevel, $teacherId);
        $subjects = $teacherId
            ? $this->adminSubjectsForTeacher((string) $teacherId, $yearId, $semesterId, $class?->id)
            : ($class
                ? $this->adminSubjectsForClass($class, $yearId, $semesterId)
                : $this->adminSubjectsForYear($yearId, $gradeLevel));
        $requestedSubjectId = $request->query('subject_id');
        $subjectId = $requestedSubjectId && $subjects->contains('id', $requestedSubjectId)
            ? $requestedSubjectId
            : ($subjects->first()?->id ?? null);
        $teachers = $class && $subjectId
            ? $this->adminSubjectTeachers($class, (string) $subjectId, $yearId, $semesterId)
            : ($teacherId
                ? $this->adminTeacherOption((string) $teacherId)
                : $this->adminTeachersForScope($yearId, $semesterId, $class?->id, $subjectId));
        $selectedTeacherId = $teacherId ?: ($teachers->count() === 1 ? $teachers->first()['id'] : null);

        return response()->json([
            'classes' => $classes,
            'class_context' => $class ? $this->serializeAdminClassContext($class) : null,
            'subjects' => $subjects->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'assessment_type' => $subject->normalizedAssessmentType(),
            ])->values(),
            'teachers' => $teachers,
            'selected_subject_id' => $subjectId,
            'selected_teacher_id' => $selectedTeacherId,
        ]);
    }

    public function adminMatrix(Request $request)
    {
        $this->authorizeScoreAdminStream();

        return response()->json($this->adminScoreMatrixPayload($request));
    }

    private function authorizeScoreAdminStream(): void
    {
        $user = Auth::user();

        if (! $user?->isAdmin() && ! $user?->isStaff() && ! $user?->isTeacher()) {
            abort(403);
        }
    }

    public function adminScoreMatrixPayload(Request $request): array
    {
        $user = Auth::user();
        $yearId = $request->query('school_year_id') ?: $this->selectedSchoolYearId($request);
        $years = SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $selectedYearId = $years->contains('id', $yearId) ? $yearId : ($years->first()?->id ?? $yearId);
        $forcedHomeroomClass = null;

        if ($user->isTeacher() && ! $user->isAdmin() && ! $user->isStaff()) {
            $forcedHomeroomClass = $this->teacherHomeroomClassForScoreMatrix($user->teacher, $selectedYearId);
            abort_unless($forcedHomeroomClass, 403, 'Chỉ giáo viên chủ nhiệm mới được xem ma trận điểm toàn lớp.');

            $selectedYearId = $forcedHomeroomClass->school_year_id;
            $request->merge([
                'school_year_id' => $selectedYearId,
                'grade_level' => $forcedHomeroomClass->grade_level,
                'class_id' => $forcedHomeroomClass->id,
                'teacher_id' => null,
            ]);
        }

        $semesters = Semester::where('school_year_id', $selectedYearId)
            ->orderBy('order')
            ->orderBy('name')
            ->get();
        $semesterId = $request->query('semester_id') ?: $this->selectedSemesterId($request);
        $selectedSemester = $semesters->firstWhere('id', $semesterId)
            ?: ($semesters->firstWhere('status', Semester::STATUS_ACTIVE) ?: $semesters->first());
        $selectedSemesterId = $selectedSemester?->id;
        $selectedTermIndex = $selectedSemester?->termIndex() ?? 1;
        $gradeLevel = $request->query('grade_level') ?: null;
        $classId = $request->query('class_id') ?: null;
        $selectedSubjectId = $request->query('subject_id') ?: null;
        $keyword = Str::lower(trim((string) $request->query('q', '')));
        $class = $classId
            ? SchoolClass::with(['homeroomTeacher', 'students'])->find($classId)
            : null;

        if ($forcedHomeroomClass) {
            $class = $forcedHomeroomClass;
            $gradeLevel = $class->grade_level;
        }

        if ($class && (string) $class->school_year_id !== (string) $selectedYearId) {
            $selectedYearId = $class->school_year_id;
            $semesters = Semester::where('school_year_id', $selectedYearId)
                ->orderBy('order')
                ->orderBy('name')
                ->get();
            $selectedSemester = $semesters->firstWhere('id', $semesterId)
                ?: ($semesters->firstWhere('status', Semester::STATUS_ACTIVE) ?: $semesters->first());
            $selectedSemesterId = $selectedSemester?->id;
            $selectedTermIndex = $selectedSemester?->termIndex() ?? 1;
            $gradeLevel = $gradeLevel ?: $class->grade_level;
        }

        $teacherId = $request->query('teacher_id') ?: null;
        if ($forcedHomeroomClass) {
            $teacherId = null;
        }

        $classes = $forcedHomeroomClass
            ? $this->adminClassOptionCollection($forcedHomeroomClass)
            : $this->adminLinkedClasses($selectedYearId, $gradeLevel, $teacherId);
        $availableSubjects = $class
            ? $this->adminSubjectsForClass($class, $selectedYearId, $selectedSemesterId)
            : ($gradeLevel ? $this->adminSubjectsForYear($selectedYearId, $gradeLevel) : collect());
        $allClassSubjects = $availableSubjects;

        $hinhThuc = Str::upper(trim((string) ($request->query('hinh_thuc_danh_gia') ?: ($request->query('evaluation_type') ?: Subject::ASSESSMENT_GRADE_10))));

        $selectedSubject = $selectedSubjectId ? $allClassSubjects->firstWhere('id', $selectedSubjectId) : null;
        $mode = match (true) {
            (bool) $class && (bool) $selectedSubject => 'subject_details',
            (bool) $class => 'class_subjects',
            (bool) $gradeLevel => 'grade_summary',
            default => 'empty',
        };

        if (! $selectedSubjectId && $mode === 'class_subjects') {
            $filteredSubjects = $availableSubjects->filter(function (Subject $subject) use ($hinhThuc) {
                $type = Str::upper(trim((string) $subject->assessment_type));
                $normType = Str::upper(trim((string) $subject->normalizedAssessmentType()));
                $isPassFail = $subject->usesPassFailAssessment()
                    || in_array($type, ['ASSESSMENT', 'PASS_FAIL', 'PASSFAIL', 'CD'], true)
                    || in_array($normType, ['ASSESSMENT', 'PASS_FAIL', 'PASSFAIL', 'CD'], true);

                if (in_array($hinhThuc, ['ASSESSMENT', 'PASS_FAIL', 'PASSFAIL', 'CD'], true)) {
                    return $isPassFail;
                }

                return ! $isPassFail && ! $subject->isNotEvaluated();
            })->values();

            $availableSubjects = $filteredSubjects->isNotEmpty() ? $filteredSubjects : $allClassSubjects;
        }

        $subjects = $selectedSubject ? collect([$selectedSubject]) : $availableSubjects;
        $students = $this->adminMatrixStudents($selectedYearId, $gradeLevel, $class, $teacherId);

        if ($keyword !== '') {
            $students = $students
                ->filter(fn (Student $student) => str_contains(Str::lower((string) $student->student_code), $keyword)
                    || str_contains(Str::lower($student->name), $keyword))
                ->values();
        }

        $headers = $mode === 'class_subjects' ? $this->adminMatrixHeaders($subjects) : collect();
        $rows = in_array($mode, ['grade_summary', 'class_subjects', 'subject_details'], true)
            ? $this->adminMatrixRows($students, $subjects, $selectedYearId, $selectedSemesterId, $mode, $allClassSubjects)
            : collect();
        $summary = $this->adminMatrixSummary($rows);
        $perPage = 45;

        return [
            'mode' => $mode,
            'filters' => [
                'school_year_id' => $selectedYearId,
                'semester_id' => $selectedSemesterId,
                'grade_level' => $gradeLevel,
                'class_id' => $class?->id,
                'subject_id' => $selectedSubject?->id,
                'hinh_thuc_danh_gia' => $hinhThuc,
                'q' => $request->query('q', ''),
            ],
            'years' => $years->map(fn (SchoolYear $year) => [
                'id' => $year->id,
                'name' => $year->name,
            ])->values(),
            'semesters' => $semesters->map(fn (Semester $semester) => [
                'id' => $semester->id,
                'name' => $semester->normalizedName(),
                'term_index' => $semester->termIndex(),
            ])->values(),
            'classes' => $classes,
            'class_context' => $class ? $this->serializeAdminClassContext($class, $selectedYearId) : null,
            'subjects' => $availableSubjects->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'assessment_type' => $subject->normalizedAssessmentType(),
            ])->values(),
            'headers' => $headers,
            'rows' => $rows->values(),
            'summary' => $summary,
            'selected_term_index' => $selectedTermIndex,
            'pagination' => [
                'total' => $rows->count(),
                'visible' => $rows->count(),
                'per_page' => $perPage,
                'show_controls' => $rows->count() > $perPage,
                'label' => 'Hiển thị ' . $rows->count() . ' trong tổng số ' . $rows->count() . ' học sinh',
            ],
        ];
    }

    private function teacherHomeroomClassForScoreMatrix(?Teacher $teacher, ?string $yearId): ?SchoolClass
    {
        if (! $teacher) {
            return null;
        }

        $query = SchoolClass::with(['homeroomTeacher', 'students'])
            ->where('homeroom_teacher_id', $teacher->id)
            ->whereIn('status', [SchoolClass::STATUS_ACTIVE, SchoolClass::STATUS_LOCKED, SchoolClass::STATUS_DRAFT])
            ->orderByDesc('created_at');

        $class = (clone $query)
            ->when($yearId, fn ($classQuery) => $classQuery->where('school_year_id', $yearId))
            ->first();

        return $class ?: $query->first();
    }

    private function adminClassOptionCollection(SchoolClass $class): Collection
    {
        return collect([[
            'id' => $class->id,
            'name' => $class->name,
            'grade_level' => (string) $class->grade_level,
            'homeroom_teacher' => $class->homeroomTeacher?->name,
        ]]);
    }

    private function adminLinkedClasses(?string $yearId, $gradeLevel, ?string $teacherId = null): Collection
    {
        $assignedClassIds = $teacherId
            ? TeachingAssignment::query()
                ->where('teacher_id', $teacherId)
                ->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->pluck('class_id')
                ->unique()
            : collect();

        return SchoolClass::with('homeroomTeacher')
            ->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
            ->when($gradeLevel, fn ($query) => $query->where('grade_level', $gradeLevel))
            ->when($teacherId, fn ($query) => $query->whereIn('id', $assignedClassIds))
            ->whereIn('status', [SchoolClass::STATUS_ACTIVE, SchoolClass::STATUS_LOCKED, SchoolClass::STATUS_DRAFT])
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get()
            ->map(fn (SchoolClass $class) => [
                'id' => $class->id,
                'name' => $class->name,
                'grade_level' => (string) $class->grade_level,
                'homeroom_teacher' => $class->homeroomTeacher?->name,
            ])
            ->values();
    }

    private function serializeAdminClassContext(SchoolClass $class, ?string $yearId = null): array
    {
        $assignments = TeachingAssignment::with('teacher')
            ->where('class_id', $class->id)
            ->when($yearId, fn ($q) => $q->where('school_year_id', $yearId))
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->get();

        $subjectTeachers = $assignments->groupBy('subject_id')->map(function ($group) {
            return $group->map(fn ($assignment) => $assignment->teacher?->name)->filter()->unique()->join(', ');
        })->all();

        return [
            'id' => $class->id,
            'name' => $class->name,
            'grade_level' => (string) $class->grade_level,
            'class_teacher' => $class->homeroomTeacher ? [
                'id' => $class->homeroomTeacher->id,
                'name' => $class->homeroomTeacher->name,
                'teacher_code' => $class->homeroomTeacher->teacher_code,
            ] : null,
            'subject_teachers' => $subjectTeachers,
            'students' => $class->students
                ->sortBy('student_code')
                ->map(fn (Student $student) => [
                    'id' => $student->id,
                    'student_code' => $student->student_code,
                    'name' => $student->name,
                ])
                ->values(),
        ];
    }

    private function adminSubjectsForYear(?string $yearId, $gradeLevel): Collection
    {
        return Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->when(in_array((int) $gradeLevel, [10, 11, 12], true), fn ($query) => $query->forGrade((int) $gradeLevel))
            ->withEvaluatedAssessment()
            ->orderBy('name')
            ->get();
    }

    private function adminSubjectsForClass(SchoolClass $class, ?string $yearId, ?string $semesterId): Collection
    {
        return Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->forGrade((int) $class->grade_level)
            ->withEvaluatedAssessment()
            ->orderBy('name')
            ->get();
    }

    private function adminSubjectsForTeacher(string $teacherId, ?string $yearId, ?string $semesterId, ?string $classId = null): Collection
    {
        $assignedSubjectIds = TeachingAssignment::query()
            ->where('teacher_id', $teacherId)
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->pluck('subject_id')
            ->unique();

        return Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->whereIn('id', $assignedSubjectIds)
            ->when($classId, function ($query) use ($classId) {
                $class = SchoolClass::find($classId);
                if ($class) {
                    $query->forGrade((int) $class->grade_level);
                }
            })
            ->withEvaluatedAssessment()
            ->orderBy('name')
            ->get();
    }

    private function adminSubjectTeachers(SchoolClass $class, string $subjectId, ?string $yearId, ?string $semesterId): Collection
    {
        return TeachingAssignment::with('teacher')
            ->where('class_id', $class->id)
            ->where('subject_id', $subjectId)
            ->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->get()
            ->map(fn (TeachingAssignment $assignment) => [
                'id' => $assignment->teacher?->id,
                'name' => $assignment->teacher?->name,
                'teacher_code' => $assignment->teacher?->teacher_code,
                'subject_id' => $assignment->subject_id,
            ])
            ->filter(fn (array $teacher) => $teacher['id'])
            ->unique('id')
            ->values();
    }

    private function adminTeacherOption(string $teacherId): Collection
    {
        $teacher = Teacher::find($teacherId);

        if (! $teacher) {
            return collect();
        }

        return collect([[
            'id' => $teacher->id,
            'name' => $teacher->name,
            'teacher_code' => $teacher->teacher_code,
            'subject_id' => null,
        ]]);
    }

    private function adminTeachersForScope(?string $yearId, ?string $semesterId, ?string $classId = null, ?string $subjectId = null): Collection
    {
        $teacherIds = TeachingAssignment::query()
            ->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->pluck('teacher_id')
            ->unique()
            ->filter();

        return Teacher::whereIn('id', $teacherIds)
            ->orderBy('name')
            ->get()
            ->map(fn (Teacher $teacher) => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'teacher_code' => $teacher->teacher_code,
                'subject_id' => null,
            ])
            ->values();
    }

    private function adminMatrixHeaders(Collection $subjects): Collection
    {
        return $subjects
            ->reject(fn (Subject $subject) => $subject->isNotEvaluated())
            ->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'assessment_type' => $subject->normalizedAssessmentType(),
                'uses_pass_fail' => $subject->usesPassFailAssessment(),
            ])
            ->values();
    }

    private function adminMatrixStudents(?string $yearId, $gradeLevel, ?SchoolClass $class = null, ?string $teacherId = null): Collection
    {
        $statusFilter = function ($query) {
            $query->whereIn('status', [Student::STATUS_STUDYING, 'studying', 'active', '1'])
                ->orWhereNull('status');
        };

        if ($class) {
            return $class->students()
                ->with('classRoom')
                ->where($statusFilter)
                ->orderBy('student_code')
                ->orderBy('name')
                ->get();
        }

        if (! $gradeLevel) {
            return collect();
        }

        $classIds = SchoolClass::query()
            ->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
            ->where(fn ($q) => $q->where('grade_level', (string) $gradeLevel)->orWhere('grade_level', (int) $gradeLevel))
            ->when($teacherId, function ($query) use ($teacherId, $yearId) {
                $assignedClassIds = TeachingAssignment::query()
                    ->where('teacher_id', $teacherId)
                    ->when($yearId, fn ($assignmentQuery) => $assignmentQuery->where('school_year_id', $yearId))
                    ->where('status', TeachingAssignment::STATUS_ACTIVE)
                    ->pluck('class_id')
                    ->unique();

                $query->whereIn('id', $assignedClassIds);
            })
            ->pluck('id');

        return Student::with('classRoom')
            ->whereIn('class_id', $classIds)
            ->where($statusFilter)
            ->orderBy('class_id')
            ->orderBy('student_code')
            ->orderBy('name')
            ->get();
    }

    private function adminMatrixRows(Collection $students, Collection $subjects, ?string $yearId, ?string $semesterId, string $mode = 'class_subjects', ?Collection $allSubjects = null): Collection
    {
        $studentIds = $students->pluck('id');
        $ledgerSubjects = $allSubjects && $allSubjects->isNotEmpty() ? $allSubjects : $subjects;
        $allSubjectIds = $ledgerSubjects->pluck('id');
        $scoreHeaders = ScoreHeader::with(['subject', 'semester', 'details.scoreColumn'])
            ->whereIn('student_id', $studentIds)
            ->whereIn('subject_id', $allSubjectIds)
            ->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
            ->get();
        $currentScores = ($semesterId ? $scoreHeaders->where('semester_id', $semesterId) : $scoreHeaders)
            ->keyBy(fn (ScoreHeader $header) => $header->student_id . ':' . $header->subject_id);
        $currentScoresByStudent = ($semesterId ? $scoreHeaders->where('semester_id', $semesterId) : $scoreHeaders)
            ->groupBy('student_id');
        $scoresByStudent = $scoreHeaders->groupBy('student_id');

        return $students->map(function (Student $student) use ($subjects, $ledgerSubjects, $currentScores, $currentScoresByStudent, $scoresByStudent, $mode) {
            $studentScores = collect($scoresByStudent->get($student->id, []));
            $currentStudentScores = collect($currentScoresByStudent->get($student->id, []));
            $annual = $this->studentAnnualAveragesBySubject($ledgerSubjects, $studentScores);
            $cells = $subjects->map(function (Subject $subject) use ($currentScores, $student) {
                $header = $currentScores->get($student->id . ':' . $subject->id);

                return [
                    'subject_id' => $subject->id,
                    'value' => $this->adminScoreCellText($header, $subject),
                    'numeric' => $subject->usesNumericAssessment(),
                    'muted' => ! $header || ($header->average === null && $subject->usesNumericAssessment()),
                ];
            })->values();

            return [
                'student' => [
                    'id' => $student->id,
                    'student_code' => $student->student_code,
                    'name' => $student->name,
                    'class_name' => $student->classRoom?->name,
                ],
                'cells' => $cells,
                'detail_cells' => $mode === 'subject_details'
                    ? $this->adminSubjectDetailCells($subjects->first(), $currentStudentScores->firstWhere('subject_id', $subjects->first()?->id))
                    : [],
                'summary' => $this->serializeAnnualSummary($annual['summary']),
                'ledger' => $this->adminStudentLedger($ledgerSubjects, $currentStudentScores),
            ];
        })->values();
    }

    private function adminSubjectDetailCells(?Subject $subject, ?ScoreHeader $header): array
    {
        if (! $subject || $subject->isNotEvaluated()) {
            return [
                'oral' => null,
                'fifteen_1' => null,
                'fifteen_2' => null,
                'midterm' => null,
                'final' => null,
                'average' => null,
            ];
        }

        if ($subject->usesPassFailAssessment()) {
            return [
                'oral' => null,
                'fifteen_1' => null,
                'fifteen_2' => null,
                'midterm' => null,
                'final' => null,
                'average' => $this->adminScoreCellText($header, $subject),
            ];
        }

        $detailsByFamily = collect($header?->details ?? [])
            ->filter(fn (ScoreDetail $detail) => $detail->value !== null)
            ->groupBy(fn (ScoreDetail $detail) => $detail->scoreColumn ? $this->scoreColumnReportFamily($detail->scoreColumn) : $detail->type)
            ->map(fn ($details) => $details
                ->sortBy(fn (ScoreDetail $detail) => [$detail->scoreColumn?->sort_order ?? 999, $detail->name])
                ->values());
        $format = fn (?ScoreDetail $detail) => $detail
            ? rtrim(rtrim(number_format((float) $detail->value, 1, '.', ''), '0'), '.')
            : null;

        return [
            'oral' => $format($detailsByFamily->get('oral', collect())->get(0)),
            'fifteen_1' => $format($detailsByFamily->get('fifteen', collect())->get(0)),
            'fifteen_2' => $format($detailsByFamily->get('fifteen', collect())->get(1)),
            'midterm' => $format($detailsByFamily->get('midterm', collect())->get(0)),
            'final' => $format($detailsByFamily->get('final', collect())->get(0)),
            'average' => $this->adminScoreCellText($header, $subject) ?: null,
        ];
    }

    private function adminScoreCellText(?ScoreHeader $header, Subject $subject): string
    {
        if (! $header) {
            return '';
        }

        if ($subject->usesNumericAssessment()) {
            return $header->average !== null ? number_format((float) $header->average, 1, '.', '') : '';
        }

        if ($subject->usesPassFailAssessment()) {
            $details = $header->details->whereNotNull('value');

            if ($details->isEmpty()) {
                return '';
            }

            return (float) $details->avg('value') >= 0.5 ? 'Đạt' : 'Chưa đạt';
        }

        return '';
    }

    private function adminStudentLedger(Collection $subjects, Collection $studentScores): array
    {
        $scoresBySubject = $studentScores->keyBy('subject_id');

        return $subjects->map(function (Subject $subject) use ($scoresBySubject) {
            $header = $scoresBySubject->get($subject->id);
            $details = $header?->details
                ? $header->details
                    ->filter(fn (ScoreDetail $detail) => $detail->value !== null)
                    ->sortBy(fn (ScoreDetail $detail) => [$detail->scoreColumn?->sort_order ?? 999, $detail->name])
                    ->map(fn (ScoreDetail $detail) => [
                        'label' => $detail->scoreColumn?->name ?: ($detail->name ?: $detail->type),
                        'family' => $detail->scoreColumn ? $this->scoreColumnReportFamily($detail->scoreColumn) : $detail->type,
                        'value' => $subject->usesPassFailAssessment()
                            ? ((float) $detail->value >= 0.5 ? 'Đ' : 'CĐ')
                            : rtrim(rtrim(number_format((float) $detail->value, 1, '.', ''), '0'), '.'),
                        'is_retest' => (bool) $detail->is_retest,
                        'tooltip' => $detail->is_retest ? ($this->serializedRetestMeta($detail)['retest_tooltip'] ?? null) : null,
                    ])
                    ->values()
                : collect();

            return [
                'subject_name' => $subject->name,
                'assessment_type' => $subject->normalizedAssessmentType(),
                'average' => $subject->usesPassFailAssessment()
                    ? match ($this->adminScoreCellText($header, $subject)) {
                        'Đạt', 'Đ' => 'Đạt',
                        'Chưa Đạt', 'Chưa đạt', 'CĐ' => 'Chưa đạt',
                        default => '',
                    }
                    : $this->adminScoreCellText($header, $subject),
                'details' => $details,
            ];
        })->values()->all();
    }

    private function adminMatrixSummary(Collection $rows): array
    {
        $termAverage = function (string $key) use ($rows): ?string {
            $values = $rows
                ->map(fn (array $row) => $row['summary'][$key] ?? null)
                ->filter(fn ($value) => $value !== null)
                ->map(fn ($value) => (float) $value)
                ->values();

            return $values->isNotEmpty() ? number_format((float) $values->avg(), 1, '.', '') : null;
        };

        return [
            'hk1_gpa' => $termAverage('hk1_gpa'),
            'hk2_gpa' => $termAverage('hk2_gpa'),
            'year_gpa' => $termAverage('year_gpa'),
        ];
    }

    private function reportCardExportHtml(array $payload): string
    {
        $headers = collect($payload['headers'] ?? []);
        $rows = collect($payload['rows'] ?? []);
        $student = $payload['student'] ?? [];
        $selectedSemester = collect($payload['semesters'] ?? [])->firstWhere('id', $payload['selected_semester_id']);
        $selectedYear = collect($payload['years'] ?? [])->firstWhere('id', $payload['selected_year_id']);
        $semester = $selectedSemester['name'] ?? '';
        $year = $selectedYear['name'] ?? '';
        $columnCount = $headers->count() + 5;
        $annualSummary = $payload['annual_summary'] ?? [];
        $html = '<!doctype html><html><head><meta charset="UTF-8"><style>';
        $html .= 'body{font-family:Arial,sans-serif;color:#111827}table{border-collapse:collapse;width:100%}th,td{border:1px solid #d1d5db;padding:8px;text-align:left;font-size:14px}th{background:#fff7ed;color:#111827;font-weight:600}.term{background:#fff7ed;color:#c2410c;font-weight:700}.gpa td{background:#fff7ed;color:#c2410c;font-weight:700}.muted{color:#6b7280}.retest{color:#c2410c;font-size:11px}.summary{margin-top:14px;padding:12px;border:1px solid #fed7aa;background:#fff7ed;color:#111827}';
        $html .= '</style></head><body>';
        $html .= '<h2>Phiếu điểm học kỳ</h2>';
        $html .= '<p><strong>Học sinh:</strong> ' . e(($student['student_code'] ?? '') . ' - ' . ($student['name'] ?? '')) . '</p>';
        $html .= '<p><strong>Lớp:</strong> ' . e($payload['class_label'] ?? '-') . ' &nbsp; <strong>Năm học:</strong> ' . e($year) . ' &nbsp; <strong>Học kỳ:</strong> ' . e($semester) . '</p>';
        $html .= '<table><thead><tr><th>Môn học</th>';

        foreach ($headers as $header) {
            $html .= '<th>' . e($header['label'] ?? '') . '</th>';
        }

        $html .= '<th>Điểm trung bình môn</th><th class="term">Tổng kết HK1</th><th class="term">Tổng kết HK2</th><th class="term">Điểm Cả Năm</th></tr></thead><tbody>';

        if ($rows->isEmpty()) {
            $html .= '<tr><td colspan="' . $columnCount . '" class="muted">Chưa có dữ liệu điểm trong học kỳ này.</td></tr>';
        }

        foreach ($rows as $row) {
            $html .= '<tr><td><strong>' . e($row['subject_name'] ?? '-') . '</strong></td>';

            foreach (($row['values'] ?? []) as $cell) {
                $text = $cell['text'] ?? '-';
                $html .= '<td>' . e($text);

                if (! empty($cell['is_retest'])) {
                    $html .= ' <span class="retest">[Bù - ' . e($cell['retest_tooltip'] ?? '') . ']</span>';
                }

                $html .= '</td>';
            }

            $average = ! empty($row['uses_pass_fail'])
                ? 'Không tính TB'
                : (($row['average'] ?? null) !== null ? $row['average'] : '-');
            $termAverages = $row['term_averages'] ?? [];
            $html .= '<td>' . e($average) . '</td>';
            $html .= '<td class="term">' . e($termAverages['hk1'] ?? '-') . '</td>';
            $html .= '<td class="term">' . e($termAverages['hk2'] ?? '-') . '</td>';
            $html .= '<td class="term">' . e($termAverages['year'] ?? '-') . '</td></tr>';
        }

        $html .= '<tr class="gpa"><td colspan="' . max(1, $columnCount - 1) . '">Điểm trung bình học kỳ (Tất cả các môn)</td><td>' . e($payload['global_gpa'] ?? '-') . '</td></tr>';
        $html .= '</tbody></table>';
        $html .= '<div class="summary">';
        $html .= '<strong>Điểm TB học kỳ 1:</strong> ' . e($annualSummary['hk1_gpa'] ?? '-') . ' &nbsp; ';
        $html .= '<strong>Điểm TB học kỳ 2:</strong> ' . e($annualSummary['hk2_gpa'] ?? '-') . ' &nbsp; ';
        $html .= '<strong>ĐIỂM TRUNG BÌNH CẢ NĂM:</strong> ' . e($annualSummary['year_gpa'] ?? '-');
        $html .= '</div></body></html>';

        return "\xEF\xBB\xBF" . $html;
    }

    private function scoreColumnConfigData(Request $request, ?string $selectedYearId, ScoreSetting $scoreSetting): array
    {
        $selectedScoreColumnYearId = $request->query('score_column_school_year_id', $selectedYearId ?: $this->selectedSchoolYearId($request));
        $selectedScoreColumnGrade = $request->query('score_column_grade_level', 'all');
        $selectedScoreColumnSubjectId = $request->query('score_column_subject_id', 'all');
        $scoreColumnKeyword = trim((string) $request->query('score_column_q', ''));

        $scoreColumnYears = SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $scoreColumnSubjects = Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->when(in_array((string) $selectedScoreColumnGrade, ['10', '11', '12'], true), fn ($query) => $query->forGrade((int) $selectedScoreColumnGrade))
            ->withEvaluatedAssessment()
            ->orderBy('name')
            ->get();

        $scoreColumnColumns = ScoreColumn::with(['schoolYear', 'subject.gradeMappings'])
            ->whereHas('subject', fn ($query) => $query->withEvaluatedAssessment())
            ->when($selectedScoreColumnYearId, fn ($query) => $query->where('school_year_id', $selectedScoreColumnYearId))
            ->when(in_array((string) $selectedScoreColumnGrade, ['10', '11', '12'], true), fn ($query) => $query->where('grade_level', $selectedScoreColumnGrade))
            ->when($selectedScoreColumnSubjectId !== 'all', fn ($query) => $query->where('subject_id', $selectedScoreColumnSubjectId))
            ->when($scoreColumnKeyword !== '', function ($query) use ($scoreColumnKeyword) {
                $query->where(function ($inner) use ($scoreColumnKeyword) {
                    $inner->where('name', 'like', "%{$scoreColumnKeyword}%")
                        ->orWhereHas('subject', fn ($subjectQuery) => $subjectQuery->where('name', 'like', "%{$scoreColumnKeyword}%"));
                });
            })
            ->orderBy('grade_level')
            ->orderBy('subject_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (ScoreColumn $column) => $column->subject?->appliesToGrade((int) $column->grade_level))
            ->reject(fn (ScoreColumn $column) => $this->scoreColumnReportFamily($column) === 'one_period')
            ->values();

        return [
            'years' => $scoreColumnYears,
            'subjects' => $scoreColumnSubjects,
            'columns' => $scoreColumnColumns,
            'selectedYearId' => $selectedScoreColumnYearId,
            'selectedGrade' => $selectedScoreColumnGrade,
            'selectedSubjectId' => $selectedScoreColumnSubjectId,
            'keyword' => $scoreColumnKeyword,
            'scoreSetting' => $scoreSetting,
        ];
    }

    private function reportStudentForUser($user): ?Student
    {
        if ($user->isStudent()) {
            return $user->student?->load(['classRoom.schoolYear', 'schoolYear']);
        }

        if (! $user->parentProfile) {
            return null;
        }

        $children = $user->parentProfile->students()
            ->with(['classRoom.schoolYear', 'schoolYear'])
            ->orderBy('student_code')
            ->get();

        return $children->firstWhere('id', session('selected_parent_student_id')) ?: $children->first();
    }

    private function studentReportData(Student $student, ?string $yearId, ?string $semesterId): array
    {
        $student->loadMissing(['classRoom.schoolYear', 'schoolYear']);
        $years = $this->studentReportYears($student);
        $selectedYearId = $years->contains('id', $yearId)
            ? $yearId
            : ($years->first()?->id ?? $student->school_year_id);
        $semesters = Semester::where('school_year_id', $selectedYearId)
            ->orderBy('order')
            ->orderBy('name')
            ->get();
        $selectedSemesterId = $semesters->contains('id', $semesterId)
            ? $semesterId
            : ($semesters->firstWhere('status', Semester::STATUS_ACTIVE)?->id ?? $semesters->first()?->id);
        $class = $this->studentClassForYear($student, $selectedYearId);
        $gradeLevel = (int) ($class?->grade_level ?: $student->classRoom?->grade_level);

        $scores = ScoreHeader::with(['subject', 'semester.schoolYear', 'details.scoreColumn'])
            ->where('student_id', $student->id)
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
            ->whereHas('subject', fn ($query) => $query->withEvaluatedAssessment())
            ->get()
            ->sortBy(fn (ScoreHeader $score) => ($score->subject->name ?? '') . ($score->semester->name ?? ''))
            ->values();
        $yearScores = ScoreHeader::with(['subject', 'semester'])
            ->where('student_id', $student->id)
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->whereHas('subject', fn ($query) => $query->withEvaluatedAssessment())
            ->get();

        $scoreMap = $scores->keyBy('subject_id');
        $assignedSubjectIds = collect();

        if ($class) {
            $assignedSubjectIds = TeachingAssignment::query()
                ->where('class_id', $class->id)
                ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->pluck('subject_id')
                ->unique()
                ->values();
        }

        $studentSubjects = Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->when($gradeLevel > 0, fn ($query) => $query->forGrade($gradeLevel))
            ->withEvaluatedAssessment()
            ->when($assignedSubjectIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $assignedSubjectIds))
            ->when($assignedSubjectIds->isEmpty() && $yearScores->isNotEmpty(), fn ($query) => $query->whereIn('id', $yearScores->pluck('subject_id')->unique()))
            ->orderBy('name')
            ->get();
        $annualAverages = $this->studentAnnualAveragesBySubject($studentSubjects, $yearScores);

        $rows = $studentSubjects
            ->map(fn (Subject $subject) => [
                'subject' => $subject,
                'score' => $scoreMap->get($subject->id),
                'annual' => $annualAverages['subjects']->get($subject->id, [
                    'hk1' => null,
                    'hk2' => null,
                    'year' => null,
                ]),
            ])
            ->values();

        $columns = ScoreColumn::query()
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when($gradeLevel > 0, fn ($query) => $query->where('grade_level', $gradeLevel))
            ->whereIn('subject_id', $studentSubjects->pluck('id'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->reject(fn (ScoreColumn $column) => $this->scoreColumnReportFamily($column) === 'one_period')
            ->values();

        $columnsBySubject = $columns
            ->groupBy('subject_id')
            ->map(fn ($subjectColumns) => $subjectColumns
                ->groupBy(fn (ScoreColumn $column) => $this->scoreColumnReportFamily($column))
                ->map(fn ($familyColumns) => $familyColumns
                    ->sortBy(fn (ScoreColumn $column) => [$this->scoreColumnReportSequence($column), $column->sort_order, $column->name])
                    ->values()));

        return [
            'years' => $years,
            'semesters' => $semesters,
            'selectedYearId' => $selectedYearId,
            'selectedSemesterId' => $selectedSemesterId,
            'class' => $class,
            'scores' => $scores,
            'rows' => $rows,
            'headers' => $this->studentReportColumnHeaders($columnsBySubject),
            'columnsBySubject' => $columnsBySubject,
            'globalGpa' => $this->calculateGlobalGPA($rows),
            'annualSummary' => $annualAverages['summary'],
        ];
    }

    private function studentReportYears(Student $student): Collection
    {
        $years = StudentClassAssignment::with(['academicYear', 'classRoom'])
            ->where('student_id', $student->id)
            ->get()
            ->pluck('academicYear')
            ->filter();

        $scoreYears = SchoolYear::whereIn(
            'id',
            ScoreHeader::where('student_id', $student->id)
                ->pluck('school_year_id')
                ->filter()
                ->unique()
        )->get();

        $years = $years->merge($scoreYears);

        if ($student->schoolYear) {
            $years->push($student->schoolYear);
        }

        if ($student->classRoom?->schoolYear) {
            $years->push($student->classRoom->schoolYear);
        }

        return $years
            ->unique('id')
            ->sortByDesc(fn (SchoolYear $year) => $year->start_date?->timestamp ?? 0)
            ->values();
    }

    private function studentClassForYear(Student $student, ?string $yearId): ?SchoolClass
    {
        $assignment = StudentClassAssignment::with('classRoom')
            ->where('student_id', $student->id)
            ->where('academic_year_id', $yearId)
            ->orderByDesc('created_at')
            ->first();

        if ($assignment?->classRoom) {
            return $assignment->classRoom;
        }

        return (string) $student->school_year_id === (string) $yearId ? $student->classRoom : null;
    }

    private function calculateGlobalGPA(Collection $rows): ?float
    {
        $numericAverages = $rows
            ->filter(fn (array $row) => $row['subject']?->usesNumericAssessment() && $row['score']?->average !== null)
            ->map(fn (array $row) => (float) $row['score']->average)
            ->values();

        if ($numericAverages->isEmpty()) {
            return null;
        }

        return round($numericAverages->avg(), 1);
    }

    private function calculateSubjectYearAverage(?float $hk1Average, ?float $hk2Average): ?float
    {
        if ($hk1Average === null || $hk2Average === null) {
            return null;
        }

        return round(($hk1Average + ($hk2Average * 2)) / 3, 1);
    }

    private function studentAnnualAveragesBySubject(Collection $subjects, Collection $yearScores): array
    {
        $scoresBySubject = $yearScores->groupBy('subject_id');
        $subjectsSummary = $subjects->mapWithKeys(function (Subject $subject) use ($scoresBySubject) {
            $subjectScores = collect($scoresBySubject->get($subject->id, []));
            $hk1Average = $this->semesterAverageForTerm($subjectScores, 1);
            $hk2Average = $this->semesterAverageForTerm($subjectScores, 2);

            return [$subject->id => [
                'hk1' => $hk1Average,
                'hk2' => $hk2Average,
                'year' => $this->calculateSubjectYearAverage($hk1Average, $hk2Average),
            ]];
        });

        $numericSubjects = $subjects->filter(fn (Subject $subject) => $subject->usesNumericAssessment());

        return [
            'subjects' => $subjectsSummary,
            'summary' => [
                'hk1_gpa' => $this->calculateGlobalTermAverage($numericSubjects, $subjectsSummary, 'hk1'),
                'hk2_gpa' => $this->calculateGlobalTermAverage($numericSubjects, $subjectsSummary, 'hk2'),
                'year_gpa' => $this->calculateGlobalTermAverage($numericSubjects, $subjectsSummary, 'year'),
            ],
        ];
    }

    private function semesterAverageForTerm(Collection $scores, int $termIndex): ?float
    {
        $termScores = $scores
            ->filter(fn (ScoreHeader $score) => (int) ($score->semester?->termIndex() ?? 0) === $termIndex && $score->average !== null)
            ->values();

        if ($termScores->isEmpty()) {
            return null;
        }

        return round((float) $termScores->avg('average'), 1);
    }

    private function calculateGlobalTermAverage(Collection $subjects, Collection $subjectsSummary, string $key): ?float
    {
        $averages = $subjects
            ->map(fn (Subject $subject) => $subjectsSummary->get($subject->id)[$key] ?? null)
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($averages->isEmpty()) {
            return null;
        }

        return round($averages->avg(), 1);
    }

    private function serializeStudentReportData(array $data, Student $student): array
    {
        $headers = $data['headers']->values()->map(fn (array $header) => [
            'family' => $header['family'],
            'index' => (int) $header['index'],
            'label' => $header['label'],
        ]);

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'student_code' => $student->student_code,
            ],
            'class_label' => $data['class']?->name ?? 'Chưa phân lớp',
            'selected_year_id' => $data['selectedYearId'],
            'selected_semester_id' => $data['selectedSemesterId'],
            'years' => $data['years']->map(fn (SchoolYear $year) => [
                'id' => $year->id,
                'name' => $year->name,
            ])->values(),
            'semesters' => $data['semesters']->map(fn (Semester $semester) => [
                'id' => $semester->id,
                'name' => $semester->normalizedName(),
            ])->values(),
            'headers' => $headers,
            'rows' => $data['rows']->map(fn (array $row) => $this->serializeStudentReportRow($row, $headers, $data['columnsBySubject']))->values(),
            'global_gpa' => $data['globalGpa'] !== null ? number_format($data['globalGpa'], 1, '.', '') : null,
            'annual_summary' => $this->serializeAnnualSummary($data['annualSummary']),
        ];
    }

    private function serializeStudentReportRow(array $row, Collection $headers, Collection $columnsBySubject): array
    {
        $subject = $row['subject'];
        $score = $row['score'];
        $columnsByFamily = collect($columnsBySubject->get($subject->id, []));

        return [
            'subject_name' => $subject->name ?? '-',
            'uses_pass_fail' => $subject->usesPassFailAssessment(),
            'uses_numeric' => $subject->usesNumericAssessment(),
            'average' => $score?->average !== null ? rtrim(rtrim(number_format((float) $score->average, 1, '.', ''), '0'), '.') : null,
            'term_averages' => $this->serializeAnnualSummary($row['annual'] ?? []),
            'values' => $headers->map(function (array $header) use ($columnsByFamily, $score, $subject) {
                $familyColumns = collect($columnsByFamily->get($header['family'], []))->values();
                $column = $familyColumns->get(((int) $header['index']) - 1);

                return $this->serializedScoreCell($score, $subject, $column);
            })->values(),
        ];
    }

    private function serializedScoreCell(?ScoreHeader $score, Subject $subject, ?ScoreColumn $column): array
    {
        if (! $score || ! $column) {
            return ['text' => '-', 'muted' => true];
        }

        $detail = $score->details?->firstWhere('score_column_id', $column->id);
        if (! $detail || $detail->value === null) {
            return ['text' => '-', 'muted' => true];
        }
        if ($subject->usesPassFailAssessment()) {
            return [
                'text' => (float) $detail->value >= 0.5 ? 'Đ' : 'CĐ',
                'muted' => false,
                ...$this->serializedRetestMeta($detail),
            ];
        }

        return [
            'text' => rtrim(rtrim(number_format((float) $detail->value, 1, '.', ''), '0'), '.'),
            'muted' => false,
            ...$this->serializedRetestMeta($detail),
        ];
    }

    private function serializeAnnualSummary(array $summary): array
    {
        $format = fn ($value) => $value !== null ? number_format((float) $value, 1, '.', '') : null;

        return [
            'hk1' => $format($summary['hk1'] ?? null),
            'hk2' => $format($summary['hk2'] ?? null),
            'year' => $format($summary['year'] ?? null),
            'hk1_gpa' => $format($summary['hk1_gpa'] ?? null),
            'hk2_gpa' => $format($summary['hk2_gpa'] ?? null),
            'year_gpa' => $format($summary['year_gpa'] ?? null),
        ];
    }

    private function subjectAnnualAveragesForClass(Collection $studentIds, Subject $subject, Semester $semester): Collection
    {
        $scores = ScoreHeader::with('semester')
            ->where('subject_id', $subject->id)
            ->where('school_year_id', $semester->school_year_id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        return $studentIds->mapWithKeys(function ($studentId) use ($scores) {
            $studentScores = collect($scores->get($studentId, []));
            $hk1Average = $this->semesterAverageForTerm($studentScores, 1);
            $hk2Average = $this->semesterAverageForTerm($studentScores, 2);

            return [$studentId => [
                'hk1' => $hk1Average,
                'hk2' => $hk2Average,
                'year' => $this->calculateSubjectYearAverage($hk1Average, $hk2Average),
            ]];
        });
    }

    private function serializedRetestMeta(ScoreDetail $detail): array
    {
        $hasRetestAudit = (bool) $detail->is_retest && $detail->original_value !== null;
        $originalValue = $hasRetestAudit
            ? rtrim(rtrim(number_format((float) $detail->original_value, 1, '.', ''), '0'), '.')
            : null;
        $updatedAt = $hasRetestAudit ? $detail->retest_updated_at?->format('d/m/Y') : null;

        return [
            'is_retest' => $hasRetestAudit,
            'original_value' => $originalValue,
            'retest_updated_at' => $updatedAt,
            'retest_tooltip' => $hasRetestAudit
                ? "Điểm gốc: {$originalValue}. Cập nhật ngày: " . ($updatedAt ?? '-')
                : null,
        ];
    }

    public function entry(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        $subject = Subject::findOrFail($data['subject_id']);
        $semester = Semester::with('schoolYear')->findOrFail($data['semester_id']);
        $this->ensureScorableSubject($subject);
        $this->ensureSubjectAppliesToClass($class, $subject);
        $this->authorizeScoreView($class, $subject->id, $semester);

        $students = Student::where('class_id', $class->id)
            ->where('status', Student::STATUS_STUDYING)
            ->orderBy('student_code')
            ->get();
        $scoreColumns = $this->scoreColumnsFor($class, $subject, $semester);
        $scoreSetting = ScoreSetting::current();
        $headers = ScoreHeader::where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->with(['details.scoreColumn'])
            ->get()
            ->keyBy('student_id');
        $subjectAnnualAverages = $this->subjectAnnualAveragesForClass($students->pluck('id'), $subject, $semester);

        $columnPermissions = $this->scoreColumnPermissions($class, $subject, $semester, $scoreColumns);
        $scoreCellPermissions = $this->scoreCellPermissions($students, $headers, $scoreColumns, $columnPermissions);
        $canSubmitScores = collect($scoreCellPermissions)
            ->flatMap(fn (array $cells) => $cells)
            ->contains(fn (array $meta) => $meta['editable']);

        return view('scores.entry', compact('class', 'subject', 'semester', 'students', 'headers', 'scoreColumns', 'columnPermissions', 'scoreCellPermissions', 'canSubmitScores', 'subjectAnnualAverages', 'scoreSetting'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'semester_id' => 'required|exists:semesters,id',
            'scores' => 'array',
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        $subject = Subject::findOrFail($data['subject_id']);
        $semester = Semester::with('schoolYear')->findOrFail($data['semester_id']);
        $this->ensureScorableSubject($subject);
        $this->ensureSubjectAppliesToClass($class, $subject);
        $this->authorizeScoreEdit($class, $subject->id, $semester);

        $scoreColumns = $this->scoreColumnsFor($class, $subject, $semester);
        $scoreSetting = ScoreSetting::current();
        $columnPermissions = $this->scoreColumnPermissions($class, $subject, $semester, $scoreColumns);
        $editableColumns = $scoreColumns->filter(fn (ScoreColumn $column) => $columnPermissions[$column->id]['editable'] ?? false);
        $usesPassFailAssessment = $subject->usesPassFailAssessment();
        $isScoreAdmin = Auth::user()->isAdmin() || Auth::user()->isStaff();

        if ($editableColumns->isEmpty()) {
            abort(403, 'Hiện không có cột điểm nào đang mở để nhập hoặc chỉnh sửa.');
        }

        $students = Student::where('class_id', $class->id)
            ->where('status', Student::STATUS_STUDYING)
            ->get()
            ->keyBy('id');
        $existingHeaders = ScoreHeader::where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->whereIn('student_id', $students->keys())
            ->with('details')
            ->get()
            ->keyBy('student_id');
        $normalizedScores = [];
        $errors = [];

        foreach ($editableColumns as $column) {
            foreach ($students as $student) {
                $header = $existingHeaders->get($student->id);
                $detail = $header?->details?->firstWhere('score_column_id', $column->id);
                $columnEditable = (bool) ($columnPermissions[$column->id]['editable'] ?? false);
                if (! $this->canEditScoreDetail($detail, $columnEditable, $isScoreAdmin)) {
                    continue;
                }

                $field = "scores.{$column->id}.{$student->id}";
                $value = trim((string) $request->input($field, ''));

                if ($value === '') {
                    $normalizedScores[$column->id][$student->id] = null;
                    continue;
                }

                if ($usesPassFailAssessment) {
                    $normalized = strtolower(str_replace([' ', '_', '-'], '', $value));

                    if (in_array($normalized, ['pass', 'dat', 'd', '1'], true)) {
                        $normalizedScores[$column->id][$student->id] = 1.0;
                        continue;
                    }

                    if (in_array($normalized, ['fail', 'chuadat', 'cd', '0'], true)) {
                        $normalizedScores[$column->id][$student->id] = 0.0;
                        continue;
                    }

                    $errors[$field] = 'Vui lòng chọn Đạt hoặc Chưa đạt.';
                    continue;
                }

                if (! preg_match(self::SCORE_PATTERN, $value)) {
                    $errors[$field] = 'Điểm phải là số từ 0 đến 10 và tối đa 1 chữ số thập phân.';
                    continue;
                }

                $normalizedScores[$column->id][$student->id] = round((float) $value, 1);
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($students, $editableColumns, $semester, $subject, $normalizedScores, $scoreSetting) {
            foreach ($students as $student) {
                $hasEditablePayload = $editableColumns->contains(
                    fn (ScoreColumn $column) => array_key_exists($student->id, $normalizedScores[$column->id] ?? [])
                );

                if (! $hasEditablePayload) {
                    continue;
                }

                $header = ScoreHeader::firstOrCreate([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'semester_id' => $semester->id,
                    'school_year_id' => $semester->school_year_id,
                ]);

                foreach ($editableColumns as $column) {
                    if (! array_key_exists($student->id, $normalizedScores[$column->id] ?? [])) {
                        continue;
                    }

                    $value = $normalizedScores[$column->id][$student->id] ?? null;

                    $detail = $header->details()
                        ->where('score_column_id', $column->id)
                        ->first();

                    if ($value === null) {
                        $detail?->delete();
                        continue;
                    }

                    $payload = [
                        'score_column_id' => $column->id,
                        'type' => $column->type,
                        'name' => $column->name,
                        'value' => $value,
                        'weight_group' => $scoreSetting->weightForScoreType($column->type),
                    ];

                    if ($detail) {
                        if ($detail->value !== null && round((float) $detail->value, 1) !== round((float) $value, 1)) {
                            $payload['is_retest'] = true;
                            $payload['original_value'] = $detail->original_value ?? $detail->value;
                            $payload['retest_updated_at'] = now();
                        }

                        $detail->update($payload);
                        continue;
                    }

                    ScoreDetail::create([
                        'score_header_id' => $header->id,
                        ...$payload,
                    ]);
                }

                $this->recalculateAverage($header);
            }
        });

        return back()->with('success', 'Đã lưu điểm cho lớp.');
    }

    private function recalculateAverage(ScoreHeader $header): void
    {
        $header->loadMissing('subject');

        if ($header->subject?->usesPassFailAssessment() || $header->subject?->isNotEvaluated()) {
            $header->average = null;
            $header->save();

            return;
        }

        $scoreSetting = ScoreSetting::current();
        $details = $header->details()
            ->with('scoreColumn')
            ->get()
            ->reject(fn (ScoreDetail $detail) => $detail->scoreColumn && $this->scoreColumnReportFamily($detail->scoreColumn) === 'one_period');
        $weightedSum = $details->sum(fn (ScoreDetail $detail) => (float) $detail->value * $scoreSetting->weightForScoreType($detail->type));
        $totalWeight = $details->sum(fn (ScoreDetail $detail) => $scoreSetting->weightForScoreType($detail->type));
        $header->average = $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : null;
        $header->save();
    }

    private function availableClassesFor($user, ?string $yearId, ?string $semesterId): Collection
    {
        if ($user->isAdmin() || $user->isStaff()) {
            return SchoolClass::when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get();
        }

        if ($user->isTeacher() && $user->teacher) {
            $assignedClassIds = $this->teacherActiveTeachingClassIds($user->teacher);

            return SchoolClass::with(['schoolYear', 'semester'])
                ->whereIn('id', $assignedClassIds)
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get();
        }

        return collect();
    }

    private function teacherActiveTeachingClassIds(Teacher $teacher): Collection
    {
        return $teacher->assignments()
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->pluck('class_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function teacherScoreEntryAssignments(Teacher $teacher): Collection
    {
        $assignedClassIds = $this->teacherActiveTeachingClassIds($teacher);

        if ($assignedClassIds->isEmpty()) {
            return collect();
        }

        return $teacher->assignments()
            ->with(['classRoom', 'subject', 'schoolYear', 'semester'])
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->whereIn('class_id', $assignedClassIds)
            ->get()
            ->sortBy(fn (TeachingAssignment $assignment) => sprintf(
                '%02d|%s|%s|%s',
                (int) ($assignment->classRoom?->grade_level ?? 0),
                $assignment->classRoom?->name ?? '',
                $assignment->subject?->name ?? '',
                $assignment->semester?->name ?? ''
            ))
            ->unique('class_id')
            ->values();
    }

    private function availableSubjectsFor($user, ?string $yearId, ?string $semesterId): Collection
    {
        $query = Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->withEvaluatedAssessment()
            ->orderBy('name');

        if ($user->isTeacher() && $user->teacher && ! ($user->isAdmin() || $user->isStaff())) {
            $hasHomeroomClass = $user->teacher->homeroomClasses()
                ->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
                ->exists();

            if ($hasHomeroomClass) {
                return $query->get();
            }

            $subjectIds = $user->teacher->assignments()
                ->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
                ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->pluck('subject_id');

            if ($subjectIds->isNotEmpty()) {
                return $query->whereIn('id', $subjectIds)->get();
            }
        }

        return $query->get();
    }

    protected function authorizeScoreView(SchoolClass $class, string $subjectId, Semester $semester): void
    {
        $user = Auth::user();
        if ($user->isAdmin() || $user->isStaff()) {
            return;
        }

        if ($user->isTeacher() && $user->teacher) {
            if ($this->isAssignedSubjectTeacher($class, $subjectId, $semester)) {
                return;
            }

            if ((string) $class->homeroom_teacher_id === (string) $user->teacher->id) {
                return;
            }
        }

        abort(403, 'Không có quyền xem điểm của lớp này.');
    }

    protected function authorizeScoreEdit(SchoolClass $class, string $subjectId, Semester $semester): void
    {
        if (Auth::user()->isAdmin() || Auth::user()->isStaff()) {
            return;
        }

        if (! $this->isAssignedSubjectTeacher($class, $subjectId, $semester)) {
            abort(403, 'Chỉ giáo viên bộ môn được phân công mới được nhập hoặc chỉnh sửa điểm.');
        }

        if ($this->isHistoricalReadOnly()) {
            abort(403, 'Đang xem dữ liệu năm học cũ, chỉ được xem điểm.');
        }

        if (! $semester->isActive()) {
            abort(403, 'Học kỳ không ở trạng thái Hoạt động nên không thể nhập hoặc chỉnh sửa điểm.');
        }
    }

    private function isAssignedSubjectTeacher(SchoolClass $class, string $subjectId, Semester $semester): bool
    {
        $user = Auth::user();
        $teacherId = optional($user->teacher)->id;

        return $teacherId && $class->assignments()
            ->where('teacher_id', $teacherId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semester->id)
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->exists();
    }

    private function scoreColumnsFor(SchoolClass $class, Subject $subject, Semester $semester): Collection
    {
        return ScoreColumn::where('school_year_id', $semester->school_year_id)
            ->where('subject_id', $subject->id)
            ->where('grade_level', (int) $class->grade_level)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->reject(fn (ScoreColumn $column) => $this->scoreColumnReportFamily($column) === 'one_period')
            ->values();
    }

    private function scoreColumnPermissions(SchoolClass $class, Subject $subject, Semester $semester, Collection $scoreColumns): array
    {
        $isScoreAdmin = Auth::user()->isAdmin() || Auth::user()->isStaff();
        $canTeacherEdit = Auth::user()->isTeacher()
            && $this->isAssignedSubjectTeacher($class, $subject->id, $semester)
            && $semester->isActive()
            && ! $this->isHistoricalReadOnly();

        return $scoreColumns->mapWithKeys(function (ScoreColumn $column) use ($canTeacherEdit, $isScoreAdmin) {
            $editable = $isScoreAdmin || ($canTeacherEdit && $column->isInputOpen());
            $reason = match (true) {
                $isScoreAdmin => 'Admin được phép chỉnh sửa điểm bất kỳ lúc nào.',
                ! $canTeacherEdit => 'Chỉ giáo viên bộ môn được phân công mới được nhập điểm.',
                $column->isInputOpen() => 'Đang mở nhập điểm.',
                default => $column->inputStatusLabel(),
            };

            return [$column->id => [
                'editable' => $editable,
                'reason' => $reason,
            ]];
        })->all();
    }

    private function scoreCellPermissions(Collection $students, Collection $headers, Collection $scoreColumns, array $columnPermissions): array
    {
        $isScoreAdmin = Auth::user()->isAdmin() || Auth::user()->isStaff();

        return $scoreColumns->mapWithKeys(function (ScoreColumn $column) use ($students, $headers, $columnPermissions, $isScoreAdmin) {
            $columnEditable = (bool) ($columnPermissions[$column->id]['editable'] ?? false);
            $cells = $students->mapWithKeys(function (Student $student) use ($headers, $column, $columnEditable, $isScoreAdmin) {
                $detail = ($headers->get($student->id))?->details?->firstWhere('score_column_id', $column->id);
                $editable = $this->canEditScoreDetail($detail, $columnEditable, $isScoreAdmin);
                $reason = match (true) {
                    $isScoreAdmin => 'Admin được phép sửa điểm bất kỳ lúc nào.',
                    ! $columnEditable => 'Cột điểm đang khóa hoặc bạn không có quyền nhập điểm.',
                    ! $detail => 'Được nhập điểm mới.',
                    $editable => 'Điểm còn trong thời hạn 7 ngày kể từ lúc tạo.',
                    default => 'Điểm đã quá 7 ngày kể từ lúc tạo, giáo viên bộ môn không được sửa.',
                };

                return [$student->id => [
                    'editable' => $editable,
                    'reason' => $reason,
                ]];
            })->all();

            return [$column->id => $cells];
        })->all();
    }

    private function canEditScoreDetail(?ScoreDetail $detail, bool $columnEditable, bool $isScoreAdmin = false): bool
    {
        if ($isScoreAdmin) {
            return true;
        }

        if (! $columnEditable) {
            return false;
        }

        if (! $detail) {
            return true;
        }

        if (! $detail->created_at) {
            return false;
        }

        return now()->lte(Carbon::parse($detail->created_at)->copy()->addDays(7));
    }

    protected function ensureScorableSubject(Subject $subject): void
    {
        if (! $subject->isEvaluated()) {
            abort(403, 'Môn học này chỉ dùng trong thời khóa biểu, không nhập điểm và không tính điểm trung bình.');
        }
    }

    protected function ensureSubjectAppliesToClass(SchoolClass $class, Subject $subject): void
    {
        if (! $subject->appliesToGrade((int) $class->grade_level)) {
            abort(403, 'Môn học này không được phân phối cho khối ' . $class->grade_level . '.');
        }
    }

    private function scoreColumnReportFamily(ScoreColumn $column): string
    {
        if ($column->type === ScoreColumn::TYPE_MIDTERM) {
            return 'midterm';
        }

        if ($column->type === ScoreColumn::TYPE_FINAL) {
            return 'final';
        }

        $name = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $column->name));

        if (str_contains($name, 'mieng') || str_contains($name, 'oral')) {
            return 'oral';
        }

        if (str_contains($name, '15')) {
            return 'fifteen';
        }

        return 'one_period';
    }

    private function scoreColumnReportSequence(ScoreColumn $column): int
    {
        $name = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $column->name));

        if (preg_match('/lan\s*(\d+)/', $name, $matches)) {
            return (int) $matches[1];
        }

        return max(1, (int) $column->sort_order);
    }

    private function studentReportColumnHeaders(Collection $columnsBySubject): Collection
    {
        $labels = [
            'oral' => 'Miệng',
            'fifteen' => '15p',
            'midterm' => 'Giữa kỳ',
            'final' => 'Cuối kỳ',
        ];

        return collect(array_keys($labels))
            ->flatMap(function (string $family) use ($columnsBySubject, $labels) {
                $maxCount = $columnsBySubject
                    ->map(fn ($subjectColumns) => collect($subjectColumns->get($family, []))->count())
                    ->max() ?? 0;

                if ($maxCount < 1) {
                    return collect();
                }

                return collect(range(1, $maxCount))
                    ->map(fn (int $index) => [
                        'family' => $family,
                        'index' => $index,
                        'label' => $maxCount > 1 ? "{$labels[$family]} (Lần {$index})" : $labels[$family],
                    ]);
            })
            ->values();
    }

    private function detailLabels(): array
    {
        return [
            ScoreColumn::TYPE_REGULAR => ScoreColumn::TYPES[ScoreColumn::TYPE_REGULAR],
            ScoreColumn::TYPE_MIDTERM => ScoreColumn::TYPES[ScoreColumn::TYPE_MIDTERM],
            ScoreColumn::TYPE_FINAL => ScoreColumn::TYPES[ScoreColumn::TYPE_FINAL],
            'oral' => 'Đánh giá thường xuyên',
            'quiz' => 'Đánh giá thường xuyên',
            'test' => 'Đánh giá thường xuyên',
            'midterm' => 'Đánh giá giữa kỳ',
            'final' => 'Đánh giá cuối kỳ',
        ];
    }
}
