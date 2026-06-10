<?php

namespace App\Http\Controllers;

use App\Models\AiAlert;
use App\Models\AiReport;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Services\AiAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiController extends Controller
{
    public function alerts()
    {
        $user = Auth::user();

        $q = AiAlert::with(['student', 'classRoom', 'semester'])->orderByDesc('created_at');

        if ($user->isAdmin()) {
            // all
        } elseif ($user->isTeacher() && $user->teacher) {
            $q->where(function ($qq) use ($user) {
                $qq->where('teacher_id', $user->teacher->id)
                    ->orWhereIn('class_id', $user->teacher->homeroomClasses()->pluck('id'));
            });
        } elseif ($user->isStudent() && $user->student) {
            $q->where('student_id', $user->student->id);
        } elseif ($user->isParent() && $user->parentProfile) {
            $studentIds = $user->parentProfile->students()->pluck('students.id');
            $q->whereIn('student_id', $studentIds);
        } else {
            $q->whereRaw('1=0');
        }

        $alerts = $q->get();
        return view('ai.alerts', compact('alerts'));
    }

    public function reports(Request $request)
    {
        $user = Auth::user();

        $q = AiReport::with(['student', 'semester'])->orderByDesc('created_at');

        if ($user->isAdmin()) {
            // all
        } elseif ($user->isStudent() && $user->student) {
            $q->where('student_id', $user->student->id);
        } elseif ($user->isParent() && $user->parentProfile) {
            $studentIds = $user->parentProfile->students()->pluck('students.id');
            $q->whereIn('student_id', $studentIds);
        } else {
            $q->whereRaw('1=0');
        }

        $reports = $q->get();
        return view('ai.reports', compact('reports'));
    }

    public function runForm()
    {
        $classes = SchoolClass::orderBy('name')->get();
        $semesters = Semester::with('schoolYear')->orderBy('order')->get();
        return view('ai.run', compact('classes', 'semesters'));
    }

    public function run(Request $request, AiAnalyzer $analyzer)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);

        $user = Auth::user();
        if (!$user->isAdmin()) {
            // allow homeroom teacher run for own class
            if (!($user->isHomeroom() && $user->teacher && $class->homeroom_teacher_id === $user->teacher->id)) {
                abort(403);
            }
        }

        $result = $analyzer->analyzeClass($data['class_id'], $data['semester_id']);

        return redirect()->route('ai.alerts')->with(
            'success',
            "Đã phân tích: lớp={$result['class']->name}, hk={$result['semester']->name}. Tạo {$result['reports']} nhận xét, {$result['alerts']} cảnh báo."
        );
    }
}
