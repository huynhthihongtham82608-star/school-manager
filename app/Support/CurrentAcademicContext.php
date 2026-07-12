<?php

namespace App\Support;

use App\Models\SchoolYear;
use App\Models\Semester;
use Illuminate\Support\Facades\Schema;

class CurrentAcademicContext
{
    public function schoolYear(): ?SchoolYear
    {
        if (! Schema::hasTable('school_years')) {
            return null;
        }

        return SchoolYear::query()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->first();
    }

    public function semester(?SchoolYear $schoolYear = null): ?Semester
    {
        if (! Schema::hasTable('semesters')) {
            return null;
        }

        $schoolYear ??= $this->schoolYear();

        if (! $schoolYear) {
            return null;
        }

        return Semester::query()
            ->where('school_year_id', $schoolYear->getKey())
            ->where('status', Semester::STATUS_ACTIVE)
            ->orderBy('order')
            ->orderBy('name')
            ->first();
    }

    public function syncSemesterForCurrentYear(SchoolYear $schoolYear): ?Semester
    {
        if (! Schema::hasTable('semesters')) {
            return null;
        }

        Semester::query()
            ->where('status', Semester::STATUS_ACTIVE)
            ->where('school_year_id', '!=', $schoolYear->getKey())
            ->update([
                'status' => Semester::STATUS_INACTIVE,
                'is_score_input_open' => false,
            ]);

        $currentSemester = $this->semester($schoolYear);

        if ($currentSemester) {
            return $currentSemester;
        }

        $nextSemester = Semester::query()
            ->where('school_year_id', $schoolYear->getKey())
            ->whereIn('status', [Semester::STATUS_INACTIVE, Semester::STATUS_DRAFT])
            ->orderByRaw("case when status = 'inactive' then 0 else 1 end")
            ->orderBy('order')
            ->orderBy('name')
            ->first();

        if (! $nextSemester) {
            return null;
        }

        $this->setCurrentSemester($nextSemester);

        return $nextSemester->refresh();
    }

    public function setCurrentSemester(Semester $semester): Semester
    {
        $currentYear = $this->schoolYear();

        if (! $currentYear || (string) $semester->school_year_id !== (string) $currentYear->getKey()) {
            throw new \InvalidArgumentException('Học kỳ hiện hành phải thuộc năm học hiện hành.');
        }

        Semester::query()
            ->where('status', Semester::STATUS_ACTIVE)
            ->whereKeyNot($semester->getKey())
            ->update([
                'status' => Semester::STATUS_INACTIVE,
                'is_score_input_open' => false,
            ]);

        $semester->update([
            'status' => Semester::STATUS_ACTIVE,
            'is_score_input_open' => true,
            'archived_at' => null,
        ]);

        return $semester->refresh();
    }
}
