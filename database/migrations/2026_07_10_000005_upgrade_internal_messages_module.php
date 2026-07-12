<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                if (! Schema::hasColumn('messages', 'target_type')) {
                    $table->string('target_type', 50)->default('individual')->after('content');
                }
                if (! Schema::hasColumn('messages', 'recipient_summary')) {
                    $table->string('recipient_summary')->nullable()->after('target_type');
                }
                if (! Schema::hasColumn('messages', 'sender_deleted_at')) {
                    $table->timestamp('sender_deleted_at')->nullable()->after('created_at');
                }
                if (! Schema::hasColumn('messages', 'sender_permanently_deleted_at')) {
                    $table->timestamp('sender_permanently_deleted_at')->nullable()->after('sender_deleted_at');
                }
            });
        }

        if (! Schema::hasTable('message_recipients')) {
            Schema::create('message_recipients', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('message_id', 50);
                $table->string('receiver_user_id', 50);
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamp('permanently_deleted_at')->nullable();
                $table->timestamps();

                $table->unique(['message_id', 'receiver_user_id'], 'message_recipient_unique');
                $table->index('receiver_user_id', 'message_recipients_receiver_idx');
                $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
                $table->foreign('receiver_user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('message_attachments')) {
            Schema::create('message_attachments', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('message_id', 50);
                $table->string('original_name');
                $table->string('path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->timestamps();

                $table->index('message_id', 'message_attachments_message_idx');
                $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('messages', 'receiver_user_id') && Schema::hasTable('message_recipients')) {
            DB::statement("
                INSERT IGNORE INTO message_recipients
                    (id, message_id, receiver_user_id, is_read, read_at, created_at, updated_at)
                SELECT
                    UUID(),
                    id,
                    receiver_user_id,
                    is_read,
                    CASE WHEN is_read = 1 THEN created_at ELSE NULL END,
                    COALESCE(created_at, NOW()),
                    COALESCE(created_at, NOW())
                FROM messages
                WHERE receiver_user_id IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('message_recipients');

        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                foreach (['sender_permanently_deleted_at', 'sender_deleted_at', 'recipient_summary', 'target_type'] as $column) {
                    if (Schema::hasColumn('messages', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
