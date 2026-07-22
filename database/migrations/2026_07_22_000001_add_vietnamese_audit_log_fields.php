<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('user_id', 50)->nullable()->index();
                $table->string('role', 50)->nullable()->index();
                $table->string('action')->index();
                $table->string('hanh_dong', 80)->nullable()->index();
                $table->string('entity_type')->nullable()->index();
                $table->string('entity_id', 50)->nullable()->index();
                $table->string('module', 120)->nullable()->index();
                $table->text('description')->nullable();
                $table->text('noi_dung_thay_doi')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });

            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'role')) {
                $table->string('role', 50)->nullable()->after('user_id')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'hanh_dong')) {
                $table->string('hanh_dong', 80)->nullable()->after('action')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'module')) {
                $table->string('module', 120)->nullable()->after('entity_id')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'noi_dung_thay_doi')) {
                $table->text('noi_dung_thay_doi')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            foreach (['noi_dung_thay_doi', 'module', 'hanh_dong', 'role'] as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
