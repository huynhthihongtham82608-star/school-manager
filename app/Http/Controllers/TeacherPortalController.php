<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;

class TeacherPortalController extends Controller
{
    public function classes(Request $request)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);

        $assignedClassIds = $teacher->assignments()
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->pluck('class_id');

        $classes = SchoolClass::with(['schoolYear', 'semester', 'students'])
            ->whereIn('id', $assignedClassIds)
            ->orWhere('homeroom_teacher_id', $teacher->id)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        return view('teachers.classes', compact('classes'));
    }

    public function classStudents(Request $request, SchoolClass $class)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);

        $canView = $class->homeroom_teacher_id === $teacher->id
            || $teacher->assignments()
                ->where('class_id', $class->id)
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->exists();

        abort_unless($canView, 403);

        $class->load(['schoolYear', 'semester', 'students' => fn ($query) => $query->orderBy('student_code')]);

        return view('teachers.class-students', compact('class'));
    }
}
