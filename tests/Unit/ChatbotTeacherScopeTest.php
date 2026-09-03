<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Chatbot\UserScopeService;
use Mockery;
use Tests\TestCase;

class ChatbotTeacherScopeTest extends TestCase
{
    public function test_teacher_teaching_class_without_homeroom_is_allowed_for_student_count_scope(): void
    {
        $scope = $this->scopeWithAllowedClasses(['class-a']);

        $this->assertTrue($scope->canAccessClass($this->teacherUser(), 'class-a'));
    }

    public function test_teacher_teaching_class_without_homeroom_is_allowed_for_student_list_scope(): void
    {
        $scope = $this->scopeWithAllowedClasses(['class-a']);

        $this->assertTrue($scope->canAccessClass($this->teacherUser(), 'class-a'));
    }

    public function test_teacher_teaching_and_homeroom_class_is_allowed(): void
    {
        $scope = $this->scopeWithAllowedClasses(['class-b']);

        $this->assertTrue($scope->canAccessClass($this->teacherUser(), 'class-b'));
    }

    public function test_teacher_without_assignment_to_class_is_denied(): void
    {
        $scope = $this->scopeWithAllowedClasses(['class-a', 'class-b']);

        $this->assertFalse($scope->canAccessClass($this->teacherUser(), 'class-c'));
    }

    public function test_homeroom_only_class_is_allowed(): void
    {
        $scope = Mockery::mock(UserScopeService::class)->makePartial();
        $scope->shouldReceive('teacherTeachingClassIds')->andReturn(collect());
        $scope->shouldReceive('teacherHomeroomClassIds')->andReturn(collect(['class-b']));

        $this->assertTrue($scope->canAccessClass($this->teacherUser(), 'class-b'));
    }

    public function test_duplicate_teaching_assignments_do_not_duplicate_scope(): void
    {
        $scope = Mockery::mock(UserScopeService::class)->makePartial();
        $scope->shouldReceive('teacherTeachingClassIds')->andReturn(collect(['class-a', 'class-a']));
        $scope->shouldReceive('teacherHomeroomClassIds')->andReturn(collect(['class-a', 'class-b']));

        $this->assertSame(['class-a', 'class-b'], $scope->teacherClassIds($this->teacherUser())->all());
    }

    private function scopeWithAllowedClasses(array $classIds): UserScopeService
    {
        $scope = Mockery::mock(UserScopeService::class)->makePartial();
        $scope->shouldReceive('teacherClassIds')->andReturn(collect($classIds));

        return $scope;
    }

    private function teacherUser(): User
    {
        return new User([
            'role' => 'teacher',
            'role_type' => 'teacher',
            'is_active' => true,
            'login_status' => true,
        ]);
    }
}
