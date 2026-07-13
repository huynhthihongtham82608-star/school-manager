<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Conduct;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SystemSetting;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function classSummary(Request $request)
    {
        $report = $this->buildReport($request);

        if ($request->query('format') === 'excel') {
            return $this->exportCsv($report);
        }

        if ($request->query('format') === 'pdf') {
            return view('reports.print', $report);
        }

        return view('reports.class_summary', $report);
    }

    private function buildReport(Request $request): array
    {
        $selectedYearId = $request->input('school_year_id') ?: $this->selectedSchoolYearId($request);
        $selectedSemesterId = $request->has('semester_id')
            ? $request->input('semester_id')
            : $this->selectedSemesterId($request);
        $scope = $request->input('scope', 'statistics');

        if ($scope === 'three_years') {
            $scope = 'multi_year';
        }

        if (in_array($scope, ['school_year', 'semester', 'grade', 'class', 'teacher', 'subject', 'student'], true)) {
            $scope = 'statistics';
        }

        if (! in_array($scope, ['statistics', 'multi_year'], true)) {
            $scope = 'statistics';
        }

        $filters = [
            'scope' => $scope,
            'school_year_id' => $selectedYearId,
            'from_year_id' => $request->input('from_year_id'),
            'to_year_id' => $request->input('to_year_id'),
            'semester_id' => $scope === 'multi_year' ? null : $selectedSemesterId,
            'grade_level' => $scope === 'multi_year' ? null : $request->input('grade_level'),
            'class_id' => $scope === 'multi_year' ? null : $request->input('class_id'),
            'teacher_id' => $scope === 'multi_year' ? null : $request->input('teacher_id'),
            'subject_id' => $scope === 'multi_year' ? null : $request->input('subject_id'),
            'student_id' => $scope === 'multi_year' ? null : $request->input('student_id'),
            'ai' => $request->boolean('ai'),
        ];

        $schoolYears = SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $selectedYear = $schoolYears->firstWhere('id', $selectedYearId) ?: $schoolYears->first();
        $user = $request->user();
        $teacherClassIds = null;
        $teacherSubjectIds = null;

        if ($user?->isTeacher() && ! $user->isAdmin() && $user->teacher) {
            $teacherAssignments = TeachingAssignment::where('teacher_id', $user->teacher->id)
                ->when($selectedYear?->id, fn ($query) => $query->where('school_year_id', $selectedYear->id))
                ->get(['class_id', 'subject_id']);
            $homeroomClassIds = SchoolClass::where('homeroom_teacher_id', $user->teacher->id)
                ->when($selectedYear?->id, fn ($query) => $query->where('school_year_id', $selectedYear->id))
                ->pluck('id');

            $teacherClassIds = $teacherAssignments->pluck('class_id')->merge($homeroomClassIds)->filter()->unique()->values();
            $teacherSubjectIds = $teacherAssignments->pluck('subject_id')->filter()->unique()->values();

            if ($filters['class_id'] && ! $teacherClassIds->contains($filters['class_id'])) {
                $filters['class_id'] = null;
            }

            if ($filters['subject_id'] && ! $teacherSubjectIds->contains($filters['subject_id'])) {
                $filters['subject_id'] = null;
            }

            $filters['teacher_id'] = $user->teacher->id;
        }

        $semesters = Semester::with('schoolYear')
            ->when($selectedYear?->id, fn ($query) => $query->where('school_year_id', $selectedYear->id))
            ->orderBy('order')
            ->orderBy('name')
            ->get();
        $selectedSemester = $filters['semester_id']
            ? $semesters->firstWhere('id', $filters['semester_id'])
            : null;

        $classes = SchoolClass::with('homeroomTeacher')
            ->when($selectedYear?->id, fn ($query) => $query->where('school_year_id', $selectedYear->id))
            ->when($teacherClassIds !== null, fn ($query) => $query->whereIn('id', $teacherClassIds))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
        $teachers = Teacher::with('primarySubject')
            ->when($user?->isTeacher() && ! $user->isAdmin() && $user->teacher, fn ($query) => $query->where('id', $user->teacher->id))
            ->orderBy('teacher_code')
            ->get();
        $scorableTypes = array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES);
        $subjects = Subject::whereIn('type', $scorableTypes)
            ->when($teacherSubjectIds !== null, fn ($query) => $query->whereIn('id', $teacherSubjectIds))
            ->orderBy('name')
            ->get();
        $studentsForFilter = Student::with('classRoom')
            ->whereIn('class_id', $classes->pluck('id'))
            ->orderBy('student_code')
            ->get();

        $students = $this->filteredStudents($filters, $classes);
        $studentIds = $students->pluck('id');

        $scoreHeaders = Schema::hasTable('score_headers')
            ? ScoreHeader::with(['student.classRoom', 'subject'])
                ->whereIn('student_id', $studentIds)
                ->when($selectedYear?->id, fn ($query) => $query->where('school_year_id', $selectedYear->id))
                ->when($filters['semester_id'], fn ($query) => $query->where('semester_id', $filters['semester_id']))
                ->when($filters['subject_id'], fn ($query) => $query->where('subject_id', $filters['subject_id']))
                ->whereHas('subject', fn ($query) => $query->whereIn('type', $scorableTypes))
                ->get()
            : collect();

        $conducts = Schema::hasTable('conducts')
            ? Conduct::with(['student.classRoom', 'classRoom'])
                ->whereIn('student_id', $studentIds)
                ->when($selectedYear?->id, fn ($query) => $query->where('school_year_id', $selectedYear->id))
                ->when($filters['semester_id'], fn ($query) => $query->where('semester_id', $filters['semester_id']))
                ->get()
            : collect();

        $attendanceRecords = Schema::hasTable('attendance_records')
            ? AttendanceRecord::with(['student.classRoom', 'classRoom'])
                ->whereIn('student_id', $studentIds)
                ->when($filters['semester_id'], fn ($query) => $query->where('semester_id', $filters['semester_id']))
                ->get()
            : collect();

        $studentRows = $this->studentRows($students, $scoreHeaders, $conducts, $attendanceRecords, $subjects);
        $studyDistribution = $this->studyDistribution($studentRows);
        $conductDistribution = $this->conductDistribution($conducts, $students);
        $attendanceDistribution = $this->attendanceDistribution($attendanceRecords);
        $graduation = $this->graduationStats($students);
        $summaryCards = $this->summaryCardsV2($students, $studentRows, $attendanceDistribution, $graduation, $classes, $teachers, $subjects);
        $gradeSummary = $this->groupSummary($students, $studentRows, $attendanceRecords, fn (Student $student) => 'Khối ' . ($student->classRoom?->grade_level ?: 'Chưa rõ'));
        $classSummary = $this->groupSummary($students, $studentRows, $attendanceRecords, fn (Student $student) => $student->classRoom?->name ?: 'Chưa có lớp');
        $subjectSummary = $this->subjectSummary($scoreHeaders);
        $teacherSummary = $this->teacherSummary($filters, $selectedYear?->id, $filters['semester_id']);
        $yearComparison = $this->yearComparison($schoolYears, $selectedYear, $filters);
        $studentReport = $this->studentReport($filters['student_id'], $scoreHeaders, $conducts, $attendanceRecords);
        $teacherReport = $this->selectedTeacherReport($filters['teacher_id'], $teacherSummary, $studentRows);
        $subjectReport = $this->selectedSubjectReport($filters['subject_id'], $scoreHeaders);
        $aiInsights = $filters['ai'] ? $this->aiInsights($filters, $studentRows, $attendanceDistribution, $graduation) : [];

        $reportFocus = $this->reportFocus($filters);
        $reportTitle = $this->reportTitle($filters, $selectedYear, $selectedSemester);
        $systemSetting = SystemSetting::current();
        $exportedBy = $user?->name ?: $user?->username ?: 'Hệ thống';

        return compact(
            'schoolYears',
            'selectedYear',
            'semesters',
            'selectedSemester',
            'classes',
            'teachers',
            'subjects',
            'studentsForFilter',
            'filters',
            'reportFocus',
            'reportTitle',
            'systemSetting',
            'exportedBy',
            'summaryCards',
            'studyDistribution',
            'conductDistribution',
            'attendanceDistribution',
            'graduation',
            'gradeSummary',
            'classSummary',
            'subjectSummary',
            'teacherSummary',
            'studentRows',
            'yearComparison',
            'studentReport',
            'teacherReport',
            'subjectReport',
            'aiInsights'
        );
    }

    private function filteredStudents(array $filters, Collection $classes): Collection
    {
        $classIds = $classes->pluck('id');

        if ($filters['grade_level']) {
            $classIds = $classes->where('grade_level', (int) $filters['grade_level'])->pluck('id');
        }

        if ($filters['class_id']) {
            $classIds = collect([$filters['class_id']]);
        }

        if ($filters['teacher_id']) {
            $teacherClassIds = TeachingAssignment::where('teacher_id', $filters['teacher_id'])
                ->when($filters['school_year_id'], fn ($query) => $query->where('school_year_id', $filters['school_year_id']))
                ->when($filters['semester_id'], fn ($query) => $query->where('semester_id', $filters['semester_id']))
                ->pluck('class_id')
                ->unique();
            $homeroomClassIds = SchoolClass::where('homeroom_teacher_id', $filters['teacher_id'])
                ->when($filters['school_year_id'], fn ($query) => $query->where('school_year_id', $filters['school_year_id']))
                ->pluck('id');
            $classIds = $classIds->intersect($teacherClassIds->merge($homeroomClassIds)->unique());
        }

        if ($filters['subject_id']) {
            $subjectClassIds = TeachingAssignment::where('subject_id', $filters['subject_id'])
                ->when($filters['school_year_id'], fn ($query) => $query->where('school_year_id', $filters['school_year_id']))
                ->when($filters['semester_id'], fn ($query) => $query->where('semester_id', $filters['semester_id']))
                ->pluck('class_id')
                ->unique();
            $classIds = $classIds->intersect($subjectClassIds);
        }

        return Student::with('classRoom')
            ->whereIn('class_id', $classIds)
            ->when($filters['student_id'], fn ($query) => $query->where('id', $filters['student_id']))
            ->orderBy('student_code')
            ->get();
    }

    private function studentRows(Collection $students, Collection $scoreHeaders, Collection $conducts, Collection $attendanceRecords, Collection $subjects): Collection
    {
        $subjectsById = $subjects->keyBy('id');
        $scoresByStudent = $scoreHeaders->groupBy('student_id');
        $conductsByStudent = $conducts->groupBy('student_id');
        $attendanceByStudent = $attendanceRecords->groupBy('student_id');

        return $students->map(function (Student $student) use ($scoresByStudent, $conductsByStudent, $attendanceByStudent, $subjectsById) {
            $avg = $this->calculateAverage($scoresByStudent->get($student->id, collect()), $subjectsById);
            $attendance = $this->attendanceDistribution($attendanceByStudent->get($student->id, collect()));
            $conduct = $conductsByStudent->get($student->id, collect())->last();

            return [
                'student' => $student,
                'average' => $avg,
                'study_rank' => $this->rankStudy($avg),
                'conduct' => $conduct?->conduct_level,
                'attendance_rate' => $attendance['rate'],
            ];
        });
    }

    private function calculateAverage(Collection $headers, Collection $subjects): ?float
    {
        $sum = 0;
        $weight = 0;

        foreach ($headers as $header) {
            if ($header->average === null) {
                continue;
            }

            $subjectWeight = $subjects->get($header->subject_id)?->calculationWeight() ?: 1;
            $sum += $header->average * $subjectWeight;
            $weight += $subjectWeight;
        }

        return $weight > 0 ? round($sum / $weight, 2) : null;
    }

    private function rankStudy(?float $avg): string
    {
        if ($avg === null) {
            return 'no_data';
        }

        if ($avg >= 8) {
            return 'excellent';
        }

        if ($avg >= 6.5) {
            return 'good';
        }

        if ($avg >= 5) {
            return 'average';
        }

        return 'needs_support';
    }

    private function studyDistribution(Collection $studentRows): array
    {
        $labels = [
            'excellent' => 'Giỏi',
            'good' => 'Khá',
            'average' => 'Trung bình',
            'needs_support' => 'Cần hỗ trợ',
            'no_data' => 'Chưa có dữ liệu',
        ];

        return collect($labels)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'value' => $studentRows->where('study_rank', $key)->count(),
        ])->values()->all();
    }

    private function conductDistribution(Collection $conducts, Collection $students): array
    {
        $latest = $conducts->groupBy('student_id')->map(fn ($items) => $items->last());
        $labels = [
            'excellent' => 'Tốt',
            'good' => 'Khá',
            'average' => 'Trung bình',
            'weak' => 'Cần cố gắng',
            'no_data' => 'Chưa có dữ liệu',
        ];

        return collect($labels)->map(function ($label, $key) use ($latest, $students) {
            $value = $key === 'no_data'
                ? max(0, $students->count() - $latest->count())
                : $latest->where('conduct_level', $key)->count();

            return compact('key', 'label', 'value');
        })->values()->all();
    }

    private function attendanceDistribution(Collection $attendanceRecords): array
    {
        $total = $attendanceRecords->count();
        $presentLike = $attendanceRecords
            ->whereIn('status', ['present', 'late', 'excused'])
            ->count();

        return [
            'total' => $total,
            'present' => $attendanceRecords->where('status', 'present')->count(),
            'late' => $attendanceRecords->where('status', 'late')->count(),
            'excused' => $attendanceRecords->where('status', 'excused')->count(),
            'absent' => $attendanceRecords->where('status', 'absent')->count(),
            'rate' => $total > 0 ? round($presentLike / $total * 100, 1) : null,
        ];
    }

    private function graduationStats(Collection $students): array
    {
        $grade12 = $students->filter(fn (Student $student) => (int) ($student->classRoom?->grade_level) === 12);
        $graduated = $grade12->where('status', Student::STATUS_GRADUATED)->count();

        return [
            'total' => $grade12->count(),
            'graduated' => $graduated,
            'rate' => $grade12->isNotEmpty() ? round($graduated / $grade12->count() * 100, 1) : null,
        ];
    }

    private function summaryCards(Collection $students, Collection $studentRows, array $attendanceDistribution, array $graduation): array
    {
        $rowsWithAverage = $studentRows->whereNotNull('average');

        return [
            ['label' => 'Học sinh', 'value' => $students->count(), 'suffix' => ''],
            ['label' => 'Điểm trung bình chung', 'value' => $rowsWithAverage->isNotEmpty() ? round($rowsWithAverage->avg('average'), 2) : 'Chưa có dữ liệu', 'suffix' => ''],
            ['label' => 'Tỷ lệ chuyên cần', 'value' => $attendanceDistribution['rate'] === null ? 'Chưa có dữ liệu' : $attendanceDistribution['rate'], 'suffix' => $attendanceDistribution['rate'] === null ? '' : '%'],
            ['label' => 'Tỷ lệ tốt nghiệp', 'value' => $graduation['rate'] === null ? 'Chưa có dữ liệu' : $graduation['rate'], 'suffix' => $graduation['rate'] === null ? '' : '%'],
        ];
    }

    private function groupSummary(Collection $students, Collection $studentRows, Collection $attendanceRecords, callable $groupResolver): Collection
    {
        return $students
            ->groupBy(fn (Student $student) => $groupResolver($student))
            ->map(function (Collection $groupStudents, string $label) use ($studentRows, $attendanceRecords) {
                $ids = $groupStudents->pluck('id');
                $rows = $studentRows->whereIn('student.id', $ids);
                $rowsWithAverage = $rows->whereNotNull('average');
                $attendance = $this->attendanceDistribution($attendanceRecords->whereIn('student_id', $ids));

                return [
                    'label' => $label,
                    'student_count' => $groupStudents->count(),
                    'average' => $rowsWithAverage->isNotEmpty() ? round($rowsWithAverage->avg('average'), 2) : null,
                    'excellent_count' => $rows->where('study_rank', 'excellent')->count(),
                    'attendance_rate' => $attendance['rate'],
                ];
            })
            ->sortBy('label')
            ->values();
    }

    private function subjectSummary(Collection $scoreHeaders): Collection
    {
        return $scoreHeaders
            ->whereNotNull('average')
            ->groupBy('subject_id')
            ->map(function (Collection $headers) {
                return [
                    'label' => $headers->first()->subject?->name ?: 'Chưa rõ môn',
                    'student_count' => $headers->pluck('student_id')->unique()->count(),
                    'average' => round($headers->avg('average'), 2),
                ];
            })
            ->sortByDesc('average')
            ->values();
    }

    private function teacherSummary(array $filters, ?string $schoolYearId, ?string $semesterId): Collection
    {
        if (! Schema::hasTable('teaching_assignments')) {
            return collect();
        }

        $assignments = TeachingAssignment::with(['teacher', 'classRoom', 'subject.periodNorms'])
            ->when($schoolYearId, fn ($query) => $query->where('school_year_id', $schoolYearId))
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->when($filters['teacher_id'], fn ($query) => $query->where('teacher_id', $filters['teacher_id']))
            ->when($filters['subject_id'], fn ($query) => $query->where('subject_id', $filters['subject_id']))
            ->get();

        return $assignments
            ->groupBy('teacher_id')
            ->map(function (Collection $items) {
                return [
                    'label' => $items->first()->teacher?->name ?: 'Chưa rõ giáo viên',
                    'class_count' => $items->pluck('class_id')->unique()->count(),
                    'subject_count' => $items->pluck('subject_id')->unique()->count(),
                    'weekly_periods' => $items->sum(fn (TeachingAssignment $assignment) => (int) ($assignment->effectiveWeeklyPeriods() ?: 0)),
                ];
            })
            ->sortBy('label')
            ->values();
    }

    private function yearComparison(Collection $schoolYears, ?SchoolYear $selectedYear, array $filters): Collection
    {
        if (! $selectedYear) {
            return collect();
        }

        $fromYear = $filters['from_year_id'] ? $schoolYears->firstWhere('id', $filters['from_year_id']) : null;
        $toYear = $filters['to_year_id'] ? $schoolYears->firstWhere('id', $filters['to_year_id']) : null;
        $fromDate = $fromYear?->start_date ?: $schoolYears->sortBy('start_date')->first()?->start_date;
        $toDate = $toYear?->start_date ?: $selectedYear->start_date;

        if ($fromDate && $toDate && $fromDate->greaterThan($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $years = $schoolYears
            ->filter(fn (SchoolYear $year) => $fromDate && $toDate && $year->start_date->betweenIncluded($fromDate, $toDate))
            ->sortBy('start_date')
            ->values();

        return $years->map(function (SchoolYear $year) use ($filters) {
            $classes = SchoolClass::where('school_year_id', $year->id)->get();
            $students = Student::with('classRoom')
                ->whereIn('class_id', $classes->pluck('id'))
                ->when($filters['grade_level'], fn ($query) => $query->whereHas('classRoom', fn ($subQuery) => $subQuery->where('grade_level', $filters['grade_level'])))
                ->get();
            $headers = Schema::hasTable('score_headers')
                ? ScoreHeader::whereIn('student_id', $students->pluck('id'))
                    ->where('school_year_id', $year->id)
                    ->whereNotNull('average')
                    ->get()
                : collect();
            $attendance = Schema::hasTable('attendance_records')
                ? AttendanceRecord::whereIn('student_id', $students->pluck('id'))->get()
                : collect();
            $conducts = Schema::hasTable('conducts')
                ? Conduct::whereIn('student_id', $students->pluck('id'))->where('school_year_id', $year->id)->get()
                : collect();
            $conductDistribution = $this->conductDistribution($conducts, $students);
            $goodConductCount = collect($conductDistribution)
                ->whereIn('key', ['excellent', 'good'])
                ->sum('value');
            $promotion = $this->promotionStats($students);

            return [
                'label' => $year->name,
                'student_count' => $students->count(),
                'average' => $headers->isNotEmpty() ? round($headers->avg('average'), 2) : null,
                'attendance_rate' => $this->attendanceDistribution($attendance)['rate'],
                'conduct_good_rate' => $students->isNotEmpty() ? round($goodConductCount / $students->count() * 100, 1) : null,
                'promotion_rate' => $promotion['rate'],
                'graduation_rate' => $this->graduationStats($students)['rate'],
            ];
        });
    }

    private function reportTitle(array $filters, ?SchoolYear $year, ?Semester $semester): string
    {
        $title = match ($filters['scope']) {
            'multi_year' => 'Báo cáo so sánh nhiều năm',
            default => 'Báo cáo thống kê',
        };

        $parts = [$title];
        if ($year) {
            $parts[] = $year->name;
        }
        if ($semester) {
            $parts[] = $semester->normalizedName();
        }

        return implode(' - ', $parts);
    }

    private function reportFocus(array $filters): string
    {
        if ($filters['scope'] === 'multi_year') {
            return 'multi_year';
        }

        if ($filters['student_id']) {
            return 'student';
        }

        if ($filters['teacher_id']) {
            return 'teacher';
        }

        if ($filters['subject_id']) {
            return 'subject';
        }

        if ($filters['class_id']) {
            return 'class';
        }

        if ($filters['grade_level']) {
            return 'grade';
        }

        if ($filters['semester_id']) {
            return 'semester';
        }

        return 'school';
    }

    private function exportCsv(array $report): StreamedResponse
    {
        $filename = Str::slug($report['reportTitle']) . '-' . now()->format('Ymd-His') . '.csv';

        return new StreamedResponse(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [$report['reportTitle']]);
            fputcsv($handle, ['Tên trường', $report['systemSetting']->school_name]);
            fputcsv($handle, ['Năm học', $report['selectedYear']?->name ?: 'Chưa chọn']);
            fputcsv($handle, ['Học kỳ', $report['selectedSemester']?->normalizedName() ?: 'Cả năm']);
            fputcsv($handle, ['Ngày xuất', now()->format('d/m/Y H:i')]);
            fputcsv($handle, ['Người xuất', $report['exportedBy']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Chỉ số', 'Giá trị']);
            foreach ($report['summaryCards'] as $card) {
                fputcsv($handle, [$card['label'], $card['value'] . $card['suffix']]);
            }

            if (($report['filters']['scope'] ?? null) === 'multi_year') {
                fputcsv($handle, []);
                fputcsv($handle, ['Năm học', 'Số học sinh', 'Điểm trung bình', 'Chuyên cần', 'Hạnh kiểm tốt/khá', 'Tỷ lệ lên lớp', 'Tỷ lệ tốt nghiệp']);
                foreach ($report['yearComparison'] as $row) {
                    fputcsv($handle, [
                        $row['label'],
                        $row['student_count'],
                        $row['average'] ?? 'Chưa có dữ liệu',
                        $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%',
                        $row['conduct_good_rate'] === null ? 'Chưa có dữ liệu' : $row['conduct_good_rate'] . '%',
                        $row['promotion_rate'] === null ? 'Chưa có dữ liệu' : $row['promotion_rate'] . '%',
                        $row['graduation_rate'] === null ? 'Chưa có dữ liệu' : $row['graduation_rate'] . '%',
                    ]);
                }

                fclose($handle);
                return;
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Học lực', 'Số lượng']);
            foreach ($report['studyDistribution'] as $row) {
                fputcsv($handle, [$row['label'], $row['value']]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Hạnh kiểm', 'Số lượng']);
            foreach ($report['conductDistribution'] as $row) {
                fputcsv($handle, [$row['label'], $row['value']]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Mã học sinh', 'Họ tên', 'Lớp', 'Điểm trung bình', 'Học lực', 'Hạnh kiểm', 'Chuyên cần']);
            foreach ($report['studentRows'] as $row) {
                fputcsv($handle, [
                    $row['student']->student_code,
                    $row['student']->name,
                    $row['student']->classRoom?->name,
                    $row['average'] ?? 'Chưa có dữ liệu',
                    $this->studyLabel($row['study_rank']),
                    $this->conductLabel($row['conduct']),
                    $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function studyLabel(?string $rank): string
    {
        return [
            'excellent' => 'Giỏi',
            'good' => 'Khá',
            'average' => 'Trung bình',
            'needs_support' => 'Cần hỗ trợ',
            'no_data' => 'Chưa có dữ liệu',
        ][$rank] ?? 'Chưa có dữ liệu';
    }

    public function conductLabel(?string $level): string
    {
        return [
            'excellent' => 'Tốt',
            'good' => 'Khá',
            'average' => 'Trung bình',
            'weak' => 'Cần cố gắng',
        ][$level] ?? 'Chưa có dữ liệu';
    }

    private function summaryCardsV2(Collection $students, Collection $studentRows, array $attendanceDistribution, array $graduation, Collection $classes, Collection $teachers, Collection $subjects): array
    {
        $rowsWithAverage = $studentRows->whereNotNull('average');
        $excellentGoodCount = $studentRows->whereIn('study_rank', ['excellent', 'good'])->count();
        $promotion = $this->promotionStats($students);

        return [
            ['label' => 'Học sinh', 'value' => $students->count(), 'suffix' => ''],
            ['label' => 'Giáo viên', 'value' => $teachers->count(), 'suffix' => ''],
            ['label' => 'Lớp học', 'value' => $classes->count(), 'suffix' => ''],
            ['label' => 'Môn học', 'value' => $subjects->count(), 'suffix' => ''],
            ['label' => 'Điểm trung bình toàn trường', 'value' => $rowsWithAverage->isNotEmpty() ? round($rowsWithAverage->avg('average'), 2) : 'Chưa có dữ liệu', 'suffix' => ''],
            ['label' => 'Tỷ lệ khá giỏi', 'value' => $students->isNotEmpty() ? round($excellentGoodCount / $students->count() * 100, 1) : 'Chưa có dữ liệu', 'suffix' => $students->isNotEmpty() ? '%' : ''],
            ['label' => 'Tỷ lệ chuyên cần', 'value' => $attendanceDistribution['rate'] === null ? 'Chưa có dữ liệu' : $attendanceDistribution['rate'], 'suffix' => $attendanceDistribution['rate'] === null ? '' : '%'],
            ['label' => 'Tỷ lệ lên lớp', 'value' => $promotion['rate'] === null ? 'Chưa có dữ liệu' : $promotion['rate'], 'suffix' => $promotion['rate'] === null ? '' : '%'],
            ['label' => 'Tỷ lệ tốt nghiệp', 'value' => $graduation['rate'] === null ? 'Chưa có dữ liệu' : $graduation['rate'], 'suffix' => $graduation['rate'] === null ? '' : '%'],
        ];
    }

    private function promotionStats(Collection $students): array
    {
        $candidateStudents = $students->filter(fn (Student $student) => (int) ($student->classRoom?->grade_level) < 12);
        $promoted = $candidateStudents
            ->whereIn('status', [Student::STATUS_STUDYING, Student::STATUS_GRADUATED])
            ->count();

        return [
            'total' => $candidateStudents->count(),
            'promoted' => $promoted,
            'rate' => $candidateStudents->isNotEmpty() ? round($promoted / $candidateStudents->count() * 100, 1) : null,
        ];
    }

    private function studentReport(?string $studentId, Collection $scoreHeaders, Collection $conducts, Collection $attendanceRecords): ?array
    {
        if (! $studentId) {
            return null;
        }

        $student = Student::with('classRoom')->find($studentId);
        if (! $student) {
            return null;
        }

        $scoreTrend = $scoreHeaders
            ->where('student_id', $studentId)
            ->groupBy('semester_id')
            ->map(fn (Collection $items) => round($items->whereNotNull('average')->avg('average'), 2))
            ->values();
        $attendance = $this->attendanceDistribution($attendanceRecords->where('student_id', $studentId));
        $latestConduct = $conducts->where('student_id', $studentId)->last();

        return [
            'student' => $student,
            'score_trend' => $scoreTrend,
            'average' => $scoreTrend->last(),
            'attendance_rate' => $attendance['rate'],
            'conduct' => $latestConduct?->conduct_level,
            'comment' => $latestConduct?->comment,
        ];
    }

    private function selectedTeacherReport(?string $teacherId, Collection $teacherSummary, Collection $studentRows): ?array
    {
        if (! $teacherId) {
            return null;
        }

        $teacher = Teacher::with('primarySubject')->find($teacherId);
        if (! $teacher) {
            return null;
        }

        $rowsWithAverage = $studentRows->whereNotNull('average');
        $passedCount = $studentRows->filter(fn ($row) => in_array($row['study_rank'], ['excellent', 'good', 'average'], true))->count();

        return [
            'teacher' => $teacher,
            'summary' => $teacherSummary->firstWhere('label', $teacher->name),
            'student_count' => $studentRows->count(),
            'average' => $rowsWithAverage->isNotEmpty() ? round($rowsWithAverage->avg('average'), 2) : null,
            'excellent_good_rate' => $studentRows->isNotEmpty() ? round($studentRows->whereIn('study_rank', ['excellent', 'good'])->count() / $studentRows->count() * 100, 1) : null,
            'passed_rate' => $studentRows->isNotEmpty() ? round($passedCount / $studentRows->count() * 100, 1) : null,
        ];
    }

    private function selectedSubjectReport(?string $subjectId, Collection $scoreHeaders): ?array
    {
        if (! $subjectId) {
            return null;
        }

        $subject = Subject::find($subjectId);
        if (! $subject) {
            return null;
        }

        $scores = $scoreHeaders->where('subject_id', $subjectId)->whereNotNull('average');

        return [
            'subject' => $subject,
            'average' => $scores->isNotEmpty() ? round($scores->avg('average'), 2) : null,
            'passed_rate' => $scores->isNotEmpty() ? round($scores->filter(fn ($score) => $score->average >= 5)->count() / $scores->count() * 100, 1) : null,
            'distribution' => [
                'Từ 8.0 trở lên' => $scores->filter(fn ($score) => $score->average >= 8)->count(),
                'Từ 6.5 đến dưới 8.0' => $scores->filter(fn ($score) => $score->average >= 6.5 && $score->average < 8)->count(),
                'Từ 5.0 đến dưới 6.5' => $scores->filter(fn ($score) => $score->average >= 5 && $score->average < 6.5)->count(),
                'Dưới 5.0' => $scores->filter(fn ($score) => $score->average < 5)->count(),
            ],
        ];
    }

    private function aiInsights(array $filters, Collection $studentRows, array $attendanceDistribution, array $graduation): array
    {
        $rowsWithAverage = $studentRows->whereNotNull('average');
        $average = $rowsWithAverage->isNotEmpty() ? round($rowsWithAverage->avg('average'), 2) : null;
        $insights = [];

        if ($average !== null) {
            $insights[] = $average >= 6.5
                ? 'Kết quả học tập trong phạm vi báo cáo đang duy trì ở mức tích cực.'
                : 'Kết quả học tập trong phạm vi báo cáo cần được theo dõi và hỗ trợ thêm.';
        }

        if ($attendanceDistribution['rate'] !== null) {
            $insights[] = $attendanceDistribution['rate'] >= 90
                ? 'Tỷ lệ chuyên cần tốt, cần tiếp tục duy trì.'
                : 'Tỷ lệ chuyên cần có thể cải thiện thêm thông qua phối hợp giữa giáo viên chủ nhiệm và phụ huynh.';
        }

        if ($graduation['rate'] !== null) {
            $insights[] = 'Tỷ lệ tốt nghiệp hiện ghi nhận ' . $graduation['rate'] . '%, nên tiếp tục theo dõi nhóm học sinh lớp 12.';
        }

        if (empty($insights)) {
            $insights[] = 'Hiện chưa có đủ dữ liệu để tạo nhận xét chi tiết. Cần bổ sung điểm số, hạnh kiểm hoặc điểm danh.';
        }

        return $insights;
    }
}
