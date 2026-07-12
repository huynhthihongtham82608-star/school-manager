<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('school_name');
            $table->string('short_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('principal_name')->nullable();
            $table->string('default_school_year_id', 50)->nullable()->index();
            $table->timestamps();

            $table->foreign('default_school_year_id')
                ->references('id')
                ->on('school_years')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
