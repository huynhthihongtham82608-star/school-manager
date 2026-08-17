<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tuition_fees')) {
            Schema::table('tuition_fees', function (Blueprint $table) {
                if (! Schema::hasColumn('tuition_fees', 'fee_items')) {
                    $table->json('fee_items')->nullable()->after('amount');
                }

                if (! Schema::hasColumn('tuition_fees', 'payment_method')) {
                    $table->string('payment_method', 30)->nullable()->after('status');
                }
            });
        }

        if (Schema::hasTable('substitute_teachings')) {
            Schema::table('substitute_teachings', function (Blueprint $table) {
                if (! Schema::hasColumn('substitute_teachings', 'scope_type')) {
                    $table->string('scope_type', 30)->default('period')->after('substitute_date');
                }

                if (! Schema::hasColumn('substitute_teachings', 'from_date')) {
                    $table->date('from_date')->nullable()->after('scope_type');
                }

                if (! Schema::hasColumn('substitute_teachings', 'to_date')) {
                    $table->date('to_date')->nullable()->after('from_date');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tuition_fees')) {
            Schema::table('tuition_fees', function (Blueprint $table) {
                if (Schema::hasColumn('tuition_fees', 'payment_method')) {
                    $table->dropColumn('payment_method');
                }

                if (Schema::hasColumn('tuition_fees', 'fee_items')) {
                    $table->dropColumn('fee_items');
                }
            });
        }

        if (Schema::hasTable('substitute_teachings')) {
            Schema::table('substitute_teachings', function (Blueprint $table) {
                if (Schema::hasColumn('substitute_teachings', 'to_date')) {
                    $table->dropColumn('to_date');
                }

                if (Schema::hasColumn('substitute_teachings', 'from_date')) {
                    $table->dropColumn('from_date');
                }

                if (Schema::hasColumn('substitute_teachings', 'scope_type')) {
                    $table->dropColumn('scope_type');
                }
            });
        }
    }
};
