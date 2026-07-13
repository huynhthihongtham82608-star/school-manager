<?php

use App\Models\ExamSchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exam_schedules')) {
            Schema::table('exam_schedules', function (Blueprint $table) {
                if (! Schema::hasColumn('exam_schedules', 'score_input_opens_at')) {
                    $table->date('score_input_opens_at')->nullable()->after('room');
                }

                if (! Schema::hasColumn('exam_schedules', 'score_input_closes_at')) {
                    $table->date('score_input_closes_at')->nullable()->after('score_input_opens_at');
                }
            });

            DB::table('exam_schedules')->orderBy('id')->get(['id', 'title', 'type', 'display_name'])->each(function ($schedule) {
                $title = trim((string) ($schedule->display_name ?: $schedule->title));
                $type = match ((string) $schedule->type) {
                    'midterm' => ExamSchedule::TYPE_MIDTERM,
                    'final', 'final_test' => ExamSchedule::TYPE_FINAL_TEST,
                    default => ExamSchedule::TYPE_CUSTOM,
                };

                $displayName = match ($type) {
                    ExamSchedule::TYPE_MIDTERM => 'Kiểm tra giữa kỳ',
                    ExamSchedule::TYPE_FINAL_TEST => 'Kiểm tra cuối kỳ',
                    default => $title !== '' && $title !== 'Khác...' && $title !== 'Khác' ? $title : 'Khác',
                };

                DB::table('exam_schedules')->where('id', $schedule->id)->update([
                    'type' => $type,
                    'display_name' => $displayName,
                    'title' => $displayName,
                ]);
            });
        }

        if (Schema::hasTable('score_details')) {
            Schema::table('score_details', function (Blueprint $table) {
                if (! Schema::hasColumn('score_details', 'name')) {
                    $table->string('name')->nullable()->after('type');
                }

                if (! Schema::hasColumn('score_details', 'exam_schedule_id')) {
                    $table->string('exam_schedule_id', 50)->nullable()->after('score_header_id')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('score_details')) {
            Schema::table('score_details', function (Blueprint $table) {
                if (Schema::hasColumn('score_details', 'exam_schedule_id')) {
                    $table->dropColumn('exam_schedule_id');
                }

                if (Schema::hasColumn('score_details', 'name')) {
                    $table->dropColumn('name');
                }
            });
        }

        if (Schema::hasTable('exam_schedules')) {
            Schema::table('exam_schedules', function (Blueprint $table) {
                if (Schema::hasColumn('exam_schedules', 'score_input_closes_at')) {
                    $table->dropColumn('score_input_closes_at');
                }

                if (Schema::hasColumn('exam_schedules', 'score_input_opens_at')) {
                    $table->dropColumn('score_input_opens_at');
                }
            });
        }
    }
};
