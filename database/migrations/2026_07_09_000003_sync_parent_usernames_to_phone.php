<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parents') || ! Schema::hasTable('users')) {
            return;
        }

        $parents = DB::table('parents')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get(['id', 'phone']);

        foreach ($parents as $parent) {
            $existingUser = DB::table('users')->where('parent_id', $parent->id)->where('role', 'parent')->first();
            $phoneOwner = DB::table('users')->where('username', $parent->phone)->first();

            if ($phoneOwner && (! $existingUser || (string) $phoneOwner->id !== (string) $existingUser->id)) {
                continue;
            }

            if ($existingUser) {
                DB::table('users')
                    ->where('id', $existingUser->id)
                    ->update([
                        'username' => $parent->phone,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            $payload = [
                'username' => $parent->phone,
                'role' => 'parent',
                'parent_id' => $parent->id,
                'password_hash' => Hash::make('12345678'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('users', 'force_change_password')) {
                $payload['force_change_password'] = true;
            }

            DB::table('users')->insert($payload);
        }
    }

    public function down(): void
    {
        // Không tự khôi phục username cũ vì không còn nguồn dữ liệu đáng tin cậy.
    }
};
