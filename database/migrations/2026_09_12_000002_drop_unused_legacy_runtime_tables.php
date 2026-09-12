<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove legacy/runtime tables that are no longer used by the current schema.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ([
                'cache_locks',
                'cache',
                'failed_jobs',
                'job_batches',
                'jobs',
                'students',
                'teachers',
            ] as $table) {
                Schema::dropIfExists($table);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Intentionally do not recreate obsolete tables on rollback.
     */
    public function down(): void
    {
        //
    }
};
