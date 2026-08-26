<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('school:consolidate-real', function () {
    foreach ([
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
    ] as $view) {
        DB::statement("DROP VIEW IF EXISTS `{$view}`");
    }

    $this->warn('Running real-data consolidation migrations...');
    $this->call('migrate', ['--force' => true]);
    $this->call('view:clear');
    $this->info('Physical flat schema consolidation completed without demo seed data or compatibility views.');
})->purpose('Migrate real data into physical flat tables and clear compiled Blade views');
