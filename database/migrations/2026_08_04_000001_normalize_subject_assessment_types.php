<?php

use App\Models\Subject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects') || ! Schema::hasColumn('subjects', 'assessment_type')) {
            return;
        }

        DB::table('subjects')
            ->where(function ($query) {
                $query->whereNull('assessment_type')
                    ->orWhere('assessment_type', '')
                    ->orWhere('assessment_type', Subject::LEGACY_ASSESSMENT_NUMERIC);
            })
            ->update(['assessment_type' => Subject::ASSESSMENT_GRADE_10]);

        DB::table('subjects')
            ->where('assessment_type', Subject::LEGACY_ASSESSMENT_PASS_FAIL)
            ->update(['assessment_type' => Subject::ASSESSMENT_ASSESSMENT]);

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('assessment_type', 30)->default(Subject::ASSESSMENT_GRADE_10)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subjects') || ! Schema::hasColumn('subjects', 'assessment_type')) {
            return;
        }

        DB::table('subjects')
            ->where('assessment_type', Subject::ASSESSMENT_GRADE_10)
            ->update(['assessment_type' => Subject::LEGACY_ASSESSMENT_NUMERIC]);

        DB::table('subjects')
            ->where('assessment_type', Subject::ASSESSMENT_ASSESSMENT)
            ->update(['assessment_type' => Subject::LEGACY_ASSESSMENT_PASS_FAIL]);

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('assessment_type', 30)->default(Subject::LEGACY_ASSESSMENT_NUMERIC)->change();
        });
    }
};
