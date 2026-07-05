<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'admission_type')) {
                $table->string('admission_type', 20)->default('new')->after('enrollment_date');
            }

            if (! Schema::hasColumn('students', 'previous_school')) {
                $table->string('previous_school')->nullable()->after('admission_type');
            }

            if (! Schema::hasColumn('students', 'transfer_grade_level')) {
                $table->unsignedTinyInteger('transfer_grade_level')->nullable()->after('previous_school');
            }

            if (! Schema::hasColumn('students', 'previous_class')) {
                $table->string('previous_class', 50)->nullable()->after('transfer_grade_level');
            }
        });

        DB::table('students')
            ->whereNull('admission_type')
            ->orWhere('admission_type', '')
            ->update(['admission_type' => 'new']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            foreach (['previous_class', 'transfer_grade_level', 'previous_school', 'admission_type'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
