<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Chatbot\ChatbotDataService;
use App\Services\Chatbot\ChatbotOrchestrator;
use App\Services\Chatbot\ChatbotToolExecutor;
use App\Services\Chatbot\ChatbotToolRegistry;
use App\Services\Chatbot\UserScopeService;
use ReflectionClass;
use Tests\TestCase;

class ChatbotToolRegistryTest extends TestCase
{
    public function test_registry_exposes_required_tool_declarations(): void
    {
        $registry = new ChatbotToolRegistry();
        $tools = collect($registry->declarations())->pluck('name');

        foreach ([
            'get_school_statistics',
            'get_class_student_count',
            'get_class_students',
            'get_teacher_classes',
            'get_teacher_homeroom_class',
            'get_teacher_schedule',
            'get_class_subject_teachers',
            'get_class_homeroom_teacher',
            'get_student_scores',
            'get_student_timetable',
            'get_student_attendance',
            'get_student_conduct',
            'get_child_tuition',
            'get_child_subject_teachers',
            'get_student_exam_schedule',
            'get_homeroom_leave_requests',
            'get_teacher_substitute_schedule',
            'get_announcements_documents',
            'get_score_calculation_rules',
            'get_system_navigation',
        ] as $tool) {
            $this->assertTrue($tools->contains($tool), "Missing chatbot tool: {$tool}");
        }
    }

    public function test_registry_does_not_expose_arbitrary_sql_tool(): void
    {
        $registry = new ChatbotToolRegistry();
        $tools = collect($registry->declarations());

        $this->assertFalse($tools->pluck('name')->contains(fn ($name) => str_contains((string) $name, 'sql')));
        $this->assertFalse($tools->pluck('description')->contains(fn ($description) => str_contains(strtolower((string) $description), 'raw sql')));
    }

    public function test_orchestrator_normalizes_ai_reply_before_showing_user(): void
    {
        $reflection = new ReflectionClass(ChatbotOrchestrator::class);
        $orchestrator = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('normalizeReply');
        $method->setAccessible(true);

        $reply = $method->invoke(
            $orchestrator,
            "**Kết quả** từ get_student_scores: `Toán\\_học` \u{FFFC} 550e8400-e29b-41d4-a716-446655440000"
        );

        $this->assertStringNotContainsString('**', $reply);
        $this->assertStringNotContainsString('`', $reply);
        $this->assertStringNotContainsString('get_student_scores', $reply);
        $this->assertStringNotContainsString('\_', $reply);
        $this->assertStringNotContainsString('550e8400', $reply);
    }

    public function test_executor_rejects_unknown_tool(): void
    {
        $scope = new UserScopeService();
        $registry = new ChatbotToolRegistry();
        $executor = new ChatbotToolExecutor($registry, new ChatbotDataService($scope), $scope);

        $result = $executor->execute(new User(['role' => 'admin', 'is_active' => true, 'login_status' => true]), 'missing_tool');

        $this->assertFalse($result['success']);
        $this->assertSame('unknown_tool', $result['error_type']);
    }

    public function test_student_cannot_use_school_statistics_tool(): void
    {
        $scope = new UserScopeService();
        $registry = new ChatbotToolRegistry();
        $executor = new ChatbotToolExecutor($registry, new ChatbotDataService($scope), $scope);

        $result = $executor->execute(new User(['role' => 'student', 'role_type' => 'student', 'is_active' => true, 'login_status' => true]), 'get_school_statistics');

        $this->assertFalse($result['success']);
        $this->assertSame('forbidden', $result['error_type']);
    }
}
