<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminHomePageController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ConductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamScheduleController;
use App\Http\Controllers\GradeWindowController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LearningDocumentController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolEventController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingPageController::class, 'index'])->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'no-cache', 'history.readonly'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/school-years/history/clear', [SchoolYearController::class, 'clearHistoryMode'])->name('school-years.history.clear');

    // Profile routes - accessible to all authenticated users
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/change-password', [ProfileController::class, 'showChangePasswordForm'])->name('profile.change-password');
    Route::post('/profile/change-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');

    Route::middleware('role:admin,staff')->group(function () {
        Route::get('admin/home-page', [AdminHomePageController::class, 'index'])->name('admin.home-page.index');
        Route::post('admin/home-page/content', [AdminHomePageController::class, 'saveContent'])->name('admin.home-page.content');
        Route::post('admin/home-page/posts', [AdminHomePageController::class, 'storePost'])->name('admin.home-page.posts.store');
        Route::post('admin/home-page/events', [AdminHomePageController::class, 'storeEvent'])->name('admin.home-page.events.store');
        Route::post('admin/home-page/documents', [AdminHomePageController::class, 'storeDocument'])->name('admin.home-page.documents.store');
        Route::post('announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::put('announcements/{post}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('announcements/{post}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
        Route::post('events', [SchoolEventController::class, 'store'])->name('events.store');
        Route::put('events/{event}', [SchoolEventController::class, 'update'])->name('events.update');
        Route::delete('events/{event}', [SchoolEventController::class, 'destroy'])->name('events.destroy');

        Route::get('school-years/initialize', [SchoolYearController::class, 'initializeForm'])->name('school-years.initialize.form');
        Route::post('school-years/initialize/preview', [SchoolYearController::class, 'initializePreview'])->name('school-years.initialize.preview');
        Route::post('school-years/initialize', [SchoolYearController::class, 'initializeStore'])->name('school-years.initialize.store');
        Route::patch('school-years/{school_year}/activate', [SchoolYearController::class, 'activate'])->name('school-years.activate');
        Route::patch('school-years/{school_year}/archive', [SchoolYearController::class, 'archive'])->name('school-years.archive');
        Route::get('school-years/{school_year}/detail', [SchoolYearController::class, 'show'])->name('school-years.detail');
        Route::resource('school-years', SchoolYearController::class);
        Route::resource('semesters', SemesterController::class)->except(['show']);
        Route::resource('classes', SchoolClassController::class)->except(['show']);
        Route::resource('subjects', SubjectController::class)->except(['show']);
        Route::resource('teachers', TeacherController::class)->except(['show']);
        Route::resource('students', StudentController::class)->except(['show']);
        Route::resource('parents', ParentController::class)->except(['show']);
        Route::resource('assignments', TeachingAssignmentController::class)->except(['show']);
        Route::resource('grade-windows', GradeWindowController::class)->only(['index', 'store', 'update']);

        Route::post('documents', [LearningDocumentController::class, 'store'])->name('documents.store');
        Route::put('documents/{document}', [LearningDocumentController::class, 'update'])->name('documents.update');
        Route::delete('documents/{document}', [LearningDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('exam-schedules', [ExamScheduleController::class, 'store'])->name('exam-schedules.store');
        Route::put('exam-schedules/{examSchedule}', [ExamScheduleController::class, 'update'])->name('exam-schedules.update');
        Route::delete('exam-schedules/{examSchedule}', [ExamScheduleController::class, 'destroy'])->name('exam-schedules.destroy');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    Route::middleware('role:admin,teacher,homeroom')->group(function () {
        Route::get('scores', [ScoreController::class, 'index'])->name('scores.index');
        Route::get('scores/entry', [ScoreController::class, 'entry'])->name('scores.entry');
        Route::post('scores/entry', [ScoreController::class, 'store'])->name('scores.store');
    });

    Route::middleware('role:admin,homeroom')->group(function () {
        Route::get('conduct', [ConductController::class, 'index'])->name('conduct.index');
        Route::post('conduct', [ConductController::class, 'store'])->name('conduct.store');
        Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    });

    Route::get('timetable', [TimetableController::class, 'index'])->name('timetable.index');
    Route::middleware('role:admin,staff')->group(function () {
        Route::get('timetable/manage', [TimetableController::class, 'manage'])->name('timetable.manage');
        Route::post('timetable/entries', [TimetableController::class, 'saveEntries'])->name('timetable.entries.save');
    });

    Route::get('messages/inbox', [MessageController::class, 'inbox'])->name('messages.inbox');
    Route::get('messages/sent', [MessageController::class, 'sent'])->name('messages.sent');
    Route::get('messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');

    Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('events', [SchoolEventController::class, 'index'])->name('events.index');
    Route::get('documents', [LearningDocumentController::class, 'index'])->name('documents.index');
    Route::get('exam-schedules', [ExamScheduleController::class, 'index'])->name('exam-schedules.index');
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
    Route::post('chatbot', [ChatbotController::class, 'ask'])->name('chatbot.ask');

    Route::get('ai/alerts', [AiController::class, 'alerts'])->name('ai.alerts');
    Route::get('ai/reports', [AiController::class, 'reports'])->name('ai.reports');
    Route::get('ai/run', [AiController::class, 'runForm'])->name('ai.run.form')->middleware('role:admin,homeroom,staff');
    Route::post('ai/run', [AiController::class, 'run'])->name('ai.run')->middleware('role:admin,homeroom,staff');

    Route::get('reports/class-summary', [ReportController::class, 'classSummary'])
        ->middleware('role:admin,teacher,homeroom')
        ->name('reports.class-summary');
});
