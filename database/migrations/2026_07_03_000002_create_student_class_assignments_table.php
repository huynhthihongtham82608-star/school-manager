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
            DB::statement('ALTER TABLE `students` MODIFY `class_id` VARCHAR(50) NULL');
        }

        if (! Schema::hasTable('student_class_assignments')) {
            Schema::create('student_class_assignments', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('student_id', 50)->index();
                $table->string('class_id', 50)->index();
                $table->string('academic_year_id', 50)->index();
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
    }
};
