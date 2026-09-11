<?php

namespace App\Http\Controllers;

use App\Support\CurrentAcademicContext;
use App\Models\Semester;
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

    protected function selectedSemester(?Request $request = null): ?Semester
    {
        $request ??= request();
        $requestedSemesterId = $request->input('semester_id') ?: $request->query('semester_id');

        if ($requestedSemesterId) {
            $semester = Semester::with('schoolYear')->find($requestedSemesterId);

            if ($semester) {
                return $semester;
            }
        }

        $selectedSemesterId = $this->selectedSemesterId($request);

        return $selectedSemesterId ? Semester::with('schoolYear')->find($selectedSemesterId) : null;
    }

    protected function isHistoricalReadOnly(?Request $request = null, bool $includeSemester = false): bool
    {
        if (session('history_school_year_id')) {
            return true;
        }

        return $includeSemester && $this->isSemesterReadOnly($request);
    }

    protected function isSemesterReadOnly(?Request $request = null): bool
    {
        $semester = $this->selectedSemester($request);

        return $semester ? ! $semester->isCurrent() : false;
    }

    protected function ensureSemesterCanWrite(Semester $semester, string $message): void
    {
        if (! $semester->isCurrent()) {
            abort(403, $message);
        }
    }
}
