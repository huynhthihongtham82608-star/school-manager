<?php

namespace App\Support;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StudentCodeGenerator
{
    public static function nextForClass(SchoolClass $class): string
    {
        $year = self::admissionYearForClass($class);
        $prefix = 'HS' . $year;
        $latestNumber = Student::where('student_code', 'like', $prefix . '%')
            ->pluck('student_code')
            ->map(fn ($code) => preg_match('/^' . preg_quote($prefix, '/') . '(\d{4})$/', (string) $code, $matches) ? (int) $matches[1] : null)
            ->filter()
            ->max();
        $nextNumber = ($latestNumber ?: 0) + 1;

        do {
            if ($nextNumber > 9999) {
                throw ValidationException::withMessages([
                    'student_code' => 'Năm vào trường ' . $year . ' đã đạt giới hạn HS' . $year . '9999.',
                ]);
            }

            $code = $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Student::where('student_code', $code)->exists() || User::where('username', $code)->exists());

        return $code;
    }

    public static function admissionYearForClass(SchoolClass $class): int
    {
        $class->loadMissing('schoolYear');

        $schoolYearStart = self::schoolYearStartYear($class);
        $gradeLevel = (int) $class->grade_level;
        $gradeOffset = max(0, $gradeLevel - 10);

        return $schoolYearStart - $gradeOffset;
    }

    private static function schoolYearStartYear(SchoolClass $class): int
    {
        $startDate = $class->schoolYear?->start_date;
        if ($startDate) {
            return (int) Carbon::parse($startDate)->format('Y');
        }

        $yearName = (string) ($class->schoolYear?->name ?? '');
        if (preg_match('/(\d{4})/', $yearName, $matches)) {
            return (int) $matches[1];
        }

        return (int) now()->format('Y');
    }
}
