<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            if (! Schema::hasColumn('semesters', 'status')) {
                $table->string('status', 20)->default('inactive')->after('is_score_input_open');
            }

            if (! Schema::hasColumn('semesters', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('semesters', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('locked_at');
            }
        });

        if (Schema::hasColumn('semesters', 'status')) {
            DB::table('semesters')
                ->whereNull('status')
                ->orWhere('status', '')
                ->update(['status' => 'inactive']);
        }
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            if (Schema::hasColumn('semesters', 'archived_at')) {
                $table->dropColumn('archived_at');
            }

            if (Schema::hasColumn('semesters', 'locked_at')) {
                $table->dropColumn('locked_at');
            }

            if (Schema::hasColumn('semesters', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
