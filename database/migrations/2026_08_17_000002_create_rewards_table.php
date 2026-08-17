<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rewards')) {
            return;
        }

        Schema::create('rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 50)->index();
            $table->string('class_id', 50)->index();
            $table->string('semester_id', 50)->nullable()->index();
            $table->string('school_year_id', 50)->nullable()->index();
            $table->string('reward_type', 50)->index();
            $table->string('decision_number')->nullable();
            $table->text('detail')->nullable();
            $table->string('created_by', 50)->nullable()->index();
            $table->string('updated_by', 50)->nullable()->index();
            $table->timestamps();

            $table->index(['class_id', 'semester_id', 'reward_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
