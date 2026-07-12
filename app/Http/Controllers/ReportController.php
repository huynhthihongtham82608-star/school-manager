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
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
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
        $selectedSemesterId = $request->input('semester_id') ?: $this->selectedSemesterId($request);
        $scope = $request->input('scope', 'school_year');

        if (! in_array($scope, ['school_year', 'semester', 'grade', 'class', 'teacher', 'subject', 'student', 'three_years'], true)) {
            $scope = 'school_year';
        }

        $filters = [
            'scope' => $scope,
            'school_year_id' => $selectedYearId,
            'semester_id' => $scope === 'school_year' || $scope === 'three_years' ? null : $selectedSemesterId,
            'grade_level' => $request->input('grade_level'),
            'class_id' => $request->input('class_id'),
            'teacher_id' => $request->input('teacher_id'),
            'subject_id' => $request->input('subject_id'),
            'student_id' => $request->input('student_id'),
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
        $subjects = Subject::when($teacherSubjectIds !== null, fn ($query) => $query->whereIn('id', $teacherSubjectIds))
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
        $summaryCards = $this->summaryCards($students, $studentRows, $attendanceDistribution, $graduation);
        $gradeSummary = $this->groupSummary($students, $studentRows, $attendanceRecords, fn (Student $student) => 'Khối ' . ($student->classRoom?->grade_level ?: 'Chưa rõ'));
        $classSummary = $this->groupSummary($students, $studentRows, $attendanceRecords, fn (Student $student) => $student->classRoom?->name ?: 'Chưa có lớp');
        $subjectSummary = $this->subjectSummary($scoreHeaders);
        $teacherSummary = $this->teacherSummary($filters, $selectedYear?->id, $filters['semester_id']);
        $yearComparison = $this->yearComparison($schoolYears, $selectedYear, $filters);

        $reportTitle = $this->reportTitle($filters, $selectedYear, $selectedSemester);

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
            'reportTitle',
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
            'yearComparison'
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

        $assignments = TeachingAssignment::with(['teacher', 'classRoom', 'subject'])
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
                    'weekly_periods' => $items->sum('weekly_periods'),
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

        $years = $schoolYears
            ->where('start_date', '<=', $selectedYear->start_date)
            ->take(3)
            ->reverse()
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

            return [
                'label' => $year->name,
                'student_count' => $students->count(),
                'average' => $headers->isNotEmpty() ? round($headers->avg('average'), 2) : null,
                'attendance_rate' => $this->attendanceDistribution($attendance)['rate'],
                'graduation_rate' => $this->graduationStats($students)['rate'],
            ];
        });
    }

    private function reportTitle(array $filters, ?SchoolYear $year, ?Semester $semester): string
    {
        $title = match ($filters['scope']) {
            'semester' => 'Báo cáo học kỳ',
            'grade' => 'Báo cáo theo khối',
            'class' => 'Báo cáo theo lớp',
            'teacher' => 'Báo cáo theo giáo viên',
            'subject' => 'Báo cáo theo môn học',
            'student' => 'Báo cáo theo học sinh',
            'three_years' => 'Báo cáo so sánh 3 năm',
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

    private function exportCsv(array $report): StreamedResponse
    {
        $filename = 'bao-cao-tong-hop-' . now()->format('Ymd-His') . '.csv';

        return new StreamedResponse(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [$report['reportTitle']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Chỉ số', 'Giá trị']);
            foreach ($report['summaryCards'] as $card) {
                fputcsv($handle, [$card['label'], $card['value'] . $card['suffix']]);
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
}
