<?php

use App\Models\Subject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects') || ! Schema::hasColumn('subjects', 'type')) {
            return;
        }

        DB::table('subjects')
            ->whereIn('type', [
                Subject::TYPE_REQUIRED,
                Subject::TYPE_ELECTIVE,
                Subject::TYPE_REMEDIAL,
            ])
            ->update(['type' => Subject::TYPE_OFFICIAL]);

        DB::table('subjects')
            ->where(function ($query) {
                $query->whereNull('type')->orWhere('type', '');
            })
            ->update(['type' => Subject::TYPE_OFFICIAL]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('subjects') || ! Schema::hasColumn('subjects', 'type')) {
            return;
        }

        DB::table('subjects')
            ->where('type', Subject::TYPE_OFFICIAL)
            ->update(['type' => Subject::TYPE_REQUIRED]);
    }
};
