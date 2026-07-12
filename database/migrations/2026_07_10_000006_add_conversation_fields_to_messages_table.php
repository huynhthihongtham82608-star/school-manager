<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('messages')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'conversation_id')) {
                $table->string('conversation_id', 50)->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('messages', 'parent_message_id')) {
                $table->string('parent_message_id', 50)->nullable()->after('conversation_id')->index();
            }
        });

        DB::table('messages')
            ->whereNull('conversation_id')
            ->update(['conversation_id' => DB::raw('id')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('messages')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'parent_message_id')) {
                $table->dropColumn('parent_message_id');
            }
            if (Schema::hasColumn('messages', 'conversation_id')) {
                $table->dropColumn('conversation_id');
            }
        });
    }
};
