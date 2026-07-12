<?php

namespace App\Http\Controllers;

use App\Support\CurrentAcademicContext;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function selectedSchoolYearId(?Request $request = null): ?string
    {
        $request ??= request();

        return session('history_school_year_id')
            ?: session('working_school_year_id')
            ?: app(CurrentAcademicContext::class)->schoolYear()?->getKey();
    }

    protected function selectedSemesterId(?Request $request = null): ?string
    {
        $request ??= request();

        $selectedYearId = $this->selectedSchoolYearId($request);
        $selectedYear = $selectedYearId ? SchoolYear::find($selectedYearId) : null;

        $workingSemesterId = session('working_semester_id');
        if ($workingSemesterId && $selectedYear) {
            $semester = \App\Models\Semester::find($workingSemesterId);
            if ($semester && (string) $semester->school_year_id === (string) $selectedYear->getKey()) {
                return $semester->getKey();
            }
        }

        return app(CurrentAcademicContext::class)->semester($selectedYear)?->getKey();
    }

    protected function isHistoricalReadOnly(): bool
    {
        return (bool) session('history_school_year_id');
    }
}
