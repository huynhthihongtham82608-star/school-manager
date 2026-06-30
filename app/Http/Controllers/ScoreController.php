<?php

namespace App\Http\Controllers;

use App\Models\GradeWindow;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreDetail;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScoreController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedYearId = $this->selectedSchoolYearId($request);
        $years = SchoolYear::all();
        $semesters = Semester::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->get();
        $subjects = Subject::all();
        $classes = SchoolClass::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->get();

        $assignments = collect();
        if ($user->isTeacher()) {
            $teacher = $user->teacher;
            if ($teacher) {
                $assignments = $teacher->assignments()
                    ->with(['classRoom', 'subject', 'schoolYear'])
                    ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                    ->get();
            }
        } else {
            $assignments = [];
        }

        return view('scores.index', compact('years', 'semesters', 'subjects', 'classes', 'assignments', 'selectedYearId'));
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

        $this->authorizeAccess($class, $subject->id);
        $this->ensureWindowOpen($class->id, $subject->id, $semester);

        $students = Student::where('class_id', $class->id)->orderBy('student_code')->get();

        $headers = ScoreHeader::where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->with('details')
            ->get()
            ->keyBy('student_id');

        return view('scores.entry', compact('class', 'subject', 'semester', 'students', 'headers'));
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

        $this->authorizeAccess($class, $subject->id);
        $this->ensureWindowOpen($class->id, $subject->id, $semester);

        $students = Student::where('class_id', $class->id)->get();

        foreach ($students as $student) {
            $inputs = $request->input("scores.{$student->id}", []);
            $header = ScoreHeader::firstOrCreate([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'semester_id' => $semester->id,
                'school_year_id' => $semester->school_year_id,
            ]);

            $header->details()->delete();

            $allDetails = [
                ['key' => 'oral', 'type' => 'oral', 'weight' => 1],
                ['key' => 'quiz', 'type' => 'quiz', 'weight' => 1],
                ['key' => 'test', 'type' => 'test', 'weight' => 2],
                ['key' => 'midterm', 'type' => 'midterm', 'weight' => 2],
                ['key' => 'final', 'type' => 'final', 'weight' => 3],
            ];

            $weightedSum = 0;
            $totalWeight = 0;

            foreach ($allDetails as $detail) {
                $values = $this->parseScores($inputs[$detail['key']] ?? '');
                foreach ($values as $value) {
                    $weightedSum += $value * $detail['weight'];
                    $totalWeight += $detail['weight'];

                    ScoreDetail::create([
                        'score_header_id' => $header->id,
                        'type' => $detail['type'],
                        'value' => $value,
                        'weight_group' => $detail['weight'],
                    ]);
                }
            }

            $header->average = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : null;
            $header->save();
        }

        return back()->with('success', 'Đã lưu điểm cho lớp');
    }

    protected function parseScores(string $input): array
    {
        return collect(explode(',', $input))
            ->map(fn($v) => trim($v))
            ->filter(fn($v) => $v !== '' && is_numeric($v))
            ->map(fn($v) => (float) $v)
            ->filter(fn($v) => $v >= 0 && $v <= 10)
            ->values()
            ->all();
    }

    protected function authorizeAccess(SchoolClass $class, string $subjectId): void
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTeacher()) {
            $teacherId = optional($user->teacher)->id;
            $isAssigned = $teacherId && $class->assignments()
                ->where('teacher_id', $teacherId)
                ->where('subject_id', $subjectId)
                ->exists();
            $isHomeroom = $class->homeroom_teacher_id === $teacherId;

            if ($isAssigned || $isHomeroom) {
                return;
            }
        }

        abort(403, 'Không có quyền nhập điểm cho lớp này');
    }

    protected function ensureWindowOpen(string $classId, string $subjectId, Semester $semester): void
    {
        $window = GradeWindow::where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semester->id)
            ->first();

        $open = $window ? $window->is_open : $semester->is_score_input_open;

        if (!$open) {
            abort(403, 'Kỳ nhập điểm đang bị khóa');
        }
    }
}
