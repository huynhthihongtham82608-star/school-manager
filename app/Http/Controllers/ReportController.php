<?php

namespace App\Http\Controllers;

use App\Models\Conduct;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function classSummary(Request $request)
    {
        $classes = SchoolClass::orderBy('name')->get();
        $semesters = Semester::with('schoolYear')->get();
        $selectedClass = null;
        $selectedSemester = null;
        $rows = collect();
        $stats = ['excellent' => 0, 'good' => 0, 'average' => 0, 'weak' => 0];

        if ($request->filled('class_id') && $request->filled('semester_id')) {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'semester_id' => 'required|exists:semesters,id',
            ]);

            $selectedClass = SchoolClass::find($request->input('class_id'));
            $selectedSemester = Semester::find($request->input('semester_id'));

            $students = Student::where('class_id', $selectedClass->id)->with('scoreHeaders')->get();
            $subjects = Subject::all()->keyBy('id');

            $rows = $students->map(function ($student) use ($selectedSemester, $subjects, &$stats) {
                $headers = $student->scoreHeaders
                    ->where('semester_id', $selectedSemester->id);

                $avg = $this->calculateAverage($headers, $subjects);
                $studyRank = $this->rankStudy($avg);
                $conduct = Conduct::where('student_id', $student->id)
                    ->where('semester_id', $selectedSemester->id)
                    ->first();

                $stats[$studyRank] = $stats[$studyRank] + 1;

                return [
                    'student' => $student,
                    'avg' => $avg,
                    'study_rank' => $studyRank,
                    'conduct' => $conduct?->conduct_level,
                ];
            });
        }

        return view('reports.class_summary', compact(
            'classes',
            'semesters',
            'selectedClass',
            'selectedSemester',
            'rows',
            'stats'
        ));
    }

    protected function calculateAverage($headers, $subjects): ?float
    {
        $sum = 0;
        $weight = 0;

        foreach ($headers as $header) {
            $subject = $subjects[$header->subject_id] ?? null;
            $subjectWeight = ($subject && $subject->is_weighted) ? 2 : 1;
            if ($header->average !== null) {
                $sum += $header->average * $subjectWeight;
                $weight += $subjectWeight;
            }
        }

        if ($weight === 0) {
            return null;
        }

        return round($sum / $weight, 2);
    }

    protected function rankStudy(?float $avg): string
    {
        if ($avg === null) {
            return 'weak';
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

        return 'weak';
    }
}
