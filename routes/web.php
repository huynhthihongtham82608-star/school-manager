<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminHomePageController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AcademicController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ConductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamScheduleController;
use App\Http\Controllers\GradeWindowController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LearningDocumentController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ParentLeaveRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RbacRoleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolEventController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\ScoreColumnController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherDepartmentController;
use App\Http\Controllers\TeacherPortalController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingPageController::class, 'index'])->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'no-cache', 'force-password-change', 'history.readonly'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/school-years/history/clear', [SchoolYearController::class, 'clearHistoryMode'])->name('school-years.history.clear');
    Route::post('/academic-context', [SchoolYearController::class, 'updateWorkingContext'])->name('academic-context.update');

    // Profile routes - accessible to all authenticated users
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/change-password', [ProfileController::class, 'showChangePasswordForm'])->name('profile.change-password');
    Route::post('/profile/change-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    Route::post('/parent/select-child', [DashboardController::class, 'selectParentChild'])->name('parent.select-child');

    Route::middleware('role:admin,staff')->group(function () {
        Route::get('academic', [AcademicController::class, 'index'])->name('academic.index');
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
        Route::patch('semesters/{semester}/mark-inactive', [SemesterController::class, 'markInactive'])->name('semesters.mark-inactive');
        Route::patch('semesters/{semester}/activate', [SemesterController::class, 'activate'])->name('semesters.activate');
        Route::patch('semesters/{semester}/lock', [SemesterController::class, 'lock'])->name('semesters.lock');
        Route::patch('semesters/{semester}/archive', [SemesterController::class, 'archive'])->name('semesters.archive');
        Route::resource('semesters', SemesterController::class);
        Route::patch('classes/{class}/activate', [SchoolClassController::class, 'activate'])->name('classes.activate');
        Route::patch('classes/{class}/lock', [SchoolClassController::class, 'lock'])->name('classes.lock');
        Route::patch('classes/{class}/archive', [SchoolClassController::class, 'archive'])->name('classes.archive');
        Route::post('classes/{class}/student-assignments', [SchoolClassController::class, 'updateStudentAssignments'])->name('classes.student-assignments.update');
        Route::resource('classes', SchoolClassController::class)->except(['show']);
        Route::resource('rooms', RoomController::class)->except(['show']);
        Route::resource('subjects', SubjectController::class)->except(['show']);
        Route::resource('departments', TeacherDepartmentController::class)->except(['show']);
        Route::post('teachers/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])->name('teachers.reset-password');
        Route::resource('teachers', TeacherController::class)->except(['show']);
        Route::get('students/import-template', [StudentController::class, 'importTemplate'])->name('students.import-template');
        Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
        Route::post('students/{student}/reset-password', [StudentController::class, 'resetPassword'])->name('students.reset-password');
        Route::resource('students', StudentController::class)->except(['show']);
        Route::post('parents/{parent}/reset-password', [ParentController::class, 'resetPassword'])->name('parents.reset-password');
        Route::resource('parents', ParentController::class)->except(['show']);
        Route::resource('assignments', TeachingAssignmentController::class)->except(['show']);
        Route::resource('score-columns', ScoreColumnController::class)->except(['show', 'create', 'edit']);
        Route::resource('grade-windows', GradeWindowController::class)->only(['index', 'store', 'update']);

        Route::post('exam-schedules', [ExamScheduleController::class, 'store'])->name('exam-schedules.store');
        Route::put('exam-schedules/{examSchedule}', [ExamScheduleController::class, 'update'])->name('exam-schedules.update');
        Route::delete('exam-schedules/{examSchedule}', [ExamScheduleController::class, 'destroy'])->name('exam-schedules.destroy');
        Route::middleware('role:admin,staff')->group(function () {
            Route::get('system/settings', [SystemSettingController::class, 'edit'])->middleware('permission:system.settings')->name('system.settings.edit');
            Route::put('system/settings', [SystemSettingController::class, 'update'])->middleware('permission:system.settings')->name('system.settings.update');
            Route::get('system/backups', [BackupController::class, 'index'])->middleware('permission:backups.manage')->name('system.backups.index');
            Route::post('system/backups', [BackupController::class, 'store'])->middleware('permission:backups.manage')->name('system.backups.store');
            Route::get('system/backups/{filename}', [BackupController::class, 'download'])->middleware('permission:backups.manage')->name('system.backups.download');
            Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit_logs.view')->name('audit-logs.index');

            Route::patch('admin-users/{admin_user}/toggle', [AdminUserController::class, 'toggle'])
                ->middleware('permission:manage_admin_accounts')
                ->name('admin-users.toggle');
            Route::post('admin-users/{admin_user}/reset-password', [AdminUserController::class, 'resetPassword'])
                ->middleware('permission:manage_admin_accounts')
                ->name('admin-users.reset-password');
            Route::resource('admin-users', AdminUserController::class)
                ->parameters(['admin-users' => 'admin_user'])
                ->except(['show', 'create', 'edit'])
                ->middleware('permission:manage_admin_accounts');

            Route::patch('rbac-roles/{rbac_role}/toggle', [RbacRoleController::class, 'toggle'])
                ->middleware('permission:manage_roles')
                ->name('rbac-roles.toggle');
            Route::resource('rbac-roles', RbacRoleController::class)
                ->parameters(['rbac-roles' => 'rbac_role'])
                ->except(['show', 'create', 'edit'])
                ->middleware('permission:manage_roles');
        });
    });

    Route::middleware('role:admin,staff,teacher,student,parent')->group(function () {
        Route::get('scores', [ScoreController::class, 'index'])->name('scores.index');
    });

    Route::middleware('role:admin,staff,teacher')->group(function () {
        Route::get('scores/entry', [ScoreController::class, 'entry'])->middleware('score.assignment')->name('scores.entry');
        Route::post('scores/entry', [ScoreController::class, 'store'])->middleware('score.assignment')->name('scores.store');
    });

    Route::middleware('role:admin,staff,teacher')->group(function () {
        Route::post('documents', [LearningDocumentController::class, 'store'])->name('documents.store');
        Route::put('documents/{document}', [LearningDocumentController::class, 'update'])->name('documents.update');
        Route::delete('documents/{document}', [LearningDocumentController::class, 'destroy'])->name('documents.destroy');
    });

    Route::middleware('role:teacher')->group(function () {
        Route::get('teacher/classes', [TeacherPortalController::class, 'classes'])->name('teacher.classes');
        Route::get('teacher/classes/{class}/students', [TeacherPortalController::class, 'classStudents'])->name('teacher.classes.students');
        Route::get('teacher/department', [TeacherPortalController::class, 'departmentOverview'])->name('teacher.department');
        Route::get('teacher/leave-requests', [ParentLeaveRequestController::class, 'manage'])->name('teacher.leave-requests.index');
        Route::patch('teacher/leave-requests/{leaveRequest}/approve', [ParentLeaveRequestController::class, 'approve'])->name('teacher.leave-requests.approve');
        Route::patch('teacher/leave-requests/{leaveRequest}/reject', [ParentLeaveRequestController::class, 'reject'])->name('teacher.leave-requests.reject');
    });

    Route::middleware('role:admin,staff,homeroom,student,parent')->group(function () {
        Route::get('conduct', [ConductController::class, 'index'])->name('conduct.index');
    });

    Route::middleware('role:admin,staff,homeroom')->group(function () {
        Route::post('conduct', [ConductController::class, 'store'])->name('conduct.store');
    });

    Route::middleware('role:admin,staff,teacher')->group(function () {
        Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    });

    Route::get('timetable', [TimetableController::class, 'index'])->name('timetable.index');
    Route::middleware('role:admin,staff')->group(function () {
        Route::get('timetable/manage', [TimetableController::class, 'manage'])->name('timetable.manage');
        Route::post('timetable/entries', [TimetableController::class, 'saveEntries'])->name('timetable.entries.save');
        Route::post('timetable/clone', [TimetableController::class, 'clone'])->name('timetable.clone');
    });

    Route::get('messages/inbox', [MessageController::class, 'inbox'])->name('messages.inbox');
    Route::get('messages/sent', [MessageController::class, 'sent'])->name('messages.sent');
    Route::get('messages/trash', [MessageController::class, 'trash'])->name('messages.trash');
    Route::get('messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('messages/{message}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    Route::patch('messages/{message}/restore', [MessageController::class, 'restore'])->name('messages.restore');
    Route::delete('messages/{message}/force', [MessageController::class, 'forceDestroy'])->name('messages.force-destroy');
    Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');

    Route::middleware('role:parent')->group(function () {
        Route::get('parent/leave-requests', [ParentLeaveRequestController::class, 'index'])->name('parent.leave-requests.index');
        Route::post('parent/leave-requests', [ParentLeaveRequestController::class, 'store'])->name('parent.leave-requests.store');
    });

    Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('events', [SchoolEventController::class, 'index'])->name('events.index');
    Route::get('documents', [LearningDocumentController::class, 'index'])->name('documents.index');
    Route::get('exam-schedules', [ExamScheduleController::class, 'index'])->name('exam-schedules.index');
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
    Route::post('chatbot', [ChatbotController::class, 'ask'])->name('chatbot.ask');

    Route::get('reports', [ReportController::class, 'classSummary'])
        ->middleware('role:admin,staff,teacher')
        ->name('reports.index');
    Route::get('reports/class-summary', [ReportController::class, 'classSummary'])
        ->middleware('role:admin,staff,teacher')
        ->name('reports.class-summary');
});
