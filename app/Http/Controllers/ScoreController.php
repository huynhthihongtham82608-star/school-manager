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
        }

        if ($user->isTeacher() && $user->teacher) {
            $assignments = $user->teacher->assignments()
                ->with(['classRoom', 'subject', 'schoolYear', 'semester'])
                ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->whereHas('subject', fn ($query) => $query
                    ->whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
                    ->where('status', Subject::STATUS_ACTIVE)
                    ->withEvaluatedAssessment())
                ->get();
        }

        return view('scores.index', compact('years', 'semesters', 'subjects', 'classes', 'teachers', 'assignments', 'selectedYearId', 'selectedSemesterId', 'scoreSetting', 'scoreColumnConfig'));
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
            ->withEvaluatedAssessment()
            ->orderBy('name')
            ->get();

        $scoreColumnColumns = ScoreColumn::with(['schoolYear', 'subject'])
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
        $subjectAnnualAverages = $this->subjectAnnualAveragesForClass($students->pluck('id'), $subject, $semester);

        $columnPermissions = $this->scoreColumnPermissions($class, $subject, $semester, $scoreColumns);
        $canSubmitScores = collect($columnPermissions)->contains(fn ($meta) => $meta['editable']);

        return view('scores.entry', compact('class', 'subject', 'semester', 'students', 'headers', 'scoreColumns', 'columnPermissions', 'canSubmitScores', 'subjectAnnualAverages'));
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
        $scoreSetting = ScoreSetting::current();
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

        DB::transaction(function () use ($students, $editableColumns, $semester, $subject, $normalizedScores, $scoreSetting) {
            foreach ($students as $student) {
                $header = ScoreHeader::firstOrCreate([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'semester_id' => $semester->id,
                    'school_year_id' => $semester->school_year_id,
                ]);

                foreach ($editableColumns as $column) {
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
        if (! $subject->isEvaluated()) {
            abort(403, 'Môn học này chỉ dùng trong thời khóa biểu, không nhập điểm và không tính điểm trung bình.');
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
