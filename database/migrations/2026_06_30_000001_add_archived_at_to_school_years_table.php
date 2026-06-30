<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('school_years', 'archived_at')) {
            Schema::table('school_years', function (Blueprint $table): void {
                $table->timestamp('archived_at')->nullable()->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('school_years', 'archived_at')) {
            Schema::table('school_years', function (Blueprint $table): void {
                $table->dropColumn('archived_at');
            });
        }
    }
};
