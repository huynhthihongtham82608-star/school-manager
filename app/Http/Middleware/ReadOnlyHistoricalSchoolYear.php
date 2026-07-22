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

        if (! $request->session()->has('history_school_year_id') || $request->isMethodSafe()) {
            return $next($request);
        }

        $allowedRoutes = [
            'logout',
            'school-years.history.clear',
            'academic-context.update',
        ];

        if (in_array((string) $request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        if (
            (string) $request->route()?->getName() === 'attendance.store'
            && $request->user()?->isAdmin()
        ) {
            return $next($request);
        }

        return back()->withErrors([
            'history_readonly' => 'Bạn đang xem dữ liệu năm học cũ ở chế độ chỉ xem. Vui lòng quay về năm học hiện hành để thực hiện thao tác thay đổi dữ liệu.',
        ]);
    }
}
