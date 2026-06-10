<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\ConductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradeWindowController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile routes - accessible to all authenticated users
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/change-password', [ProfileController::class, 'showChangePasswordForm'])->name('profile.change-password');
    Route::post('/profile/change-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');

    Route::middleware('role:admin,staff')->group(function () {
        Route::resource('school-years', SchoolYearController::class)->except(['show']);
        Route::resource('semesters', SemesterController::class)->except(['show']);
        Route::resource('classes', SchoolClassController::class)->except(['show']);
        Route::resource('subjects', SubjectController::class)->except(['show']);
        Route::resource('teachers', TeacherController::class)->except(['show']);
        Route::resource('students', StudentController::class)->except(['show']);
        Route::resource('parents', ParentController::class)->except(['show']);
        Route::resource('assignments', TeachingAssignmentController::class)->except(['show']);
        Route::resource('grade-windows', GradeWindowController::class)->only(['index', 'store', 'update']);
    });

    Route::middleware('role:admin,teacher,homeroom')->group(function () {
        Route::get('scores', [ScoreController::class, 'index'])->name('scores.index');
        Route::get('scores/entry', [ScoreController::class, 'entry'])->name('scores.entry');
        Route::post('scores/entry', [ScoreController::class, 'store'])->name('scores.store');
    });

    Route::middleware('role:admin,homeroom')->group(function () {
        Route::get('conduct', [ConductController::class, 'index'])->name('conduct.index');
        Route::post('conduct', [ConductController::class, 'store'])->name('conduct.store');
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

    Route::get('ai/alerts', [AiController::class, 'alerts'])->name('ai.alerts');
    Route::get('ai/reports', [AiController::class, 'reports'])->name('ai.reports');
    Route::get('ai/run', [AiController::class, 'runForm'])->name('ai.run.form')->middleware('role:admin,homeroom,staff');
    Route::post('ai/run', [AiController::class, 'run'])->name('ai.run')->middleware('role:admin,homeroom,staff');

    Route::get('reports/class-summary', [ReportController::class, 'classSummary'])
        ->middleware('role:admin,teacher,homeroom')
        ->name('reports.class-summary');
});
