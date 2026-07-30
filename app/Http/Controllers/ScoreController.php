<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreColumn;
use App\Models\ScoreDetail;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $teachers = collect();
        $student = null;
        $studentScores = collect();
        $studentReportRows = collect();

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
                $studentScores = ScoreHeader::with(['subject', 'semester.schoolYear', 'details.scoreColumn'])
                    ->where('student_id', $student->id)
                    ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                    ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                    ->get()
                    ->sortBy(fn (ScoreHeader $score) => ($score->subject->name ?? '') . ($score->semester->name ?? ''))
                    ->values();

                $scoreMap = $studentScores->keyBy('subject_id');
                $assignedSubjectIds = collect();

                if ($student->class_id) {
                    $assignedSubjectIds = TeachingAssignment::query()
                        ->where('class_id', $student->class_id)
                        ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                        ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                        ->where('status', TeachingAssignment::STATUS_ACTIVE)
                        ->pluck('subject_id')
                        ->unique()
                        ->values();
                }

                $studentSubjects = Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
                    ->where('status', Subject::STATUS_ACTIVE)
                    ->when($assignedSubjectIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $assignedSubjectIds))
                    ->orderBy('name')
                    ->get();

                $studentReportRows = $studentSubjects
                    ->map(fn (Subject $subject) => [
                        'subject' => $subject,
                        'score' => $scoreMap->get($subject->id),
                    ])
                    ->values();
            }

            $detailLabels = $this->detailLabels();

            return view('scores.index', compact('years', 'semesters', 'subjects', 'classes', 'selectedYearId', 'selectedSemesterId', 'student', 'studentScores', 'studentReportRows', 'detailLabels'));
        }

        $assignments = collect();
        if ($user->isAdmin() || $user->isStaff()) {
            $teachers = Teacher::orderBy('name')->get();
            $assignments = TeachingAssignment::query()
                ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->get(['teacher_id', 'class_id', 'subject_id', 'semester_id']);
        }

        if ($user->isTeacher() && $user->teacher) {
            $assignments = $user->teacher->assignments()
                ->with(['classRoom', 'subject', 'schoolYear', 'semester'])
                ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->whereHas('subject', fn ($query) => $query
                    ->whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
                    ->where('status', Subject::STATUS_ACTIVE))
                ->get();
        }

        return view('scores.index', compact('years', 'semesters', 'subjects', 'classes', 'teachers', 'assignments', 'selectedYearId', 'selectedSemesterId'));
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
        $this->authorizeScoreView($class, $subject->id, $semester);

        $students = Student::where('class_id', $class->id)
            ->where('status', Student::STATUS_STUDYING)
            ->orderBy('student_code')
            ->get();
        $scoreColumns = $this->scoreColumnsFor($class, $subject, $semester);
        $headers = ScoreHeader::where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->with(['details.scoreColumn'])
            ->get()
            ->keyBy('student_id');

        $columnPermissions = $this->scoreColumnPermissions($class, $subject, $semester, $scoreColumns);
        $canSubmitScores = collect($columnPermissions)->contains(fn ($meta) => $meta['editable']);

        return view('scores.entry', compact('class', 'subject', 'semester', 'students', 'headers', 'scoreColumns', 'columnPermissions', 'canSubmitScores'));
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
        $this->authorizeScoreEdit($class, $subject->id, $semester);

        $scoreColumns = $this->scoreColumnsFor($class, $subject, $semester);
        $columnPermissions = $this->scoreColumnPermissions($class, $subject, $semester, $scoreColumns);
        $editableColumns = $scoreColumns->filter(fn (ScoreColumn $column) => $columnPermissions[$column->id]['editable'] ?? false);
        $usesPassFailAssessment = $subject->usesPassFailAssessment();

        if ($editableColumns->isEmpty()) {
            abort(403, 'Hiện không có cột điểm nào đang mở để nhập hoặc chỉnh sửa.');
        }

        $students = Student::where('class_id', $class->id)
            ->where('status', Student::STATUS_STUDYING)
            ->get()
            ->keyBy('id');
        $normalizedScores = [];
        $errors = [];

        foreach ($editableColumns as $column) {
            foreach ($students as $student) {
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

        DB::transaction(function () use ($students, $editableColumns, $semester, $subject, $normalizedScores) {
            foreach ($students as $student) {
                $header = ScoreHeader::firstOrCreate([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'semester_id' => $semester->id,
                    'school_year_id' => $semester->school_year_id,
                ]);

                foreach ($editableColumns as $column) {
                    $value = $normalizedScores[$column->id][$student->id] ?? null;

                    $header->details()
                        ->where('score_column_id', $column->id)
                        ->delete();

                    if ($value === null) {
                        continue;
                    }

                    ScoreDetail::create([
                        'score_header_id' => $header->id,
                        'score_column_id' => $column->id,
                        'type' => $column->type,
                        'name' => $column->name,
                        'value' => $value,
                        'weight_group' => $column->weight_group,
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

        if ($header->subject?->usesPassFailAssessment()) {
            $header->average = null;
            $header->save();

            return;
        }

        $details = $header->details()->get();
        $weightedSum = $details->sum(fn (ScoreDetail $detail) => (float) $detail->value * (int) $detail->weight_group);
        $totalWeight = $details->sum(fn (ScoreDetail $detail) => (int) $detail->weight_group);
        $header->average = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : null;
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
            $assignedClassIds = $user->teacher->assignments()
                ->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
                ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->pluck('class_id');
            $homeroomClassIds = $user->teacher->homeroomClasses()
                ->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))
                ->pluck('id');

            return SchoolClass::whereIn('id', $assignedClassIds->merge($homeroomClassIds)->unique()->values())
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get();
        }

        return collect();
    }

    private function availableSubjectsFor($user, ?string $yearId, ?string $semesterId): Collection
    {
        $query = Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
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
            ->get();
    }

    private function scoreColumnPermissions(SchoolClass $class, Subject $subject, Semester $semester, Collection $scoreColumns): array
    {
        $canTeacherEdit = Auth::user()->isTeacher()
            && $this->isAssignedSubjectTeacher($class, $subject->id, $semester)
            && $semester->isActive()
            && ! $this->isHistoricalReadOnly();

        return $scoreColumns->mapWithKeys(function (ScoreColumn $column) use ($canTeacherEdit) {
            $editable = $canTeacherEdit && $column->isInputOpen();
            $reason = match (true) {
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

    protected function ensureScorableSubject(Subject $subject): void
    {
        if (! $subject->isScorable()) {
            abort(403, 'Môn học này chỉ dùng trong thời khóa biểu, không nhập điểm và không tính điểm trung bình.');
        }
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
