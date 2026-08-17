<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('group')->default('system')->index();
                $table->timestamps();
            });
        }

        $defaults = [
            'level_1' => json_encode([
                'key' => 'level_1',
                'label' => 'Tốt',
                'gpa_min' => '8.0',
                'subject_min' => '6.5',
            ], JSON_UNESCAPED_UNICODE),
            'level_2' => json_encode([
                'key' => 'level_2',
                'label' => 'Khá',
                'gpa_min' => '6.5',
                'subject_min' => '5.0',
            ], JSON_UNESCAPED_UNICODE),
            'level_3' => json_encode([
                'key' => 'level_3',
                'label' => 'Đạt',
                'gpa_min' => '5.0',
                'subject_min' => '3.5',
            ], JSON_UNESCAPED_UNICODE),
            'conduct_level_1' => json_encode([
                'key' => 'conduct_level_1',
                'label' => 'Tốt',
                'max_unexcused_absence' => '5',
                'max_period_absence' => '3',
                'max_late' => '5',
            ], JSON_UNESCAPED_UNICODE),
            'conduct_level_2' => json_encode([
                'key' => 'conduct_level_2',
                'label' => 'Khá',
                'max_unexcused_absence' => '8',
                'max_period_absence' => '6',
                'max_late' => '10',
            ], JSON_UNESCAPED_UNICODE),
            'conduct_level_3' => json_encode([
                'key' => 'conduct_level_3',
                'label' => 'Đạt',
                'max_unexcused_absence' => '12',
                'max_period_absence' => '10',
                'max_late' => '15',
            ], JSON_UNESCAPED_UNICODE),
            'academic_excellent_min' => '8.0',
            'academic_excellent_subject_min' => '6.5',
            'academic_good_min' => '6.5',
            'academic_good_subject_min' => '5.0',
            'academic_pass_min' => '5.0',
            'academic_pass_subject_min' => '3.5',
            'conduct_unexcused_absence_limit' => '5',
            'conduct_period_absence_limit' => '3',
            'conduct_late_limit' => '5',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'evaluation_rules',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
