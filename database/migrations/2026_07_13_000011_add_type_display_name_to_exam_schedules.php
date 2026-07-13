<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_schedules')) {
            return;
        }

        Schema::table('exam_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_schedules', 'type')) {
                $table->string('type', 50)->nullable()->after('title')->index();
            }

            if (! Schema::hasColumn('exam_schedules', 'display_name')) {
                $table->string('display_name')->nullable()->after('type');
            }
        });

        DB::table('exam_schedules')
            ->select(['id', 'title'])
            ->orderBy('id')
            ->get()
            ->each(function ($schedule) {
                $title = trim((string) $schedule->title);
                $type = match ($title) {
                    'Kiểm tra 15 phút' => 'fifteen_minutes',
                    'Kiểm tra 1 tiết' => 'one_period',
                    'Giữa kỳ', 'Kiểm tra giữa kỳ' => 'midterm',
                    'Cuối kỳ', 'Kiểm tra cuối kỳ' => 'final_test',
                    'Thi học kỳ' => 'final',
                    'Thi lại' => 'retake',
                    'Kiểm tra bù' => 'makeup',
                    default => 'custom',
                };

                $displayName = match ($type) {
                    'fifteen_minutes' => 'Kiểm tra 15 phút',
                    'one_period' => 'Kiểm tra 1 tiết',
                    'midterm' => 'Kiểm tra giữa kỳ',
                    'final_test' => 'Kiểm tra cuối kỳ',
                    'final' => 'Thi học kỳ',
                    'retake' => 'Thi lại',
                    'makeup' => 'Kiểm tra bù',
                    default => $title !== '' && $title !== 'Khác' ? $title : 'Khác',
                };

                DB::table('exam_schedules')
                    ->where('id', $schedule->id)
                    ->update([
                        'type' => $type,
                        'display_name' => $displayName,
                        'title' => $displayName,
                    ]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('exam_schedules')) {
            return;
        }

        Schema::table('exam_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('exam_schedules', 'display_name')) {
                $table->dropColumn('display_name');
            }

            if (Schema::hasColumn('exam_schedules', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
