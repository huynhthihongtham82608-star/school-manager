<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 50)->index();
            $table->string('class_id', 50)->index();
            $table->string('semester_id', 50)->nullable()->index();
            $table->date('attendance_date')->index();
            $table->string('status')->default('present')->index();
            $table->text('note')->nullable();
            $table->string('recorded_by', 50)->nullable()->index();
            $table->timestamps();

            $table->unique(['student_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
