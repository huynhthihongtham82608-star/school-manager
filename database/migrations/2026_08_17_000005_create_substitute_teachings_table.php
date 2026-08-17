<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('substitute_teachings')) {
            return;
        }

        Schema::create('substitute_teachings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('substitute_date')->index();
            $table->string('timetable_entry_id', 50)->index();
            $table->string('class_id', 50)->index();
            $table->string('semester_id', 50)->nullable()->index();
            $table->string('school_year_id', 50)->nullable()->index();
            $table->string('original_teacher_id', 50)->nullable()->index();
            $table->string('substitute_teacher_id', 50)->index();
            $table->string('status', 30)->default('pending')->index();
            $table->text('note')->nullable();
            $table->string('created_by', 50)->nullable()->index();
            $table->string('updated_by', 50)->nullable()->index();
            $table->timestamps();

            $table->index(['class_id', 'semester_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substitute_teachings');
    }
};
