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

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'religion')) {
                $table->string('religion', 100)->nullable()->after('ethnicity');
            }
        });

        DB::table('students')
            ->whereNull('religion')
            ->update(['religion' => 'Không']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasColumn('students', 'religion')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('religion');
        });
    }
};
