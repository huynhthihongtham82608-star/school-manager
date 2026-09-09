<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\ParentProfile;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\ScoreHeader;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class RolePortalAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');
        view()->share('errors', new ViewErrorBag());
    }

    public function test_student_portal_main_pages_render_and_admin_mutations_are_denied(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['student']);
        session([
            'working_school_year_id' => $fixture['year']->getKey(),
            'working_semester_id' => $fixture['semester']->getKey(),
        ]);

        $this->assertOkUrls([
            route('dashboard'),
            route('profile.show'),
            route('timetable.index'),
            route('scores.index'),
            route('attendance.index'),
            route('conduct.index'),
            route('announcements.index'),
            route('events.index'),
            route('documents.index'),
            route('messages.inbox'),
            route('chatbot.index'),
        ]);

        $this->get($this->urlPath(route('students.index')))->assertForbidden();
        $this->post($this->urlPath(route('attendance.store')), [])->assertForbidden();
        $this->post($this->urlPath(route('logout')))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_parent_portal_keeps_each_child_scope_separate_and_denies_unlinked_child(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['parent']);
        session([
            'working_school_year_id' => $fixture['year']->getKey(),
            'working_semester_id' => $fixture['semester']->getKey(),
            'selected_parent_student_id' => $fixture['student']->getKey(),
        ]);

        $this->assertOkUrls([
            route('dashboard'),
            route('profile.show'),
            route('timetable.index'),
            route('scores.index'),
            route('attendance.index'),
            route('conduct.index'),
            route('parent.tuition-fees.index'),
            route('parent.leave-requests.index'),
            route('announcements.index'),
            route('events.index'),
            route('documents.index'),
            route('messages.inbox'),
            route('chatbot.index'),
        ]);

        $this->post($this->urlPath(route('parent.select-child')), [
            'student_id' => $fixture['second_student']->getKey(),
        ])->assertRedirect();
        $this->assertSame((string) $fixture['second_student']->getKey(), (string) session('selected_parent_student_id'));

        $this->post($this->urlPath(route('parent.select-child')), [
            'student_id' => $fixture['outside_student']->getKey(),
        ])->assertSessionHasErrors('student_id');
        $this->assertNotSame((string) $fixture['outside_student']->getKey(), (string) session('selected_parent_student_id'));

        $this->post($this->urlPath(route('attendance.store')), [])->assertForbidden();
    }

    public function test_subject_teacher_sees_assigned_class_but_not_homeroom_only_features_or_outside_class(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['teacher']);
        session([
            'working_school_year_id' => $fixture['year']->getKey(),
            'working_semester_id' => $fixture['semester']->getKey(),
        ]);

        $this->assertOkUrls([
            route('dashboard'),
            route('teacher.classes'),
            route('teacher.classes.students', $fixture['class']),
            route('timetable.index'),
            route('scores.index'),
            route('attendance.index'),
            route('announcements.index'),
            route('events.index'),
            route('documents.index'),
            route('messages.inbox'),
            route('chatbot.index'),
        ]);

        $this->get($this->urlPath(route('teacher.classes.students', $fixture['outside_class'])))->assertForbidden();
        $this->get($this->urlPath(route('teacher.homeroom')))->assertForbidden();
        $this->get($this->urlPath(route('teacher.homeroom.scores')))->assertForbidden();
        $this->get('/teacher/conduct')->assertForbidden();
        $this->get($this->urlPath(route('teacher.leave-requests.index')))->assertForbidden();
    }

    public function test_homeroom_teacher_can_open_homeroom_features_inside_own_scope(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['homeroom']);
        session([
            'working_school_year_id' => $fixture['year']->getKey(),
            'working_semester_id' => $fixture['semester']->getKey(),
        ]);

        $this->assertOkUrls([
            route('dashboard'),
            route('teacher.homeroom'),
            route('teacher.homeroom.scores'),
            route('teacher.classes.students', $fixture['homeroom_class']),
            '/teacher/conduct',
            route('teacher.leave-requests.index'),
            route('teacher.tuition-fees.homeroom'),
            route('timetable.index'),
            route('attendance.index'),
            route('messages.inbox'),
            route('chatbot.index'),
        ]);

        $this->get($this->urlPath(route('teacher.classes.students', $fixture['outside_class'])))->assertForbidden();
    }

    public function test_layout_uses_shared_toast_and_confirm_without_browser_confirm_or_alert(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['student']);
        $html = $this->get($this->urlPath(route('dashboard')))->assertOk()->getContent();

        $this->assertStringContainsString('window.SchoolToast', $html);
        $this->assertStringContainsString('window.SchoolConfirm', $html);
        $this->assertStringNotContainsString('alert(', $html);
        $this->assertStringNotContainsString('window.confirm(', $html);
    }

    private function fixture(): array
    {
        $token = Str::lower(Str::random(8));

        $year = SchoolYear::create([
            'name' => 'Test ' . $token,
            'start_date' => '2026-08-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);

        $semester = Semester::create([
            'name' => 'Học kỳ 1',
            'order' => 1,
            'school_year_id' => $year->getKey(),
            'is_score_input_open' => true,
            'status' => Semester::STATUS_ACTIVE,
        ]);

        $class = SchoolClass::create([
            'name' => '10T' . strtoupper(Str::random(3)),
            'grade_level' => 10,
            'cohort' => '2026-2029',
            'school_year_id' => $year->getKey(),
            'semester_id' => $semester->getKey(),
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);

        $homeroomClass = SchoolClass::create([
            'name' => '11T' . strtoupper(Str::random(3)),
            'grade_level' => 11,
            'cohort' => '2025-2028',
            'school_year_id' => $year->getKey(),
            'semester_id' => $semester->getKey(),
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);

        $outsideClass = SchoolClass::create([
            'name' => '12T' . strtoupper(Str::random(3)),
            'grade_level' => 12,
            'cohort' => '2024-2027',
            'school_year_id' => $year->getKey(),
            'semester_id' => $semester->getKey(),
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);

        $subject = Subject::create([
            'code' => 'TST' . strtoupper(Str::random(6)),
            'name' => 'Môn kiểm thử ' . $token,
            'credit' => 1,
            'type' => Subject::TYPE_OFFICIAL,
            'assessment_type' => Subject::ASSESSMENT_GRADE_10,
            'status' => Subject::STATUS_ACTIVE,
        ]);

        $teacher = Teacher::create([
            'teacher_code' => 'GV' . strtoupper(Str::random(8)),
            'name' => 'Giáo viên bộ môn test',
            'gender' => Teacher::GENDER_NAM,
            'dob' => '1980-01-01',
            'phone' => '08' . random_int(10000000, 99999999),
            'email' => 'teacher_' . $token . '@example.test',
            'work_status' => Teacher::STATUS_WORKING,
            'primary_subject_id' => $subject->getKey(),
            'is_homeroom' => false,
        ]);

        $homeroom = Teacher::create([
            'teacher_code' => 'CN' . strtoupper(Str::random(8)),
            'name' => 'Giáo viên chủ nhiệm test',
            'gender' => Teacher::GENDER_NU,
            'dob' => '1981-01-01',
            'phone' => '07' . random_int(10000000, 99999999),
            'email' => 'homeroom_' . $token . '@example.test',
            'work_status' => Teacher::STATUS_WORKING,
            'primary_subject_id' => $subject->getKey(),
            'is_homeroom' => true,
        ]);

        $homeroomClass->forceFill(['homeroom_teacher_id' => $homeroom->getKey()])->save();

        TeachingAssignment::create([
            'teacher_id' => $teacher->getKey(),
            'class_id' => $class->getKey(),
            'subject_id' => $subject->getKey(),
            'school_year_id' => $year->getKey(),
            'semester_id' => $semester->getKey(),
            'role' => TeachingAssignment::ROLE_PRIMARY,
            'weekly_periods' => 2,
            'status' => TeachingAssignment::STATUS_ACTIVE,
        ]);

        TeachingAssignment::create([
            'teacher_id' => $homeroom->getKey(),
            'class_id' => $homeroomClass->getKey(),
            'subject_id' => $subject->getKey(),
            'school_year_id' => $year->getKey(),
            'semester_id' => $semester->getKey(),
            'role' => TeachingAssignment::ROLE_PRIMARY,
            'weekly_periods' => 2,
            'status' => TeachingAssignment::STATUS_ACTIVE,
        ]);

        $student = $this->student($class, $year, 'A');
        $secondStudent = $this->student($homeroomClass, $year, 'B');
        $outsideStudent = $this->student($outsideClass, $year, 'C');

        ScoreHeader::create([
            'student_id' => $student->getKey(),
            'subject_id' => $subject->getKey(),
            'semester_id' => $semester->getKey(),
            'school_year_id' => $year->getKey(),
            'average' => 8.5,
        ]);

        AttendanceRecord::create([
            'student_id' => $student->getKey(),
            'class_id' => $class->getKey(),
            'semester_id' => $semester->getKey(),
            'attendance_date' => '2026-09-10',
            'session_type' => AttendanceRecord::SESSION_MORNING,
            'session_key' => 'morning',
            'session_label' => 'Buổi sáng',
            'session_order' => 1,
            'status' => AttendanceRecord::STATUS_PRESENT,
            'recorded_by' => $teacher->getKey(),
        ]);

        $parent = ParentProfile::create([
            'parent_code' => 'PH' . strtoupper(Str::random(8)),
            'name' => 'Phụ huynh test',
            'phone' => '09' . random_int(10000000, 99999999),
            'email' => 'parent_' . $token . '@example.test',
        ]);

        DB::table('parent_student')->insert([
            [
                'parent_id' => $parent->getKey(),
                'student_id' => $student->getKey(),
                'relation' => ParentProfile::RELATION_GUARDIAN,
            ],
            [
                'parent_id' => $parent->getKey(),
                'student_id' => $secondStudent->getKey(),
                'relation' => ParentProfile::RELATION_GUARDIAN,
            ],
        ]);

        return [
            'year' => $year,
            'semester' => $semester,
            'class' => $class,
            'homeroom_class' => $homeroomClass,
            'outside_class' => $outsideClass,
            'student' => User::findOrFail($student->getKey()),
            'second_student' => User::findOrFail($secondStudent->getKey()),
            'outside_student' => User::findOrFail($outsideStudent->getKey()),
            'parent' => User::findOrFail($parent->getKey()),
            'teacher' => User::findOrFail($teacher->getKey()),
            'homeroom' => User::findOrFail($homeroom->getKey()),
        ];
    }

    private function assertOkUrls(array $urls): void
    {
        foreach ($urls as $url) {
            $response = $this->get($this->urlPath($url));

            $this->assertSame(200, $response->getStatusCode(), 'Expected 200 for ' . $url);
        }
    }

    private function urlPath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        return Str::after($path, '/school-manager/public') ?: '/';
    }

    private function student(SchoolClass $class, SchoolYear $year, string $suffix): Student
    {
        $token = Str::upper(Str::random(8));

        return Student::create([
            'student_code' => 'HS' . $token,
            'name' => 'Học sinh test ' . $suffix,
            'gender' => Student::GENDER_NAM,
            'dob' => '2010-01-01',
            'email' => Str::lower('student_' . $token . '@example.test'),
            'enrollment_date' => '2026-08-01',
            'admission_type' => Student::ADMISSION_NEW,
            'class_id' => $class->getKey(),
            'school_year_id' => $year->getKey(),
            'status' => Student::STATUS_STUDYING,
        ]);
    }
}
