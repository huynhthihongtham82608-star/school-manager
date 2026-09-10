<?php

namespace Tests\Feature;

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\KhenThuongController;
use App\Http\Controllers\ParentLeaveRequestController;
use App\Http\Controllers\ScoreController;
use App\Models\AttendanceRecord;
use App\Models\Conduct;
use App\Models\ParentLeaveRequest;
use App\Models\Reward;
use App\Models\SchoolClass;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\User;
use App\Services\AcademicEvaluationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Tests\TestCase;

class ResultManagementBatchTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        view()->share('errors', new ViewErrorBag());
    }

    public function test_attendance_page_renders_compact_filter_dropdown_without_changing_existing_controls(): void
    {
        $admin = $this->adminUser();
        $semester = $this->semesterWithClass();
        $class = SchoolClass::where('school_year_id', $semester->school_year_id)->firstOrFail();

        session([
            'working_school_year_id' => $semester->school_year_id,
            'working_semester_id' => $semester->getKey(),
        ]);
        $this->actingAs($admin);

        $view = app(AttendanceController::class)->index($this->requestFor('/attendance', [
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
            'class_id' => $class->getKey(),
        ], $admin));

        $this->assertInstanceOf(View::class, $view);
        $html = $view->render();

        $this->assertStringContainsString('attendance-filter-dropdown', $html);
        $this->assertStringContainsString('data-attendance-filter-fields', $html);
        $this->assertStringContainsString('attendance-filter-actions', $html);
        $this->assertStringContainsString('attendance-filter-button', $html);
        $this->assertStringContainsString('.semester-filter, .class-filter, .session-filter, .period', $html);
        $this->assertStringContainsString('[data-attendance-search-text]', $html);
        $this->assertStringContainsString('Áp dụng', $html);
    }

    public function test_attendance_page_blocks_auto_table_search_toolbar_inside_attendance_summary_cards(): void
    {
        $admin = $this->adminUser();
        $semester = $this->semesterWithClass();
        $this->actingAs($admin);

        $view = app(AttendanceController::class)->index($this->requestFor('/attendance', [
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
            'attendance_view' => 'week',
        ], $admin));

        $html = $view->render();

        $this->assertStringContainsString('[data-attendance-card-no-toolbar] > .admin-table-tools', $html);
        $this->assertStringContainsString('attendance-weekly-title', $html);
        $this->assertStringContainsString('toolbar.remove()', $html);
    }

    public function test_score_admin_page_renders_compact_filter_clear_sort_icons_and_zero_safe_cells(): void
    {
        $admin = $this->adminUser();
        $semester = $this->semesterWithClass();

        session([
            'working_school_year_id' => $semester->school_year_id,
            'working_semester_id' => $semester->getKey(),
        ]);
        $this->actingAs($admin);

        $view = app(ScoreController::class)->index($this->requestFor('/scores', [
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
        ], $admin));

        $this->assertInstanceOf(View::class, $view);
        $html = $view->render();

        $this->assertStringContainsString('admin-score-filter-menu', $html);
        $this->assertStringContainsString('admin-score-filter-dropdown', $html);
        $this->assertStringContainsString('admin-score-filter-trigger', $html);
        $this->assertStringContainsString('admin-score-filter-actions', $html);
        $this->assertStringContainsString('data-admin-score-apply', $html);
        $this->assertStringContainsString('data-bs-toggle="dropdown"', $html);
        $this->assertStringContainsString('↕', $html);
        $this->assertStringContainsString("const hasValue = value !== null && value !== undefined && value !== ''", $html);
        $this->assertStringContainsString("controls.subject.value = ''", $html);
        $this->assertStringNotContainsString("iconSpan.textContent = '?'", $html);
    }

    public function test_score_admin_matrix_keeps_selected_numeric_and_assessment_subjects(): void
    {
        $admin = $this->adminUser();
        $semester = $this->semesterWithClass();
        $class = SchoolClass::where('school_year_id', $semester->school_year_id)
            ->get()
            ->first(fn (SchoolClass $candidate) => $this->requiredNumericSubjectsForClass($candidate)->isNotEmpty()
                && $this->assessmentSubjectsForClass($candidate)->isNotEmpty());

        if (! $class) {
            $this->markTestSkipped('No class with both numeric and assessment subjects is available.');
        }

        $numericSubject = $this->requiredNumericSubjectsForClass($class)->first();
        $assessmentSubject = $this->assessmentSubjectsForClass($class)->first();
        $this->actingAs($admin);

        $numericPayload = app(ScoreController::class)->adminScoreMatrixPayload($this->requestFor('/scores/admin-matrix', [
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
            'grade_level' => $class->grade_level,
            'class_id' => $class->getKey(),
            'subject_id' => $numericSubject->getKey(),
            'hinh_thuc_danh_gia' => Subject::ASSESSMENT_GRADE_10,
        ], $admin));

        $assessmentPayload = app(ScoreController::class)->adminScoreMatrixPayload($this->requestFor('/scores/admin-matrix', [
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
            'grade_level' => $class->grade_level,
            'class_id' => $class->getKey(),
            'subject_id' => $assessmentSubject->getKey(),
            'hinh_thuc_danh_gia' => Subject::ASSESSMENT_ASSESSMENT,
        ], $admin));

        $this->assertSame('subject_details', $numericPayload['mode']);
        $this->assertSame((string) $numericSubject->getKey(), (string) $numericPayload['filters']['subject_id']);
        $this->assertSame('subject_details', $assessmentPayload['mode']);
        $this->assertSame((string) $assessmentSubject->getKey(), (string) $assessmentPayload['filters']['subject_id']);
        $this->assertContains(Subject::ASSESSMENT_ASSESSMENT, collect($assessmentPayload['subjects'])->pluck('assessment_type')->all());
    }

    public function test_period_attendance_absence_is_not_counted_as_daily_absence(): void
    {
        $admin = $this->adminUser();
        $entry = TimetableEntry::with('timetable.classRoom.students', 'timetable.semester')
            ->where('status', 'active')
            ->whereHas('timetable.classRoom.students')
            ->first();

        if (! $entry || ! $entry->timetable?->classRoom || ! $entry->timetable?->semester) {
            $this->markTestSkipped('No timetable entry with class and semester is available.');
        }

        $student = $entry->timetable->classRoom->students()->first();
        if (! $student) {
            $this->markTestSkipped('No student is available for the timetable class.');
        }

        $date = now()->toDateString();
        AttendanceRecord::create([
            'student_id' => $student->getKey(),
            'class_id' => $entry->timetable->class_id,
            'semester_id' => $entry->timetable->semester_id,
            'attendance_date' => $date,
            'session_type' => AttendanceRecord::SESSION_PERIOD,
            'session_key' => 'period:' . $entry->getKey(),
            'timetable_entry_id' => $entry->getKey(),
            'session_label' => 'Period test',
            'session_order' => $entry->periodInSession(),
            'status' => AttendanceRecord::STATUS_ABSENT,
            'recorded_by' => $admin->getKey(),
        ]);

        $controller = app(AttendanceController::class);
        $method = new \ReflectionMethod($controller, 'adminAttendanceMatrix');
        $method->setAccessible(true);
        $matrix = $method->invoke($controller, collect([$entry->timetable->classRoom]), $entry->timetable->semester_id, $date);
        $row = $matrix->first();

        $this->assertSame(0, $row->daily_unexcused_absent);
        $this->assertSame(1, $row->period_absent);
    }

    public function test_main_session_attendance_requires_real_timetable_session(): void
    {
        $admin = $this->adminUser();
        $semester = Semester::where('status', Semester::STATUS_ACTIVE)
            ->whereHas('schoolYear')
            ->first();

        if (! $semester) {
            $this->markTestSkipped('No active semester is available.');
        }

        $class = SchoolClass::create([
            'name' => 'TST Morning Only ' . Str::upper(Str::random(5)),
            'grade_level' => 10,
            'cohort' => '2026-2029',
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);
        $student = $this->createStudent($class, 'MORN');
        $subject = $this->requiredNumericSubjectsForClass($class)->first()
            ?: Subject::where('status', Subject::STATUS_ACTIVE)->first();

        if (! $subject) {
            $this->markTestSkipped('No subject is available.');
        }

        $date = Carbon::parse('2026-09-14');
        $timetable = Timetable::create([
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
            'class_id' => $class->getKey(),
            'week_start' => $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'week_end' => $date->copy()->startOfWeek(Carbon::MONDAY)->addDays(5)->toDateString(),
        ]);
        TimetableEntry::create([
            'timetable_id' => $timetable->getKey(),
            'day_of_week' => $date->isoWeekday(),
            'period' => 1,
            'subject_id' => $subject->getKey(),
            'status' => TimetableEntry::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin);

        try {
            app(AttendanceController::class)->store(
                $this->attendanceStoreRequest($admin, $semester, $class, $date, AttendanceRecord::SESSION_AFTERNOON, $student)
            );
            $this->fail('Afternoon attendance should be blocked when the class has no afternoon timetable.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attendance_type', $exception->errors());
        }

        $this->assertDatabaseMissing('attendance_records', [
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'semester_id' => $semester->getKey(),
            'attendance_date' => $date->toDateString(),
            'session_type' => AttendanceRecord::SESSION_AFTERNOON,
        ]);

        $morningResponse = app(AttendanceController::class)->store(
            $this->attendanceStoreRequest($admin, $semester, $class, $date, AttendanceRecord::SESSION_MORNING, $student)
        );

        $this->assertTrue(method_exists($morningResponse, 'getStatusCode'));
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'semester_id' => $semester->getKey(),
            'attendance_date' => $date->toDateString(),
            'session_type' => AttendanceRecord::SESSION_MORNING,
        ]);
    }

    public function test_day_view_keeps_unscheduled_afternoon_visible_without_opening_required_attendance(): void
    {
        $admin = $this->adminUser();
        $semester = $this->activeSemester();
        $date = Carbon::parse('2026-09-14');
        [$class, $student] = $this->createScheduledClass($semester, $date, [1]);

        $this->actingAs($admin);
        $view = app(AttendanceController::class)->index($this->requestFor('/attendance', [
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
            'class_id' => $class->getKey(),
            'date' => $date->toDateString(),
            'attendance_view' => 'day',
            'attendance_type' => AttendanceRecord::SESSION_AFTERNOON,
        ], $admin));

        $html = $view->render();

        $this->assertStringContainsString('Không có lịch học trong buổi đã chọn', $html);
        $this->assertStringContainsString('value="afternoon" selected', $html);
        $this->assertStringNotContainsString('id="attendance-register"', $html);
        $this->assertDatabaseMissing('attendance_records', [
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'attendance_date' => $date->toDateString(),
            'session_type' => AttendanceRecord::SESSION_AFTERNOON,
        ]);
    }

    public function test_morning_and_afternoon_sessions_are_stored_independently_when_both_are_scheduled(): void
    {
        $admin = $this->adminUser();
        $semester = $this->activeSemester();
        $date = Carbon::parse('2026-09-14');
        [$class, $student] = $this->createScheduledClass($semester, $date, [1, 7]);
        $this->actingAs($admin);

        app(AttendanceController::class)->store(
            $this->attendanceStoreRequest($admin, $semester, $class, $date, AttendanceRecord::SESSION_MORNING, $student, AttendanceRecord::STATUS_ABSENT)
        );
        app(AttendanceController::class)->store(
            $this->attendanceStoreRequest($admin, $semester, $class, $date, AttendanceRecord::SESSION_AFTERNOON, $student, AttendanceRecord::STATUS_LATE)
        );

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->getKey(),
            'attendance_date' => $date->toDateString(),
            'session_key' => AttendanceRecord::SESSION_MORNING,
            'status' => AttendanceRecord::STATUS_ABSENT,
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->getKey(),
            'attendance_date' => $date->toDateString(),
            'session_key' => AttendanceRecord::SESSION_AFTERNOON,
            'status' => AttendanceRecord::STATUS_LATE,
        ]);
    }

    public function test_weekly_matrix_does_not_promote_one_period_absence_to_full_session_or_double_count_main_session_absence(): void
    {
        $admin = $this->adminUser();
        $semester = $this->activeSemester();
        $date = Carbon::parse('2026-09-14');
        [$class, $student, $timetable, $entries] = $this->createScheduledClass($semester, $date, [1, 2, 7]);

        AttendanceRecord::create([
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'semester_id' => $semester->getKey(),
            'attendance_date' => $date->toDateString(),
            'session_type' => AttendanceRecord::SESSION_PERIOD,
            'session_key' => 'period:' . $entries[1]->getKey(),
            'timetable_entry_id' => $entries[1]->getKey(),
            'session_label' => 'Tiết 1',
            'session_order' => 1,
            'status' => AttendanceRecord::STATUS_ABSENT,
            'recorded_by' => $admin->getKey(),
        ]);

        $periodOnlyCell = $this->weeklyCell($admin, $class, $semester, $date, $student);
        $this->assertSame(['V1'], collect($periodOnlyCell['markers'])->all());

        AttendanceRecord::create([
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'semester_id' => $semester->getKey(),
            'attendance_date' => $date->toDateString(),
            'session_type' => AttendanceRecord::SESSION_MORNING,
            'session_key' => AttendanceRecord::SESSION_MORNING,
            'session_label' => 'Điểm danh Buổi Sáng',
            'session_order' => 1,
            'status' => AttendanceRecord::STATUS_ABSENT,
            'recorded_by' => $admin->getKey(),
        ]);

        $morningCell = $this->weeklyCell($admin, $class, $semester, $date, $student);
        $this->assertSame(['S'], collect($morningCell['markers'])->all());

        AttendanceRecord::create([
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'semester_id' => $semester->getKey(),
            'attendance_date' => $date->toDateString(),
            'session_type' => AttendanceRecord::SESSION_AFTERNOON,
            'session_key' => AttendanceRecord::SESSION_AFTERNOON,
            'session_label' => 'Điểm danh Buổi Chiều',
            'session_order' => 2,
            'status' => AttendanceRecord::STATUS_ABSENT,
            'recorded_by' => $admin->getKey(),
        ]);

        $fullDayCell = $this->weeklyCell($admin, $class, $semester, $date, $student);
        $this->assertSame(['S', 'C'], collect($fullDayCell['markers'])->all());
    }

    public function test_attendance_session_log_keeps_morning_afternoon_and_period_sessions_separate(): void
    {
        $admin = $this->adminUser();
        $semester = $this->activeSemester();
        $date = Carbon::parse('2026-09-14');
        [$class, $student, $timetable, $entries] = $this->createScheduledClass($semester, $date, [1, 7]);

        foreach ([AttendanceRecord::SESSION_MORNING, AttendanceRecord::SESSION_AFTERNOON] as $sessionType) {
            AttendanceRecord::create([
                'student_id' => $student->getKey(),
                'class_id' => $class->getKey(),
                'semester_id' => $semester->getKey(),
                'attendance_date' => $date->toDateString(),
                'session_type' => $sessionType,
                'session_key' => $sessionType,
                'session_label' => AttendanceRecord::SESSION_TYPES[$sessionType],
                'session_order' => $sessionType === AttendanceRecord::SESSION_AFTERNOON ? 2 : 1,
                'status' => AttendanceRecord::STATUS_PRESENT,
                'recorded_by' => $admin->getKey(),
            ]);
        }

        AttendanceRecord::create([
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'semester_id' => $semester->getKey(),
            'attendance_date' => $date->toDateString(),
            'session_type' => AttendanceRecord::SESSION_PERIOD,
            'session_key' => 'period:' . $entries[1]->getKey(),
            'timetable_entry_id' => $entries[1]->getKey(),
            'session_label' => 'Tiết 1',
            'session_order' => 1,
            'status' => AttendanceRecord::STATUS_ABSENT,
            'recorded_by' => $admin->getKey(),
        ]);

        $sessions = $this->attendanceSessionsFor($admin, $class, $semester, $date);

        $this->assertCount(3, $sessions);
        $this->assertTrue($sessions->contains(fn ($session) => $session->session_type === AttendanceRecord::SESSION_MORNING));
        $this->assertTrue($sessions->contains(fn ($session) => $session->session_type === AttendanceRecord::SESSION_AFTERNOON));
        $this->assertTrue($sessions->contains(fn ($session) => $session->session_type === AttendanceRecord::SESSION_PERIOD));
    }

    public function test_approved_parent_leave_syncs_only_scheduled_sessions_without_duplicate_records(): void
    {
        $semester = $this->activeSemester();
        $date = Carbon::parse('2026-09-14');
        $homeroomUser = $this->homeroomUser();
        [$class, $student] = $this->createScheduledClass($semester, $date, [1], $homeroomUser->teacher);
        $parent = $this->parentUser();
        $leaveRequest = ParentLeaveRequest::create([
            'parent_id' => $parent->getKey(),
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'leave_date' => $date->toDateString(),
            'reason' => 'Nghỉ ốm',
            'status' => ParentLeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($homeroomUser);
        $request = Request::create('/teacher/leave-requests/' . $leaveRequest->getKey() . '/approve', 'PATCH', [
            'homeroom_note' => 'Đã xác nhận',
        ]);
        $request->setUserResolver(fn () => $homeroomUser);
        app()->instance('request', $request);

        app(ParentLeaveRequestController::class)->approve($request, $leaveRequest);
        app(ParentLeaveRequestController::class)->approve($request, $leaveRequest->fresh());

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->getKey(),
            'attendance_date' => $date->toDateString(),
            'session_key' => AttendanceRecord::SESSION_MORNING,
            'status' => AttendanceRecord::STATUS_EXCUSED,
        ]);
        $this->assertDatabaseMissing('attendance_records', [
            'student_id' => $student->getKey(),
            'attendance_date' => $date->toDateString(),
            'session_key' => AttendanceRecord::SESSION_AFTERNOON,
        ]);
        $this->assertSame(2, AttendanceRecord::where('student_id', $student->getKey())
            ->whereDate('attendance_date', $date->toDateString())
            ->count());
    }

    public function test_reward_scan_requires_complete_numeric_subject_data_and_preserves_manual_rewards(): void
    {
        $admin = $this->adminUser();
        $semester = $this->semesterWithClass();
        $class = $this->classWithMultipleRequiredNumericSubjects($semester);

        if (! $class) {
            $this->markTestSkipped('No class with multiple required numeric subjects is available.');
        }

        $requiredSubjects = $this->requiredNumericSubjectsForClass($class);
        $this->assertGreaterThan(1, $requiredSubjects->count());

        session([
            'working_school_year_id' => $semester->school_year_id,
            'working_semester_id' => $semester->getKey(),
        ]);
        $this->actingAs($admin);

        $completeStudent = $this->createStudent($class, 'COMP');
        $incompleteStudent = $this->createStudent($class, 'MISS');
        $manualStudent = $this->createStudent($class, 'MANU');

        foreach ($requiredSubjects as $subject) {
            $this->createAverage($completeStudent, $subject, $semester, 9.2);
            $this->createAverage($manualStudent, $subject, $semester, 9.2);
        }

        foreach ($requiredSubjects->slice(0, $requiredSubjects->count() - 1) as $subject) {
            $this->createAverage($incompleteStudent, $subject, $semester, 9.2);
        }

        foreach ([$completeStudent, $incompleteStudent, $manualStudent] as $student) {
            Conduct::create([
                'student_id' => $student->getKey(),
                'class_id' => $class->getKey(),
                'semester_id' => $semester->getKey(),
                'school_year_id' => $semester->school_year_id,
                'conduct_level' => Conduct::LEVEL_GOOD,
                'comment' => null,
            ]);
        }

        $manualDetail = 'Manual approved reward';
        Reward::create([
            'student_id' => $manualStudent->getKey(),
            'class_id' => $class->getKey(),
            'semester_id' => $semester->getKey(),
            'school_year_id' => $semester->school_year_id,
            'reward_type' => Reward::TYPE_SUDDEN,
            'detail' => $manualDetail,
            'decision_number' => 'MANUAL-TEST',
            'created_by' => $admin->getKey(),
            'updated_by' => $admin->getKey(),
        ]);

        $response = $this->scanRewards($semester, $class, $admin);
        $this->assertSame(200, $response->getStatusCode());

        $this->assertDatabaseHas('rewards', [
            'student_id' => $completeStudent->getKey(),
            'semester_id' => $semester->getKey(),
        ]);
        $this->assertDatabaseMissing('rewards', [
            'student_id' => $incompleteStudent->getKey(),
            'semester_id' => $semester->getKey(),
        ]);
        $this->assertDatabaseHas('rewards', [
            'student_id' => $manualStudent->getKey(),
            'semester_id' => $semester->getKey(),
            'reward_type' => Reward::TYPE_SUDDEN,
            'detail' => $manualDetail,
        ]);

        $this->scanRewards($semester, $class, $admin);

        $this->assertSame(1, Reward::where('student_id', $completeStudent->getKey())
            ->where('semester_id', $semester->getKey())
            ->count());
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

    private function semesterWithClass(): Semester
    {
        return Semester::whereHas('schoolYear.classes')
            ->orderBy('order')
            ->firstOrFail();
    }

    private function activeSemester(): Semester
    {
        return Semester::where('status', Semester::STATUS_ACTIVE)
            ->whereHas('schoolYear')
            ->first()
            ?: $this->semesterWithClass();
    }

    private function classWithMultipleRequiredNumericSubjects(Semester $semester): ?SchoolClass
    {
        return SchoolClass::where('school_year_id', $semester->school_year_id)
            ->get()
            ->first(fn (SchoolClass $class) => $this->requiredNumericSubjectsForClass($class)->count() > 1);
    }

    private function requiredNumericSubjectsForClass(SchoolClass $class)
    {
        return Subject::with('gradeMappings')
            ->whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->withEvaluatedAssessment()
            ->get()
            ->filter(fn (Subject $subject) => $subject->usesNumericAssessment() && $subject->appliesToGrade((int) $class->grade_level))
            ->values();
    }

    private function assessmentSubjectsForClass(SchoolClass $class)
    {
        return Subject::with('gradeMappings')
            ->whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->withEvaluatedAssessment()
            ->get()
            ->filter(fn (Subject $subject) => $subject->usesPassFailAssessment() && $subject->appliesToGrade((int) $class->grade_level))
            ->values();
    }

    private function createStudent(SchoolClass $class, string $suffix): Student
    {
        $token = Str::upper(Str::random(8));

        return Student::create([
            'student_code' => 'TST' . $suffix . $token,
            'name' => 'Test Student ' . $suffix . ' ' . $token,
            'gender' => Student::GENDER_NAM,
            'dob' => '2010-01-01',
            'class_id' => $class->getKey(),
            'school_year_id' => $class->school_year_id,
            'status' => Student::STATUS_STUDYING,
        ]);
    }

    private function createAverage(Student $student, Subject $subject, Semester $semester, float $average): void
    {
        ScoreHeader::create([
            'student_id' => $student->getKey(),
            'subject_id' => $subject->getKey(),
            'semester_id' => $semester->getKey(),
            'school_year_id' => $semester->school_year_id,
            'average' => $average,
        ]);
    }

    private function scanRewards(Semester $semester, SchoolClass $class, User $admin)
    {
        $request = Request::create('/rewards/scan', 'POST', [
            'semester_id' => $semester->getKey(),
            'class_id' => $class->getKey(),
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        return app(KhenThuongController::class)->scan($request, app(AcademicEvaluationService::class));
    }

    private function attendanceStoreRequest(
        User $admin,
        Semester $semester,
        SchoolClass $class,
        Carbon $date,
        string $sessionType,
        Student $student,
        string $status = AttendanceRecord::STATUS_PRESENT
    ): Request
    {
        $request = Request::create('/attendance', 'POST', [
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
            'class_id' => $class->getKey(),
            'attendance_date' => $date->toDateString(),
            'attendance_type' => $sessionType,
            'status' => [
                $student->getKey() => $status,
            ],
            'note' => [
                $student->getKey() => null,
            ],
        ]);
        $request->setUserResolver(fn () => $admin);
        app()->instance('request', $request);

        return $request;
    }

    private function createScheduledClass(Semester $semester, Carbon $date, array $periods, ?Teacher $homeroomTeacher = null): array
    {
        $class = SchoolClass::create([
            'name' => 'TST Attendance ' . Str::upper(Str::random(6)),
            'grade_level' => 10,
            'cohort' => '2026-2029',
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
            'homeroom_teacher_id' => $homeroomTeacher?->getKey(),
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);
        $student = $this->createStudent($class, 'ATT');
        $subject = Subject::where('status', Subject::STATUS_ACTIVE)->first();

        if (! $subject) {
            $this->markTestSkipped('No subject is available.');
        }

        $timetable = Timetable::create([
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->getKey(),
            'class_id' => $class->getKey(),
            'week_start' => $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'week_end' => $date->copy()->startOfWeek(Carbon::MONDAY)->addDays(5)->toDateString(),
        ]);
        $entries = [];

        foreach ($periods as $period) {
            $entries[(int) $period] = TimetableEntry::create([
                'timetable_id' => $timetable->getKey(),
                'day_of_week' => $date->isoWeekday(),
                'period' => (int) $period,
                'subject_id' => $subject->getKey(),
                'teacher_id' => $homeroomTeacher?->getKey(),
                'status' => TimetableEntry::STATUS_ACTIVE,
            ]);
        }

        return [$class, $student, $timetable, $entries];
    }

    private function weeklyCell(User $admin, SchoolClass $class, Semester $semester, Carbon $date, Student $student): array
    {
        $controller = app(AttendanceController::class);
        $method = new \ReflectionMethod($controller, 'weeklyMatrix');
        $method->setAccessible(true);
        $matrix = $method->invoke($controller, $admin, $class, $semester->getKey(), $date->toDateString(), collect([$class]));

        return $matrix['rows']
            ->first(fn (array $row) => (string) $row['student']->getKey() === (string) $student->getKey())['cells'][$date->toDateString()];
    }

    private function attendanceSessionsFor(User $admin, SchoolClass $class, Semester $semester, Carbon $date)
    {
        $records = AttendanceRecord::with(['student', 'classRoom', 'semester.schoolYear', 'timetableEntry.subject'])
            ->where('class_id', $class->getKey())
            ->where('semester_id', $semester->getKey())
            ->whereDate('attendance_date', $date->toDateString())
            ->get();
        $request = Request::create('/attendance', 'GET', []);
        $request->setUserResolver(fn () => $admin);
        $controller = app(AttendanceController::class);
        $method = new \ReflectionMethod($controller, 'paginateSessions');
        $method->setAccessible(true);

        return collect($method->invoke($controller, $records, $request)->items());
    }

    private function homeroomUser(): User
    {
        $teacher = Teacher::create([
            'teacher_code' => 'TCH' . Str::upper(Str::random(8)),
            'name' => 'Giáo viên chủ nhiệm test',
            'gender' => Teacher::GENDER_NAM,
            'dob' => '1985-01-01',
            'phone' => '09' . random_int(10000000, 99999999),
            'email' => Str::lower(Str::random(8)) . '@example.test',
            'work_status' => Teacher::STATUS_WORKING,
            'is_homeroom' => true,
        ]);

        return User::findOrFail($teacher->getKey());
    }

    private function parentUser(): User
    {
        return User::create([
            'username' => 'parent_' . Str::lower(Str::random(8)),
            'full_name' => 'Phụ huynh test',
            'email' => Str::lower(Str::random(8)) . '@example.test',
            'role' => 'parent',
            'role_type' => 'parent',
            'password_hash' => '$2y$12$Z2FJ9wVbk03D58EQ38Fn6O9z3.nBTeoNRZoh9c6uPfYRJrL46Y0wW',
            'is_active' => 1,
            'login_status' => 1,
        ]);
    }
}
