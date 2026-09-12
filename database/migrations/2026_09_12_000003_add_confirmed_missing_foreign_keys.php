<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $foreignKeys = [
        ['attendance_records', 'class_id', 'classes', 'id', 'fk_att_records_class', 'restrict'],
        ['attendance_records', 'semester_id', 'semesters', 'id', 'fk_att_records_semester', 'restrict'],
        ['attendance_records', 'timetable_entry_id', 'timetable_entries', 'id', 'fk_att_records_timetable_entry', 'set null'],
        ['attendance_records', 'recorded_by', 'users', 'id', 'fk_att_records_recorded_by', 'set null'],
        ['audit_logs', 'user_id', 'users', 'id', 'fk_audit_logs_user', 'set null'],
        ['chatbot_messages', 'user_id', 'users', 'id', 'fk_chatbot_messages_user', 'set null'],
        ['classes', 'semester_id', 'semesters', 'id', 'fk_classes_semester', 'restrict'],
        ['exam_schedules', 'class_id', 'classes', 'id', 'fk_exam_schedules_class', 'restrict'],
        ['exam_schedules', 'subject_id', 'subjects', 'id', 'fk_exam_schedules_subject', 'restrict'],
        ['exam_schedules', 'semester_id', 'semesters', 'id', 'fk_exam_schedules_semester', 'restrict'],
        ['rewards', 'class_id', 'classes', 'id', 'fk_rewards_class', 'restrict'],
        ['rewards', 'semester_id', 'semesters', 'id', 'fk_rewards_semester', 'restrict'],
        ['rewards', 'school_year_id', 'school_years', 'id', 'fk_rewards_school_year', 'restrict'],
        ['rewards', 'approved_by', 'users', 'id', 'fk_rewards_approved_by', 'set null'],
        ['rewards', 'created_by', 'users', 'id', 'fk_rewards_created_by', 'set null'],
        ['rewards', 'updated_by', 'users', 'id', 'fk_rewards_updated_by', 'set null'],
        ['rooms', 'fixed_class_id', 'classes', 'id', 'fk_rooms_fixed_class', 'set null'],
        ['school_posts', 'subject_id', 'subjects', 'id', 'fk_school_posts_subject', 'set null'],
        ['school_posts', 'class_id', 'classes', 'id', 'fk_school_posts_class', 'set null'],
        ['school_posts', 'uploaded_by', 'users', 'id', 'fk_school_posts_uploaded_by', 'set null'],
        ['score_columns', 'school_year_id', 'school_years', 'id', 'fk_score_columns_school_year', 'restrict'],
        ['score_columns', 'subject_id', 'subjects', 'id', 'fk_score_columns_subject', 'restrict'],
        ['student_scores', 'student_id', 'users', 'id', 'fk_student_scores_student', 'restrict'],
        ['student_scores', 'subject_id', 'subjects', 'id', 'fk_student_scores_subject', 'restrict'],
        ['student_scores', 'semester_id', 'semesters', 'id', 'fk_student_scores_semester', 'restrict'],
        ['student_scores', 'school_year_id', 'school_years', 'id', 'fk_student_scores_school_year', 'restrict'],
        ['student_scores', 'class_id', 'classes', 'id', 'fk_student_scores_class', 'restrict'],
        ['student_scores', 'exam_schedule_id', 'exam_schedules', 'id', 'fk_student_scores_exam_schedule', 'restrict'],
        ['student_scores', 'score_column_id', 'score_columns', 'id', 'fk_student_scores_score_column', 'restrict'],
        ['student_scores', 'score_header_id', 'student_scores', 'id', 'fk_student_scores_score_header', 'cascade'],
        ['substitute_teachings', 'timetable_entry_id', 'timetable_entries', 'id', 'fk_sub_teachings_timetable_entry', 'restrict'],
        ['substitute_teachings', 'class_id', 'classes', 'id', 'fk_sub_teachings_class', 'restrict'],
        ['substitute_teachings', 'semester_id', 'semesters', 'id', 'fk_sub_teachings_semester', 'restrict'],
        ['substitute_teachings', 'school_year_id', 'school_years', 'id', 'fk_sub_teachings_school_year', 'restrict'],
        ['substitute_teachings', 'created_by', 'users', 'id', 'fk_sub_teachings_created_by', 'set null'],
        ['substitute_teachings', 'updated_by', 'users', 'id', 'fk_sub_teachings_updated_by', 'set null'],
        ['timetable_entries', 'timetable_id', 'timetables', 'id', 'fk_timetable_entries_timetable', 'cascade'],
        ['timetable_entries', 'assignment_id', 'teaching_assignments', 'id', 'fk_timetable_entries_assignment', 'restrict'],
        ['timetable_entries', 'subject_id', 'subjects', 'id', 'fk_timetable_entries_subject', 'restrict'],
        ['timetable_entries', 'teacher_id', 'users', 'id', 'fk_timetable_entries_teacher', 'set null'],
        ['timetable_entries', 'room_id', 'rooms', 'id', 'fk_timetable_entries_room', 'restrict'],
        ['tuition_fees', 'class_id', 'classes', 'id', 'fk_tuition_fees_class', 'restrict'],
        ['tuition_fees', 'semester_id', 'semesters', 'id', 'fk_tuition_fees_semester', 'restrict'],
        ['tuition_fees', 'school_year_id', 'school_years', 'id', 'fk_tuition_fees_school_year', 'restrict'],
        ['tuition_fees', 'updated_by', 'users', 'id', 'fk_tuition_fees_updated_by', 'set null'],
    ];

    public function up(): void
    {
        foreach ($this->foreignKeys as [$table, $column, $referencesTable, $referencesColumn, $name, $onDelete]) {
            if (! $this->canCreateForeignKey($table, $column, $referencesTable, $referencesColumn, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($column, $referencesTable, $referencesColumn, $name, $onDelete) {
                $foreign = $blueprint->foreign($column, $name)
                    ->references($referencesColumn)
                    ->on($referencesTable)
                    ->cascadeOnUpdate();

                match ($onDelete) {
                    'cascade' => $foreign->cascadeOnDelete(),
                    'set null' => $foreign->nullOnDelete(),
                    default => $foreign->restrictOnDelete(),
                };
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->foreignKeys) as [$table, , , , $name]) {
            if (! Schema::hasTable($table) || ! $this->foreignKeyExists($name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($name) {
                $blueprint->dropForeign($name);
            });
        }
    }

    private function canCreateForeignKey(string $table, string $column, string $referencesTable, string $referencesColumn, string $name): bool
    {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasTable($referencesTable)
            || ! Schema::hasColumn($table, $column)
            || ! Schema::hasColumn($referencesTable, $referencesColumn)
            || $this->foreignKeyExists($name)
            || $this->relationAlreadyExists($table, $column, $referencesTable, $referencesColumn)
        ) {
            return false;
        }

        if (! $this->columnsAreCompatible($table, $column, $referencesTable, $referencesColumn)) {
            $this->warnSkipped("Skip FK {$name}: incompatible column types.");

            return false;
        }

        $orphans = DB::table($table . ' as child')
            ->leftJoin($referencesTable . ' as parent', "parent.{$referencesColumn}", '=', "child.{$column}")
            ->whereNotNull("child.{$column}")
            ->whereNull("parent.{$referencesColumn}")
            ->count();

        if ($orphans > 0) {
            $this->warnSkipped("Skip FK {$name}: {$orphans} orphan row(s).");

            return false;
        }

        return true;
    }

    private function columnsAreCompatible(string $table, string $column, string $referencesTable, string $referencesColumn): bool
    {
        $child = $this->columnMetadata($table, $column);
        $parent = $this->columnMetadata($referencesTable, $referencesColumn);

        if (! $child || ! $parent) {
            return false;
        }

        $childType = (string) $child->DATA_TYPE;
        $parentType = (string) $parent->DATA_TYPE;
        $stringTypes = ['char', 'varchar'];

        if (in_array($childType, $stringTypes, true) && in_array($parentType, $stringTypes, true)) {
            return (string) $child->CHARACTER_SET_NAME === (string) $parent->CHARACTER_SET_NAME
                && (string) $child->COLLATION_NAME === (string) $parent->COLLATION_NAME;
        }

        return $childType === $parentType
            && (string) $child->COLUMN_TYPE === (string) $parent->COLUMN_TYPE;
    }

    private function columnMetadata(string $table, string $column): ?object
    {
        return DB::selectOne(
            "SELECT DATA_TYPE, COLUMN_TYPE, CHARACTER_SET_NAME, COLLATION_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );
    }

    private function foreignKeyExists(string $name): bool
    {
        return (bool) DB::selectOne(
            "SELECT 1
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
             LIMIT 1",
            [$name]
        );
    }

    private function relationAlreadyExists(string $table, string $column, string $referencesTable, string $referencesColumn): bool
    {
        return (bool) DB::selectOne(
            "SELECT 1
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME = ?
               AND REFERENCED_COLUMN_NAME = ?
             LIMIT 1",
            [$table, $column, $referencesTable, $referencesColumn]
        );
    }

    private function warnSkipped(string $message): void
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $message . PHP_EOL);
        }
    }
};
