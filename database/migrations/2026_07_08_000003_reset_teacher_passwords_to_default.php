<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'teacher')
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
