<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subjects') && ! Schema::hasColumn('subjects', 'assessment_type')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->string('assessment_type', 30)->default('numeric')->after('type')->index();
            });
        }

        if (Schema::hasTable('rooms') && ! Schema::hasColumn('rooms', 'fixed_class_id')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->string('fixed_class_id', 50)->nullable()->after('capacity')->index();
            });
        }

        if (Schema::hasTable('classes') && ! Schema::hasColumn('classes', 'cohort')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->string('cohort', 20)->nullable()->after('grade_level')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'cohort')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropColumn('cohort');
            });
        }

        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'fixed_class_id')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('fixed_class_id');
            });
        }

        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'assessment_type')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('assessment_type');
            });
        }
    }
};
