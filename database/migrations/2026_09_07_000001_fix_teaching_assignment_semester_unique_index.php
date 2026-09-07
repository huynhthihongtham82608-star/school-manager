<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_INDEX = 'teacher_class_subject_unique';
    private const NEW_INDEX = 'teaching_assignments_year_semester_class_subject_unique';

    public function up(): void
    {
        if (! Schema::hasTable('teaching_assignments')) {
            return;
        }

        $this->assertNoDuplicateRows([
            'school_year_id',
            'semester_id',
            'class_id',
            'subject_id',
        ], self::NEW_INDEX);

        if ($this->indexExists(self::OLD_INDEX)) {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                $table->dropUnique(self::OLD_INDEX);
            });
        }

        if (! $this->indexExists(self::NEW_INDEX)) {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                $table->unique(
                    ['school_year_id', 'semester_id', 'class_id', 'subject_id'],
                    self::NEW_INDEX
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('teaching_assignments')) {
            return;
        }

        $this->assertNoDuplicateRows([
            'teacher_id',
            'class_id',
            'subject_id',
            'school_year_id',
        ], self::OLD_INDEX);

        if ($this->indexExists(self::NEW_INDEX)) {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                $table->dropUnique(self::NEW_INDEX);
            });
        }

        if (! $this->indexExists(self::OLD_INDEX)) {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                $table->unique(
                    ['teacher_id', 'class_id', 'subject_id', 'school_year_id'],
                    self::OLD_INDEX
                );
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'teaching_assignments')
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }

    private function assertNoDuplicateRows(array $columns, string $indexName): void
    {
        $select = collect($columns)
            ->map(fn (string $column) => "`{$column}`")
            ->implode(', ');

        $groupBy = $select;

        $duplicate = DB::selectOne("
            SELECT {$select}, COUNT(*) AS total
            FROM `teaching_assignments`
            GROUP BY {$groupBy}
            HAVING total > 1
            LIMIT 1
        ");

        if ($duplicate) {
            throw new RuntimeException("Cannot create {$indexName}: duplicate teaching assignment rows already exist.");
        }
    }
};
