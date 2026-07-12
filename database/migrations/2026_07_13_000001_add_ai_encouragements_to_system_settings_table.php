<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_settings') || Schema::hasColumn('system_settings', 'ai_encouragements')) {
            return;
        }

        Schema::table('system_settings', function (Blueprint $table) {
            $table->json('ai_encouragements')->nullable()->after('default_school_year_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_settings') || ! Schema::hasColumn('system_settings', 'ai_encouragements')) {
            return;
        }

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn('ai_encouragements');
        });
    }
};
