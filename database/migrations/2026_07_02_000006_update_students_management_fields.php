<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        DB::statement("ALTER TABLE `students` MODIFY `status` VARCHAR(30) NOT NULL DEFAULT 'studying'");

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'place_of_birth')) {
                $table->string('place_of_birth')->nullable()->after('address');
            }

            if (! Schema::hasColumn('students', 'ethnicity')) {
                $table->string('ethnicity', 100)->nullable()->after('place_of_birth');
            }

            if (! Schema::hasColumn('students', 'enrollment_date')) {
                $table->date('enrollment_date')->nullable()->after('email');
            }

            if (! Schema::hasColumn('students', 'avatar')) {
                $table->string('avatar')->nullable()->after('enrollment_date');
            }

            if (! Schema::hasColumn('students', 'note')) {
                $table->text('note')->nullable()->after('avatar');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            foreach (['note', 'avatar', 'enrollment_date', 'ethnicity', 'place_of_birth'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        DB::table('students')
            ->whereNotIn('status', ['studying', 'inactive', 'graduated'])
            ->update(['status' => 'studying']);

        DB::statement("ALTER TABLE `students` MODIFY `status` ENUM('studying','inactive','graduated') NOT NULL DEFAULT 'studying'");
    }
};
