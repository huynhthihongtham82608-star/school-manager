<?php

use App\Models\TeachingAssignment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('timetable_entries')) {
            return;
        }

        Schema::table('timetable_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('timetable_entries', 'assignment_id')) {
                $table->string('assignment_id', 50)->nullable()->after('timetable_id')->index();
            }

            if (! Schema::hasColumn('timetable_entries', 'status')) {
                $table->string('status', 20)->default('active')->after('note')->index();
            }

            if (! Schema::hasColumn('timetable_entries', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('status');
            }
        });

        if (Schema::hasTable('timetables') && Schema::hasTable('teaching_assignments')) {
            DB::table('timetable_entries')
                ->join('timetables', 'timetables.id', '=', 'timetable_entries.timetable_id')
                ->select([
                    'timetable_entries.id',
                    'timetable_entries.teacher_id',
                    'timetable_entries.subject_id',
                    'timetables.school_year_id',
                    'timetables.semester_id',
                    'timetables.class_id',
                ])
                ->whereNull('timetable_entries.assignment_id')
                ->orderBy('timetable_entries.id')
                ->get()
                ->each(function ($entry) {
                    $assignmentId = DB::table('teaching_assignments')
                        ->where('school_year_id', $entry->school_year_id)
                        ->where('semester_id', $entry->semester_id)
                        ->where('class_id', $entry->class_id)
                        ->where('subject_id', $entry->subject_id)
                        ->where('teacher_id', $entry->teacher_id)
                        ->where('status', TeachingAssignment::STATUS_ACTIVE)
                        ->value('id');

                    if ($assignmentId) {
                        DB::table('timetable_entries')
                            ->where('id', $entry->id)
                            ->update([
                                'assignment_id' => $assignmentId,
                                'status' => 'active',
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('timetable_entries')) {
            return;
        }

        Schema::table('timetable_entries', function (Blueprint $table) {
            foreach (['archived_at', 'status', 'assignment_id'] as $column) {
                if (Schema::hasColumn('timetable_entries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
