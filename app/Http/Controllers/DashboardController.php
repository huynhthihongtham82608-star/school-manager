<?php

namespace App\Http\Controllers;

use App\Models\Conduct;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreHeader;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'students' => Student::count(),
            'teachers' => Teacher::count(),
            'classes' => SchoolClass::count(),
            'assignments' => TeachingAssignment::count(),
        ];

        $activeYear = SchoolYear::where('is_active', true)->first();

        $teacherAssignments = collect();
        $homeroomClass = null;
        $studentScores = collect();
        $conduct = null;

        if ($user->isTeacher()) {
            $teacher = $user->teacher;
            if ($teacher) {
                $teacherAssignments = $teacher->assignments()->with(['classRoom', 'subject', 'schoolYear'])->get();
                $homeroomClass = SchoolClass::where('homeroom_teacher_id', $teacher->id)->with('students')->first();
            }
        } elseif ($user->isStudent()) {
            $student = $user->student;
            if ($student) {
                $studentScores = ScoreHeader::where('student_id', $student->id)
                    ->with(['subject', 'semester'])
                    ->get();
                $conduct = Conduct::where('student_id', $student->id)->with(['classRoom', 'semester'])->get();
            }
        }

        return view('dashboard', compact(
            'user',
            'stats',
            'activeYear',
            'teacherAssignments',
            'homeroomClass',
            'studentScores',
            'conduct'
        ));
    }
}
