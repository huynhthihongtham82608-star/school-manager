<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rewards')) {
            return;
        }

        Schema::table('rewards', function (Blueprint $table) {
            if (! Schema::hasColumn('rewards', 'approval_status')) {
                $table->string('approval_status', 20)->default('approved')->index()->after('detail');
            }

            if (! Schema::hasColumn('rewards', 'approved_by')) {
                $table->string('approved_by', 50)->nullable()->index()->after('approval_status');
            }

            if (! Schema::hasColumn('rewards', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        DB::table('rewards')
            ->whereNull('approval_status')
            ->update(['approval_status' => 'approved']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('rewards')) {
            return;
        }

        Schema::table('rewards', function (Blueprint $table) {
            foreach (['approved_at', 'approved_by', 'approval_status'] as $column) {
                if (Schema::hasColumn('rewards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
