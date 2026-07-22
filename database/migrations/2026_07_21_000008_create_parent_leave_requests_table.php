<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parent_leave_requests')) {
            return;
        }

        Schema::create('parent_leave_requests', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('parent_id', 36);
            $table->char('student_id', 36);
            $table->char('class_id', 36)->nullable();
            $table->date('leave_date');
            $table->text('reason');
            $table->string('status', 30)->default('pending')->index();
            $table->text('homeroom_note')->nullable();
            $table->char('reviewed_by', 36)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'leave_date']);
            $table->foreign('parent_id')->references('id')->on('parents')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_leave_requests');
    }
};
