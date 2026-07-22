<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('timetable_entries') || ! Schema::hasColumn('timetable_entries', 'teacher_id')) {
            return;
        }

        $this->dropForeignKeyIfExists('timetable_entries', 'timetable_entries_teacher_fk');

        DB::statement('ALTER TABLE `timetable_entries` MODIFY `teacher_id` varchar(50) NULL');

        DB::statement(
            'ALTER TABLE `timetable_entries`
             ADD CONSTRAINT `timetable_entries_teacher_fk`
             FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('timetable_entries') || ! Schema::hasColumn('timetable_entries', 'teacher_id')) {
            return;
        }

        $fallbackTeacherId = DB::table('teachers')->orderBy('id')->value('id');

        if (! $fallbackTeacherId) {
            return;
        }

        $this->dropForeignKeyIfExists('timetable_entries', 'timetable_entries_teacher_fk');

        DB::table('timetable_entries')->whereNull('teacher_id')->update([
            'teacher_id' => $fallbackTeacherId,
        ]);

        DB::statement('ALTER TABLE `timetable_entries` MODIFY `teacher_id` varchar(50) NOT NULL');

        DB::statement(
            'ALTER TABLE `timetable_entries`
             ADD CONSTRAINT `timetable_entries_teacher_fk`
             FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE'
        );
    }

    private function dropForeignKeyIfExists(string $table, string $constraint): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};
