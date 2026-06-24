<?php

namespace App\Http\Controllers;

use App\Models\AiAlert;
use App\Models\AiReport;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Services\AiAnalyzer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiController extends Controller
{
    public function alerts()
    {
        return $this->supportView('alerts');
    }

    public function reports(Request $request)
    {
        return $this->supportView('reports');
    }

    public function runForm()
    {
        return $this->supportView('analysis');
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
            if (!($user->isHomeroom() && $user->teacher && $class->homeroom_teacher_id === $user->teacher->id)) {
                abort(403);
            }
        }

        $result = $analyzer->analyzeClass($data['class_id'], $data['semester_id']);

        return redirect()->route('ai.alerts')->with(
            'success',
            "Đã phân tích: lớp={$result['class']->name}, học kỳ={$result['semester']->name}. Tạo {$result['reports']} nhận xét, {$result['alerts']} cảnh báo."
        );
    }

    private function supportView(string $activeTab)
    {
        $user = Auth::user();

        $alerts = $this->alertsQuery($user)->get();
        $reports = $this->reportsQuery($user)->get();
        $classes = collect();
        $semesters = collect();

        if ($user->isAdmin() || $user->isStaff() || $user->isHomeroom()) {
            $classes = SchoolClass::orderBy('name')->get();
            $semesters = Semester::with('schoolYear')->orderBy('order')->get();
        }

        return view('ai.index', compact('activeTab', 'alerts', 'reports', 'classes', 'semesters'));
    }

    private function alertsQuery($user): Builder
    {
        $query = AiAlert::with(['student', 'classRoom', 'semester'])->orderByDesc('created_at');

        if ($user->isAdmin() || $user->isStaff()) {
            return $query;
        }

        if ($user->isTeacher() && $user->teacher) {
            return $query->where(function ($subQuery) use ($user) {
                $subQuery->where('teacher_id', $user->teacher->id)
                    ->orWhereIn('class_id', $user->teacher->homeroomClasses()->pluck('id'));
            });
        }

        if ($user->isStudent() && $user->student) {
            return $query->where('student_id', $user->student->id);
        }

        if ($user->isParent() && $user->parentProfile) {
            $studentIds = $user->parentProfile->students()->pluck('students.id');
            return $query->whereIn('student_id', $studentIds);
        }

        return $query->whereRaw('1=0');
    }

    private function reportsQuery($user): Builder
    {
        $query = AiReport::with(['student', 'semester'])->orderByDesc('created_at');

        if ($user->isAdmin() || $user->isStaff()) {
            return $query;
        }

        if ($user->isStudent() && $user->student) {
            return $query->where('student_id', $user->student->id);
        }

        if ($user->isParent() && $user->parentProfile) {
            $studentIds = $user->parentProfile->students()->pluck('students.id');
            return $query->whereIn('student_id', $studentIds);
        }

        return $query->whereRaw('1=0');
    }
}
