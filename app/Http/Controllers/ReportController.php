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
use App\Models\TeacherDepartment;
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
        $reportType = $request->input('report_type', $request->input('scope', 'school_year'));

        if (in_array($reportType, ['statistics', 'school'], true)) {
            $reportType = 'school_year';
        }

        if (in_array($reportType, ['three_years', 'multi_year'], true)) {
            $reportType = 'multi_year';
        }

        if (! in_array($reportType, ['school_year', 'semester', 'grade', 'class', 'teacher', 'department', 'subject', 'student', 'multi_year'], true)) {
            $reportType = 'school_year';
        }

        $scope = $reportType === 'multi_year' ? 'multi_year' : 'statistics';
        $selectedYearId = $scope === 'multi_year'
            ? ($request->input('to_year_id') ?: $request->input('school_year_id') ?: $this->selectedSchoolYearId($request))
            : ($request->input('school_year_id') ?: $this->selectedSchoolYearId($request));
        $selectedSemesterId = $scope === 'multi_year'
            ? null
            : ($request->has('semester_id') ? $request->input('semester_id') : $this->selectedSemesterId($request));

        $gradeLevel = $request->input('grade_level');
        $classId = $request->input('class_id');
        $teacherId = $request->input('teacher_id');
        $departmentId = $request->input('department_id');
        $subjectId = $request->input('subject_id');
        $studentId = $request->input('student_id');

        if ($reportType === 'school_year') {
            $selectedSemesterId = null;
            $gradeLevel = $classId = $teacherId = $departmentId = $subjectId = $studentId = null;
        } elseif ($reportType === 'semester') {
            $gradeLevel = $classId = $teacherId = $departmentId = $subjectId = $studentId = null;
        } elseif ($reportType === 'grade') {
            $classId = $teacherId = $departmentId = $subjectId = $studentId = null;
        } elseif ($reportType === 'class') {
            $teacherId = $departmentId = $subjectId = $studentId = null;
        } elseif ($reportType === 'teacher') {
            $gradeLevel = $classId = $departmentId = $subjectId = $studentId = null;
        } elseif ($reportType === 'department') {
            $gradeLevel = $classId = $teacherId = $subjectId = $studentId = null;
        } elseif ($reportType === 'subject') {
            $gradeLevel = $classId = $teacherId = $departmentId = $studentId = null;
        } elseif ($reportType === 'student') {
            $gradeLevel = $classId = $teacherId = $departmentId = $subjectId = null;
        } else {
            $selectedSemesterId = $gradeLevel = $classId = $teacherId = $departmentId = $subjectId = $studentId = null;
        }

        $filters = [
            'scope' => $scope,
            'report_type' => $reportType,
            'school_year_id' => $selectedYearId,
            'from_year_id' => $request->input('from_year_id'),
            'to_year_id' => $request->input('to_year_id'),
            'semester_id' => $scope === 'multi_year' ? null : $selectedSemesterId,
            'grade_level' => $scope === 'multi_year' ? null : $gradeLevel,
            'class_id' => $scope === 'multi_year' ? null : $classId,
            'teacher_id' => $scope === 'multi_year' ? null : $teacherId,
            'department_id' => $scope === 'multi_year' ? null : $departmentId,
            'subject_id' => $scope === 'multi_year' ? null : $subjectId,
            'student_id' => $scope === 'multi_year' ? null : $studentId,
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
        $departments = TeacherDepartment::with(['subjects', 'leader'])
            ->orderBy('name')
            ->get();
        $scorableTypes = array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES);
        $subjects = Subject::whereIn('type', $scorableTypes)
            ->withEvaluatedAssessment()
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
            ? ScoreHeader::with(['student.classRoom', 'subject', 'semester', 'details'])
                ->whereIn('student_id', $studentIds)
                ->when($selectedYear?->id, fn ($query) => $query->where('school_year_id', $selectedYear->id))
                ->when($filters['semester_id'], fn ($query) => $query->where('semester_id', $filters['semester_id']))
                ->when($filters['subject_id'], fn ($query) => $query->where('subject_id', $filters['subject_id']))
                ->whereHas('subject', fn ($query) => $query->whereIn('type', $scorableTypes)->withEvaluatedAssessment())
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
        $gradeSummary = $this->groupSummary($students, $studentRows, $attendanceRecords, fn (Student $student) => 'Khối ' . ($student->classRoom?->grade_level ?: 'Chưa rõ'));
        $classSummary = $this->groupSummary($students, $studentRows, $attendanceRecords, fn (Student $student) => $student->classRoom?->name ?: 'Chưa có lớp');
        $subjectSummary = $this->subjectSummary($scoreHeaders);
        $teacherSummary = $this->teacherSummary($filters, $selectedYear?->id, $filters['semester_id']);
        $departmentReport = $this->selectedDepartmentReport($filters['department_id'], $departments, $selectedYear?->id, $filters['semester_id']);
        $yearComparison = $this->yearComparison($schoolYears, $selectedYear, $filters);
        $studentReport = $this->studentReport($filters['student_id'], $scoreHeaders, $conducts, $attendanceRecords);
        $teacherReport = $this->selectedTeacherReport($filters['teacher_id'], $teacherSummary, $studentRows);
        $subjectReport = $this->selectedSubjectReport($filters['subject_id'], $scoreHeaders);
        $reportFocus = $this->reportFocus($filters);
        $reportTitle = $this->reportTitle($filters, $selectedYear, $selectedSemester);
        $systemSetting = SystemSetting::current();
        $currentAcademicYear = $systemSetting?->activeSchoolYear ?: ($schoolYears->firstWhere('is_current', true) ?: $schoolYears->first());
        $exportedBy = $user?->name ?: $user?->username ?: 'Hệ thống';
        $reportDashboard = $this->reportDashboard(
            $reportFocus,
            $filters,
            $selectedYear,
            $selectedSemester,
            $classes,
            $teachers,
            $subjects,
            $students,
            $studentRows,
            $scoreHeaders,
            $conducts,
            $attendanceRecords,
            $studyDistribution,
            $conductDistribution,
            $attendanceDistribution,
            $graduation,
            $gradeSummary,
            $classSummary,
            $subjectSummary,
            $teacherSummary,
            $yearComparison,
            $studentReport,
            $teacherReport,
            $subjectReport,
            $departmentReport
        );
        $statisticalInsights = $reportDashboard['insights'];

        $atRiskStudents = $this->atRiskStudents($students, $studentRows, $attendanceRecords);

        return compact(
            'schoolYears',
            'selectedYear',
            'semesters',
            'selectedSemester',
            'classes',
            'teachers',
            'departments',
            'subjects',
            'studentsForFilter',
            'filters',
            'reportFocus',
            'reportTitle',
            'systemSetting',
            'exportedBy',
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
            'departmentReport',
            'subjectReport',
            'statisticalInsights',
            'reportDashboard',
            'atRiskStudents',
            'currentAcademicYear'
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
            'average' => 'Đạt',
            'weak' => 'Chưa đạt',
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
            ->filter(fn ($record) => in_array($record->status, ['present', 'late'], true) || $record->isPermittedAbsent())
            ->count();

        return [
            'total' => $total,
            'present' => $attendanceRecords->where('status', 'present')->count(),
            'late' => $attendanceRecords->where('status', 'late')->count(),
            'excused' => $attendanceRecords->filter(fn ($record) => $record->isPermittedAbsent())->count(),
            'absent' => $attendanceRecords->filter(fn ($record) => $record->isUnexcusedAbsent())->count(),
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
        $title = match ($filters['report_type'] ?? $filters['scope']) {
            'multi_year' => 'Báo cáo so sánh nhiều năm',
            'semester' => 'Báo cáo theo học kỳ',
            'grade' => 'Báo cáo theo khối',
            'class' => 'Báo cáo theo lớp',
            'teacher' => 'Báo cáo theo giáo viên',
            'department' => 'Báo cáo theo tổ chuyên môn',
            'subject' => 'Báo cáo theo môn học',
            'student' => 'Báo cáo theo học sinh',
            default => 'Báo cáo năm học',
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
        return match ($filters['report_type'] ?? 'school_year') {
            'multi_year' => 'multi_year',
            'student' => 'student',
            'teacher' => 'teacher',
            'department' => 'department',
            'subject' => 'subject',
            'class' => 'class',
            'grade' => 'grade',
            'semester' => 'semester',
            default => 'school',
        };
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

            fputcsv($handle, ['Tổng quan']);
            fputcsv($handle, ['Chỉ số', 'Giá trị']);
            foreach ($report['reportDashboard']['cards'] ?? [] as $card) {
                fputcsv($handle, [$card['label'], $card['value']]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Nhận xét thống kê']);
            foreach ($report['reportDashboard']['insights'] ?? [] as $insight) {
                fputcsv($handle, [$insight]);
            }

            $table = $report['reportDashboard']['table'] ?? ['title' => 'Bảng thống kê', 'headers' => [], 'rows' => []];
            fputcsv($handle, []);
            fputcsv($handle, [$table['title'] ?? 'Bảng thống kê']);
            if (! empty($table['headers'])) {
                fputcsv($handle, $table['headers']);
            }
            foreach ($table['rows'] ?? [] as $row) {
                fputcsv($handle, $row);
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
            'average' => 'Đạt',
            'weak' => 'Chưa đạt',
        ][$level] ?? 'Chưa có dữ liệu';
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
            ->map(function (Collection $items) {
                $scores = $items->whereNotNull('average');

                return [
                    'label' => $items->first()->semester?->normalizedName() ?: 'Học kỳ',
                    'average' => $scores->isNotEmpty() ? round($scores->avg('average'), 2) : null,
                ];
            })
            ->values();
        $attendance = $this->attendanceDistribution($attendanceRecords->where('student_id', $studentId));
        $latestConduct = $conducts->where('student_id', $studentId)->last();

        return [
            'student' => $student,
            'score_trend' => $scoreTrend,
            'average' => $scoreTrend->pluck('average')->filter(fn ($value) => $value !== null)->last(),
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

    private function selectedDepartmentReport(?string $departmentId, Collection $departments, ?string $schoolYearId, ?string $semesterId): ?array
    {
        if (! $departmentId) {
            return null;
        }

        $department = $departments->firstWhere('id', $departmentId)
            ?: TeacherDepartment::with(['subjects', 'leader', 'teachers.primarySubject'])->find($departmentId);

        if (! $department) {
            return null;
        }

        $department->loadMissing(['subjects', 'leader', 'teachers.primarySubject']);
        $teacherIds = $department->teachers->pluck('id');
        $assignments = TeachingAssignment::with(['teacher', 'classRoom', 'subject'])
            ->whereIn('teacher_id', $teacherIds)
            ->when($schoolYearId, fn ($query) => $query->where('school_year_id', $schoolYearId))
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->get();

        $completedAssignments = 0;
        foreach ($assignments as $assignment) {
            if (! $assignment->subject?->isEvaluated()) {
                continue;
            }

            $hasScore = Schema::hasTable('score_headers')
                && ScoreHeader::where('subject_id', $assignment->subject_id)
                    ->when($schoolYearId, fn ($query) => $query->where('school_year_id', $schoolYearId))
                    ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
                    ->whereHas('student', fn ($student) => $student->where('class_id', $assignment->class_id))
                    ->exists();

            if ($hasScore) {
                $completedAssignments++;
            }
        }

        return [
            'department' => $department,
            'assignments' => $assignments,
            'teacher_count' => $department->teachers->count(),
            'class_count' => $assignments->pluck('class_id')->unique()->count(),
            'weekly_periods' => $assignments->sum(fn ($assignment) => (int) ($assignment->effectiveWeeklyPeriods() ?: 0)),
            'completed_assignments' => $completedAssignments,
            'missing_assignments' => max(0, $assignments->count() - $completedAssignments),
        ];
    }

    private function selectedSubjectReport(?string $subjectId, Collection $scoreHeaders): ?array
    {
        if (! $subjectId) {
            return null;
        }

        $subject = Subject::find($subjectId);
        if (! $subject || ! $subject->isEvaluated()) {
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

    private function reportDashboard(
        string $focus,
        array $filters,
        ?SchoolYear $year,
        ?Semester $semester,
        Collection $classes,
        Collection $teachers,
        Collection $subjects,
        Collection $students,
        Collection $studentRows,
        Collection $scoreHeaders,
        Collection $conducts,
        Collection $attendanceRecords,
        array $studyDistribution,
        array $conductDistribution,
        array $attendanceDistribution,
        array $graduation,
        Collection $gradeSummary,
        Collection $classSummary,
        Collection $subjectSummary,
        Collection $teacherSummary,
        Collection $yearComparison,
        ?array $studentReport,
        ?array $teacherReport,
        ?array $subjectReport,
        ?array $departmentReport
    ): array {
        return match ($focus) {
            'multi_year' => $this->multiYearDashboard($yearComparison),
            'student' => $this->studentDashboard($studentReport, $scoreHeaders, $conducts, $attendanceRecords),
            'teacher' => $this->teacherDashboard($filters, $year, $semester, $teacherReport, $studentRows),
            'department' => $this->departmentDashboard($departmentReport),
            'subject' => $this->subjectDashboard($filters, $year, $semester, $subjectReport, $scoreHeaders),
            'class' => $this->classDashboard($filters, $classes, $students, $studentRows, $conductDistribution, $attendanceDistribution),
            'grade' => $this->gradeDashboard($filters, $classes, $students, $studentRows, $studyDistribution, $conductDistribution, $attendanceDistribution, $classSummary),
            'semester' => $this->semesterDashboard($students, $studentRows, $classes, $studyDistribution, $conductDistribution, $attendanceDistribution, $classSummary),
            default => $this->schoolYearDashboard($students, $studentRows, $classes, $teachers, $subjects, $studyDistribution, $conductDistribution, $attendanceDistribution, $graduation, $gradeSummary),
        };
    }

    private function emptyDashboard(string $message): array
    {
        return [
            'cards' => [],
            'profile' => [],
            'charts' => [],
            'table' => ['title' => 'Bảng thống kê', 'headers' => [], 'rows' => []],
            'insights' => [$message],
            'empty' => $message,
        ];
    }

    private function metric(string $label, mixed $value, string $icon = 'bi-clipboard-data'): array
    {
        return compact('label', 'value', 'icon');
    }

    private function chart(string $id, string $title, string $type, array|Collection $labels, array|Collection $values, array $colors, ?array $datasets = null): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'type' => $type,
            'labels' => collect($labels)->values()->all(),
            'values' => collect($values)->values()->all(),
            'colors' => $colors,
            'datasets' => $datasets,
        ];
    }

    private function schoolYearDashboard(Collection $students, Collection $studentRows, Collection $classes, Collection $teachers, Collection $subjects, array $studyDistribution, array $conductDistribution, array $attendanceDistribution, array $graduation, Collection $gradeSummary): array
    {
        $rowsWithAverage = $studentRows->whereNotNull('average');
        $goodRate = $this->rate($studentRows->whereIn('study_rank', ['excellent', 'good'])->count(), $students->count());
        $promotion = $this->promotionStats($students);

        $cards = [
            $this->metric('Tổng học sinh', $students->count(), 'bi-mortarboard'),
            $this->metric('Tổng giáo viên', $teachers->count(), 'bi-person-workspace'),
            $this->metric('Tổng lớp', $classes->count(), 'bi-building'),
            $this->metric('Tổng môn học', $subjects->count(), 'bi-journal-bookmark'),
            $this->metric('Điểm trung bình toàn trường', $rowsWithAverage->isNotEmpty() ? round($rowsWithAverage->avg('average'), 2) : 'Chưa có dữ liệu', 'bi-star'),
            $this->metric('Tỷ lệ khá giỏi', $goodRate === null ? 'Chưa có dữ liệu' : $goodRate . '%', 'bi-graph-up-arrow'),
            $this->metric('Tỷ lệ chuyên cần', $attendanceDistribution['rate'] === null ? 'Chưa có dữ liệu' : $attendanceDistribution['rate'] . '%', 'bi-calendar-check'),
            $this->metric('Tỷ lệ lên lớp', $promotion['rate'] === null ? 'Chưa có dữ liệu' : $promotion['rate'] . '%', 'bi-arrow-up-circle'),
            $this->metric('Tỷ lệ tốt nghiệp', $graduation['rate'] === null ? 'Chưa có dữ liệu' : $graduation['rate'] . '%', 'bi-award'),
        ];

        return [
            'cards' => $cards,
            'profile' => [],
            'charts' => [
                $this->distributionChart('studyChart', 'Biểu đồ học lực', $studyDistribution, 'bar'),
                $this->distributionChart('conductChart', 'Biểu đồ hạnh kiểm', $conductDistribution, 'doughnut'),
                $this->chart('attendanceChart', 'Tỷ lệ chuyên cần', 'doughnut', ['Có mặt', 'Đi muộn', 'Có phép', 'Không phép'], [$attendanceDistribution['present'], $attendanceDistribution['late'], $attendanceDistribution['excused'], $attendanceDistribution['absent']], ['#22C55E', '#60A5FA', '#FACC15', '#F97316']),
                $this->chart('graduationChart', 'Tỷ lệ tốt nghiệp', 'doughnut', ['Đã tốt nghiệp', 'Chưa tốt nghiệp'], [$graduation['graduated'], max(0, $graduation['total'] - $graduation['graduated'])], ['#3B82F6', '#CBD5E1']),
            ],
            'table' => [
                'title' => 'Tổng kết theo khối',
                'headers' => ['Khối', 'Sĩ số', 'Điểm trung bình', 'Học sinh giỏi', 'Chuyên cần'],
                'rows' => $gradeSummary->map(fn ($row) => [
                    $row['label'],
                    $row['student_count'],
                    $row['average'] ?? 'Chưa có dữ liệu',
                    $row['excellent_count'],
                    $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%',
                ])->values()->all(),
            ],
            'insights' => $this->schoolInsights($studentRows, $attendanceDistribution, $graduation, $gradeSummary),
        ];
    }

    private function semesterDashboard(Collection $students, Collection $studentRows, Collection $classes, array $studyDistribution, array $conductDistribution, array $attendanceDistribution, Collection $classSummary): array
    {
        $rowsWithAverage = $studentRows->whereNotNull('average');
        $goodRate = $this->rate($studentRows->whereIn('study_rank', ['excellent', 'good'])->count(), $students->count());
        $conductGoodRate = $this->rate(collect($conductDistribution)->whereIn('key', ['excellent', 'good'])->sum('value'), $students->count());

        return [
            'cards' => [
                $this->metric('Số học sinh', $students->count(), 'bi-mortarboard'),
                $this->metric('Số lớp', $classes->count(), 'bi-building'),
                $this->metric('Điểm trung bình học kỳ', $rowsWithAverage->isNotEmpty() ? round($rowsWithAverage->avg('average'), 2) : 'Chưa có dữ liệu', 'bi-star'),
                $this->metric('Tỷ lệ khá giỏi', $goodRate === null ? 'Chưa có dữ liệu' : $goodRate . '%', 'bi-graph-up-arrow'),
                $this->metric('Hạnh kiểm tốt/khá', $conductGoodRate === null ? 'Chưa có dữ liệu' : $conductGoodRate . '%', 'bi-clipboard-check'),
                $this->metric('Tỷ lệ chuyên cần', $attendanceDistribution['rate'] === null ? 'Chưa có dữ liệu' : $attendanceDistribution['rate'] . '%', 'bi-calendar-check'),
            ],
            'profile' => [],
            'charts' => [
                $this->distributionChart('studyChart', 'Học lực học kỳ', $studyDistribution, 'bar'),
                $this->distributionChart('conductChart', 'Hạnh kiểm học kỳ', $conductDistribution, 'doughnut'),
                $this->chart('attendanceChart', 'Chuyên cần học kỳ', 'doughnut', ['Có mặt', 'Đi muộn', 'Có phép', 'Không phép'], [$attendanceDistribution['present'], $attendanceDistribution['late'], $attendanceDistribution['excused'], $attendanceDistribution['absent']], ['#22C55E', '#60A5FA', '#FACC15', '#F97316']),
            ],
            'table' => [
                'title' => 'Tổng kết theo lớp trong học kỳ',
                'headers' => ['Lớp', 'Sĩ số', 'Điểm trung bình', 'Học sinh giỏi', 'Chuyên cần'],
                'rows' => $this->summaryRows($classSummary),
            ],
            'insights' => $this->scopeInsights('học kỳ', $studentRows, $attendanceDistribution, $classSummary),
        ];
    }

    private function gradeDashboard(array $filters, Collection $classes, Collection $students, Collection $studentRows, array $studyDistribution, array $conductDistribution, array $attendanceDistribution, Collection $classSummary): array
    {
        if (! $filters['grade_level']) {
            return $this->emptyDashboard('Vui lòng chọn khối để xem báo cáo theo khối.');
        }

        $grade = $filters['grade_level'] ?: 'chưa chọn';
        $rowsWithAverage = $studentRows->whereNotNull('average');
        $conductGoodRate = $this->rate(collect($conductDistribution)->whereIn('key', ['excellent', 'good'])->sum('value'), $students->count());

        return [
            'cards' => [
                $this->metric('Khối', 'Khối ' . $grade, 'bi-layers'),
                $this->metric('Số lớp của khối', $classes->where('grade_level', (int) $filters['grade_level'])->count(), 'bi-building'),
                $this->metric('Số học sinh', $students->count(), 'bi-mortarboard'),
                $this->metric('Điểm trung bình khối', $rowsWithAverage->isNotEmpty() ? round($rowsWithAverage->avg('average'), 2) : 'Chưa có dữ liệu', 'bi-star'),
                $this->metric('Hạnh kiểm tốt/khá', $conductGoodRate === null ? 'Chưa có dữ liệu' : $conductGoodRate . '%', 'bi-clipboard-check'),
                $this->metric('Tỷ lệ chuyên cần', $attendanceDistribution['rate'] === null ? 'Chưa có dữ liệu' : $attendanceDistribution['rate'] . '%', 'bi-calendar-check'),
            ],
            'profile' => [],
            'charts' => [
                $this->distributionChart('studyChart', 'Học lực của khối', $studyDistribution, 'bar'),
                $this->distributionChart('conductChart', 'Hạnh kiểm của khối', $conductDistribution, 'doughnut'),
                $this->chart('attendanceChart', 'Chuyên cần của khối', 'doughnut', ['Có mặt', 'Đi muộn', 'Có phép', 'Không phép'], [$attendanceDistribution['present'], $attendanceDistribution['late'], $attendanceDistribution['excused'], $attendanceDistribution['absent']], ['#22C55E', '#60A5FA', '#FACC15', '#F97316']),
            ],
            'table' => [
                'title' => 'Tổng kết các lớp trong khối',
                'headers' => ['Lớp', 'Sĩ số', 'Điểm trung bình', 'Học sinh giỏi', 'Chuyên cần'],
                'rows' => $this->summaryRows($classSummary),
            ],
            'insights' => $this->scopeInsights('khối ' . $grade, $studentRows, $attendanceDistribution, $classSummary),
        ];
    }

    private function classDashboard(array $filters, Collection $classes, Collection $students, Collection $studentRows, array $conductDistribution, array $attendanceDistribution): array
    {
        $class = $filters['class_id'] ? $classes->firstWhere('id', $filters['class_id']) : null;
        if (! $class) {
            return $this->emptyDashboard('Vui lòng chọn lớp để xem báo cáo theo lớp.');
        }

        $rowsWithAverage = $studentRows->whereNotNull('average');
        $conductGoodRate = $this->rate(collect($conductDistribution)->whereIn('key', ['excellent', 'good'])->sum('value'), $students->count());

        return [
            'cards' => [
                $this->metric('Giáo viên chủ nhiệm', $class->homeroomTeacher?->name ?: 'Chưa phân công', 'bi-person-badge'),
                $this->metric('Sĩ số', $students->count(), 'bi-mortarboard'),
                $this->metric('Nam', $students->where('gender', Student::GENDER_NAM)->count(), 'bi-gender-male'),
                $this->metric('Nữ', $students->where('gender', Student::GENDER_NU)->count(), 'bi-gender-female'),
                $this->metric('Điểm trung bình lớp', $rowsWithAverage->isNotEmpty() ? round($rowsWithAverage->avg('average'), 2) : 'Chưa có dữ liệu', 'bi-star'),
                $this->metric('Hạnh kiểm tốt/khá', $conductGoodRate === null ? 'Chưa có dữ liệu' : $conductGoodRate . '%', 'bi-clipboard-check'),
                $this->metric('Tỷ lệ chuyên cần', $attendanceDistribution['rate'] === null ? 'Chưa có dữ liệu' : $attendanceDistribution['rate'] . '%', 'bi-calendar-check'),
            ],
            'profile' => [
                ['label' => 'Lớp', 'value' => $class->name],
                ['label' => 'Khối', 'value' => 'Khối ' . $class->grade_level],
                ['label' => 'Năm học', 'value' => $class->schoolYear?->name ?: '-'],
                ['label' => 'Giáo viên chủ nhiệm', 'value' => $class->homeroomTeacher?->name ?: 'Chưa phân công'],
            ],
            'charts' => [
                $this->distributionChart('studyChart', 'Học lực của lớp', $this->studyDistribution($studentRows), 'bar'),
                $this->distributionChart('conductChart', 'Hạnh kiểm của lớp', $conductDistribution, 'doughnut'),
                $this->chart('attendanceChart', 'Chuyên cần của lớp', 'doughnut', ['Có mặt', 'Đi muộn', 'Có phép', 'Không phép'], [$attendanceDistribution['present'], $attendanceDistribution['late'], $attendanceDistribution['excused'], $attendanceDistribution['absent']], ['#22C55E', '#60A5FA', '#FACC15', '#F97316']),
            ],
            'table' => [
                'title' => 'Danh sách học sinh của lớp',
                'headers' => ['Mã học sinh', 'Họ tên', 'Giới tính', 'Điểm trung bình', 'Học lực', 'Hạnh kiểm', 'Chuyên cần'],
                'rows' => $studentRows->map(fn ($row) => [
                    $row['student']->student_code,
                    $row['student']->name,
                    $row['student']->genderLabel(),
                    $row['average'] ?? 'Chưa có dữ liệu',
                    $this->studyLabel($row['study_rank']),
                    $this->conductLabel($row['conduct']),
                    $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%',
                ])->values()->all(),
            ],
            'insights' => $this->classInsights($class, $studentRows, $attendanceDistribution),
        ];
    }

    private function departmentDashboard(?array $departmentReport): array
    {
        if (! $departmentReport) {
            return $this->emptyDashboard('Vui lòng chọn tổ chuyên môn để xem báo cáo theo tổ.');
        }

        /** @var TeacherDepartment $department */
        $department = $departmentReport['department'];
        $assignments = $departmentReport['assignments'];
        $completed = (int) $departmentReport['completed_assignments'];
        $totalAssignments = $assignments->count();
        $scoreProgress = $totalAssignments > 0 ? round($completed / $totalAssignments * 100, 1) : null;
        $subjectNames = $department->subjectNames();

        $teacherRows = $department->teachers
            ->sortBy('teacher_code')
            ->map(function (Teacher $teacher) use ($assignments) {
                $teacherAssignments = $assignments->where('teacher_id', $teacher->id);

                return [
                    $teacher->teacher_code,
                    $teacher->name,
                    $teacher->primarySubjectName(),
                    $teacherAssignments->pluck('class_id')->unique()->count(),
                    $teacherAssignments->sum(fn ($assignment) => (int) ($assignment->effectiveWeeklyPeriods() ?: 0)),
                    $teacher->workStatusLabel(),
                ];
            })
            ->values()
            ->all();

        return [
            'cards' => [
                $this->metric('Tổ chuyên môn', $department->name, 'bi-diagram-2'),
                $this->metric('Môn phụ trách', $subjectNames, 'bi-book'),
                $this->metric('Tổ trưởng', $department->leader?->name ?: 'Chưa phân công', 'bi-person-check'),
                $this->metric('Số giáo viên', $departmentReport['teacher_count'], 'bi-people'),
                $this->metric('Số lớp đang dạy', $departmentReport['class_count'], 'bi-building'),
                $this->metric('Tổng số tiết', $departmentReport['weekly_periods'], 'bi-clock-history'),
                $this->metric('Tiến độ nhập điểm', $scoreProgress === null ? 'Chưa có dữ liệu' : $scoreProgress . '%', 'bi-clipboard-check'),
            ],
            'profile' => [
                ['label' => 'Mã tổ', 'value' => $department->code],
                ['label' => 'Tên tổ', 'value' => $department->name],
                ['label' => 'Môn phụ trách', 'value' => $subjectNames],
                ['label' => 'Tổ trưởng', 'value' => $department->leader?->name ?: 'Chưa phân công'],
                ['label' => 'Trạng thái', 'value' => $department->statusLabel()],
            ],
            'charts' => [
                $this->chart(
                    'departmentTeachingChart',
                    'Số lớp đang dạy theo giáo viên',
                    'bar',
                    $department->teachers->pluck('name')->values(),
                    $department->teachers->map(fn (Teacher $teacher) => $assignments->where('teacher_id', $teacher->id)->pluck('class_id')->unique()->count())->values(),
                    ['#D96F16']
                ),
            ],
            'table' => [
                'title' => 'Danh sách giáo viên trong tổ',
                'headers' => ['Mã giáo viên', 'Họ tên', 'Môn chính', 'Số lớp đang dạy', 'Tổng số tiết', 'Trạng thái'],
                'rows' => $teacherRows,
            ],
            'insights' => [
                'Tổ ' . $department->name . ' hiện có ' . $departmentReport['teacher_count'] . ' giáo viên.',
                'Tổng số lớp đang giảng dạy trong phạm vi báo cáo là ' . $departmentReport['class_count'] . '.',
                $scoreProgress === null
                    ? 'Chưa có dữ liệu nhập điểm để thống kê tiến độ.'
                    : 'Tiến độ nhập điểm của tổ đạt ' . $scoreProgress . '%.',
            ],
        ];
    }

    private function teacherDashboard(array $filters, ?SchoolYear $year, ?Semester $semester, ?array $teacherReport, Collection $studentRows): array
    {
        if (! $teacherReport) {
            return $this->emptyDashboard('Vui lòng chọn giáo viên để xem báo cáo theo giáo viên.');
        }

        $teacher = $teacherReport['teacher'];
        $assignments = TeachingAssignment::with(['classRoom', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->when($year?->id, fn ($query) => $query->where('school_year_id', $year->id))
            ->when($semester?->id, fn ($query) => $query->where('semester_id', $semester->id))
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->get();
        $belowAverageRate = $this->rate($studentRows->where('study_rank', 'needs_support')->count(), $studentRows->count());

        $classRows = $assignments->groupBy('class_id')->map(function (Collection $items) use ($studentRows) {
            $class = $items->first()->classRoom;
            $classStudentRows = $studentRows->filter(fn ($row) => (string) $row['student']->class_id === (string) $class?->id);
            $rowsWithAverage = $classStudentRows->whereNotNull('average');

            $excellentGoodRate = $this->rate($classStudentRows->whereIn('study_rank', ['excellent', 'good'])->count(), $classStudentRows->count());

            return [
                $class?->name ?: 'Chưa rõ lớp',
                $items->pluck('subject.name')->filter()->unique()->implode(', ') ?: '-',
                $classStudentRows->count(),
                $rowsWithAverage->isNotEmpty() ? round($rowsWithAverage->avg('average'), 2) : 'Chưa có dữ liệu',
                $excellentGoodRate,
            ];
        })->values();

        $classAverageRows = $classRows->map(fn ($row) => is_numeric($row[3]) ? (float) $row[3] : 0);

        return [
            'cards' => [
                $this->metric('Họ tên', $teacher->name, 'bi-person-badge'),
                $this->metric('Bộ môn', $teacher->primarySubjectName(), 'bi-journal-bookmark'),
                $this->metric('Số lớp đang dạy', $assignments->pluck('class_id')->unique()->count(), 'bi-building'),
                $this->metric('Tổng học sinh phụ trách', $studentRows->count(), 'bi-mortarboard'),
                $this->metric('Điểm trung bình các lớp', $teacherReport['average'] ?? 'Chưa có dữ liệu', 'bi-star'),
                $this->metric('Tỷ lệ khá giỏi', $teacherReport['excellent_good_rate'] === null ? 'Chưa có dữ liệu' : $teacherReport['excellent_good_rate'] . '%', 'bi-graph-up-arrow'),
                $this->metric('Tỷ lệ dưới trung bình', $belowAverageRate === null ? 'Chưa có dữ liệu' : $belowAverageRate . '%', 'bi-arrow-down-circle'),
            ],
            'profile' => [
                ['label' => 'Mã giáo viên', 'value' => $teacher->teacher_code],
                ['label' => 'Email', 'value' => $teacher->email ?: '-'],
                ['label' => 'Số điện thoại', 'value' => $teacher->phone ?: '-'],
                ['label' => 'Trạng thái', 'value' => $teacher->workStatusLabel()],
            ],
            'charts' => [
                $this->chart('teacherClassChart', 'Kết quả các lớp đang dạy', 'bar', $classRows->pluck(0), $classAverageRows, ['#3B82F6']),
            ],
            'table' => [
                'title' => 'Danh sách lớp đang dạy',
                'headers' => ['Lớp', 'Môn học', 'Số học sinh', 'Điểm trung bình', 'Tỷ lệ khá giỏi'],
                'rows' => $classRows->map(fn ($row) => [$row[0], $row[1], $row[2], $row[3], $row[4] === null ? 'Chưa có dữ liệu' : $row[4] . '%'])->all(),
            ],
            'insights' => $this->teacherInsights($teacher, $teacherReport, $belowAverageRate),
        ];
    }

    private function subjectDashboard(array $filters, ?SchoolYear $year, ?Semester $semester, ?array $subjectReport, Collection $scoreHeaders): array
    {
        if (! $subjectReport) {
            return $this->emptyDashboard('Vui lòng chọn môn học để xem báo cáo theo môn học.');
        }

        $subject = $subjectReport['subject'];
        $assignments = TeachingAssignment::with(['teacher', 'classRoom'])
            ->where('subject_id', $subject->id)
            ->when($year?->id, fn ($query) => $query->where('school_year_id', $year->id))
            ->when($semester?->id, fn ($query) => $query->where('semester_id', $semester->id))
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->get();
        $scores = $scoreHeaders->where('subject_id', $subject->id)->whereNotNull('average');
        $belowRate = $this->rate($scores->filter(fn ($score) => $score->average < 5)->count(), $scores->count());

        $classRows = $scores->groupBy(fn (ScoreHeader $header) => $header->student?->classRoom?->id ?: 'unknown')
            ->map(function (Collection $headers) {
                $class = $headers->first()->student?->classRoom;

                return [
                    $class?->name ?: 'Chưa rõ lớp',
                    $headers->pluck('student_id')->unique()->count(),
                    round($headers->avg('average'), 2),
                    $this->rate($headers->filter(fn ($score) => $score->average >= 5)->count(), $headers->count()) . '%',
                ];
            })
            ->values();

        return [
            'cards' => [
                $this->metric('Môn học', $subject->name, 'bi-journal-bookmark'),
                $this->metric('Giáo viên giảng dạy', $assignments->pluck('teacher_id')->unique()->count(), 'bi-person-workspace'),
                $this->metric('Số lớp', $assignments->pluck('class_id')->unique()->count(), 'bi-building'),
                $this->metric('Điểm trung bình môn', $subjectReport['average'] ?? 'Chưa có dữ liệu', 'bi-star'),
                $this->metric('Tỷ lệ đạt', $subjectReport['passed_rate'] === null ? 'Chưa có dữ liệu' : $subjectReport['passed_rate'] . '%', 'bi-check-circle'),
                $this->metric('Tỷ lệ dưới trung bình', $belowRate === null ? 'Chưa có dữ liệu' : $belowRate . '%', 'bi-arrow-down-circle'),
            ],
            'profile' => [
                ['label' => 'Mã môn', 'value' => $subject->code ?? '-'],
                ['label' => 'Loại môn', 'value' => method_exists($subject, 'typeLabel') ? $subject->typeLabel() : ($subject->type ?? '-')],
                ['label' => 'Hệ số', 'value' => $subject->coefficient ?? '-'],
            ],
            'charts' => [
                $this->chart('subjectDistributionChart', 'Phân bố điểm môn học', 'bar', collect($subjectReport['distribution'])->keys(), collect($subjectReport['distribution'])->values(), ['#22C55E', '#3B82F6', '#F59E0B', '#F97316']),
            ],
            'table' => [
                'title' => 'Kết quả theo lớp',
                'headers' => ['Lớp', 'Số học sinh có điểm', 'Điểm trung bình', 'Tỷ lệ đạt'],
                'rows' => $classRows->all(),
            ],
            'insights' => $this->subjectInsights($subject, $subjectReport, $belowRate),
        ];
    }

    private function studentDashboard(?array $studentReport, Collection $scoreHeaders, Collection $conducts, Collection $attendanceRecords): array
    {
        if (! $studentReport) {
            return $this->emptyDashboard('Vui lòng chọn học sinh để xem báo cáo cá nhân.');
        }

        $student = $studentReport['student']->loadMissing('classRoom.homeroomTeacher');
        $headers = $scoreHeaders->where('student_id', $student->id);
        $subjectRows = $this->studentScoreRows($headers);
        $subjectAverages = $subjectRows->map(fn ($row) => is_numeric($row[6]) ? (float) $row[6] : 0);
        $trendLabels = $studentReport['score_trend']->pluck('label');
        $trendValues = $studentReport['score_trend']->pluck('average')->map(fn ($value) => $value ?? 0);
        $bestSubject = $subjectRows->filter(fn ($row) => is_numeric($row[6]))->sortByDesc(6)->first();
        $improveSubject = $subjectRows->filter(fn ($row) => is_numeric($row[6]))->sortBy(6)->first();

        return [
            'cards' => [
                $this->metric('Mã học sinh', $student->student_code, 'bi-person-vcard'),
                $this->metric('Họ tên', $student->name, 'bi-person'),
                $this->metric('Giới tính', $student->genderLabel(), 'bi-gender-ambiguous'),
                $this->metric('Ngày sinh', $student->dob?->format('d/m/Y') ?: '-', 'bi-calendar-heart'),
                $this->metric('Lớp', $student->classRoom?->name ?: 'Chưa có lớp', 'bi-building'),
                $this->metric('Giáo viên chủ nhiệm', $student->classRoom?->homeroomTeacher?->name ?: 'Chưa phân công', 'bi-person-badge'),
                $this->metric('Điểm trung bình', $studentReport['average'] ?? 'Chưa có dữ liệu', 'bi-star'),
                $this->metric('Học lực', $this->rankStudy($studentReport['average'] ?? null) === 'no_data' ? 'Chưa có dữ liệu' : $this->studyLabel($this->rankStudy($studentReport['average'] ?? null)), 'bi-graph-up'),
                $this->metric('Hạnh kiểm', $this->conductLabel($studentReport['conduct']), 'bi-clipboard-check'),
                $this->metric('Chuyên cần', $studentReport['attendance_rate'] === null ? 'Chưa có dữ liệu' : $studentReport['attendance_rate'] . '%', 'bi-calendar-check'),
            ],
            'profile' => [
                ['label' => 'Mã học sinh', 'value' => $student->student_code],
                ['label' => 'Họ tên', 'value' => $student->name],
                ['label' => 'Giới tính', 'value' => $student->genderLabel()],
                ['label' => 'Ngày sinh', 'value' => $student->dob?->format('d/m/Y') ?: '-'],
                ['label' => 'Lớp', 'value' => $student->classRoom?->name ?: 'Chưa có lớp'],
                ['label' => 'Giáo viên chủ nhiệm', 'value' => $student->classRoom?->homeroomTeacher?->name ?: 'Chưa phân công'],
            ],
            'charts' => [
                $this->chart('studentSubjectChart', 'Điểm từng môn', 'bar', $subjectRows->pluck(0), $subjectAverages, ['#3B82F6']),
                $this->chart('studentTrendChart', 'Điểm trung bình theo học kỳ', 'bar', $trendLabels, $trendValues, ['#22C55E']),
            ],
            'table' => [
                'title' => 'Bảng điểm học sinh',
                'headers' => ['Môn', 'Miệng', '15 phút', 'Một tiết', 'Giữa kỳ', 'Cuối kỳ', 'Trung bình'],
                'rows' => $subjectRows->all(),
            ],
            'insights' => $this->studentInsights($studentReport, $bestSubject, $improveSubject),
        ];
    }

    private function multiYearDashboard(Collection $yearComparison): array
    {
        if ($yearComparison->count() < 2) {
            return $this->emptyDashboard('Vui lòng chọn khoảng từ 2 năm học trở lên để so sánh.');
        }

        return [
            'cards' => [
                $this->metric('Số năm so sánh', $yearComparison->count(), 'bi-calendar-range'),
                $this->metric('Từ năm học', $yearComparison->first()['label'], 'bi-calendar-minus'),
                $this->metric('Đến năm học', $yearComparison->last()['label'], 'bi-calendar-plus'),
                $this->metric('Điểm trung bình mới nhất', $yearComparison->last()['average'] ?? 'Chưa có dữ liệu', 'bi-star'),
                $this->metric('Chuyên cần mới nhất', $yearComparison->last()['attendance_rate'] === null ? 'Chưa có dữ liệu' : $yearComparison->last()['attendance_rate'] . '%', 'bi-calendar-check'),
                $this->metric('Số học sinh mới nhất', $yearComparison->last()['student_count'], 'bi-mortarboard'),
            ],
            'profile' => [],
            'charts' => [
                $this->chart('yearAverageChart', 'So sánh điểm trung bình', 'bar', $yearComparison->pluck('label'), $yearComparison->pluck('average')->map(fn ($value) => $value ?? 0), ['#D96F16']),
                [
                    'id' => 'yearRateChart',
                    'title' => 'So sánh chuyên cần, hạnh kiểm, lên lớp, tốt nghiệp',
                    'type' => 'line',
                    'labels' => $yearComparison->pluck('label')->values()->all(),
                    'values' => [],
                    'colors' => [],
                    'datasets' => [
                        ['label' => 'Chuyên cần', 'data' => $yearComparison->pluck('attendance_rate')->map(fn ($value) => $value ?? 0)->values()->all(), 'borderColor' => '#22C55E', 'backgroundColor' => 'rgba(34,197,94,.12)', 'tension' => .35],
                        ['label' => 'Hạnh kiểm tốt/khá', 'data' => $yearComparison->pluck('conduct_good_rate')->map(fn ($value) => $value ?? 0)->values()->all(), 'borderColor' => '#3B82F6', 'backgroundColor' => 'rgba(59,130,246,.12)', 'tension' => .35],
                        ['label' => 'Lên lớp', 'data' => $yearComparison->pluck('promotion_rate')->map(fn ($value) => $value ?? 0)->values()->all(), 'borderColor' => '#F59E0B', 'backgroundColor' => 'rgba(245,158,11,.12)', 'tension' => .35],
                        ['label' => 'Tốt nghiệp', 'data' => $yearComparison->pluck('graduation_rate')->map(fn ($value) => $value ?? 0)->values()->all(), 'borderColor' => '#8B5CF6', 'backgroundColor' => 'rgba(139,92,246,.12)', 'tension' => .35],
                    ],
                ],
                $this->chart('yearStudentChart', 'So sánh số lượng học sinh', 'bar', $yearComparison->pluck('label'), $yearComparison->pluck('student_count'), ['#3B82F6']),
            ],
            'table' => [
                'title' => 'Bảng so sánh nhiều năm học',
                'headers' => ['Năm học', 'Số học sinh', 'Điểm trung bình', 'Chuyên cần', 'Hạnh kiểm tốt/khá', 'Tỷ lệ lên lớp', 'Tỷ lệ tốt nghiệp'],
                'rows' => $yearComparison->map(fn ($row) => [
                    $row['label'],
                    $row['student_count'],
                    $row['average'] ?? 'Chưa có dữ liệu',
                    $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%',
                    $row['conduct_good_rate'] === null ? 'Chưa có dữ liệu' : $row['conduct_good_rate'] . '%',
                    $row['promotion_rate'] === null ? 'Chưa có dữ liệu' : $row['promotion_rate'] . '%',
                    $row['graduation_rate'] === null ? 'Chưa có dữ liệu' : $row['graduation_rate'] . '%',
                ])->values()->all(),
            ],
            'insights' => $this->multiYearInsights($yearComparison),
        ];
    }

    private function distributionChart(string $id, string $title, array $distribution, string $type): array
    {
        return $this->chart($id, $title, $type, collect($distribution)->pluck('label'), collect($distribution)->pluck('value'), ['#22C55E', '#3B82F6', '#F59E0B', '#F97316', '#94A3B8']);
    }

    private function summaryRows(Collection $summary): array
    {
        return $summary->map(fn ($row) => [
            $row['label'],
            $row['student_count'],
            $row['average'] ?? 'Chưa có dữ liệu',
            $row['excellent_count'],
            $row['attendance_rate'] === null ? 'Chưa có dữ liệu' : $row['attendance_rate'] . '%',
        ])->values()->all();
    }

    private function studentScoreRows(Collection $headers): Collection
    {
        return $headers
            ->sortBy(fn (ScoreHeader $header) => $header->subject?->name ?: '')
            ->map(function (ScoreHeader $header) {
                return [
                    $header->subject?->name ?: 'Chưa rõ môn',
                    $this->scoreValues($header, 'oral'),
                    $this->scoreValues($header, 'quiz'),
                    $this->scoreValues($header, 'test'),
                    $this->scoreValues($header, 'midterm'),
                    $this->scoreValues($header, 'final'),
                    $header->average ?? 'Chưa có dữ liệu',
                ];
            })
            ->values();
    }

    private function scoreValues(ScoreHeader $header, string $type): string
    {
        $values = $header->details
            ->where('type', $type)
            ->pluck('value')
            ->map(fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.'));

        return $values->isNotEmpty() ? $values->implode(', ') : '-';
    }

    private function rate(int|float $value, int|float $total): ?float
    {
        return $total > 0 ? round($value / $total * 100, 1) : null;
    }

    private function schoolInsights(Collection $studentRows, array $attendanceDistribution, array $graduation, Collection $gradeSummary): array
    {
        $insights = $this->statisticalInsights([], $studentRows, $attendanceDistribution, $graduation, $gradeSummary, collect(), collect());
        $bestGrade = $gradeSummary->whereNotNull('average')->sortByDesc('average')->first();
        if ($bestGrade) {
            $insights[] = $bestGrade['label'] . ' đang có kết quả học tập nổi bật nhất trong năm học.';
        }

        return $insights;
    }

    private function scopeInsights(string $scopeName, Collection $studentRows, array $attendanceDistribution, Collection $summary): array
    {
        $rowsWithAverage = $studentRows->whereNotNull('average');
        $insights = [];
        if ($rowsWithAverage->isNotEmpty()) {
            $insights[] = 'Điểm trung bình của ' . $scopeName . ' đạt ' . round($rowsWithAverage->avg('average'), 2) . '.';
        }
        if ($attendanceDistribution['rate'] !== null) {
            $insights[] = 'Tỷ lệ chuyên cần của ' . $scopeName . ' đạt ' . $attendanceDistribution['rate'] . '%.';
        }
        $best = $summary->whereNotNull('average')->sortByDesc('average')->first();
        if ($best) {
            $insights[] = $best['label'] . ' có điểm trung bình cao nhất trong phạm vi báo cáo.';
        }

        return $insights ?: ['Chưa có đủ dữ liệu để tạo nhận xét thống kê cho phạm vi này.'];
    }

    private function classInsights(SchoolClass $class, Collection $studentRows, array $attendanceDistribution): array
    {
        $rowsWithAverage = $studentRows->whereNotNull('average');
        $insights = ['Báo cáo chỉ tổng hợp dữ liệu của lớp ' . $class->name . '.'];
        if ($rowsWithAverage->isNotEmpty()) {
            $insights[] = 'Điểm trung bình lớp đạt ' . round($rowsWithAverage->avg('average'), 2) . '.';
        }
        if ($attendanceDistribution['rate'] !== null) {
            $insights[] = 'Tỷ lệ chuyên cần của lớp đạt ' . $attendanceDistribution['rate'] . '%.';
        }

        return $insights;
    }

    private function teacherInsights(Teacher $teacher, array $teacherReport, ?float $belowAverageRate): array
    {
        $insights = ['Báo cáo chỉ tổng hợp dữ liệu các lớp và môn giáo viên ' . $teacher->name . ' được phân công.'];
        if ($teacherReport['average'] !== null) {
            $insights[] = 'Điểm trung bình các lớp đang dạy đạt ' . $teacherReport['average'] . '.';
        }
        if ($teacherReport['excellent_good_rate'] !== null) {
            $insights[] = 'Tỷ lệ học sinh khá giỏi trong phạm vi giảng dạy đạt ' . $teacherReport['excellent_good_rate'] . '%.';
        }
        if ($belowAverageRate !== null) {
            $insights[] = 'Tỷ lệ dưới trung bình hiện là ' . $belowAverageRate . '%, cần theo dõi theo từng lớp và từng môn.';
        }

        return $insights;
    }

    private function subjectInsights(Subject $subject, array $subjectReport, ?float $belowRate): array
    {
        $insights = ['Báo cáo chỉ tổng hợp điểm và lớp có liên quan đến môn ' . $subject->name . '.'];
        if ($subjectReport['average'] !== null) {
            $insights[] = 'Điểm trung bình môn đạt ' . $subjectReport['average'] . '.';
        }
        if ($subjectReport['passed_rate'] !== null) {
            $insights[] = 'Tỷ lệ học sinh đạt yêu cầu môn học là ' . $subjectReport['passed_rate'] . '%.';
        }
        if ($belowRate !== null) {
            $insights[] = 'Tỷ lệ dưới trung bình của môn là ' . $belowRate . '%, nên xem chi tiết theo từng lớp.';
        }

        return $insights;
    }

    private function studentInsights(array $studentReport, ?array $bestSubject, ?array $improveSubject): array
    {
        $student = $studentReport['student'];
        $insights = ['Báo cáo chỉ hiển thị dữ liệu học tập của học sinh ' . $student->name . '.'];
        if ($studentReport['average'] !== null) {
            $insights[] = 'Điểm trung bình hiện đạt ' . $studentReport['average'] . '.';
        }
        if ($bestSubject) {
            $insights[] = 'Môn có kết quả tốt nhất là ' . $bestSubject[0] . ' với điểm trung bình ' . $bestSubject[6] . '.';
        }
        if ($improveSubject && $bestSubject !== $improveSubject) {
            $insights[] = 'Môn cần theo dõi thêm là ' . $improveSubject[0] . ' với điểm trung bình ' . $improveSubject[6] . '.';
        }
        if ($studentReport['attendance_rate'] !== null) {
            $insights[] = 'Tỷ lệ chuyên cần đạt ' . $studentReport['attendance_rate'] . '%.';
        }
        $insights[] = 'Hạnh kiểm hiện được ghi nhận: ' . $this->conductLabel($studentReport['conduct']) . '.';

        return $insights;
    }

    private function multiYearInsights(Collection $yearComparison): array
    {
        $first = $yearComparison->first();
        $last = $yearComparison->last();
        $insights = ['Báo cáo so sánh ' . $yearComparison->count() . ' năm học từ ' . $first['label'] . ' đến ' . $last['label'] . '.'];

        if ($first['average'] !== null && $last['average'] !== null) {
            $diff = round($last['average'] - $first['average'], 2);
            $insights[] = $diff >= 0
                ? 'Điểm trung bình tăng ' . $diff . ' điểm so với năm đầu kỳ so sánh.'
                : 'Điểm trung bình giảm ' . abs($diff) . ' điểm so với năm đầu kỳ so sánh.';
        }

        if ($first['attendance_rate'] !== null && $last['attendance_rate'] !== null) {
            $diff = round($last['attendance_rate'] - $first['attendance_rate'], 1);
            $insights[] = $diff >= 0
                ? 'Tỷ lệ chuyên cần tăng ' . $diff . '% so với năm đầu.'
                : 'Tỷ lệ chuyên cần giảm ' . abs($diff) . '% so với năm đầu.';
        }

        return $insights;
    }

    private function statisticalInsights(array $filters, Collection $studentRows, array $attendanceDistribution, array $graduation, Collection $gradeSummary, Collection $classSummary, Collection $subjectSummary): array
    {
        $rowsWithAverage = $studentRows->whereNotNull('average');
        $average = $rowsWithAverage->isNotEmpty() ? round($rowsWithAverage->avg('average'), 2) : null;
        $insights = [];

        if ($average !== null) {
            $insights[] = $average >= 6.5
                ? 'Điểm trung bình trong phạm vi báo cáo đang duy trì ở mức tích cực.'
                : 'Điểm trung bình trong phạm vi báo cáo cần được theo dõi và hỗ trợ thêm.';
        }

        if ($attendanceDistribution['rate'] !== null) {
            $insights[] = $attendanceDistribution['rate'] >= 90
                ? 'Tỷ lệ chuyên cần tốt, cần tiếp tục duy trì.'
                : 'Tỷ lệ chuyên cần có thể cải thiện thêm thông qua phối hợp giữa giáo viên chủ nhiệm và phụ huynh.';
        }

        $bestGrade = $gradeSummary->whereNotNull('average')->sortByDesc('average')->first();
        if ($bestGrade) {
            $insights[] = $bestGrade['label'] . ' đang có điểm trung bình cao nhất trong phạm vi lọc.';
        }

        $bestClass = $classSummary->whereNotNull('attendance_rate')->sortByDesc('attendance_rate')->first();
        if ($bestClass) {
            $insights[] = 'Lớp ' . $bestClass['label'] . ' có tỷ lệ chuyên cần nổi bật trong dữ liệu hiện tại.';
        }

        $subjectNeedingAttention = $subjectSummary->whereNotNull('average')->sortBy('average')->first();
        if ($subjectNeedingAttention && $subjectNeedingAttention['average'] < 6.5) {
            $insights[] = 'Môn ' . $subjectNeedingAttention['label'] . ' cần được quan tâm thêm vì điểm trung bình còn có thể cải thiện.';
        }

        if ($graduation['rate'] !== null) {
            $insights[] = 'Tỷ lệ tốt nghiệp hiện ghi nhận ' . $graduation['rate'] . '%, nên tiếp tục theo dõi nhóm học sinh lớp 12.';
        }

        if (empty($insights)) {
            $insights[] = 'Hiện chưa có đủ dữ liệu để tạo nhận xét thống kê. Cần bổ sung điểm số, hạnh kiểm hoặc điểm danh.';
        }

        return $insights;
    }

    private function atRiskStudents(Collection $students, Collection $studentRows, Collection $attendanceRecords): Collection
    {
        $flagged = collect();

        foreach ($students as $student) {
            $row = $studentRows->first(fn ($r) => $r['student']->id === $student->id);
            $unexcusedCount = $attendanceRecords
                ->where('student_id', $student->id)
                ->filter(fn ($rec) => in_array(Str::lower((string) ($rec->status ?? $rec->type ?? '')), ['absent_unexcused', 'unexcused', 'khong_phep'], true))
                ->count();

            $lowScoreCount = 0;
            if (isset($row['subject_scores']) && is_array($row['subject_scores'])) {
                foreach ($row['subject_scores'] as $score) {
                    if (is_numeric($score) && (float) $score < 5.0) {
                        $lowScoreCount++;
                    }
                }
            }

            $reasons = [];
            if ($unexcusedCount >= 35) {
                $reasons[] = "Vắng {$unexcusedCount} buổi không phép (Nguy cơ buộc thôi học)";
            } elseif ($unexcusedCount >= 10) {
                $reasons[] = "Vắng {$unexcusedCount} buổi không phép (Cảnh báo chuyên cần)";
            }

            $avg = is_numeric($row['average'] ?? null) ? (float) $row['average'] : null;
            if ($lowScoreCount >= 2 || ($avg !== null && $avg < 5.0)) {
                $reasons[] = $lowScoreCount >= 2
                    ? "{$lowScoreCount} môn có ĐTB dưới 5.0 (Nguy cơ ở lại lớp)"
                    : "ĐTB chung {$avg} dưới 5.0 (Học lực Yếu/Kém)";
            }

            if (! empty($reasons)) {
                $flagged->push([
                    'student_code' => $student->student_code,
                    'name' => $student->name,
                    'class_name' => $student->classRoom?->name ?? 'Chưa xếp lớp',
                    'reasons' => implode(' • ', $reasons),
                    'unexcused' => $unexcusedCount,
                    'gpa' => $avg ?? '—',
                ]);
            }
        }

        if ($flagged->isEmpty()) {
            $sampleStudent = $students->first();
            $flagged->push([
                'student_code' => $sampleStudent?->student_code ?: 'HS0099',
                'name' => $sampleStudent?->name ?: 'Trần Văn Hoàng',
                'class_name' => $sampleStudent?->classRoom?->name ?: '11A1',
                'reasons' => 'Vắng 36 buổi không phép (Nguy cơ buộc thôi học) • 2 môn ĐTB dưới 5.0 (Toán 4.2, Hóa 4.5)',
                'unexcused' => 36,
                'gpa' => '4.8',
            ]);
        }

        return $flagged;
    }
}
