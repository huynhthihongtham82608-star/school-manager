<?php

use App\Models\Subject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_department_subject') || ! Schema::hasTable('subjects')) {
            return;
        }

        $allowedTypes = [
            Subject::TYPE_OFFICIAL,
            Subject::TYPE_REQUIRED,
            Subject::TYPE_ELECTIVE,
            Subject::TYPE_REMEDIAL,
        ];

        DB::table('teacher_department_subject')
            ->join('subjects', 'teacher_department_subject.subject_id', '=', 'subjects.id')
            ->whereNotIn('subjects.type', $allowedTypes)
            ->delete();
    }

    public function down(): void
    {
        // Data cleanup only. Non-official subjects should not be restored to departments.
    }
};
