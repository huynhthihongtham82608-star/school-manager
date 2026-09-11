<?php

namespace App\Http\Middleware;

use App\Models\SchoolYear;
use App\Models\Semester;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadOnlyHistoricalSchoolYear
{
    public function handle(Request $request, Closure $next): Response
    {
        $historyYearId = $request->query('history_school_year_id') ?: $request->query('school_year_id');

        if ($historyYearId) {
            $year = SchoolYear::find($historyYearId);
            $currentYear = SchoolYear::where('is_active', true)->whereNull('archived_at')->first();

            if ($year && (! $currentYear || (string) $year->getKey() !== (string) $currentYear->getKey())) {
                $semester = null;
                if ($request->query('semester_id')) {
                    $semester = Semester::find($request->query('semester_id'));
                    if ($semester && (string) $semester->school_year_id !== (string) $year->getKey()) {
                        $semester = null;
                    }
                }

                $semester ??= Semester::where('school_year_id', $year->getKey())
                    ->orderByRaw("case when status = 'active' then 0 when status = 'inactive' then 1 else 2 end")
                    ->orderBy('order')
                    ->orderBy('name')
                    ->first();

                $request->session()->put([
                    'history_school_year_id' => $year->id,
                    'working_school_year_id' => $year->id,
                    'viewing_mode' => 'history',
                    'viewing_school_year_id' => $year->id,
                    'viewing_school_year_name' => $year->name,
                ]);

                if ($semester) {
                    $request->session()->put('working_semester_id', $semester->getKey());
                } else {
                    $request->session()->forget('working_semester_id');
                }
            }
        }

        if ($request->isMethodSafe()) {
            return $next($request);
        }

        if (! $request->session()->has('history_school_year_id') && ! $this->isViewingPastSemester($request)) {
            return $next($request);
        }

        $allowedRoutes = [
            'logout',
            'school-years.history.clear',
            'academic-context.update',
            'scores.store',
            'semesters.score-input.open',
            'semesters.score-input.close',
        ];

        if (in_array((string) $request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        if (
            $this->isViewingPastSemester($request)
            && ! $request->session()->has('history_school_year_id')
            && ! $this->isSemesterBoundMutationRoute($request)
        ) {
            return $next($request);
        }

        if (
            (string) $request->route()?->getName() === 'attendance.store'
            && $request->user()?->isAdmin()
            && ! $this->isViewingPastSemester($request)
        ) {
            return $next($request);
        }

        $message = $this->isViewingPastSemester($request) && ! $request->session()->has('history_school_year_id')
            ? 'Bạn đang xem dữ liệu học kỳ không hiện hành ở chế độ chỉ xem. Vui lòng chuyển về học kỳ hiện hành để thực hiện thao tác thay đổi dữ liệu.'
            : 'Bạn đang xem dữ liệu năm học cũ ở chế độ chỉ xem. Vui lòng quay về năm học hiện hành để thực hiện thao tác thay đổi dữ liệu.';

        return back()->withErrors([
            'history_readonly' => $message,
        ]);
    }

    private function isViewingPastSemester(Request $request): bool
    {
        $semesterId = $request->input('semester_id') ?: $request->query('semester_id') ?: $request->session()->get('working_semester_id');
        $semester = $semesterId ? Semester::find($semesterId) : null;

        return $semester ? ! $semester->isCurrent() : false;
    }

    private function isSemesterBoundMutationRoute(Request $request): bool
    {
        return in_array((string) $request->route()?->getName(), [
            'attendance.store',
            'conduct.store',
            'assignments.store',
            'assignments.update',
            'assignments.destroy',
            'exam-schedules.store',
            'exam-schedules.update',
            'exam-schedules.destroy',
            'rewards.scan',
            'rewards.store',
            'rewards.update',
            'rewards.destroy',
            'tuition-fees.update',
            'substitute-teachings.store',
            'substitute-teachings.update',
            'substitute-teachings.destroy',
            'timetable.entries.save',
            'timetable.clone',
        ], true);
    }
}
