<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects')) {
            return;
        }

        if (! Schema::hasColumn('subjects', 'code')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->after('id');
            });
        }

        $subjects = DB::table('subjects')
            ->select('id')
            ->orderBy('created_at')
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        foreach ($subjects as $index => $subject) {
            DB::table('subjects')
                ->where('id', $subject->id)
                ->update(['code' => '__SUBJECT_CODE_TMP_' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT)]);
        }

        foreach ($subjects as $index => $subject) {
            DB::table('subjects')
                ->where('id', $subject->id)
                ->update(['code' => 'MH' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)]);
        }

        $hasCodeUnique = ! empty(DB::select("SHOW INDEX FROM `subjects` WHERE Key_name = 'subjects_code_unique'"));

        if (! $hasCodeUnique) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unique('code', 'subjects_code_unique');
            });
        }
    }

    public function down(): void
    {
        // Subject codes are identifiers used by the application UI. The previous
        // free-form codes cannot be restored safely after normalization.
    }
};
