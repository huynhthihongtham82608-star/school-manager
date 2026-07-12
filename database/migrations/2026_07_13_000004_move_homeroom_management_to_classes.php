<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teaching_assignments') && Schema::hasColumn('teaching_assignments', 'is_homeroom_assignment')) {
            DB::table('teaching_assignments')->update(['is_homeroom_assignment' => false]);
        }

        if (
            Schema::hasTable('teachers')
            && Schema::hasColumn('teachers', 'is_homeroom')
            && Schema::hasTable('classes')
            && Schema::hasColumn('classes', 'homeroom_teacher_id')
        ) {
            DB::table('teachers')->update(['is_homeroom' => false]);

            $homeroomTeacherIds = DB::table('classes')
                ->join('school_years', 'school_years.id', '=', 'classes.school_year_id')
                ->whereNotNull('classes.homeroom_teacher_id')
                ->where('classes.status', '!=', 'archived')
                ->whereNull('school_years.archived_at')
                ->select('classes.homeroom_teacher_id');

            DB::table('teachers')
                ->whereIn('id', $homeroomTeacherIds)
                ->update(['is_homeroom' => true]);
        }
    }

    public function down(): void
    {
        // Dữ liệu GVCN được quản lý tại bảng classes, không khôi phục cờ trùng trong phân công.
    }
};
