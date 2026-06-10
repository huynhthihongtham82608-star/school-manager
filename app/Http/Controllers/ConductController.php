<?php

namespace App\Http\Controllers;

use App\Models\Conduct;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConductController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $classes = SchoolClass::with('schoolYear')->get();

        if ($user->isHomeroom()) {
            $teacherId = optional($user->teacher)->id;
            $classes = $classes->where('homeroom_teacher_id', $teacherId);
        }

        $semesters = Semester::with('schoolYear')->get();
        $selectedClass = null;
        $selectedSemester = null;
        $students = collect();
        $records = collect();

        if ($request->filled('class_id') && $request->filled('semester_id')) {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'semester_id' => 'required|exists:semesters,id',
            ]);

            $selectedClass = SchoolClass::find($request->input('class_id'));
            $selectedSemester = Semester::find($request->input('semester_id'));
            $this->authorizeHomeroom($selectedClass);

            $students = Student::where('class_id', $selectedClass->id)->orderBy('student_code')->get();
            $records = Conduct::where('class_id', $selectedClass->id)
                ->where('semester_id', $selectedSemester->id)
                ->get()
                ->keyBy('student_id');
        }

        return view('conduct.index', compact('classes', 'semesters', 'selectedClass', 'selectedSemester', 'students', 'records'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'semester_id' => 'required|exists:semesters,id',
            'conduct' => 'array',
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        $semester = Semester::findOrFail($data['semester_id']);
        $this->authorizeHomeroom($class);

        $students = Student::where('class_id', $class->id)->get();

        foreach ($students as $student) {
            $entry = $request->input("conduct.{$student->id}", []);
            if (!isset($entry['conduct_level'])) {
                continue;
            }

            Conduct::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'semester_id' => $semester->id,
                    'school_year_id' => $semester->school_year_id,
                    'class_id' => $class->id,
                ],
                [
                    'conduct_level' => $entry['conduct_level'],
                    'comment' => $entry['comment'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Đã lưu hạnh kiểm');
    }

    protected function authorizeHomeroom(SchoolClass $class): void
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isHomeroom() && optional($user->teacher)->id === $class->homeroom_teacher_id) {
            return;
        }

        abort(403, 'Chỉ GVCN hoặc admin được nhập hạnh kiểm');
    }
}
