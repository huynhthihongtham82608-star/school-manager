<?php

namespace App\Services;

use App\Models\AiAlert;
use App\Models\AiReport;
use App\Models\Conduct;
use App\Models\SchoolClass;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use Carbon\Carbon;

class AiAnalyzer
{
    public function analyzeClass(string $classId, string $semesterId): array
    {
        $class = SchoolClass::with('homeroomTeacher')->findOrFail($classId);
        $semester = Semester::with('schoolYear')->findOrFail($semesterId);

        $subjects = Subject::all()->keyBy('id');

        $students = Student::where('class_id', $class->id)->orderBy('student_code')->get();
        $previousSemester = Semester::where('school_year_id', $semester->school_year_id)
            ->where('order', '<', $semester->order)
            ->orderByDesc('order')
            ->first();

        $createdReports = 0;
        $createdAlerts = 0;

        foreach ($students as $student) {
            $avgNow = $this->calcAvg($student->id, $semester->id, $subjects);
            $avgPrev = $previousSemester ? $this->calcAvg($student->id, $previousSemester->id, $subjects) : null;

            $trend = $this->trend($avgPrev, $avgNow);
            $risk = $this->riskLevel($avgPrev, $avgNow);

            $weakSubjects = $this->weakSubjects($student->id, $semester->id, $subjects);
            $weakText = $weakSubjects ? ('Mon can cai thien: ' . implode(', ', $weakSubjects)) : 'Chua du du lieu de goi y mon can cai thien.';

            $summary = $this->buildSummary($student->name, $avgNow, $avgPrev, $trend, $risk, $weakText);

            $report = AiReport::updateOrCreate(
                ['student_id' => $student->id, 'semester_id' => $semester->id],
                [
                    'summary' => $summary,
                    'trend' => $trend,
                    'created_at' => Carbon::now(),
                ]
            );
            if ($report->wasRecentlyCreated) {
                $createdReports++;
            }

            if (in_array($risk, ['medium', 'high'], true)) {
                $alertMsg = $this->buildAlertMessage($student->name, $avgNow, $avgPrev, $risk, $weakSubjects);

                $alert = AiAlert::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'semester_id' => $semester->id,
                        'message' => $alertMsg,
                    ],
                    [
                        'teacher_id' => $class->homeroom_teacher_id,
                        'class_id' => $class->id,
                        'risk_level' => $risk,
                        'is_read' => 0,
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

    protected function calcAvg(string $studentId, string $semesterId, $subjects): ?float
    {
        $headers = ScoreHeader::where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->get();

        $sum = 0.0;
        $w = 0.0;
        foreach ($headers as $h) {
            if ($h->average === null) {
                continue;
            }
            $subject = $subjects[$h->subject_id] ?? null;
            $sw = ($subject && $subject->is_weighted) ? 2.0 : 1.0;
            $sum += ((float) $h->average) * $sw;
            $w += $sw;
        }

        if ($w <= 0) {
            return null;
        }
        return round($sum / $w, 2);
    }

    protected function trend(?float $prev, ?float $now): ?string
    {
        if ($prev === null || $now === null) {
            return 'stable';
        }
        $d = $now - $prev;
        if ($d >= 0.3) {
            return 'up';
        }
        if ($d <= -0.3) {
            return 'down';
        }
        return 'stable';
    }

    protected function riskLevel(?float $prev, ?float $now): string
    {
        if ($now === null) {
            return 'medium';
        }
        $drop = ($prev === null) ? 0.0 : ($now - $prev);

        if ($now < 5.0 || $drop <= -1.0) {
            return 'high';
        }
        if ($now < 6.5 || $drop <= -0.6) {
            return 'medium';
        }
        return 'low';
    }

    protected function weakSubjects(string $studentId, string $semesterId, $subjects): array
    {
        $headers = ScoreHeader::where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->get();

        $weak = [];
        foreach ($headers as $h) {
            if ($h->average !== null && (float) $h->average < 5.0) {
                $s = $subjects[$h->subject_id] ?? null;
                $weak[] = $s ? $s->name : $h->subject_id;
            }
        }
        return array_values(array_unique($weak));
    }

    protected function buildSummary(string $name, ?float $avgNow, ?float $avgPrev, ?string $trend, string $risk, string $weakText): string
    {
        $now = $avgNow === null ? 'N/A' : number_format($avgNow, 2);
        $prev = $avgPrev === null ? 'N/A' : number_format($avgPrev, 2);
        $t = $trend ?? 'stable';
        return "Hoc sinh: {$name}. TB hoc ky hien tai: {$now}. TB hoc ky truoc: {$prev}. Xu huong: {$t}. Muc rui ro: {$risk}. {$weakText}";
    }

    protected function buildAlertMessage(string $name, ?float $avgNow, ?float $avgPrev, string $risk, array $weakSubjects): string
    {
        $now = $avgNow === null ? 'N/A' : number_format($avgNow, 2);
        $prev = $avgPrev === null ? 'N/A' : number_format($avgPrev, 2);
        $weak = $weakSubjects ? ('Can luu y: ' . implode(', ', $weakSubjects)) : 'Can theo doi them du lieu diem.';
        return "[AI] {$name}: TB={$now} (truoc={$prev}), rui ro={$risk}. {$weak}";
    }
}

