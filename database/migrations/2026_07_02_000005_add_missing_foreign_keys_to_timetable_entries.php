<?php

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

        if (
            Schema::hasTable('teaching_assignments')
            && Schema::hasColumn('timetable_entries', 'assignment_id')
            && ! $this->hasForeignKey('timetable_entries', 'assignment_id')
        ) {
            $this->assertNoOrphans(
                'assignment_id',
                'teaching_assignments',
                'Không thể thêm foreign key cho timetable_entries.assignment_id vì đang có dữ liệu phân công không tồn tại.'
            );

            Schema::table('timetable_entries', function (Blueprint $table) {
                $table->foreign('assignment_id', 'timetable_entries_assignment_fk')
                    ->references('id')
                    ->on('teaching_assignments')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (
            Schema::hasTable('rooms')
            && Schema::hasColumn('timetable_entries', 'room_id')
            && ! $this->hasForeignKey('timetable_entries', 'room_id')
        ) {
            $this->assertNoOrphans(
                'room_id',
                'rooms',
                'Không thể thêm foreign key cho timetable_entries.room_id vì đang có dữ liệu phòng học không tồn tại.'
            );

            Schema::table('timetable_entries', function (Blueprint $table) {
                $table->foreign('room_id', 'timetable_entries_room_fk')
                    ->references('id')
                    ->on('rooms')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('timetable_entries')) {
            return;
        }

        if ($this->hasForeignKey('timetable_entries', 'assignment_id', 'timetable_entries_assignment_fk')) {
            Schema::table('timetable_entries', function (Blueprint $table) {
                $table->dropForeign('timetable_entries_assignment_fk');
            });
        }

        if ($this->hasForeignKey('timetable_entries', 'room_id', 'timetable_entries_room_fk')) {
            Schema::table('timetable_entries', function (Blueprint $table) {
                $table->dropForeign('timetable_entries_room_fk');
            });
        }
    }

    private function hasForeignKey(string $table, string $column, ?string $constraint = null): bool
    {
        $query = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME');

        if ($constraint !== null) {
            $query->where('CONSTRAINT_NAME', $constraint);
        }

        return $query->exists();
    }

    private function assertNoOrphans(string $column, string $referencedTable, string $message): void
    {
        $orphans = DB::table('timetable_entries as te')
            ->leftJoin($referencedTable.' as ref', 'ref.id', '=', 'te.'.$column)
            ->whereNotNull('te.'.$column)
            ->whereNull('ref.id')
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException($message);
        }
    }
};
