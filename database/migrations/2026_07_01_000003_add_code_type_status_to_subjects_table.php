<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects')) {
            return;
        }

        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'code')) {
                $table->string('code', 50)->nullable()->after('id');
            }

            if (! Schema::hasColumn('subjects', 'type')) {
                $table->string('type', 20)->default('required')->after('credit');
            }

            if (! Schema::hasColumn('subjects', 'status')) {
                $table->string('status', 20)->default('active')->after('type');
            }
        });

        $usedCodes = [];

        DB::table('subjects')
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get()
            ->each(function ($subject) use (&$usedCodes) {
                $currentCode = trim((string) $subject->code);

                if ($currentCode !== '') {
                    $usedCodes[$currentCode] = true;
                    return;
                }

                $base = Str::upper(Str::slug((string) $subject->name, '_'));
                $base = preg_replace('/[^A-Z0-9_]/', '', $base) ?: 'MON_HOC';
                $code = Str::limit($base, 45, '');
                $suffix = 1;

                while (isset($usedCodes[$code])) {
                    $nextSuffix = '_' . $suffix++;
                    $code = Str::limit($base, 50 - strlen($nextSuffix), '') . $nextSuffix;
                }

                $usedCodes[$code] = true;

                DB::table('subjects')
                    ->where('id', $subject->id)
                    ->update([
                        'code' => $code,
                        'type' => 'required',
                        'status' => 'active',
                    ]);
            });

        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unique('code', 'subjects_code_unique');
            });
        } catch (Throwable $e) {
            // Existing databases may already have this index.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('subjects')) {
            return;
        }

        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropUnique('subjects_code_unique');
            });
        } catch (Throwable $e) {
            // Ignore missing index on rollback.
        }

        Schema::table('subjects', function (Blueprint $table) {
            foreach (['status', 'type', 'code'] as $column) {
                if (Schema::hasColumn('subjects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
