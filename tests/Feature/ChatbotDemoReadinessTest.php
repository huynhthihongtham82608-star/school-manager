<?php

namespace Tests\Feature;

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
use App\Services\Chatbot\GeminiClient;
use App\Services\Chatbot\SchoolChatbotService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ChatbotDemoReadinessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_demo_mode_answers_from_existing_student_scope_without_calling_gemini(): void
    {
        config(['services.chatbot.demo_mode' => true]);
        $this->mockGeminiMustNotBeCalled();

        $fixture = $this->chatbotFixture();

        $reply = app(SchoolChatbotService::class)
            ->answer($fixture['student_user'], 'xem điểm của tôi');

        $this->assertSame('success', $reply['status']);
        $this->assertStringContainsString('Chế độ trình diễn', $reply['reply']);
        $this->assertStringContainsString('không gọi Gemini', $reply['reply']);
        $this->assertStringContainsString($fixture['student']->name, $reply['reply']);
        $this->assertStringNotContainsString('get_student_scores', $reply['reply']);
        $this->assertStringNotContainsString((string) $fixture['student']->id, $reply['reply']);
    }

    public function test_demo_mode_parent_with_multiple_children_requires_child_clarification(): void
    {
        config(['services.chatbot.demo_mode' => true]);
        $this->mockGeminiMustNotBeCalled();

        $fixture = $this->chatbotFixture();

        $reply = app(SchoolChatbotService::class)
            ->answer($fixture['parent_user'], 'xem điểm của con');

        $this->assertSame('error', $reply['status']);
        $this->assertStringContainsString('Chế độ trình diễn', $reply['reply']);
        $this->assertStringContainsString('Anh/chị muốn xem thông tin của học sinh nào', $reply['reply']);
        $this->assertStringContainsString($fixture['student']->student_code, $reply['reply']);
        $this->assertStringContainsString($fixture['second_student']->student_code, $reply['reply']);
    }

    public function test_demo_mode_teacher_scope_allows_assigned_class_and_denies_outside_class(): void
    {
        config(['services.chatbot.demo_mode' => true]);
        $this->mockGeminiMustNotBeCalled();

        $fixture = $this->chatbotFixture();

        $allowed = app(SchoolChatbotService::class)
            ->answer($fixture['teacher_user'], 'Lớp ' . $fixture['class']->name . ' có sĩ số bao nhiêu?');
        $denied = app(SchoolChatbotService::class)
            ->answer($fixture['teacher_user'], 'Lớp ' . $fixture['outside_class']->name . ' có sĩ số bao nhiêu?');

        $this->assertSame('success', $allowed['status']);
        $this->assertStringContainsString($fixture['class']->name, $allowed['reply']);
        $this->assertStringContainsString('1 học sinh', $allowed['reply']);

        $this->assertSame('error', $denied['status']);
        $this->assertStringContainsString('không có quyền', Str::lower($denied['reply']));
    }

    public function test_non_demo_mode_uses_gemini_client(): void
    {
        config(['services.chatbot.demo_mode' => false]);

        $gemini = Mockery::mock(GeminiClient::class);
        $gemini->shouldReceive('chooseTool')
            ->once()
            ->andReturn([
                'success' => true,
                'model' => 'fake-gemini',
                'http_status' => 200,
                'duration_ms' => 10,
                'text' => 'Xin chào, tôi đang sẵn sàng hỗ trợ.',
                'function_call' => null,
            ]);
        $gemini->shouldNotReceive('finalAnswer');
        $this->app->instance(GeminiClient::class, $gemini);

        $reply = app(SchoolChatbotService::class)
            ->answer($this->chatbotFixture()['student_user'], 'xin chào');

        $this->assertSame('success', $reply['status']);
        $this->assertStringContainsString('Xin chào', $reply['reply']);
        $this->assertStringNotContainsString('Chế độ trình diễn', $reply['reply']);
    }

    private function mockGeminiMustNotBeCalled(): void
    {
        $gemini = Mockery::mock(GeminiClient::class);
        $gemini->shouldNotReceive('chooseTool');
        $gemini->shouldNotReceive('finalAnswer');
        $this->app->instance(GeminiClient::class, $gemini);
    }

    private function chatbotFixture(): array
    {
        $token = Str::lower(Str::random(8));

        $year = SchoolYear::create([
            'name' => 'Chatbot Demo ' . $token,
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
            'name' => '10D' . random_int(10, 99),
            'grade_level' => 10,
            'cohort' => '2026-2029',
            'school_year_id' => $year->getKey(),
            'semester_id' => $semester->getKey(),
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);

        $outsideClass = SchoolClass::create([
            'name' => '11D' . random_int(10, 99),
            'grade_level' => 11,
            'cohort' => '2025-2028',
            'school_year_id' => $year->getKey(),
            'semester_id' => $semester->getKey(),
            'capacity' => 45,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]);

        $subject = Subject::create([
            'code' => 'CBD' . strtoupper(Str::random(6)),
            'name' => 'Môn chatbot demo ' . $token,
            'credit' => 1,
            'type' => Subject::TYPE_OFFICIAL,
            'assessment_type' => Subject::ASSESSMENT_GRADE_10,
            'status' => Subject::STATUS_ACTIVE,
        ]);

        $teacher = Teacher::create([
            'teacher_code' => 'GVD' . strtoupper(Str::random(7)),
            'name' => 'Giáo viên chatbot demo',
            'gender' => Teacher::GENDER_NAM,
            'dob' => '1980-01-01',
            'phone' => '06' . random_int(10000000, 99999999),
            'email' => 'teacher_demo_' . $token . '@example.test',
            'work_status' => Teacher::STATUS_WORKING,
            'primary_subject_id' => $subject->getKey(),
            'is_homeroom' => false,
        ]);

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

        $student = $this->student($class, $year, 'A');
        $secondStudent = $this->student($outsideClass, $year, 'B');

        ScoreHeader::create([
            'student_id' => $student->getKey(),
            'subject_id' => $subject->getKey(),
            'semester_id' => $semester->getKey(),
            'school_year_id' => $year->getKey(),
            'average' => 8.5,
        ]);

        $parent = ParentProfile::create([
            'parent_code' => 'PHD' . strtoupper(Str::random(7)),
            'name' => 'Phụ huynh chatbot demo',
            'phone' => '05' . random_int(10000000, 99999999),
            'email' => 'parent_demo_' . $token . '@example.test',
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
            'outside_class' => $outsideClass,
            'subject' => $subject,
            'student' => $student,
            'second_student' => $secondStudent,
            'teacher' => $teacher,
            'parent' => $parent,
            'student_user' => User::findOrFail($student->getKey()),
            'teacher_user' => User::findOrFail($teacher->getKey()),
            'parent_user' => User::findOrFail($parent->getKey()),
        ];
    }

    private function student(SchoolClass $class, SchoolYear $year, string $suffix): Student
    {
        $token = Str::upper(Str::random(8));

        return Student::create([
            'student_code' => 'HSD' . $token,
            'name' => 'Học sinh chatbot demo ' . $suffix,
            'gender' => Student::GENDER_NAM,
            'dob' => '2010-01-01',
            'email' => Str::lower('student_demo_' . $token . '@example.test'),
            'enrollment_date' => '2026-08-01',
            'admission_type' => Student::ADMISSION_NEW,
            'class_id' => $class->getKey(),
            'school_year_id' => $year->getKey(),
            'status' => Student::STATUS_STUDYING,
        ]);
    }
}
