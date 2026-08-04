<?php

use App\Models\Subject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const GRADE_LEVELS = [10, 11, 12];

    public function up(): void
    {
        if (! Schema::hasTable('subject_grade_mappings')) {
            Schema::create('subject_grade_mappings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('subject_id', 50)->index();
                $table->unsignedTinyInteger('grade_level')->index();
                $table->timestamps();

                $table->unique(['subject_id', 'grade_level'], 'subject_grade_mapping_unique');
            });
        }

        $this->backfillMappings();
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_grade_mappings');
    }

    private function backfillMappings(): void
    {
        if (! Schema::hasTable('subjects') || ! Schema::hasTable('subject_grade_mappings')) {
            return;
        }

        $now = now();
        $subjects = DB::table('subjects')
            ->select(['id', 'type'])
            ->where('status', Subject::STATUS_ACTIVE)
            ->get();

        foreach ($subjects as $subject) {
            $gradeLevels = $this->gradeLevelsForSubject((string) $subject->id);

            foreach ($gradeLevels as $gradeLevel) {
                $exists = DB::table('subject_grade_mappings')
                    ->where('subject_id', (string) $subject->id)
                    ->where('grade_level', $gradeLevel)
                    ->exists();

                if (! $exists) {
                    DB::table('subject_grade_mappings')->insert([
                        'id' => (string) Str::uuid(),
                        'subject_id' => (string) $subject->id,
                        'grade_level' => $gradeLevel,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private function gradeLevelsForSubject(string $subjectId): array
    {
        if (! Schema::hasTable('subject_period_norms')) {
            return self::GRADE_LEVELS;
        }

        $normGrades = DB::table('subject_period_norms')
            ->where('subject_id', $subjectId)
            ->pluck('grade_level')
            ->map(fn ($gradeLevel) => (int) $gradeLevel)
            ->filter(fn (int $gradeLevel) => in_array($gradeLevel, self::GRADE_LEVELS, true))
            ->unique()
            ->values()
            ->all();

        return $normGrades ?: self::GRADE_LEVELS;
    }
};
