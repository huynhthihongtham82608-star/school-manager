<?php

namespace App\Http\Middleware;

use App\Models\SchoolYear;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadOnlyHistoricalSchoolYear
{
    public function handle(Request $request, Closure $next): Response
    {
        $historyYearId = $request->query('history_school_year_id');

        if ($historyYearId) {
            $year = SchoolYear::find($historyYearId);

            if ($year && $year->isArchived()) {
                $request->session()->put([
                    'history_school_year_id' => $year->id,
                    'viewing_mode' => 'archive',
                    'viewing_school_year_id' => $year->id,
                    'viewing_school_year_name' => $year->name,
                ]);
            }
        }

        if (! $request->session()->has('history_school_year_id') || $request->isMethodSafe()) {
            return $next($request);
        }

        $allowedRoutes = [
            'logout',
            'school-years.history.clear',
        ];

        if (in_array((string) $request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return back()->withErrors([
            'history_readonly' => 'Bạn đang xem dữ liệu năm học đã lưu trữ ở chế độ chỉ xem. Vui lòng quay lại năm học hiện tại để chỉnh sửa dữ liệu.',
        ]);
    }
}
