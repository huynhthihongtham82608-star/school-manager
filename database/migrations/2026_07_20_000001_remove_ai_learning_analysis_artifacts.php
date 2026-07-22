<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ai_alerts');
        Schema::dropIfExists('ai_reports');

        if (Schema::hasTable('system_settings') && Schema::hasColumn('system_settings', 'ai_encouragements')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->dropColumn('ai_encouragements');
            });
        }
    }

    public function down(): void
    {
        //
    }
};
