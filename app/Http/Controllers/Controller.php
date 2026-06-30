<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function selectedSchoolYearId(?Request $request = null): ?string
    {
        $request ??= request();

        return $request->query('school_year_id')
            ?: session('history_school_year_id');
    }

    protected function isHistoricalReadOnly(): bool
    {
        return (bool) session('history_school_year_id');
    }
}
