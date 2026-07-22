<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('teacher_departments')
            || ! Schema::hasTable('teacher_department_subject')
            || ! Schema::hasTable('teachers')
            || ! Schema::hasColumn('teachers', 'department_id')
            || ! Schema::hasColumn('teachers', 'primary_subject_id')
        ) {
            return;
        }

        $leaderIds = DB::table('teacher_departments')
            ->whereNotNull('leader_teacher_id')
            ->pluck('leader_teacher_id')
            ->filter()
            ->values();

        DB::table('teacher_department_subject')
            ->orderBy('department_id')
            ->get(['department_id', 'subject_id'])
            ->each(function ($row) use ($leaderIds) {
                DB::table('teachers')
                    ->where('primary_subject_id', $row->subject_id)
                    ->when($leaderIds->isNotEmpty(), function ($query) use ($leaderIds, $row) {
                        $query->where(function ($inner) use ($leaderIds, $row) {
                            $inner->whereNotIn('id', $leaderIds)
                                ->orWhere('department_id', $row->department_id);
                        });
                    })
                    ->update([
                        'department_id' => $row->department_id,
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        // Data synchronization only. No rollback to avoid losing valid manual assignments.
    }
};
