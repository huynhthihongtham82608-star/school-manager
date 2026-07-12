<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $teachers = DB::table('teachers')
            ->join('users', 'users.teacher_id', '=', 'teachers.id')
            ->where('users.role', 'teacher')
            ->select('users.id as user_id', 'teachers.teacher_code')
            ->get();

        foreach ($teachers as $teacher) {
            if (! $teacher->teacher_code) {
                continue;
            }

            DB::table('users')
                ->where('id', $teacher->user_id)
                ->update(['username' => $teacher->teacher_code]);
        }
    }

    public function down(): void
    {
        // Cannot safely infer previous custom usernames after syncing to teacher_code.
    }
};
