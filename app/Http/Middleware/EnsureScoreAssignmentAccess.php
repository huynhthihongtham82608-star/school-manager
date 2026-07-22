<?php

namespace App\Http\Middleware;

use App\Models\Semester;
use App\Models\TeachingAssignment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureScoreAssignmentAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Thầy/cô không có quyền hạn xử lý bảng điểm này!');
        }

        if ($user->isAdmin() || $user->isStaff()) {
            return $next($request);
        }

        if (! $user->isTeacher() || ! $user->teacher) {
            abort(403, 'Thầy/cô không có quyền hạn xử lý bảng điểm này!');
        }

        $classId = $request->input('class_id', $request->query('class_id'));
        $subjectId = $request->input('subject_id', $request->query('subject_id'));
        $semesterId = $request->input('semester_id', $request->query('semester_id'));

        if (! $classId || ! $subjectId || ! $semesterId) {
            abort(403, 'Thầy/cô không có quyền hạn xử lý bảng điểm này!');
        }

        $semester = Semester::find($semesterId);

        $hasAssignment = TeachingAssignment::where('teacher_id', $user->teacher->id)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->when($semester, fn ($query) => $query->where('school_year_id', $semester->school_year_id))
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->exists();

        if (! $hasAssignment) {
            abort(403, 'Thầy/cô không có quyền hạn xử lý bảng điểm này!');
        }

        return $next($request);
    }
}
