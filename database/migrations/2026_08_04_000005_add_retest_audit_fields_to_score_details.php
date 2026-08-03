<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('score_details')) {
            return;
        }

        Schema::table('score_details', function (Blueprint $table) {
            if (! Schema::hasColumn('score_details', 'is_retest')) {
                $table->boolean('is_retest')->default(false)->after('weight_group')->index();
            }

            if (! Schema::hasColumn('score_details', 'original_value')) {
                $table->decimal('original_value', 5, 2)->nullable()->after('is_retest');
            }

            if (! Schema::hasColumn('score_details', 'retest_updated_at')) {
                $table->timestamp('retest_updated_at')->nullable()->after('original_value');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('score_details')) {
            return;
        }

        Schema::table('score_details', function (Blueprint $table) {
            foreach (['retest_updated_at', 'original_value', 'is_retest'] as $column) {
                if (Schema::hasColumn('score_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
