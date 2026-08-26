<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $sourceTables = [
        'student_class_assignments',
        'student_transfers',
        'subject_grade_mappings',
        'subject_period_norms',
    ];

    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->dropReferencingForeignKeys();
            $this->prepareStudentMovements();
            $this->prepareSubjects();
            $this->mergeStudentMovements();
            $this->mergeSubjectRules();
            $this->optimizeParentAndRbacTables();
            $this->dropSourceTables();
            $this->createRoutingTriggers();
            $this->restoreForeignKeys();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('This final consolidation migration is intentionally irreversible. Restore from backup if rollback is required.');
    }

    private function prepareStudentMovements(): void
    {
        if (! Schema::hasTable('student_movements')) {
            Schema::create('student_movements', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('type', 30)->default('assignment')->index();
                $table->string('source_id', 50)->nullable()->index();
                $table->string('student_id', 50)->index();
                $table->string('class_id', 50)->nullable()->index();
                $table->string('academic_year_id', 50)->nullable()->index();
                $table->string('from_class_id', 50)->nullable()->index();
                $table->string('to_class_id', 50)->nullable()->index();
                $table->date('movement_date')->nullable()->index();
                $table->string('status', 20)->default('active')->index();
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }
    }

    private function prepareSubjects(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            foreach ([
                'subject_record_type' => fn () => $table->string('subject_record_type', 30)->default('subject')->after('id')->index(),
                'source_id' => fn () => $table->string('source_id', 50)->nullable()->after('subject_record_type')->index(),
                'subject_id' => fn () => $table->string('subject_id', 50)->nullable()->after('source_id')->index(),
                'grade_level' => fn () => $table->unsignedTinyInteger('grade_level')->nullable()->after('subject_id')->index(),
                'periods_per_week' => fn () => $table->unsignedTinyInteger('periods_per_week')->nullable()->after('grade_level'),
            ] as $column => $callback) {
                if (! Schema::hasColumn('subjects', $column)) {
                    $callback();
                }
            }
        });

        DB::statement('ALTER TABLE `subjects` MODIFY `name` VARCHAR(255) NULL');
        DB::table('subjects')->whereNull('subject_record_type')->orWhere('subject_record_type', '')->update(['subject_record_type' => 'subject']);
    }

    private function mergeStudentMovements(): void
    {
        if (Schema::hasTable('student_class_assignments')) {
            DB::statement("
                INSERT IGNORE INTO student_movements (id, type, source_id, student_id, class_id, academic_year_id, status, created_at, updated_at)
                SELECT id, 'assignment', id, student_id, class_id, academic_year_id, status, created_at, updated_at
                FROM student_class_assignments
            ");
        }

        if (Schema::hasTable('student_transfers')) {
            DB::statement("
                INSERT IGNORE INTO student_movements (id, type, source_id, student_id, class_id, academic_year_id, from_class_id, to_class_id, movement_date, status, note, created_at, updated_at)
                SELECT st.id, 'transfer', st.id, st.student_id, st.to_class_id, COALESCE(tc.school_year_id, fc.school_year_id), st.from_class_id, st.to_class_id, st.transfer_date, 'active', st.note, NOW(), NOW()
                FROM student_transfers st
                LEFT JOIN classes tc ON tc.id = st.to_class_id
                LEFT JOIN classes fc ON fc.id = st.from_class_id
            ");
        }
    }

    private function mergeSubjectRules(): void
    {
        if (Schema::hasTable('subject_grade_mappings')) {
            DB::statement("
                INSERT IGNORE INTO subjects (id, subject_record_type, source_id, subject_id, grade_level, credit, type, assessment_type, status, created_at, updated_at)
                SELECT UUID(), 'grade_mapping', id, subject_id, grade_level, 1, 'official', 'NONE', 'active', created_at, updated_at
                FROM subject_grade_mappings
            ");
        }

        if (Schema::hasTable('subject_period_norms')) {
            DB::statement("
                INSERT IGNORE INTO subjects (id, subject_record_type, source_id, subject_id, grade_level, periods_per_week, credit, type, assessment_type, status, created_at, updated_at)
                SELECT UUID(), 'period_norm', id, subject_id, grade_level, periods_per_week, 1, 'official', 'NONE', 'active', created_at, updated_at
                FROM subject_period_norms
            ");
        }
    }

    private function optimizeParentAndRbacTables(): void
    {
        $this->addIndexIfMissing('parent_student', ['parent_id', 'student_id'], 'parent_student_pair_idx');
        $this->addIndexIfMissing('parent_student', ['student_id', 'parent_id'], 'parent_student_reverse_pair_idx');
        $this->addIndexIfMissing('rbac_role_user', ['user_id', 'role_id'], 'rbac_role_user_user_role_idx');
        $this->addIndexIfMissing('rbac_permission_role', ['role_id', 'permission_id'], 'rbac_permission_role_pair_idx');
        $this->addIndexIfMissing('rbac_permission_role', ['permission_id', 'role_id'], 'rbac_permission_role_reverse_idx');
    }

    private function dropSourceTables(): void
    {
        foreach ($this->sourceTables as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createRoutingTriggers(): void
    {
        foreach (['student_movements_route_insert', 'subjects_route_child_insert'] as $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS `{$trigger}`");
        }

        DB::unprepared("
            CREATE TRIGGER student_movements_route_insert BEFORE INSERT ON student_movements
            FOR EACH ROW
            BEGIN
                IF NEW.id IS NULL THEN SET NEW.id = UUID(); END IF;
                IF NEW.type IS NULL THEN
                    IF NEW.from_class_id IS NOT NULL OR NEW.to_class_id IS NOT NULL THEN SET NEW.type = 'transfer';
                    ELSE SET NEW.type = 'assignment';
                    END IF;
                END IF;
                IF NEW.class_id IS NULL THEN SET NEW.class_id = NEW.to_class_id; END IF;
                IF NEW.movement_date IS NULL AND NEW.type = 'transfer' THEN SET NEW.movement_date = CURDATE(); END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER subjects_route_child_insert BEFORE INSERT ON subjects
            FOR EACH ROW
            BEGIN
                IF NEW.id IS NULL THEN SET NEW.id = UUID(); END IF;
                IF NEW.subject_record_type IS NULL THEN
                    IF NEW.subject_id IS NOT NULL AND NEW.periods_per_week IS NOT NULL THEN SET NEW.subject_record_type = 'period_norm';
                    ELSEIF NEW.subject_id IS NOT NULL AND NEW.grade_level IS NOT NULL THEN SET NEW.subject_record_type = 'grade_mapping';
                    ELSE SET NEW.subject_record_type = 'subject';
                    END IF;
                END IF;
            END
        ");
    }

    private function dropReferencingForeignKeys(): void
    {
        $tables = $this->sourceTables;
        $placeholders = implode(',', array_fill(0, count($tables), '?'));

        $foreignKeys = DB::select(
            "SELECT DISTINCT k.TABLE_NAME, k.CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE k
             WHERE k.CONSTRAINT_SCHEMA = DATABASE()
               AND k.REFERENCED_TABLE_NAME IN ({$placeholders})",
            $tables
        );

        foreach ($foreignKeys as $foreignKey) {
            DB::statement('ALTER TABLE `' . $foreignKey->TABLE_NAME . '` DROP FOREIGN KEY `' . $foreignKey->CONSTRAINT_NAME . '`');
        }
    }

    private function restoreForeignKeys(): void
    {
        foreach ([
            ['student_movements', 'student_id', 'users', 'id', 'CASCADE'],
            ['student_movements', 'class_id', 'classes', 'id', 'SET NULL'],
            ['student_movements', 'academic_year_id', 'school_years', 'id', 'SET NULL'],
            ['student_movements', 'from_class_id', 'classes', 'id', 'SET NULL'],
            ['student_movements', 'to_class_id', 'classes', 'id', 'SET NULL'],
            ['subjects', 'subject_id', 'subjects', 'id', 'CASCADE'],
        ] as [$table, $column, $referencesTable, $referencesColumn, $deleteRule]) {
            $this->addForeignKey($table, $column, $referencesTable, $referencesColumn, $deleteRule);
        }
    }

    private function addIndexIfMissing(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        $exists = DB::selectOne(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $name]
        );

        if (! $exists) {
            DB::statement('ALTER TABLE `' . $table . '` ADD INDEX `' . $name . '` (`' . implode('`, `', $columns) . '`)');
        }
    }

    private function addForeignKey(string $table, string $column, string $referencesTable, string $referencesColumn, string $onDelete): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $name = substr($table . '_' . $column . '_final_fk', 0, 64);
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $name]
        );

        if (! $exists) {
            DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$column}`) REFERENCES `{$referencesTable}` (`{$referencesColumn}`) ON DELETE {$onDelete}");
        }
    }
};
