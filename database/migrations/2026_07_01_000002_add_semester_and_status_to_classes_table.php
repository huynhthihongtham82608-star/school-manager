<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (! Schema::hasColumn('classes', 'semester_id')) {
                $table->string('semester_id', 50)->nullable()->after('school_year_id')->index();
            }

            if (! Schema::hasColumn('classes', 'status')) {
                $table->string('status', 20)->default('draft')->after('capacity')->index();
            }

            if (! Schema::hasColumn('classes', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('classes', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('locked_at');
            }
        });

        if (Schema::hasColumn('classes', 'semester_id') && Schema::hasTable('semesters')) {
            DB::table('classes')
                ->whereNull('semester_id')
                ->orderBy('id')
                ->get(['id', 'school_year_id'])
                ->each(function ($class) {
                    $semesterId = DB::table('semesters')
                        ->where('school_year_id', $class->school_year_id)
                        ->orderBy('name')
                        ->value('id');

                    if ($semesterId) {
                        DB::table('classes')->where('id', $class->id)->update(['semester_id' => $semesterId]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'archived_at')) {
                $table->dropColumn('archived_at');
            }

            if (Schema::hasColumn('classes', 'locked_at')) {
                $table->dropColumn('locked_at');
            }

            if (Schema::hasColumn('classes', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('classes', 'semester_id')) {
                $table->dropColumn('semester_id');
            }
        });
    }
};
