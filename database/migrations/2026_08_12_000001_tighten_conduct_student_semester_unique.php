<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('conducts')) {
            return;
        }

        try {
            Schema::table('conducts', function (Blueprint $table) {
                $table->dropUnique('student_semester_conduct_unique');
            });
        } catch (Throwable) {
        }

        Schema::table('conducts', function (Blueprint $table) {
            $table->unique(['student_id', 'semester_id'], 'conducts_student_semester_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('conducts')) {
            return;
        }

        try {
            Schema::table('conducts', function (Blueprint $table) {
                $table->dropUnique('conducts_student_semester_unique');
            });
        } catch (Throwable) {
        }

        Schema::table('conducts', function (Blueprint $table) {
            $table->unique(['student_id', 'semester_id', 'school_year_id'], 'student_semester_conduct_unique');
        });
    }
};
