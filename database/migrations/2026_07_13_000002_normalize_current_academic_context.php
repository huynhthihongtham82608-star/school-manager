<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_years')) {
            return;
        }

        $currentYear = DB::table('school_years')
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->first();

        if (! $currentYear) {
            $currentYear = DB::table('school_years')
                ->whereNull('archived_at')
                ->orderByDesc('start_date')
                ->orderByDesc('created_at')
                ->first();

            if ($currentYear) {
                DB::table('school_years')->where('id', $currentYear->id)->update(['is_active' => true]);
            }
        }

        if ($currentYear) {
            DB::table('school_years')
                ->where('id', '!=', $currentYear->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        if (! $currentYear || ! Schema::hasTable('semesters')) {
            return;
        }

        DB::table('semesters')
            ->where('status', 'active')
            ->where('school_year_id', '!=', $currentYear->id)
            ->update([
                'status' => 'inactive',
                'is_score_input_open' => false,
            ]);

        $currentSemester = DB::table('semesters')
            ->where('school_year_id', $currentYear->id)
            ->where('status', 'active')
            ->orderBy('order')
            ->orderBy('name')
            ->first();

        if (! $currentSemester) {
            $currentSemester = DB::table('semesters')
                ->where('school_year_id', $currentYear->id)
                ->whereIn('status', ['inactive', 'draft'])
                ->orderByRaw("case when status = 'inactive' then 0 else 1 end")
                ->orderBy('order')
                ->orderBy('name')
                ->first();

            if ($currentSemester) {
                DB::table('semesters')->where('id', $currentSemester->id)->update([
                    'status' => 'active',
                    'is_score_input_open' => true,
                ]);
            }
        }

        if ($currentSemester) {
            DB::table('semesters')
                ->where('id', '!=', $currentSemester->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'inactive',
                    'is_score_input_open' => false,
                ]);
        }
    }

    public function down(): void
    {
        // Migration này chỉ chuẩn hóa dữ liệu hiện hành, không rollback để tránh làm sai trạng thái đang dùng.
    }
};
