<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. 2024-2025
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('teacher_code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('main_subject')->nullable();
            $table->boolean('is_homeroom')->default(false);
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedTinyInteger('credit')->default(1);
            $table->boolean('is_weighted')->default(false); // hệ số 2 môn
            $table->timestamps();
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 10A1
            $table->unsignedTinyInteger('grade_level'); // 10/11/12
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->unsignedSmallInteger('capacity')->default(45);
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code')->unique();
            $table->string('name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('dob')->nullable();
            $table->string('address')->nullable();
            $table->string('parent_phone')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->enum('status', ['studying', 'inactive'])->default('studying');
            $table->timestamps();
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // HK1/HK2
            $table->unsignedTinyInteger('order')->default(1);
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->boolean('is_score_input_open')->default(true);
            $table->timestamps();
        });

        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['teacher_id', 'class_id', 'subject_id', 'school_year_id'], 'teacher_class_subject_unique');
        });

        Schema::create('score_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->decimal('average', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'subject_id', 'semester_id', 'school_year_id'], 'student_subject_semester_unique');
        });

        Schema::create('score_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('score_header_id')->constrained('score_headers')->cascadeOnDelete();
            $table->enum('type', ['oral', 'quiz', 'test', 'midterm', 'final']);
            $table->decimal('value', 5, 2);
            $table->unsignedTinyInteger('weight_group')->default(1); // HS1=1, HS2=2, HS3=3
            $table->timestamps();
        });

        Schema::create('conducts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->enum('conduct_level', ['excellent', 'good', 'average', 'weak'])->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'semester_id', 'school_year_id'], 'student_semester_conduct_unique');
        });

        Schema::create('grade_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->boolean('is_open')->default(true);
            $table->timestamps();
            $table->unique(['class_id', 'subject_id', 'semester_id', 'school_year_id'], 'grade_window_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_windows');
        Schema::dropIfExists('conducts');
        Schema::dropIfExists('score_details');
        Schema::dropIfExists('score_headers');
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('students');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('school_years');
    }
};
