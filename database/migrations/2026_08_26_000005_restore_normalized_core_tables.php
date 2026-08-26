<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->dropRoutingTriggers();
            $this->restoreRbacTables();
            $this->restoreDepartmentSubjectTable();
            $this->restoreScoreTables();
            $this->restoreTimetableEntries();
            $this->pruneUsersCompatibilityColumns();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('This 3NF restoration migration is intentionally irreversible. Restore from backup if rollback is required.');
    }

    private function restoreRbacTables(): void
    {
        if (! Schema::hasTable('rbac_roles')) {
            Schema::create('rbac_roles', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('key', 100)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rbac_permissions')) {
            Schema::create('rbac_permissions', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('key', 120)->unique();
                $table->string('name');
                $table->string('group', 120)->index();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rbac_permission_role')) {
            Schema::create('rbac_permission_role', function (Blueprint $table) {
                $table->string('role_id', 50);
                $table->string('permission_id', 50);
                $table->timestamps();

                $table->primary(['role_id', 'permission_id']);
                $table->foreign('role_id')->references('id')->on('rbac_roles')->cascadeOnDelete();
                $table->foreign('permission_id')->references('id')->on('rbac_permissions')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('rbac_role_user')) {
            Schema::create('rbac_role_user', function (Blueprint $table) {
                $table->string('user_id', 50);
                $table->string('role_id', 50);
                $table->timestamps();

                $table->primary(['user_id', 'role_id']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('role_id')->references('id')->on('rbac_roles')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('rbac_matrix')) {
            DB::statement("
                INSERT IGNORE INTO rbac_permissions (id, `key`, name, `group`, description, created_at, updated_at)
                SELECT id, `key`, name, `group`, description, COALESCE(created_at, NOW()), COALESCE(updated_at, NOW())
                FROM rbac_matrix
                WHERE record_type = 'permission'
            ");

            DB::statement("
                INSERT IGNORE INTO rbac_roles (id, `key`, name, description, is_system, is_active, created_at, updated_at)
                SELECT id, `key`, name, description, is_system, is_active, COALESCE(created_at, NOW()), COALESCE(updated_at, NOW())
                FROM rbac_matrix
                WHERE record_type = 'role'
            ");

            DB::statement("
                INSERT IGNORE INTO rbac_permission_role (role_id, permission_id, created_at, updated_at)
                SELECT rp.role_id, rp.permission_id, COALESCE(rp.created_at, NOW()), COALESCE(rp.updated_at, NOW())
                FROM rbac_matrix rp
                INNER JOIN rbac_roles rr ON rr.id = rp.role_id
                INNER JOIN rbac_permissions p ON p.id = rp.permission_id
                WHERE rp.record_type = 'role_permission'
            ");

            DB::statement("
                INSERT IGNORE INTO rbac_role_user (user_id, role_id, created_at, updated_at)
                SELECT ur.user_id, ur.role_id, COALESCE(ur.created_at, NOW()), COALESCE(ur.updated_at, NOW())
                FROM rbac_matrix ur
                INNER JOIN users u ON u.id = ur.user_id
                INNER JOIN rbac_roles rr ON rr.id = ur.role_id
                WHERE ur.record_type = 'user_role'
            ");

            Schema::dropIfExists('rbac_matrix');
        }
    }

    private function restoreDepartmentSubjectTable(): void
    {
        if (! Schema::hasTable('teacher_department_subject')) {
            Schema::create('teacher_department_subject', function (Blueprint $table) {
                $table->string('department_id', 50);
                $table->string('subject_id', 50);
                $table->timestamps();

                $table->primary(['department_id', 'subject_id'], 'department_subject_primary');
                $table->index('subject_id', 'department_subject_subject_idx');
                $table->foreign('department_id')->references('id')->on('teacher_departments')->cascadeOnDelete();
                $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('teacher_departments', 'department_record_type')) {
            DB::statement("
                INSERT IGNORE INTO teacher_department_subject (department_id, subject_id, created_at, updated_at)
                SELECT td.department_id, td.subject_id, COALESCE(td.created_at, NOW()), COALESCE(td.updated_at, NOW())
                FROM teacher_departments td
                INNER JOIN teacher_departments d ON d.id = td.department_id
                INNER JOIN subjects s ON s.id = td.subject_id
                WHERE td.department_record_type = 'subject'
                  AND td.department_id IS NOT NULL
                  AND td.subject_id IS NOT NULL
            ");

            DB::table('teacher_departments')->where('department_record_type', 'subject')->delete();
            $this->dropColumns('teacher_departments', ['department_record_type', 'department_id', 'subject_id']);
        }
    }

    private function restoreScoreTables(): void
    {
        if (! Schema::hasTable('score_columns')) {
            Schema::create('score_columns', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('school_year_id', 50)->index();
                $table->string('subject_id', 50)->index();
                $table->unsignedTinyInteger('grade_level')->index();
                $table->string('name');
                $table->string('type', 30)->index();
                $table->unsignedTinyInteger('weight_group')->default(1);
                $table->date('input_opens_at')->nullable();
                $table->date('input_closes_at')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                $table->index(['school_year_id', 'subject_id', 'grade_level'], 'score_columns_scope_idx');
            });
        }

        if (! Schema::hasTable('score_settings')) {
            Schema::create('score_settings', function (Blueprint $table) {
                $table->id();
                $table->string('source_id', 50)->nullable()->index();
                $table->unsignedTinyInteger('weight_gdtx')->default(1);
                $table->unsignedTinyInteger('weight_dggk')->default(2);
                $table->unsignedTinyInteger('weight_dgck')->default(3);
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('student_scores', 'score_record_type')) {
            DB::statement("
                INSERT IGNORE INTO score_columns (id, school_year_id, subject_id, grade_level, name, type, weight_group, input_opens_at, input_closes_at, sort_order, is_active, created_at, updated_at)
                SELECT id, school_year_id, subject_id, grade_level,
                       COALESCE(name, score_name, score_type, 'Cột điểm'),
                       COALESCE(type, score_type, 'regular'),
                       COALESCE(weight_group, 1),
                       input_opens_at,
                       input_closes_at,
                       COALESCE(sort_order, 0),
                       COALESCE(is_active, 1),
                       COALESCE(created_at, NOW()),
                       COALESCE(updated_at, NOW())
                FROM student_scores
                WHERE score_record_type = 'column'
                  AND school_year_id IS NOT NULL
                  AND subject_id IS NOT NULL
                  AND grade_level IS NOT NULL
            ");

            $setting = DB::table('student_scores')->where('score_record_type', 'setting')->orderBy('created_at')->first();
            if ($setting && DB::table('score_settings')->doesntExist()) {
                DB::table('score_settings')->insert([
                    'source_id' => (string) $setting->id,
                    'weight_gdtx' => (int) ($setting->weight_gdtx ?: 1),
                    'weight_dggk' => (int) ($setting->weight_dggk ?: 2),
                    'weight_dgck' => (int) ($setting->weight_dgck ?: 3),
                    'created_at' => $setting->created_at ?? now(),
                    'updated_at' => $setting->updated_at ?? now(),
                ]);
            }

            if (DB::table('score_settings')->doesntExist()) {
                DB::table('score_settings')->insert([
                    'source_id' => null,
                    'weight_gdtx' => 1,
                    'weight_dggk' => 2,
                    'weight_dgck' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('student_scores')->whereIn('score_record_type', ['column', 'setting'])->delete();
        }
    }

    private function restoreTimetableEntries(): void
    {
        if (! Schema::hasTable('timetable_entries')) {
            Schema::create('timetable_entries', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('timetable_id', 50)->index();
                $table->string('assignment_id', 50)->nullable()->index();
                $table->unsignedTinyInteger('day_of_week')->index();
                $table->unsignedTinyInteger('period')->index();
                $table->string('subject_id', 50)->nullable()->index();
                $table->string('teacher_id', 50)->nullable()->index();
                $table->string('room', 50)->nullable();
                $table->string('room_id', 50)->nullable()->index();
                $table->string('note')->nullable();
                $table->string('status', 20)->default('active')->index();
                $table->timestamp('archived_at')->nullable();
            });
        }

        if (Schema::hasColumn('timetables', 'timetable_record_type')) {
            DB::statement("
                INSERT IGNORE INTO timetable_entries (id, timetable_id, assignment_id, day_of_week, period, subject_id, teacher_id, room, room_id, note, status, archived_at)
                SELECT e.id, e.timetable_id, e.assignment_id, e.day_of_week, e.period, e.subject_id, e.teacher_id, e.room, e.room_id, e.note, COALESCE(e.status, 'active'), e.archived_at
                FROM timetables e
                INNER JOIN timetables t ON t.id = e.timetable_id
                WHERE e.timetable_record_type = 'entry'
                  AND e.timetable_id IS NOT NULL
                  AND e.day_of_week IS NOT NULL
                  AND e.period IS NOT NULL
            ");

            DB::table('timetables')->where('timetable_record_type', 'entry')->delete();
            $this->dropColumns('timetables', [
                'timetable_record_type',
                'timetable_id',
                'assignment_id',
                'day_of_week',
                'period',
                'subject_id',
                'teacher_id',
                'room',
                'room_id',
                'note',
                'status',
                'archived_at',
            ]);
        }
    }

    private function pruneUsersCompatibilityColumns(): void
    {
        // These compatibility IDs are still read by API payloads and legacy model relations.
        // Keeping them preserves the flat users table without reintroducing students/teachers/parents.
    }

    private function dropRoutingTriggers(): void
    {
        foreach ([
            'rbac_matrix_route_insert',
            'teacher_departments_route_insert',
            'student_scores_route_insert',
            'timetables_route_insert',
        ] as $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS `{$trigger}`");
        }
    }

    private function dropColumns(string $table, array $columns): void
    {
        $columns = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));
        if ($columns === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns) {
            foreach ($columns as $column) {
                $blueprint->dropColumn($column);
            }
        });
    }
};
