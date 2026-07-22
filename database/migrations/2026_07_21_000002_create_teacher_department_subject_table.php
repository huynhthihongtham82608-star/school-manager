<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_department_subject')) {
            Schema::create('teacher_department_subject', function (Blueprint $table) {
                $table->string('department_id', 50);
                $table->string('subject_id', 50);
                $table->timestamps();

                $table->primary(['department_id', 'subject_id'], 'department_subject_primary');
                $table->unique('subject_id', 'department_subject_subject_unique');

                $table->foreign('department_id')
                    ->references('id')
                    ->on('teacher_departments')
                    ->cascadeOnDelete();

                $table->foreign('subject_id')
                    ->references('id')
                    ->on('subjects')
                    ->cascadeOnDelete();
            });
        }

        if (
            Schema::hasTable('teacher_departments')
            && Schema::hasColumn('teacher_departments', 'subject_id')
            && Schema::hasTable('teacher_department_subject')
        ) {
            DB::table('teacher_departments')
                ->whereNotNull('subject_id')
                ->orderBy('code')
                ->get(['id', 'subject_id'])
                ->each(function ($department) {
                    DB::table('teacher_department_subject')->updateOrInsert(
                        ['department_id' => $department->id, 'subject_id' => $department->subject_id],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                });

            Schema::table('teacher_departments', function (Blueprint $table) {
                try {
                    $table->dropForeign('teacher_departments_subject_id_foreign');
                } catch (Throwable) {
                    // Databases created manually may not have this foreign key.
                }

                try {
                    $table->dropUnique('teacher_departments_subject_id_unique');
                } catch (Throwable) {
                    // Ignore when the unique index is absent.
                }

                $table->dropColumn('subject_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teacher_departments') && ! Schema::hasColumn('teacher_departments', 'subject_id')) {
            Schema::table('teacher_departments', function (Blueprint $table) {
                $table->string('subject_id', 50)->nullable()->unique()->after('name');
            });

            Schema::table('teacher_departments', function (Blueprint $table) {
                try {
                    $table->foreign('subject_id')
                        ->references('id')
                        ->on('subjects')
                        ->nullOnDelete();
                } catch (Throwable) {
                    // Ignore databases without foreign key support.
                }
            });
        }

        if (Schema::hasTable('teacher_department_subject') && Schema::hasColumn('teacher_departments', 'subject_id')) {
            DB::table('teacher_department_subject')
                ->orderBy('department_id')
                ->get(['department_id', 'subject_id'])
                ->each(function ($row) {
                    DB::table('teacher_departments')
                        ->where('id', $row->department_id)
                        ->whereNull('subject_id')
                        ->update(['subject_id' => $row->subject_id, 'updated_at' => now()]);
                });
        }

        Schema::dropIfExists('teacher_department_subject');
    }
};
