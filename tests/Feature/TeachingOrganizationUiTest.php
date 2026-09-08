<?php

namespace Tests\Feature;

use App\Http\Controllers\ExamScheduleController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\SubstituteTeachingController;
use App\Http\Controllers\SystemRegulationController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\TimetableController;
use App\Models\AttendanceRecord;
use App\Models\ExamSchedule;
use App\Models\Room;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\SubstituteTeaching;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeachingOrganizationUiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        view()->share('errors', new ViewErrorBag());
    }

    public function test_assignment_forms_render_compact_two_column_layout(): void
    {
        $admin = $this->adminUser();
        $assignment = TeachingAssignment::firstOrFail();
        $this->actingAs($admin);

        $createView = app(TeachingAssignmentController::class)->create();
        $this->assertInstanceOf(View::class, $createView);
        $createHtml = $createView->render();
        $this->assertStringContainsString('assignment-form-compact', $createHtml);
        $this->assertStringContainsString('multiple size="5"', $createHtml);
        $this->assertStringContainsString('col-lg-6', $createHtml);

        $editView = app(TeachingAssignmentController::class)->edit($assignment);
        $this->assertInstanceOf(View::class, $editView);
        $editHtml = $editView->render();
        $this->assertStringContainsString('assignment-form-compact', $editHtml);
        $this->assertStringContainsString('multiple size="5"', $editHtml);
        $this->assertStringContainsString('col-lg-6', $editHtml);
    }

    public function test_assignment_detail_modal_shows_saved_note(): void
    {
        $admin = $this->adminUser();
        $assignment = TeachingAssignment::whereNotNull('semester_id')->firstOrFail();
        $semester = Semester::findOrFail($assignment->semester_id);
        $assignment->forceFill(['note' => 'Ghi chú kiểm thử phân công'])->save();
        session([
            'working_school_year_id' => $semester->school_year_id,
            'working_semester_id' => $semester->getKey(),
        ]);
        $this->actingAs($admin);

        $view = app(TeachingAssignmentController::class)->index($this->requestFor('/assignments', [
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
        ], $admin));
        $this->assertInstanceOf(View::class, $view);
        $this->assertStringContainsString('Ghi chú kiểm thử phân công', $view->render());
    }

    public function test_admin_can_render_timetable_by_teacher(): void
    {
        $admin = $this->adminUser();
        $teacher = Teacher::where('work_status', Teacher::STATUS_WORKING)->firstOrFail();
        $semester = $this->activeSemester();
        $this->actingAs($admin);

        $view = app(TimetableController::class)->index($this->requestFor('/timetable', [
            'view_mode' => 'teacher',
            'teacher_id' => $teacher->getKey(),
            'semester_id' => $semester->getKey(),
            'school_year_id' => $semester->school_year_id,
        ], $admin));

        $this->assertInstanceOf(View::class, $view);
        $html = $view->render();
        $this->assertStringContainsString('Thời khóa biểu giáo viên', $html);
        $this->assertStringContainsString($teacher->name, $html);
    }

    public function test_timetable_manage_honors_requested_semester(): void
    {
        $admin = $this->adminUser();
        $semesters = Semester::whereHas('schoolYear', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
        $this->assertGreaterThanOrEqual(1, $semesters->count());
        $semester = $semesters->first();
        $class = \App\Models\SchoolClass::where('school_year_id', $semester->school_year_id)
            ->where('status', \App\Models\SchoolClass::STATUS_ACTIVE)
            ->firstOrFail();

        session([
            'working_school_year_id' => $semester->school_year_id,
            'working_semester_id' => null,
        ]);
        $this->actingAs($admin);

        foreach ($semesters as $requestedSemester) {
            $view = app(TimetableController::class)->manage($this->requestFor('/timetable/manage', [
                'class_id' => $class->getKey(),
                'school_year_id' => $requestedSemester->school_year_id,
                'semester_id' => $requestedSemester->getKey(),
            ], $admin));

            $this->assertInstanceOf(View::class, $view);
            $this->assertSame((string) $requestedSemester->getKey(), (string) $view->getData()['selectedSemesterId']);
            $this->assertSame((string) $requestedSemester->getKey(), (string) $view->getData()['selectedSemester']?->getKey());
        }

        $html = $view->render();
        $this->assertStringContainsString('action="' . route('timetable.manage') . '"', $html);
        $this->assertStringContainsString('id="timetable-manage-semester-select"', $html);
        $this->assertStringContainsString('name="semester_id"', $html);
        $this->assertStringContainsString('new URLSearchParams(new FormData(form))', $html);
        $this->assertStringContainsString('window.location.assign(`${action}?${params.toString()}`)', $html);
        $this->assertLessThan(
            strpos($html, 'id="timetable-resource-data"'),
            strpos($html, 'data-scheduler-filter-script'),
            'The semester/class filter script must render before scheduler drag-drop scripts.'
        );
    }

    public function test_topbar_academic_context_can_switch_current_year_semester(): void
    {
        $admin = $this->adminUser();
        $year = SchoolYear::where('is_active', true)->firstOrFail();
        $semester = Semester::where('school_year_id', $year->getKey())
            ->orderByDesc('name')
            ->firstOrFail();
        $redirectTo = route('timetable.manage', [
            'class_id' => 'demo-class',
            'school_year_id' => $year->getKey(),
            'semester_id' => $semester->getKey(),
        ]);
        $this->actingAs($admin);

        app('session.store')->start();
        $request = Request::create('/academic-context', 'POST', [
            'school_year_id' => $year->getKey(),
            'semester_id' => $semester->getKey(),
            'redirect_to' => $redirectTo,
        ]);
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        $response = app(SchoolYearController::class)->updateWorkingContext($request);

        $this->assertSame((string) $year->getKey(), (string) session('working_school_year_id'));
        $this->assertSame((string) $semester->getKey(), (string) session('working_semester_id'));
        $this->assertStringContainsString('semester_id=' . urlencode((string) $semester->getKey()), $response->getTargetUrl());

        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('data-academic-context-redirect', $layout);
        $this->assertStringContainsString('data-academic-context-select', $layout);
        $this->assertStringContainsString('HTMLFormElement.prototype.submit.call(form)', $layout);
        $this->assertStringNotContainsString('onchange="this.form.submit()"', $layout);
    }

    public function test_timetable_room_override_keeps_valid_assignment_selection(): void
    {
        $admin = $this->adminUser();
        $entry = TimetableEntry::with(['timetable.entries.assignment.subject.periodNorms', 'timetable.semester', 'assignment'])
            ->where('status', TimetableEntry::STATUS_ACTIVE)
            ->whereNotNull('assignment_id')
            ->whereHas('assignment', fn ($query) => $query->where('status', TeachingAssignment::STATUS_ACTIVE))
            ->whereHas('timetable.semester', fn ($query) => $query->where('status', '!=', Semester::STATUS_ARCHIVED))
            ->first();

        if (! $entry) {
            $this->markTestSkipped('No active timetable entry with a teaching assignment is available.');
        }

        $busyRoomIds = TimetableEntry::where('day_of_week', $entry->day_of_week)
            ->where('period', $entry->period)
            ->where('status', TimetableEntry::STATUS_ACTIVE)
            ->whereHas('timetable', function ($query) use ($entry) {
                $query->where('school_year_id', $entry->timetable->school_year_id)
                    ->where('semester_id', $entry->timetable->semester_id);
            })
            ->pluck('room_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->all();

        $room = Room::where('status', Room::STATUS_ACTIVE)
            ->whereNotIn('id', $busyRoomIds)
            ->first();

        if (! $room) {
            $this->markTestSkipped('No free active room is available for the selected timetable slot.');
        }

        $entriesPayload = [];
        foreach ($entry->timetable->entries as $existing) {
            $entryValue = $existing->assignment_id
                ? 'assignment:' . $existing->assignment_id
                : ($existing->subject_id ? 'subject:' . $existing->subject_id : '');

            $entriesPayload[$existing->day_of_week][$existing->period] = [
                'entry_value' => $entryValue,
                'room_id' => $existing->room_id,
                'status' => $existing->status,
            ];
        }

        $entriesPayload[$entry->day_of_week][$entry->period]['room_id'] = $room->getKey();
        $this->actingAs($admin);

        $request = Request::create('/timetable/entries', 'POST', [
            'timetable_id' => $entry->timetable_id,
            'entries' => $entriesPayload,
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        $response = app(TimetableController::class)->saveEntries($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame((string) $room->getKey(), (string) $entry->fresh()->room_id);
        $this->assertSame((string) $entry->assignment_id, (string) $entry->fresh()->assignment_id);
    }

    public function test_exam_schedule_page_uses_assignment_style_filter_dropdown_room_catalog_and_valid_table_markup(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        $view = app(ExamScheduleController::class)->index($this->requestFor('/exam-schedules', [], $admin));
        $this->assertInstanceOf(View::class, $view);
        $html = $view->render();
        $this->assertStringContainsString('data-bs-toggle="dropdown"', $html);
        $this->assertStringContainsString('dropdown-menu dropdown-menu-end p-3', $html);
        $this->assertStringContainsString('d-grid gap-3', $html);
        $this->assertStringNotContainsString('id="examFilterPanel"', $html);
        $this->assertStringContainsString('data-exam-room-select', $html);
        $this->assertStringContainsString('data-exam-class-select', $html);
        $this->assertGreaterThan(
            strpos($html, 'Thêm lịch kiểm tra'),
            strpos($html, 'title="Bộ lọc"'),
            'The filter button must render after the create button.'
        );

        if (ExamSchedule::exists()) {
            $mainTableClose = strpos($html, '</table>');
            $detailModal = strpos($html, '<div class="modal fade content-modal exam-detail-modal');

            $this->assertNotFalse($mainTableClose);
            $this->assertNotFalse($detailModal);
            $this->assertGreaterThan($mainTableClose, $detailModal, 'Exam schedule modals must render after the main table.');
        }
    }

    public function test_fixed_class_room_delete_is_denied(): void
    {
        $room = Room::whereNotNull('fixed_class_id')->first();

        if (! $room) {
            $this->markTestSkipped('No room with a fixed class is available.');
        }

        $this->actingAs($this->adminUser());
        $response = app(RoomController::class)->destroy($room);

        $this->assertTrue($response->getSession()->get('errors')->has('room'));
        $this->assertDatabaseHas('rooms', ['id' => $room->getKey()]);
    }

    public function test_fixed_class_room_edit_form_loads_through_web_request(): void
    {
        $this->withoutExceptionHandling();
        \Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');

        $room = Room::with('fixedClass.schoolYear')->whereNotNull('fixed_class_id')->first();

        if (! $room || ! $room->fixedClass) {
            $this->markTestSkipped('No room with a fixed class is available.');
        }

        $this->actingAs($this->adminUser());

        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/rooms/' . $room->getKey() . '/edit');

        $response->assertOk();
        $response->assertSee('data-room-form', false);
        $response->assertSee('value="' . e($room->fixed_class_id) . '"', false);
        $response->assertSee(e($room->fixedClass->name), false);
    }

    public function test_admin_can_submit_daily_attendance_through_web_route(): void
    {
        $this->withoutExceptionHandling();
        \Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');

        $semester = Semester::where('status', Semester::STATUS_ACTIVE)->first();

        if (! $semester) {
            $this->markTestSkipped('No active semester is available.');
        }

        $class = \App\Models\SchoolClass::where('school_year_id', $semester->school_year_id)
            ->whereHas('students', fn ($query) => $query->where('status', Student::STATUS_STUDYING))
            ->first();

        if (! $class) {
            $this->markTestSkipped('No class with students is available for the active semester.');
        }

        $studentStatuses = Student::where('class_id', $class->getKey())
            ->where('status', Student::STATUS_STUDYING)
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(string) $id => AttendanceRecord::STATUS_PRESENT])
            ->all();

        $this->actingAs($this->adminUser());

        $response = $this->post('/attendance', [
            'school_year_id' => $semester->school_year_id,
            'class_id' => $class->getKey(),
            'semester_id' => $semester->getKey(),
            'attendance_date' => now()->format('Y-m-d'),
            'attendance_type' => AttendanceRecord::SESSION_MORNING,
            'status' => $studentStatuses,
        ]);

        $response->assertStatus(302);
        $this->assertNotSame(403, $response->getStatusCode());
        $response->assertSessionHas('success');
    }

    public function test_period_attendance_uses_timetable_entries_validation_table(): void
    {
        $entry = TimetableEntry::with('timetable.semester', 'timetable.classRoom.students')
            ->where('status', TimetableEntry::STATUS_ACTIVE)
            ->whereHas('timetable.semester', fn ($query) => $query->where('status', Semester::STATUS_ACTIVE))
            ->first();

        if (! $entry || ! $entry->timetable?->classRoom) {
            $this->markTestSkipped('No active timetable entry is available.');
        }

        $students = Student::where('class_id', $entry->timetable->class_id)
            ->where('status', Student::STATUS_STUDYING)
            ->pluck('id');

        if ($students->isEmpty()) {
            $this->markTestSkipped('No students are available in the selected class.');
        }

        $admin = $this->adminUser();
        $this->actingAs($admin);

        $request = Request::create('/attendance', 'POST', [
            'school_year_id' => $entry->timetable->school_year_id,
            'class_id' => $entry->timetable->class_id,
            'semester_id' => $entry->timetable->semester_id,
            'attendance_date' => now()->format('Y-m-d'),
            'attendance_type' => AttendanceRecord::SESSION_PERIOD,
            'timetable_entry_id' => $entry->getKey(),
            'status' => $students->mapWithKeys(fn ($id) => [(string) $id => AttendanceRecord::STATUS_PRESENT])->all(),
        ]);
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        $response = app(AttendanceController::class)->store($request);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_substitute_teaching_rejects_single_date_that_does_not_match_entry_weekday(): void
    {
        $entry = TimetableEntry::with(['timetable', 'assignment.teacher'])
            ->where('status', TimetableEntry::STATUS_ACTIVE)
            ->whereNotNull('assignment_id')
            ->first();

        if (! $entry) {
            $this->markTestSkipped('No active timetable entry with assignment is available.');
        }

        $teacher = Teacher::where('work_status', Teacher::STATUS_WORKING)
            ->whereKeyNot($entry->assignment?->teacher_id)
            ->first();

        if (! $teacher) {
            $this->markTestSkipped('No replacement teacher is available.');
        }

        $wrongDate = now();
        while ((int) $wrongDate->isoWeekday() === (int) $entry->day_of_week) {
            $wrongDate = $wrongDate->addDay();
        }

        $admin = $this->adminUser();
        $this->actingAs($admin);
        $request = Request::create('/substitute-teachings', 'POST', [
            'scope_type' => SubstituteTeaching::SCOPE_PERIOD,
            'substitute_date' => $wrongDate->format('Y-m-d'),
            'timetable_entry_id' => $entry->getKey(),
            'substitute_teacher_id' => $teacher->getKey(),
            'status' => SubstituteTeaching::STATUS_PENDING,
            'note' => '',
        ]);
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        $this->expectException(ValidationException::class);
        app(SubstituteTeachingController::class)->store($request);
    }

    public function test_evaluation_level_delete_requires_explicit_confirmation(): void
    {
        $levels = collect(app(\App\Services\AcademicEvaluationService::class)->levels());

        if ($levels->count() < 2) {
            $this->markTestSkipped('Need at least two academic levels to test delete confirmation.');
        }

        $payload = $levels->take($levels->count() - 1)->map(fn ($level) => [
            'label' => $level['label'],
            'gpa_min' => $level['gpa_min'],
            'subject_min' => $level['subject_min'],
        ])->values()->all();

        $admin = $this->adminUser();
        $this->actingAs($admin);
        $request = Request::create('/system/academic-levels', 'PUT', [
            'academic_levels' => $payload,
            'confirmed_delete' => 0,
        ]);
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        $this->expectException(ValidationException::class);
        app(SystemRegulationController::class)->updateAcademicLevels($request);
    }

    private function adminUser(): User
    {
        return User::where('role', 'admin')
            ->orWhere('role_type', 'admin')
            ->firstOrFail();
    }

    private function requestFor(string $uri, array $query, User $user): Request
    {
        $request = Request::create($uri, 'GET', $query);
        $request->setUserResolver(fn () => $user);
        app()->instance('request', $request);

        return $request;
    }

    private function activeSemester(): Semester
    {
        $year = SchoolYear::where('is_active', true)->first();

        return Semester::when($year, fn ($query) => $query->where('school_year_id', $year->getKey()))
            ->firstOrFail();
    }
}
