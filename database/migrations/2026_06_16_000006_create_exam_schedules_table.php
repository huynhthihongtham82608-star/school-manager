<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('type', 50)->nullable()->index();
            $table->string('display_name')->nullable();
            $table->string('class_id', 50)->nullable()->index();
            $table->string('subject_id', 50)->nullable()->index();
            $table->string('semester_id', 50)->nullable()->index();
            $table->date('exam_date')->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('room')->nullable();
            $table->date('score_input_opens_at')->nullable();
            $table->date('score_input_closes_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_schedules');
    }
};
