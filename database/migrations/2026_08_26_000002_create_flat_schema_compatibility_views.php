<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $legacyViews = [
        'students',
        'teachers',
        'parents',
        'score_headers',
        'score_details',
        'score_columns',
        'grade_windows',
        'score_settings',
        'school_events',
        'learning_documents',
        'home_page_contents',
        'settings',
        'message_recipients',
        'message_attachments',
        'timetable_entries',
        'teacher_department_subject',
    ];

    public function up(): void
    {
        $this->dropLegacyViews();
    }

    public function down(): void
    {
        $this->dropLegacyViews();
    }

    private function dropLegacyViews(): void
    {
        foreach ($this->legacyViews as $view) {
            DB::statement("DROP VIEW IF EXISTS `{$view}`");
        }
    }
};
