<?php

namespace App\Http\Controllers;

use App\Models\ExamSchedule;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreDetail;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ScoreController extends Controller
{
    private const SCORE_TYPES = [
        'oral' => ['label' => 'Miệng', 'weight' => 1, 'kind' => 'regular'],
        'quiz' => ['label' => '15 phút', 'weight' => 1, 'kind' => 'regular'],
        'test' => ['label' => 'Một tiết', 'weight' => 2, 'kind' => 'regular'],
        'midterm' => ['label' => 'Giữa kỳ', 'weight' => 2, 'kind' => 'scheduled', 'exam_type' => ExamSchedule::TYPE_MIDTERM],
        'final' => ['label' => 'Cuối kỳ', 'weight' => 3, 'kind' => 'scheduled', 'exam_type' => ExamSchedule::TYPE_FINAL_TEST],
    ];

    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedYearId = $this->selectedSchoolYearId($request);
        $selectedSemesterId = $this->selectedSemesterId($request);
        $years = SchoolYear::all();
        $semesters = Semester::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->get();
        $subjects = $this->availableSubjectsFor($user, $selectedYearId, $selectedSemesterId);
        $classes = $this->availableClassesFor($user, $selectedYearId, $selectedSemesterId);
        $student = null;
        $studentScores = collect();

        if ($user->isStudent()) {
            $student = $user->student?->load('classRoom');
            if ($student) {
                $studentScores = ScoreHeader::with(['subject', 'semester.schoolYear', 'details'])
                    ->where('student_id', $student->id)
                    ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                    ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                    ->get()
                    ->sortBy(fn (ScoreHeader $score) => ($score->subject->name ?? '') . ($score->semester->name ?? ''))
                    ->values();
            }

            $detailLabels = collect(self::SCORE_TYPES)->mapWithKeys(fn ($meta, $type) => [$type => $meta['label']])->all();

            return view('scores.index', compact('years', 'semesters', 'subjects', 'classes', 'selectedYearId', 'selectedSemesterId', 'student', 'studentScores', 'detailLabels'));
        }

        $assignments = collect();
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

        return view('scores.index', compact('years', 'semesters', 'subjects', 'classes', 'assignments', 'selectedYearId', 'selectedSemesterId'));
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

        $students = Student::where('class_id', $class->id)->orderBy('student_code')->get();
        $headers = ScoreHeader::where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->with('details.examSchedule')
            ->get()
            ->keyBy('student_id');
        $scoreTypes = $this->scoreTypePermissions($class, $subject, $semester);
        $canSubmitScores = collect($scoreTypes)->contains(fn ($meta) => $meta['editable']);

        return view('scores.entry', compact('class', 'subject', 'semester', 'students', 'headers', 'scoreTypes', 'canSubmitScores'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'semester_id' => 'required|exists:semesters,id',
            'scores' => 'array',
            'score_names' => 'array',
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        $subject = Subject::findOrFail($data['subject_id']);
        $semester = Semester::with('schoolYear')->findOrFail($data['semester_id']);
        $this->ensureScorableSubject($subject);
        $this->authorizeScoreEdit($class, $subject->id, $semester);

        $scoreTypes = $this->scoreTypePermissions($class, $subject, $semester);
        $editableTypes = collect($scoreTypes)
            ->filter(fn ($meta) => $meta['editable'])
            ->keys();

        if ($editableTypes->isEmpty()) {
            abort(403, 'Hiện không có cột điểm nào đang được mở để nhập hoặc chỉnh sửa.');
        }

        $students = Student::where('class_id', $class->id)->get();
        foreach ($students as $student) {
            $inputs = $request->input("scores.{$student->id}", []);
            $header = ScoreHeader::firstOrCreate([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'semester_id' => $semester->id,
                'school_year_id' => $semester->school_year_id,
            ]);

            $header->details()->whereIn('type', $editableTypes->all())->delete();

            foreach ($editableTypes as $type) {
                $meta = $scoreTypes[$type];
                $values = $this->parseScores($inputs[$type] ?? '');
                $scoreName = trim((string) $request->input("score_names.{$type}", ''));
                $scoreName = $scoreName !== '' ? $scoreName : ($meta['schedule']?->displayName());

                foreach ($values as $value) {
                    ScoreDetail::create([
                        'score_header_id' => $header->id,
                        'exam_schedule_id' => $meta['schedule']?->id,
                        'type' => $type,
                        'name' => $scoreName,
                        'value' => $value,
                        'weight_group' => $meta['weight'],
                    ]);
                }
            }

            $this->recalculateAverage($header);
        }

        return back()->with('success', 'Đã lưu điểm cho lớp.');
    }

    protected function parseScores(string $input): array
    {
        return collect(explode(',', $input))
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => $v !== '' && is_numeric($v))
            ->map(fn ($v) => (float) $v)
            ->filter(fn ($v) => $v >= 0 && $v <= 10)
            ->values()
            ->all();
    }

    private function recalculateAverage(ScoreHeader $header): void
    {
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

    private function scoreTypePermissions(SchoolClass $class, Subject $subject, Semester $semester): array
    {
        $canTeacherEdit = Auth::user()->isTeacher()
            && $this->isAssignedSubjectTeacher($class, $subject->id, $semester)
            && $semester->isActive()
            && ! $this->isHistoricalReadOnly();

        return collect(self::SCORE_TYPES)->mapWithKeys(function (array $meta, string $type) use ($class, $subject, $semester, $canTeacherEdit) {
            $schedule = null;
            $editable = false;
            $reason = null;

            if ($meta['kind'] === 'regular') {
                $editable = $canTeacherEdit;
                $reason = $editable ? 'Giáo viên bộ môn được nhập trực tiếp.' : 'Chỉ giáo viên bộ môn được nhập điểm thường xuyên.';
            } else {
                $schedule = $this->scoreScheduleFor($class, $subject, $semester, $meta['exam_type']);
                $editable = $canTeacherEdit && $schedule?->isScoreInputOpen();
                $reason = match (true) {
                    ! $schedule => 'Admin chưa tạo kỳ kiểm tra hoặc chưa mở nhập điểm.',
                    $schedule->isScoreInputOpen() => 'Đang mở nhập điểm.',
                    default => $schedule->scoreInputStatusLabel(),
                };
            }

            return [$type => [
                ...$meta,
                'editable' => $editable,
                'schedule' => $schedule,
                'reason' => $reason,
            ]];
        })->all();
    }

    private function scoreScheduleFor(SchoolClass $class, Subject $subject, Semester $semester, string $examType): ?ExamSchedule
    {
        return ExamSchedule::where('class_id', $class->id)
            ->where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->where('type', $examType)
            ->where('note', 'not like', '%"status":"canceled"%')
            ->orderByDesc('score_input_closes_at')
            ->orderByDesc('exam_date')
            ->first();
    }

    protected function ensureScorableSubject(Subject $subject): void
    {
        if (! $subject->isScorable()) {
            abort(403, 'Môn học này chỉ dùng trong thời khóa biểu, không nhập điểm và không tính điểm trung bình.');
        }
    }
}
