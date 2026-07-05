<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('classes')) {
            return;
        }

        if ($this->indexExists('classes', 'classes_name_unique')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropUnique('classes_name_unique');
            });
        }

        if (
            Schema::hasColumn('classes', 'school_year_id')
            && Schema::hasColumn('classes', 'semester_id')
            && Schema::hasColumn('classes', 'name')
            && ! $this->indexExists('classes', 'classes_year_semester_name_unique')
        ) {
            Schema::table('classes', function (Blueprint $table) {
                $table->unique(['school_year_id', 'semester_id', 'name'], 'classes_year_semester_name_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('classes')) {
            return;
        }

        if ($this->indexExists('classes', 'classes_year_semester_name_unique')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropUnique('classes_year_semester_name_unique');
            });
        }

        if (Schema::hasColumn('classes', 'name') && ! $this->indexExists('classes', 'classes_name_unique')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->unique('name', 'classes_name_unique');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
