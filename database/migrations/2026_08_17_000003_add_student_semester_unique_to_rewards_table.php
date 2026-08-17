<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rewards')) {
            return;
        }

        Schema::table('rewards', function (Blueprint $table) {
            $table->unique(['student_id', 'semester_id'], 'rewards_student_semester_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rewards')) {
            return;
        }

        Schema::table('rewards', function (Blueprint $table) {
            $table->dropUnique('rewards_student_semester_unique');
        });
    }
};
