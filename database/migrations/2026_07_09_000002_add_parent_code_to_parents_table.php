<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parents') || Schema::hasColumn('parents', 'parent_code')) {
            return;
        }

        Schema::table('parents', function (Blueprint $table) {
            $table->string('parent_code', 20)->nullable()->unique()->after('id');
        });

        $parents = DB::table('parents')
            ->whereNull('parent_code')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id']);

        $number = 1;
        foreach ($parents as $parent) {
            DB::table('parents')
                ->where('id', $parent->id)
                ->update(['parent_code' => 'PH' . str_pad((string) $number, 4, '0', STR_PAD_LEFT)]);
            $number++;
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('parents') || ! Schema::hasColumn('parents', 'parent_code')) {
            return;
        }

        Schema::table('parents', function (Blueprint $table) {
            $table->dropUnique('parents_parent_code_unique');
            $table->dropColumn('parent_code');
        });
    }
};
