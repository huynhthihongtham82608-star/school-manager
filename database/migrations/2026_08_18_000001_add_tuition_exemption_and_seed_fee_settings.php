<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tuition_fees') && ! Schema::hasColumn('tuition_fees', 'exemption_type')) {
            Schema::table('tuition_fees', function (Blueprint $table) {
                $table->string('exemption_type', 40)->default('default')->after('payment_method');
            });
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'tuition_fee_items'],
                [
                    'group' => 'tuition_rules',
                    'value' => json_encode([
                        ['key' => 'tuition_hk1', 'label' => 'Học phí', 'amount' => 1200000],
                        ['key' => 'health_insurance', 'label' => 'Bảo hiểm Y tế', 'amount' => 680000],
                        ['key' => 'accident_insurance', 'label' => 'Bảo hiểm Tai nạn', 'amount' => 150000],
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tuition_fees') && Schema::hasColumn('tuition_fees', 'exemption_type')) {
            Schema::table('tuition_fees', function (Blueprint $table) {
                $table->dropColumn('exemption_type');
            });
        }
    }
};
