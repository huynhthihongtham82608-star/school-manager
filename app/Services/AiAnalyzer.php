<?php

namespace App\Services;

use App\Models\AiAlert;
use App\Models\AiReport;
use App\Models\AttendanceRecord;
use App\Models\Conduct;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AiAnalyzer
{
    public function analyzeClass(string $classId, string $semesterId): array
    {
        $class = SchoolClass::with('homeroomTeacher')->findOrFail($classId);
        $semester = Semester::with('schoolYear')->findOrFail($semesterId);
        $students = Student::where('class_id', $class->id)->orderBy('student_code')->get();
        $createdReports = 0;
        $createdAlerts = 0;

        foreach ($students as $student) {
            $analysis = $this->analyzeStudent($student->id, $semester->id);
            $summary = $this->summaryText($analysis);

            $report = AiReport::updateOrCreate(
                ['student_id' => $student->id, 'semester_id' => $semester->id],
                [
                    'summary' => $summary,
                    'trend' => $analysis['trend'],
                    'created_at' => Carbon::now(),
                ]
            );

            if ($report->wasRecentlyCreated) {
                $createdReports++;
            }

            if (in_array($analysis['attention_level'], ['medium', 'high'], true)) {
                $alert = AiAlert::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'semester_id' => $semester->id,
                        'message' => $this->buildAlertMessage($analysis),
                    ],
                    [
                        'teacher_id' => $class->homeroom_teacher_id,
                        'class_id' => $class->id,
                        'risk_level' => $analysis['attention_level'],
                        'is_read' => false,
                        'created_at' => Carbon::now(),
                    ]
                );

                if ($alert->wasRecentlyCreated) {
                    $createdAlerts++;
                }
            }
        }

        return [
            'reports' => $createdReports,
            'alerts' => $createdAlerts,
            'students' => $students->count(),
            'class' => $class,
            'semester' => $semester,
        ];
    }

    public function analyzeStudent(string $studentId, string $semesterId): array
    {
        $student = Student::with('classRoom')->findOrFail($studentId);
        $semester = Semester::with('schoolYear')->findOrFail($semesterId);
        $subjects = Subject::orderBy('name')->get()->keyBy('id');
        $previousSemester = $this->previousSemester($semester);
        $subjectRows = $this->studentSubjectRows($student->id, $semester->id, $subjects);
        $avgNow = $this->weightedAverage($subjectRows);
        $avgPrev = $previousSemester ? $this->calcAvg($student->id, $previousSemester->id, $subjects) : null;
        $trend = $this->trend($avgPrev, $avgNow);
        $attendance = $this->attendanceStats($student->id, $student->class_id, $semester->id);
        $conduct = Conduct::where('student_id', $student->id)->where('semester_id', $semester->id)->first();
        $attentionLevel = $this->attentionLevel($avgPrev, $avgNow, $attendance['rate']);
        $hasData = $subjectRows->contains(fn ($row) => $row['average'] !== null)
            || ($attendance['total'] ?? 0) > 0
            || $conduct !== null;

        return [
            'type' => 'student',
            'student' => $student,
            'semester' => $semester,
            'class' => $student->classRoom,
            'has_data' => $hasData,
            'no_data_message' => $hasData ? null : $this->noDataMessage(),
            'overview' => $this->studentOverview($avgNow, $trend, $attendance, $conduct?->conduct_level, $hasData),
            'avg_now' => $avgNow,
            'avg_prev' => $avgPrev,
            'trend' => $trend,
            'trend_label' => $hasData ? $this->trendLabel($trend) : 'Chưa đủ dữ liệu',
            'attendance' => $attendance,
            'conduct' => $conduct?->conduct_level,
            'conduct_label' => $this->conductLabel($conduct?->conduct_level),
            'subjects' => $subjectRows,
            'attention_level' => $attentionLevel,
            'attention_label' => $hasData ? $this->attentionLabel($attentionLevel) : 'Chưa đủ dữ liệu',
            'strengths' => $hasData ? $this->studentStrengths($subjectRows, $attendance, $trend, $conduct?->conduct_level) : ['Hiện chưa có đủ dữ liệu để AI nhận xét điểm mạnh của học sinh.'],
            'improvements' => $hasData ? $this->studentImprovements($subjectRows, $avgNow, $avgPrev, $attendance, $conduct?->conduct_level) : ['Cần bổ sung điểm số hoặc dữ liệu học tập để AI đưa ra nhận xét.'],
            'recommendations' => $hasData ? $this->studentRecommendations($subjectRows, $attendance, $trend) : ['Cập nhật điểm số, điểm danh hoặc hạnh kiểm trước khi thực hiện phân tích.'],
            'note' => $this->note(),
        ];
    }

    public function analyzeClassSnapshot(string $classId, string $semesterId): array
    {
        $class = SchoolClass::with(['homeroomTeacher', 'students'])->findOrFail($classId);
        $semester = Semester::with('schoolYear')->findOrFail($semesterId);
        $subjects = Subject::orderBy('name')->get()->keyBy('id');
        $studentIds = $class->students->pluck('id')->values();
        $headers = ScoreHeader::query()
            ->whereIn('student_id', $studentIds)
            ->where('semester_id', $semester->id)
            ->get();
        $previousSemester = $this->previousSemester($semester);
        $previousHeaders = $previousSemester
            ? ScoreHeader::query()->whereIn('student_id', $studentIds)->where('semester_id', $previousSemester->id)->get()
            : collect();
        $studentAverages = $headers
            ->groupBy('student_id')
            ->map(fn (Collection $rows) => $this->calculateAverageFromHeaders($rows, $subjects));
        $previousAverages = $previousHeaders
            ->groupBy('student_id')
            ->map(fn (Collection $rows) => $this->calculateAverageFromHeaders($rows, $subjects));
        $attendanceRows = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->where('class_id', $class->id)
            ->where('semester_id', $semester->id)
            ->get();
        $attendanceRate = $this->attendanceRateFromRecords($attendanceRows);
        $attendanceRatesByStudent = $attendanceRows->groupBy('student_id')
            ->map(fn (Collection $rows) => $this->attendanceRateFromRecords($rows));
        $conductRows = Conduct::query()
            ->whereIn('student_id', $studentIds)
            ->where('semester_id', $semester->id)
            ->get();

        $validAverages = $studentAverages->filter(fn ($avg) => $avg !== null);
        $classAverage = $validAverages->isEmpty() ? null : round($validAverages->avg(), 2);
        $subjectAverages = $this->subjectAveragesFromHeaders($headers, $subjects);
        $attentionCount = $studentIds->filter(function ($studentId) use ($studentAverages, $previousAverages, $attendanceRatesByStudent) {
            $level = $this->attentionLevel(
                $previousAverages->get($studentId),
                $studentAverages->get($studentId),
                $attendanceRatesByStudent->get($studentId)
            );

            return in_array($level, ['medium', 'high'], true);
        })->count();
        $improvedCount = $studentIds->filter(fn ($studentId) => $this->trend($previousAverages->get($studentId), $studentAverages->get($studentId)) === 'up')->count();
        $conductDistribution = $conductRows->pluck('conduct_level')
            ->map(fn ($level) => $this->conductLabel($level))
            ->filter()
            ->countBy();
        $hasData = $headers->isNotEmpty() || $attendanceRows->isNotEmpty() || $conductRows->isNotEmpty();

        return [
            'type' => 'class',
            'class' => $class,
            'semester' => $semester,
            'has_data' => $hasData,
            'no_data_message' => $hasData ? null : $this->noDataMessage(),
            'overview' => $this->classOverview($class->students->count(), $classAverage, $attendanceRate, $improvedCount, $hasData),
            'student_count' => $class->students->count(),
            'class_average' => $classAverage,
            'attendance_rate' => $attendanceRate === null ? null : round($attendanceRate, 1),
            'improved_count' => $improvedCount,
            'attention_count' => $attentionCount,
            'subject_averages' => $subjectAverages,
            'strong_subjects' => $subjectAverages->where('average', '>=', 7.5)->take(3)->values(),
            'support_subjects' => $subjectAverages->where('average', '<', 6.5)->take(3)->values(),
            'conduct_distribution' => $conductDistribution,
            'strengths' => $hasData ? $this->classStrengths($classAverage, $attendanceRate, $improvedCount, $subjectAverages) : ['Hiện chưa có đủ dữ liệu để AI nhận xét điểm mạnh của lớp.'],
            'improvements' => $hasData ? $this->classImprovements($attentionCount, $subjectAverages, $attendanceRate) : ['Cần bổ sung điểm số hoặc dữ liệu học tập để AI đưa ra nhận xét.'],
            'recommendations' => $hasData ? $this->classRecommendations($attentionCount, $subjectAverages, $attendanceRate) : ['Cập nhật điểm số, điểm danh hoặc hạnh kiểm trước khi thực hiện phân tích lớp.'],
            'note' => $this->note(),
        ];
    }

    public function analyzeSchoolSnapshot(?string $schoolYearId = null, ?string $semesterId = null): array
    {
        $schoolYear = $schoolYearId ? SchoolYear::find($schoolYearId) : SchoolYear::where('is_active', true)->first();
        $semester = $semesterId ? Semester::with('schoolYear')->find($semesterId) : null;
        $classes = SchoolClass::query()
            ->with('students')
            ->when($schoolYear, fn ($query) => $query->where('school_year_id', $schoolYear->id))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
        $subjects = Subject::orderBy('name')->get()->keyBy('id');
        $studentClassMap = $classes->flatMap(fn (SchoolClass $class) => $class->students->mapWithKeys(fn (Student $student) => [$student->id => $class->id]));
        $studentIds = $studentClassMap->keys();
        $scoreHeaders = ScoreHeader::query()
            ->whereIn('student_id', $studentIds)
            ->when($semester, fn ($query) => $query->where('semester_id', $semester->id))
            ->get();
        $headersByClass = $scoreHeaders
            ->groupBy(fn (ScoreHeader $header) => $studentClassMap->get($header->student_id));

        $classRows = $classes->map(function (SchoolClass $class) use ($subjects, $headersByClass) {
            $headers = $headersByClass->get($class->id, collect());
            $average = $this->calculateAverageFromHeaders($headers, $subjects);

            return [
                'class' => $class->name,
                'grade' => $class->grade_level,
                'students' => $class->students->count(),
                'average' => $average,
            ];
        });

        $gradeRows = $classRows
            ->groupBy('grade')
            ->map(fn (Collection $rows, $grade) => [
                'grade' => 'Khối ' . $grade,
                'students' => $rows->sum('students'),
                'average' => $rows->pluck('average')->filter(fn ($avg) => $avg !== null)->avg(),
            ])
            ->values()
            ->map(fn (array $row) => array_merge($row, ['average' => $row['average'] === null ? null : round($row['average'], 2)]));

        $subjectRows = $this->subjectAveragesFromHeaders($scoreHeaders, $subjects);
        $hasData = $scoreHeaders->isNotEmpty();

        return [
            'type' => 'school',
            'school_year' => $schoolYear,
            'semester' => $semester,
            'has_data' => $hasData,
            'no_data_message' => $hasData ? null : $this->noDataMessage(),
            'overview' => $this->schoolOverview($classes->count(), $classRows, $gradeRows, $subjectRows, $hasData),
            'class_rows' => $classRows,
            'grade_rows' => $gradeRows,
            'subject_rows' => $subjectRows,
            'strengths' => $hasData ? [
                'Dữ liệu tổng quan đã được tổng hợp theo khối, lớp và môn học.',
                $classes->count() > 0 ? 'Hệ thống đang theo dõi ' . $classes->count() . ' lớp trong phạm vi đã chọn.' : 'Có thể bắt đầu phân tích khi có dữ liệu lớp học.',
            ] : ['Hiện chưa có đủ dữ liệu để AI nhận xét điểm mạnh ở phạm vi toàn trường.'],
            'improvements' => $hasData
                ? ($subjectRows->where('average', '<', 6.5)->take(3)->map(fn ($row) => 'Môn ' . $row['subject'] . ' nên được theo dõi thêm để hỗ trợ học sinh phù hợp.')->values()->all()
                    ?: ['Chưa có nhóm môn nào cần theo dõi thêm từ dữ liệu hiện tại.'])
                : ['Cần bổ sung điểm số hoặc dữ liệu học tập để AI đưa ra nhận xét.'],
            'recommendations' => $hasData ? [
                'Rà soát các lớp có điểm trung bình còn thấp hơn mặt bằng chung để xây dựng kế hoạch hỗ trợ.',
                'Kết hợp dữ liệu điểm, chuyên cần và hạnh kiểm khi đánh giá tình hình học tập.',
                'Duy trì việc cập nhật dữ liệu thường xuyên để nhận xét AI chính xác hơn.',
            ] : ['Cập nhật dữ liệu điểm, điểm danh và hạnh kiểm trước khi thực hiện phân tích toàn trường.'],
            'note' => $this->note(),
        ];
    }

    protected function studentSubjectRows(string $studentId, string $semesterId, Collection $subjects): Collection
    {
        return ScoreHeader::where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->get()
            ->map(function (ScoreHeader $header) use ($subjects) {
                $subject = $subjects[$header->subject_id] ?? null;

                return [
                    'subject' => $subject?->name ?? 'Môn học',
                    'average' => $header->average === null ? null : round((float) $header->average, 2),
                    'weight' => $subject ? (float) $subject->calculationWeight() : 1.0,
                ];
            })
            ->sortBy('subject')
            ->values();
    }

    protected function weightedAverage(Collection $rows): ?float
    {
        $sum = 0.0;
        $weight = 0.0;

        foreach ($rows as $row) {
            if ($row['average'] === null) {
                continue;
            }

            $sum += $row['average'] * $row['weight'];
            $weight += $row['weight'];
        }

        return $weight > 0 ? round($sum / $weight, 2) : null;
    }

    protected function calcAvg(string $studentId, string $semesterId, Collection $subjects): ?float
    {
        return $this->weightedAverage($this->studentSubjectRows($studentId, $semesterId, $subjects));
    }

    protected function calculateAverageFromHeaders(Collection $headers, Collection $subjects): ?float
    {
        $rows = $headers->map(function (ScoreHeader $header) use ($subjects) {
            $subject = $subjects[$header->subject_id] ?? null;

            return [
                'average' => $header->average === null ? null : (float) $header->average,
                'weight' => $subject ? (float) $subject->calculationWeight() : 1.0,
            ];
        });

        return $this->weightedAverage($rows);
    }

    protected function previousSemester(Semester $semester): ?Semester
    {
        if ($semester->termIndex() !== 2) {
            return null;
        }

        return Semester::where('school_year_id', $semester->school_year_id)
            ->get()
            ->first(fn (Semester $item) => $item->termIndex() === 1);
    }

    protected function trend(?float $prev, ?float $now): string
    {
        if ($prev === null || $now === null) {
            return 'stable';
        }

        $diff = $now - $prev;

        if ($diff >= 0.3) {
            return 'up';
        }

        if ($diff <= -0.3) {
            return 'down';
        }

        return 'stable';
    }

    protected function trendLabel(string $trend): string
    {
        return [
            'up' => 'Có tiến bộ',
            'down' => 'Có xu hướng giảm',
            'stable' => 'Ổn định',
        ][$trend] ?? 'Ổn định';
    }

    protected function attentionLevel(?float $prev, ?float $now, ?float $attendanceRate): string
    {
        $drop = ($prev === null || $now === null) ? 0.0 : ($now - $prev);

        if (($now !== null && $now < 5.0) || $drop <= -1.0 || ($attendanceRate !== null && $attendanceRate < 75)) {
            return 'high';
        }

        if (($now !== null && $now < 6.5) || $drop <= -0.6 || ($attendanceRate !== null && $attendanceRate < 85)) {
            return 'medium';
        }

        return 'low';
    }

    protected function attentionLabel(string $level): string
    {
        return [
            'high' => 'Cần hỗ trợ thêm',
            'medium' => 'Nên theo dõi',
            'low' => 'Ổn định',
        ][$level] ?? 'Ổn định';
    }

    protected function attendanceStats(string $studentId, ?string $classId, string $semesterId): array
    {
        if (! $classId) {
            return [
                'total' => 0,
                'present' => 0,
                'late' => 0,
                'excused' => 0,
                'absent' => 0,
                'rate' => null,
            ];
        }

        $records = AttendanceRecord::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('semester_id', $semesterId)
            ->get();
        $total = $records->count();
        $positive = $records->whereIn('status', ['present', 'late', 'excused'])->count();

        return [
            'total' => $total,
            'present' => $records->where('status', 'present')->count(),
            'late' => $records->where('status', 'late')->count(),
            'excused' => $records->where('status', 'excused')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'rate' => $total > 0 ? round($positive / $total * 100, 1) : null,
        ];
    }

    protected function attendanceRateFromRecords(Collection $records): ?float
    {
        $total = $records->count();

        if ($total === 0) {
            return null;
        }

        $positive = $records->whereIn('status', ['present', 'late', 'excused'])->count();

        return round($positive / $total * 100, 1);
    }

    protected function noDataMessage(): string
    {
        return 'Hiện chưa có đủ dữ liệu để AI phân tích. Cần bổ sung điểm số hoặc dữ liệu học tập để AI đưa ra nhận xét.';
    }

    protected function displayNumber(?float $value): ?string
    {
        return $value === null ? null : number_format($value, 2, ',', '.');
    }

    protected function displayPercent(?float $value): ?string
    {
        return $value === null ? null : number_format($value, 1, ',', '.') . '%';
    }

    protected function studentOverview(?float $avgNow, string $trend, array $attendance, ?string $conduct, bool $hasData): array
    {
        if (! $hasData) {
            return [$this->noDataMessage()];
        }

        return array_values(array_filter([
            $avgNow === null ? null : 'Điểm trung bình hiện tại: ' . $this->displayNumber($avgNow) . '.',
            'Xu hướng học tập: ' . $this->trendLabel($trend) . '.',
            ($attendance['rate'] ?? null) === null ? null : 'Tỷ lệ chuyên cần: ' . $this->displayPercent($attendance['rate']) . '.',
            $conduct ? 'Hạnh kiểm: ' . $this->conductLabel($conduct) . '.' : null,
        ]));
    }

    protected function classOverview(int $studentCount, ?float $classAverage, ?float $attendanceRate, int $improvedCount, bool $hasData): array
    {
        if (! $hasData) {
            return [$this->noDataMessage()];
        }

        return array_values(array_filter([
            'Sĩ số lớp: ' . $studentCount . ' học sinh.',
            $classAverage === null ? null : 'Điểm trung bình lớp: ' . $this->displayNumber($classAverage) . '.',
            $attendanceRate === null ? null : 'Tỷ lệ chuyên cần: ' . $this->displayPercent($attendanceRate) . '.',
            $improvedCount > 0 ? 'Có ' . $improvedCount . ' học sinh ghi nhận xu hướng tiến bộ.' : null,
        ]));
    }

    protected function schoolOverview(int $classCount, Collection $classRows, Collection $gradeRows, Collection $subjectRows, bool $hasData): array
    {
        if (! $hasData) {
            return [$this->noDataMessage()];
        }

        $averageRows = $classRows->pluck('average')->filter(fn ($avg) => $avg !== null);
        $overallAverage = $averageRows->isEmpty() ? null : round($averageRows->avg(), 2);

        return array_values(array_filter([
            'Số lớp được tổng hợp: ' . $classCount . '.',
            'Số khối có dữ liệu: ' . $gradeRows->count() . '.',
            'Số môn học có dữ liệu điểm: ' . $subjectRows->filter(fn ($row) => $row['average'] !== null)->count() . '.',
            $overallAverage === null ? null : 'Điểm trung bình chung: ' . $this->displayNumber($overallAverage) . '.',
        ]));
    }

    protected function conductLabel(?string $conduct): ?string
    {
        return [
            'excellent' => 'Tốt',
            'good' => 'Khá',
            'average' => 'Trung bình',
            'weak' => 'Cần rèn luyện thêm',
        ][$conduct] ?? null;
    }

    protected function studentStrengths(Collection $subjects, array $attendance, string $trend, ?string $conduct): array
    {
        $items = [];
        $strongSubjects = $subjects->filter(fn ($row) => $row['average'] !== null && $row['average'] >= 7.5)->take(3);

        foreach ($strongSubjects as $row) {
            $items[] = 'Có kết quả tích cực ở môn ' . $row['subject'] . '.';
        }

        if (($attendance['rate'] ?? null) !== null && $attendance['rate'] >= 90) {
            $items[] = 'Chuyên cần tốt, duy trì việc tham gia học tập đều đặn.';
        }

        if ($trend === 'up') {
            $items[] = 'Kết quả học tập có dấu hiệu tiến bộ so với giai đoạn trước.';
        }

        if (in_array($conduct, ['excellent', 'good'], true)) {
            $items[] = 'Nề nếp và hạnh kiểm được ghi nhận tích cực.';
        }

        return $items ?: ['Học sinh đã có dữ liệu học tập để nhà trường tiếp tục theo dõi và hỗ trợ phù hợp.'];
    }

    protected function studentImprovements(Collection $subjects, ?float $avgNow, ?float $avgPrev, array $attendance, ?string $conduct): array
    {
        $items = [];
        $supportSubjects = $subjects->filter(fn ($row) => $row['average'] !== null && $row['average'] < 6.5)->take(3);

        foreach ($supportSubjects as $row) {
            $items[] = 'Môn ' . $row['subject'] . ' nên được luyện tập thêm để cải thiện kết quả.';
        }

        if ($avgPrev !== null && $avgNow !== null && $avgNow < $avgPrev - 0.3) {
            $items[] = 'Điểm trung bình có xu hướng giảm nhẹ, nên theo dõi thêm trong thời gian tới.';
        }

        if (($attendance['rate'] ?? null) !== null && $attendance['rate'] < 85) {
            $items[] = 'Chuyên cần cần được quan tâm thêm để việc học ổn định hơn.';
        }

        if ($conduct === 'weak') {
            $items[] = 'Hạnh kiểm cần được rèn luyện thêm với sự đồng hành của giáo viên chủ nhiệm.';
        }

        return $items ?: ['Chưa ghi nhận nội dung cần cải thiện rõ rệt từ dữ liệu hiện tại.'];
    }

    protected function studentRecommendations(Collection $subjects, array $attendance, string $trend): array
    {
        $items = [];
        $supportSubjects = $subjects->filter(fn ($row) => $row['average'] !== null && $row['average'] < 6.5)->take(2);

        foreach ($supportSubjects as $row) {
            $items[] = 'Sắp xếp ôn tập hoặc phụ đạo thêm môn ' . $row['subject'] . '.';
        }

        if (($attendance['rate'] ?? null) !== null && $attendance['rate'] < 85) {
            $items[] = 'Trao đổi nhẹ nhàng với học sinh và phụ huynh để hỗ trợ duy trì chuyên cần.';
        }

        if ($trend === 'down') {
            $items[] = 'Giáo viên nên theo dõi thêm trong các lần kiểm tra tiếp theo.';
        }

        $items[] = 'Động viên học sinh duy trì các điểm mạnh hiện có.';

        return array_values(array_unique($items));
    }

    protected function classStrengths(?float $classAverage, ?float $attendanceRate, int $improvedCount, Collection $subjectAverages): array
    {
        $items = [];

        if ($classAverage !== null) {
            $items[] = 'Điểm trung bình lớp hiện đạt ' . number_format($classAverage, 2) . ', là cơ sở để giáo viên theo dõi tiến độ học tập.';
        }

        if ($attendanceRate !== null && $attendanceRate >= 90) {
            $items[] = 'Tỷ lệ chuyên cần của lớp đang ở mức tích cực.';
        }

        if ($improvedCount > 0) {
            $items[] = 'Có ' . $improvedCount . ' học sinh ghi nhận xu hướng tiến bộ.';
        }

        $strongSubjects = $subjectAverages->where('average', '>=', 7.5)->take(2);
        foreach ($strongSubjects as $row) {
            $items[] = 'Môn ' . $row['subject'] . ' là điểm sáng của lớp.';
        }

        return $items ?: ['Lớp đã có dữ liệu nền để giáo viên tiếp tục theo dõi và hỗ trợ.'];
    }

    protected function classImprovements(int $attentionCount, Collection $subjectAverages, ?float $attendanceRate): array
    {
        $items = [];

        if ($attentionCount > 0) {
            $items[] = 'Có ' . $attentionCount . ' học sinh nên được quan tâm và hỗ trợ thêm.';
        }

        foreach ($subjectAverages->where('average', '<', 6.5)->take(3) as $row) {
            $items[] = 'Môn ' . $row['subject'] . ' nên được bổ sung hoạt động ôn tập.';
        }

        if ($attendanceRate !== null && $attendanceRate < 85) {
            $items[] = 'Tỷ lệ chuyên cần của lớp nên được theo dõi thêm.';
        }

        return $items ?: ['Chưa ghi nhận vấn đề nổi bật cần cải thiện từ dữ liệu hiện tại.'];
    }

    protected function classRecommendations(int $attentionCount, Collection $subjectAverages, ?float $attendanceRate): array
    {
        $items = [
            'Giáo viên nên kết hợp dữ liệu điểm, chuyên cần và hạnh kiểm khi theo dõi lớp.',
        ];

        if ($attentionCount > 0) {
            $items[] = 'Lập danh sách học sinh cần hỗ trợ để trao đổi thêm với giáo viên bộ môn.';
        }

        if ($subjectAverages->where('average', '<', 6.5)->isNotEmpty()) {
            $items[] = 'Tổ chức ôn tập theo nhóm môn học cần cải thiện.';
        }

        if ($attendanceRate !== null && $attendanceRate < 85) {
            $items[] = 'Trao đổi với lớp để khuyến khích duy trì chuyên cần.';
        }

        return $items;
    }

    protected function subjectAveragesForClass(string $classId, string $semesterId, Collection $subjects): Collection
    {
        $studentIds = Student::where('class_id', $classId)->pluck('id');

        $headers = ScoreHeader::whereIn('student_id', $studentIds)
            ->where('semester_id', $semesterId)
            ->get();

        return $this->subjectAveragesFromHeaders($headers, $subjects);
    }

    protected function subjectAveragesFromHeaders(Collection $headers, Collection $subjects): Collection
    {
        return $headers
            ->groupBy('subject_id')
            ->map(function (Collection $headers, string $subjectId) use ($subjects) {
                $values = $headers->pluck('average')->filter(fn ($avg) => $avg !== null);

                return [
                    'subject' => $subjects[$subjectId]?->name ?? 'Môn học',
                    'average' => $values->isEmpty() ? null : round($values->avg(), 2),
                ];
            })
            ->sortByDesc(fn ($row) => $row['average'] ?? -1)
            ->values();
    }

    protected function subjectAveragesForSchool(Collection $classIds, ?string $semesterId, Collection $subjects): Collection
    {
        $studentIds = Student::whereIn('class_id', $classIds)->pluck('id');

        return ScoreHeader::whereIn('student_id', $studentIds)
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->get()
            ->groupBy('subject_id')
            ->map(function (Collection $headers, string $subjectId) use ($subjects) {
                $values = $headers->pluck('average')->filter(fn ($avg) => $avg !== null);

                return [
                    'subject' => $subjects[$subjectId]?->name ?? 'Môn học',
                    'average' => $values->isEmpty() ? null : round($values->avg(), 2),
                ];
            })
            ->sortByDesc(fn ($row) => $row['average'] ?? -1)
            ->values();
    }

    protected function buildAlertMessage(array $analysis): string
    {
        $name = $analysis['student']->name;
        $label = $analysis['attention_label'];
        $focus = $analysis['improvements'][0] ?? 'Nên theo dõi thêm trong thời gian tới.';

        return "AI nhận thấy {$name} {$label}. {$focus} Khuyến nghị giáo viên hỗ trợ theo hướng động viên và phù hợp với dữ liệu hiện tại.";
    }

    protected function summaryText(array $analysis): string
    {
        return "🌟 Điểm mạnh\n"
            . $this->bulletText($analysis['strengths'])
            . "\n📘 Điểm cần cải thiện\n"
            . $this->bulletText($analysis['improvements'])
            . "\n💡 Khuyến nghị\n"
            . $this->bulletText($analysis['recommendations']);
    }

    protected function bulletText(array $items): string
    {
        return collect($items)->map(fn ($item) => '- ' . $item)->implode("\n");
    }

    protected function note(): string
    {
        return 'Lưu ý: Các nhận xét và khuyến nghị được tạo dựa trên dữ liệu hiện có trong hệ thống, chỉ mang tính chất tham khảo và hỗ trợ giáo viên trong quá trình theo dõi học sinh.';
    }
}
