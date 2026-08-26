<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('users') ||
            ! Schema::hasColumn('users', 'password_hash') ||
            ! Schema::hasColumn('users', 'force_change_password')
        ) {
            return;
        }

        DB::table('users')
            ->where('role', 'teacher')
            ->where(function ($query) {
                $query->whereNull('password_hash')
                    ->orWhere('password_hash', '');
            })
            ->update([
                'password_hash' => Hash::make('12345678'),
                'force_change_password' => true,
            ]);
    }

    public function down(): void
    {
        // Password hashes cannot be restored safely.
    }
};
