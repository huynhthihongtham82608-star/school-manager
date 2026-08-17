<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tuition_fees')) {
            return;
        }

        Schema::create('tuition_fees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 50)->index();
            $table->string('class_id', 50)->index();
            $table->string('semester_id', 50)->nullable()->index();
            $table->string('school_year_id', 50)->nullable()->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status', 30)->default('unpaid')->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->string('updated_by', 50)->nullable()->index();
            $table->timestamps();

            $table->unique(['student_id', 'semester_id'], 'tuition_fees_student_semester_unique');
            $table->index(['class_id', 'semester_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuition_fees');
    }
};
