<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_cache')) {
            return;
        }

        if (DB::table('system_cache')->exists()) {
            throw new RuntimeException('system_cache still contains data; refusing to drop it during 36-table normalization.');
        }

        Schema::dropIfExists('system_cache');
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_cache')) {
            Schema::create('system_cache', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('record_type', 30)->index();
                $table->string('cache_key')->index();
                $table->mediumText('value')->nullable();
                $table->string('owner')->nullable();
                $table->integer('expiration');
                $table->timestamps();
            });
        }
    }
};
