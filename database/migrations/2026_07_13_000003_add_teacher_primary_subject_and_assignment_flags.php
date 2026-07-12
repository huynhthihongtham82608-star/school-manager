<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                if (! Schema::hasColumn('teachers', 'primary_subject_id')) {
                    $table->string('primary_subject_id', 50)->nullable()->after('main_subject')->index();
                }
            });

            if (Schema::hasTable('subjects')) {
                DB::table('teachers')
                    ->whereNull('primary_subject_id')
                    ->whereNotNull('main_subject')
                    ->orderBy('id')
                    ->get()
                    ->each(function ($teacher) {
                        $subject = DB::table('subjects')
                            ->where('name', $teacher->main_subject)
                            ->orWhere('code', $teacher->main_subject)
                            ->first();

                        $subject ??= DB::table('subjects')
                            ->where('name', 'like', '%' . $teacher->main_subject . '%')
                            ->orWhereRaw('? like concat("%", name, "%")', [$teacher->main_subject])
                            ->first();

                        if ($subject) {
                            DB::table('teachers')
                                ->where('id', $teacher->id)
                                ->update([
                                    'primary_subject_id' => $subject->id,
                                    'main_subject' => $subject->name,
                                ]);
                        }
                    });
            }
        }

        if (Schema::hasTable('teaching_assignments')) {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                if (! Schema::hasColumn('teaching_assignments', 'weekly_periods')) {
                    $table->unsignedTinyInteger('weekly_periods')->nullable()->after('custom_role');
                }

                if (! Schema::hasColumn('teaching_assignments', 'is_homeroom_assignment')) {
                    $table->boolean('is_homeroom_assignment')->default(false)->after('weekly_periods');
                }
            });

            if (Schema::hasTable('teachers')) {
                DB::table('teaching_assignments')
                    ->join('teachers', 'teachers.id', '=', 'teaching_assignments.teacher_id')
                    ->whereNotNull('teachers.primary_subject_id')
                    ->whereColumn('teaching_assignments.subject_id', '!=', 'teachers.primary_subject_id')
                    ->update(['teaching_assignments.subject_id' => DB::raw('teachers.primary_subject_id')]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teaching_assignments')) {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('teaching_assignments', 'is_homeroom_assignment')) {
                    $table->dropColumn('is_homeroom_assignment');
                }

                if (Schema::hasColumn('teaching_assignments', 'weekly_periods')) {
                    $table->dropColumn('weekly_periods');
                }
            });
        }

        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                if (Schema::hasColumn('teachers', 'primary_subject_id')) {
                    $table->dropColumn('primary_subject_id');
                }
            });
        }
    }
};
