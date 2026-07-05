<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subject_period_norms')) {
            return;
        }

        Schema::create('subject_period_norms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('subject_id', 50)->index();
            $table->unsignedTinyInteger('grade_level');
            $table->unsignedTinyInteger('periods_per_week');
            $table->timestamps();

            $table->unique(['subject_id', 'grade_level'], 'subject_grade_period_norm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_period_norms');
    }
};
