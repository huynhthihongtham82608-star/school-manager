<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('score_details') || ! Schema::hasTable('score_columns') || ! Schema::hasColumn('score_details', 'score_column_id')) {
            return;
        }

        $defaultNames = [
            'oral' => 'Kiểm tra miệng',
            'quiz' => 'Kiểm tra 15 phút',
            'test' => 'Kiểm tra 1 tiết',
            'midterm' => 'Kiểm tra giữa kỳ',
            'final' => 'Kiểm tra cuối kỳ',
        ];

        DB::table('score_details')
            ->join('score_headers', 'score_headers.id', '=', 'score_details.score_header_id')
            ->join('students', 'students.id', '=', 'score_headers.student_id')
            ->join('classes', 'classes.id', '=', 'students.class_id')
            ->whereNull('score_details.score_column_id')
            ->select([
                'score_details.id',
                'score_details.type',
                'score_details.name',
                'score_headers.school_year_id',
                'score_headers.subject_id',
                'classes.grade_level',
            ])
            ->orderBy('score_details.id')
            ->get()
            ->each(function ($detail) use ($defaultNames) {
                $targetName = trim((string) ($detail->name ?: ($defaultNames[$detail->type] ?? '')));

                if ($targetName === '') {
                    return;
                }

                $column = DB::table('score_columns')
                    ->where('school_year_id', $detail->school_year_id)
                    ->where('subject_id', $detail->subject_id)
                    ->where('grade_level', $detail->grade_level)
                    ->where('name', $targetName)
                    ->first();

                if (! $column) {
                    return;
                }

                DB::table('score_details')
                    ->where('id', $detail->id)
                    ->update([
                        'score_column_id' => $column->id,
                        'type' => $column->type,
                        'name' => $column->name,
                        'weight_group' => $column->weight_group,
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('score_details') && Schema::hasColumn('score_details', 'score_column_id')) {
            DB::table('score_details')->update(['score_column_id' => null]);
        }
    }
};
