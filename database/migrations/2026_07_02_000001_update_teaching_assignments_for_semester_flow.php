<?php

use App\Models\Semester;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teaching_assignments')) {
            return;
        }

        Schema::table('teaching_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('teaching_assignments', 'semester_id')) {
                $table->string('semester_id', 50)->nullable()->after('school_year_id')->index();
            }

            if (! Schema::hasColumn('teaching_assignments', 'role')) {
                $table->string('role', 50)->default('primary')->after('semester_id')->index();
            }

            if (! Schema::hasColumn('teaching_assignments', 'custom_role')) {
                $table->string('custom_role')->nullable()->after('role');
            }

            if (! Schema::hasColumn('teaching_assignments', 'note')) {
                $table->text('note')->nullable()->after('custom_role');
            }

            if (! Schema::hasColumn('teaching_assignments', 'status')) {
                $table->string('status', 20)->default('active')->after('note')->index();
            }

            if (! Schema::hasColumn('teaching_assignments', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('status');
            }
        });

        if (Schema::hasColumn('teaching_assignments', 'semester_id') && Schema::hasTable('semesters')) {
            DB::table('teaching_assignments')
                ->whereNull('semester_id')
                ->orderBy('school_year_id')
                ->get()
                ->each(function ($assignment) {
                    $semesterId = DB::table('semesters')
                        ->where('school_year_id', $assignment->school_year_id)
                        ->where('status', Semester::STATUS_ACTIVE)
                        ->value('id')
                        ?: DB::table('semesters')
                            ->where('school_year_id', $assignment->school_year_id)
                            ->orderBy('order')
                            ->orderBy('name')
                            ->value('id');

                    if ($semesterId) {
                        DB::table('teaching_assignments')
                            ->where('id', $assignment->id)
                            ->update(['semester_id' => $semesterId]);
                    }
                });
        }

        try {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                $table->dropUnique('teacher_class_subject_unique');
            });
        } catch (Throwable $e) {
            // Existing databases may already have a different index state.
        }

        try {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                $table->unique(
                    ['teacher_id', 'class_id', 'subject_id', 'school_year_id', 'semester_id', 'role', 'custom_role'],
                    'assignment_unique_with_role'
                );
            });
        } catch (Throwable $e) {
            // Avoid blocking deployment if the index already exists.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('teaching_assignments')) {
            return;
        }

        try {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                $table->dropUnique('assignment_unique_with_role');
            });
        } catch (Throwable $e) {
            // Ignore missing index.
        }

        Schema::table('teaching_assignments', function (Blueprint $table) {
            foreach (['archived_at', 'status', 'note', 'custom_role', 'role', 'semester_id'] as $column) {
                if (Schema::hasColumn('teaching_assignments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
