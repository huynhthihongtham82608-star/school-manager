<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chatbot_messages')) {
            return;
        }

        Schema::table('chatbot_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('chatbot_messages', 'intent')) {
                $table->string('intent', 120)->nullable()->after('answer')->index();
            }

            if (! Schema::hasColumn('chatbot_messages', 'entities')) {
                $table->json('entities')->nullable()->after('intent');
            }

            if (! Schema::hasColumn('chatbot_messages', 'tool_name')) {
                $table->string('tool_name', 120)->nullable()->after('entities')->index();
            }

            if (! Schema::hasColumn('chatbot_messages', 'tool_args')) {
                $table->json('tool_args')->nullable()->after('tool_name');
            }

            if (! Schema::hasColumn('chatbot_messages', 'tool_result_summary')) {
                $table->json('tool_result_summary')->nullable()->after('tool_args');
            }

            if (! Schema::hasColumn('chatbot_messages', 'model')) {
                $table->string('model', 120)->nullable()->after('tool_result_summary');
            }

            if (! Schema::hasColumn('chatbot_messages', 'latency_ms')) {
                $table->unsignedInteger('latency_ms')->nullable()->after('model');
            }

            if (! Schema::hasColumn('chatbot_messages', 'error')) {
                $table->text('error')->nullable()->after('latency_ms');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('chatbot_messages')) {
            return;
        }

        Schema::table('chatbot_messages', function (Blueprint $table) {
            foreach (['error', 'latency_ms', 'model', 'tool_result_summary', 'tool_args', 'tool_name', 'entities', 'intent'] as $column) {
                if (Schema::hasColumn('chatbot_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
