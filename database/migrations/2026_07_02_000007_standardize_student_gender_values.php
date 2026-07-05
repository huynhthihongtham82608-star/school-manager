<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasColumn('students', 'gender')) {
            return;
        }

        $unsupported = DB::table('students')
            ->whereNull('gender')
            ->orWhereRaw("CAST(`gender` AS CHAR) NOT IN (0x6d616c65, 0x66656d616c65, 'nam', 'nu')")
            ->count();

        if ($unsupported > 0) {
            throw new RuntimeException('Không thể tự chuẩn hóa giới tính vì đang có dữ liệu không xác định.');
        }

        DB::statement("ALTER TABLE `students` MODIFY `gender` VARCHAR(10) NULL DEFAULT NULL");
        DB::statement("UPDATE `students` SET `gender` = CASE CAST(`gender` AS CHAR) WHEN 0x6d616c65 THEN 'nam' WHEN 0x66656d616c65 THEN 'nu' ELSE `gender` END");
        DB::statement("ALTER TABLE `students` MODIFY `gender` ENUM('nam','nu') NOT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasColumn('students', 'gender')) {
            return;
        }

        DB::statement("ALTER TABLE `students` MODIFY `gender` VARCHAR(10) NULL DEFAULT NULL");
    }
};
