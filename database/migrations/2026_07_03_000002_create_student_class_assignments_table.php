<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'class_id')) {
            if ($this->foreignKeyExists('students', 'students_class_id_foreign')) {
                DB::statement('ALTER TABLE `students` DROP FOREIGN KEY `students_class_id_foreign`');
            }

            DB::statement('ALTER TABLE `students` MODIFY `class_id` BIGINT UNSIGNED NULL');
            $this->addStudentsClassForeignKey('SET NULL');
        }

        if (! Schema::hasTable('student_class_assignments')) {
            Schema::create('student_class_assignments', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('class_id')->index();
                $table->unsignedBigInteger('academic_year_id')->index();
                $table->string('status', 20)->default('active')->index();
                $table->timestamps();

                $table->unique(['student_id', 'academic_year_id', 'class_id'], 'student_class_year_unique');
                $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
                $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('school_years')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('student_class_assignments')) {
            DB::table('students')
                ->whereNotNull('class_id')
                ->whereNotNull('school_year_id')
                ->orderBy('id')
                ->get(['id', 'class_id', 'school_year_id', 'created_at', 'updated_at'])
                ->each(function ($student) {
                    $exists = DB::table('student_class_assignments')
                        ->where('student_id', $student->id)
                        ->where('academic_year_id', $student->school_year_id)
                        ->where('class_id', $student->class_id)
                        ->exists();

                    if (! $exists) {
                        DB::table('student_class_assignments')->insert([
                            'id' => (string) Str::uuid(),
                            'student_id' => $student->id,
                            'class_id' => $student->class_id,
                            'academic_year_id' => $student->school_year_id,
                            'status' => 'active',
                            'created_at' => $student->created_at ?: now(),
                            'updated_at' => $student->updated_at ?: now(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_class_assignments');

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'class_id')) {
            if ($this->foreignKeyExists('students', 'students_class_id_foreign')) {
                DB::statement('ALTER TABLE `students` DROP FOREIGN KEY `students_class_id_foreign`');
            }

            $hasNullClass = DB::table('students')->whereNull('class_id')->exists();
            DB::statement('ALTER TABLE `students` MODIFY `class_id` BIGINT UNSIGNED ' . ($hasNullClass ? 'NULL' : 'NOT NULL'));
            $this->addStudentsClassForeignKey($hasNullClass ? 'SET NULL' : 'CASCADE');
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return (bool) DB::selectOne(
            'SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1',
            [$table, $constraint]
        );
    }

    private function addStudentsClassForeignKey(string $onDelete): void
    {
        if (
            ! Schema::hasTable('students')
            || ! Schema::hasTable('classes')
            || ! Schema::hasColumn('students', 'class_id')
            || $this->foreignKeyExists('students', 'students_class_id_foreign')
        ) {
            return;
        }

        DB::statement("ALTER TABLE `students` ADD CONSTRAINT `students_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE {$onDelete}");
    }
};
