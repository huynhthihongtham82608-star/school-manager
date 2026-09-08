<?php

namespace Tests\Feature;

use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolYearController;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentClassAssignment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Tests\TestCase;

class ClassTransferAndSchoolYearInitializationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_same_year_transfer_is_limited_to_same_grade_classes(): void
    {
        $this->withoutMiddleware();

        $year = $this->makeSchoolYear('2090 - 2091');
        $source = $this->makeClass($year, '11A1 probe', 11);
        $sameGradeTarget = $this->makeClass($year, '11A2 probe', 11);
        $differentGradeTarget = $this->makeClass($year, '10A1 probe', 10);
        $student = $this->makeStudent($year, $source, 'UT-TRANSFER');

        StudentClassAssignment::create([
            'student_id' => $student->getKey(),
            'class_id' => $source->getKey(),
            'academic_year_id' => $year->getKey(),
            'status' => StudentClassAssignment::STATUS_ACTIVE,
        ]);

        $blockedResponse = app(SchoolClassController::class)->updateStudentAssignments($this->postRequest([
            'action' => 'transfer',
            'student_ids' => [$student->getKey()],
            'target_class_id' => $differentGradeTarget->getKey(),
        ]), $source);

        $this->assertTrue($blockedResponse->getSession()->get('errors')->has('target_class_id'));

        $this->assertSame((string) $source->getKey(), (string) $student->fresh()->class_id);

        $successResponse = app(SchoolClassController::class)->updateStudentAssignments($this->postRequest([
            'action' => 'transfer',
            'student_ids' => [$student->getKey()],
            'target_class_id' => $sameGradeTarget->getKey(),
        ]), $source);

        $this->assertTrue($successResponse->getSession()->has('success'));
        $this->assertSame((string) $sameGradeTarget->getKey(), (string) $student->fresh()->class_id);
        $this->assertDatabaseHas('student_movements', [
            'type' => 'transfer',
            'student_id' => $student->getKey(),
            'from_class_id' => $source->getKey(),
            'to_class_id' => $sameGradeTarget->getKey(),
        ]);
    }

    public function test_initialize_year_supports_manual_promote_repeat_and_graduate_selection(): void
    {
        $this->withoutMiddleware();

        [$sourceStart, $targetStart, $targetEnd] = $this->unusedYearWindow();
        $sourceYear = $this->makeSchoolYear($this->yearName($sourceStart, $targetStart), false, true);
        $sourceGrade10 = $this->makeClass($sourceYear, '10A probe ' . Str::random(5), 10);
        $sourceGrade11 = $this->makeClass($sourceYear, '11A probe ' . Str::random(5), 11);
        $sourceGrade12 = $this->makeClass($sourceYear, '12A probe ' . Str::random(5), 12);

        $promoted = $this->makeStudent($sourceYear, $sourceGrade10, 'UT-PROMOTE');
        $repeated = $this->makeStudent($sourceYear, $sourceGrade11, 'UT-REPEAT');
        $graduated = $this->makeStudent($sourceYear, $sourceGrade12, 'UT-GRAD');

        foreach ([$promoted, $repeated, $graduated] as $student) {
            StudentClassAssignment::create([
                'student_id' => $student->getKey(),
                'class_id' => $student->class_id,
                'academic_year_id' => $sourceYear->getKey(),
                'status' => StudentClassAssignment::STATUS_ACTIVE,
            ]);
        }

        $response = app(SchoolYearController::class)->initializeStore($this->postRequest([
            'source_year_id' => $sourceYear->getKey(),
            'start_year' => $targetStart,
            'end_year' => $targetEnd,
            'options' => ['promote_students', 'repeat_students', 'graduate_grade_12'],
            'promote_student_ids' => [$promoted->getKey()],
            'repeat_student_ids' => [$repeated->getKey()],
            'graduate_student_ids' => [$graduated->getKey()],
            'confirm_initialization' => 1,
        ]));

        $this->assertInstanceOf(View::class, $response);

        $targetYear = SchoolYear::where('name', $this->yearName($targetStart, $targetEnd))->firstOrFail();
        $promotedClass = SchoolClass::findOrFail($promoted->fresh()->class_id);
        $repeatedClass = SchoolClass::findOrFail($repeated->fresh()->class_id);

        $this->assertSame((string) $targetYear->getKey(), (string) $promoted->fresh()->school_year_id);
        $this->assertSame(11, (int) $promotedClass->grade_level);
        $this->assertSame((string) $targetYear->getKey(), (string) $repeated->fresh()->school_year_id);
        $this->assertSame(11, (int) $repeatedClass->grade_level);
        $this->assertSame(Student::STATUS_GRADUATED, $graduated->fresh()->status);

        $this->assertDatabaseHas('student_movements', [
            'type' => 'assignment',
            'student_id' => $repeated->getKey(),
            'class_id' => $sourceGrade11->getKey(),
            'academic_year_id' => $sourceYear->getKey(),
        ]);
        $this->assertDatabaseHas('student_movements', [
            'type' => 'assignment',
            'student_id' => $repeated->getKey(),
            'class_id' => $repeatedClass->getKey(),
            'academic_year_id' => $targetYear->getKey(),
        ]);

        $cleanup = new \ReflectionMethod(SchoolYearController::class, 'deleteInitialSchoolYearData');
        $cleanup->setAccessible(true);
        $cleanup->invoke(app(SchoolYearController::class), $targetYear);

        $this->assertSame((string) $sourceYear->getKey(), (string) $promoted->fresh()->school_year_id);
        $this->assertSame((string) $sourceGrade10->getKey(), (string) $promoted->fresh()->class_id);
        $this->assertSame((string) $sourceYear->getKey(), (string) $repeated->fresh()->school_year_id);
        $this->assertSame((string) $sourceGrade11->getKey(), (string) $repeated->fresh()->class_id);
    }

    public function test_initialize_year_rejects_student_selected_for_both_promote_and_repeat(): void
    {
        $this->withoutMiddleware();

        [$sourceStart, $targetStart, $targetEnd] = $this->unusedYearWindow();
        $sourceYear = $this->makeSchoolYear($this->yearName($sourceStart, $targetStart), false, true);
        $sourceClass = $this->makeClass($sourceYear, '10B probe ' . Str::random(5), 10);
        $student = $this->makeStudent($sourceYear, $sourceClass, 'UT-OVERLAP');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Một học sinh không thể vừa lên lớp vừa ở lại lớp');

        app(SchoolYearController::class)->initializeStore($this->postRequest([
            'source_year_id' => $sourceYear->getKey(),
            'start_year' => $targetStart,
            'end_year' => $targetEnd,
            'options' => ['promote_students', 'repeat_students'],
            'promote_student_ids' => [$student->getKey()],
            'repeat_student_ids' => [$student->getKey()],
            'confirm_initialization' => 1,
        ]));
    }

    public function test_default_cohort_is_calculated_from_school_year_and_grade(): void
    {
        $method = new \ReflectionMethod(SchoolClassController::class, 'defaultCohortForGrade');
        $method->setAccessible(true);

        $year = new SchoolYear([
            'name' => '2026 - 2027',
            'start_date' => '2026-08-01',
        ]);

        $this->assertSame('2026 - 2029', $method->invoke(app(SchoolClassController::class), $year, 10));
        $this->assertSame('2025 - 2028', $method->invoke(app(SchoolClassController::class), $year, 11));
        $this->assertSame('2024 - 2027', $method->invoke(app(SchoolClassController::class), $year, 12));
    }

    public function test_store_uses_grade_based_default_cohort_when_blank(): void
    {
        $this->withoutMiddleware();

        $year = $this->makeSchoolYear('2088 - 2089', false);
        $name = '11C cohort ' . Str::random(6);

        app(SchoolClassController::class)->store($this->postRequest([
            'name' => $name,
            'grade_level' => 11,
            'cohort' => '',
            'school_year_id' => $year->getKey(),
            'capacity' => 45,
        ]));

        $this->assertDatabaseHas('classes', [
            'name' => $name,
            'grade_level' => 11,
            'school_year_id' => $year->getKey(),
            'cohort' => '2087 - 2090',
        ]);
    }

    public function test_edit_form_keeps_existing_cohort_value_on_open(): void
    {
        $this->withoutMiddleware();

        $year = $this->makeSchoolYear('2086 - 2087', false);
        $class = $this->makeClass($year, '12C cohort ' . Str::random(6), 12);
        $class->update(['cohort' => 'custom cohort']);

        $html = app(SchoolClassController::class)->edit($class)->render();

        $this->assertStringContainsString('value="custom cohort"', $html);
        $this->assertStringContainsString('data-cohort-autofill="0"', $html);
    }

    private function makeSchoolYear(string $name, bool $active = true, bool $archived = false): SchoolYear
    {
        [$start, $end] = array_map('intval', explode(' - ', $name));

        return SchoolYear::create([
            'name' => $name,
            'start_date' => sprintf('%04d-08-01', $start),
            'end_date' => sprintf('%04d-05-31', $end),
            'is_active' => $active,
            'archived_at' => $archived ? now() : null,
        ]);
    }

    private function makeClass(SchoolYear $year, string $name, int $grade): SchoolClass
    {
        return SchoolClass::create([
            'name' => $name,
            'grade_level' => $grade,
            'school_year_id' => $year->getKey(),
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);
    }

    private function makeStudent(SchoolYear $year, SchoolClass $class, string $prefix): Student
    {
        $code = $prefix . '-' . Str::upper(Str::random(8));

        return Student::create([
            'student_code' => $code,
            'name' => 'Student ' . $code,
            'gender' => Student::GENDER_NAM,
            'dob' => '2010-01-01',
            'class_id' => $class->getKey(),
            'school_year_id' => $year->getKey(),
            'status' => Student::STATUS_STUDYING,
        ]);
    }

    private function unusedYearWindow(): array
    {
        for ($start = 2097; $start >= 2050; $start--) {
            $sourceName = $this->yearName($start, $start + 1);
            $targetName = $this->yearName($start + 1, $start + 2);

            if (! SchoolYear::whereIn('name', [$sourceName, $targetName])->exists()) {
                return [$start, $start + 1, $start + 2];
            }
        }

        $this->fail('No free test year window found.');
    }

    private function yearName(int $start, int $end): string
    {
        return $start . ' - ' . $end;
    }

    private function postRequest(array $data): Request
    {
        $request = Request::create('/', 'POST', $data);
        $request->setLaravelSession($this->app['session.store']);

        return $request;
    }
}
