<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'homeroom')
            ->update(['role' => 'teacher']);
    }

    public function down(): void
    {
        // Intentionally left empty. GVCN is now represented by teachers.is_homeroom.
    }
};
