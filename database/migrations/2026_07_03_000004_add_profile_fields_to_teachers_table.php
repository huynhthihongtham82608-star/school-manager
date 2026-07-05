<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (! Schema::hasColumn('teachers', 'dob')) {
                $table->date('dob')->nullable()->after('name');
            }

            if (! Schema::hasColumn('teachers', 'gender')) {
                $table->string('gender', 10)->nullable()->after('dob');
            }

            if (! Schema::hasColumn('teachers', 'address')) {
                $table->string('address')->nullable()->after('email');
            }

            if (! Schema::hasColumn('teachers', 'joined_at')) {
                $table->date('joined_at')->nullable()->after('address');
            }

            if (! Schema::hasColumn('teachers', 'work_status')) {
                $table->string('work_status', 30)->default('working')->after('joined_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            foreach (['dob', 'gender', 'address', 'joined_at', 'work_status'] as $column) {
                if (Schema::hasColumn('teachers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
