<?php

namespace App\Services\Chatbot;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ChatbotToolExecutor
{
    public function __construct(
        private readonly ChatbotToolRegistry $registry,
        private readonly ChatbotDataService $data,
        private readonly UserScopeService $scope,
    ) {
    }

    public function execute(User $user, string $toolName, array $arguments = []): array
    {
        $startedAt = microtime(true);
        $tool = $this->registry->get($toolName);

        if (! $tool) {
            return $this->result(false, null, 'Tool không tồn tại trong hệ thống.', 'unknown_tool', $startedAt);
        }

        $arguments = $this->sanitizeArguments($arguments);

        if (! $this->authorized($user, $toolName)) {
            return $this->result(false, null, 'Bạn chưa có quyền truy vấn nhóm dữ liệu này.', 'forbidden', $startedAt);
        }

        $handler = (string) ($tool['handler'] ?? '');

        if ($handler === '' || ! method_exists($this->data, $handler)) {
            return $this->result(false, null, 'Tool chưa có bộ xử lý dữ liệu tương ứng.', 'missing_handler', $startedAt);
        }

        $payload = $this->data->{$handler}($user, $arguments);

        if (($payload['success'] ?? true) === false) {
            return $this->result(false, $payload, (string) ($payload['message'] ?? 'Không lấy được dữ liệu phù hợp.'), (string) ($payload['error_type'] ?? 'tool_error'), $startedAt, $arguments);
        }

        return $this->result(true, $payload, null, null, $startedAt, $arguments);
    }

    private function authorized(User $user, string $toolName): bool
    {
        if ($user->is_active === false || $user->login_status === false) {
            return false;
        }

        if ($this->scope->isAdminLike($user)) {
            return $this->adminAuthorized($user, $toolName);
        }

        if ($user->isStudent()) {
            return in_array($toolName, [
                'get_student_scores',
                'get_student_timetable',
                'get_student_attendance',
                'get_student_conduct',
                'get_student_exam_schedule',
                'get_class_subject_teachers',
                'get_class_homeroom_teacher',
                'get_announcements_documents',
                'get_score_calculation_rules',
                'get_system_navigation',
            ], true);
        }

        if ($user->isParent()) {
            return in_array($toolName, [
                'get_student_scores',
                'get_student_timetable',
                'get_student_attendance',
                'get_student_conduct',
                'get_child_tuition',
                'get_student_exam_schedule',
                'get_class_subject_teachers',
                'get_class_homeroom_teacher',
                'get_child_subject_teachers',
                'get_announcements_documents',
                'get_score_calculation_rules',
                'get_system_navigation',
            ], true);
        }

        if ($user->isTeacher()) {
            return in_array($toolName, [
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
                'get_student_exam_schedule',
                'get_homeroom_leave_requests',
                'get_teacher_substitute_schedule',
                'get_announcements_documents',
                'get_score_calculation_rules',
                'get_system_navigation',
            ], true);
        }

        return in_array($toolName, [
            'get_announcements_documents',
            'get_score_calculation_rules',
            'get_system_navigation',
        ], true);
    }

    private function adminAuthorized(User $user, string $toolName): bool
    {
        if ($user->isSuperAdmin() || $user->role === 'admin') {
            return true;
        }

        $permissionMap = [
            'get_school_statistics' => ['view_users', 'students.manage', 'teachers.manage', 'classes.manage'],
            'get_class_student_count' => ['view_users', 'students.manage', 'classes.manage'],
            'get_class_students' => ['view_users', 'students.manage', 'classes.manage'],
            'get_teacher_classes' => ['assignments.manage', 'teachers.manage'],
            'get_teacher_homeroom_class' => ['assignments.manage', 'teachers.manage', 'classes.manage'],
            'get_teacher_schedule' => ['timetable.manage', 'assignments.manage'],
            'get_class_subject_teachers' => ['assignments.manage', 'classes.manage'],
            'get_class_homeroom_teacher' => ['assignments.manage', 'classes.manage'],
            'get_child_subject_teachers' => ['assignments.manage', 'classes.manage'],
            'get_student_scores' => ['view_scores', 'scores.view', 'scores.manage'],
            'get_student_timetable' => ['timetable.manage', 'classes.manage'],
            'get_student_attendance' => ['attendance.view', 'attendance.manage'],
            'get_student_conduct' => ['conduct.view', 'conduct.manage'],
            'get_child_tuition' => ['view_tuition', 'collect_tuition', 'setup_tuition_fees'],
            'get_student_exam_schedule' => ['exams.manage'],
            'get_homeroom_leave_requests' => ['view_leave_requests', 'approve_leave_requests'],
            'get_teacher_substitute_schedule' => ['timetable.manage', 'assignments.manage'],
            'get_announcements_documents' => ['announcements.manage', 'documents.manage', 'system.settings'],
            'get_score_calculation_rules' => ['view_scores', 'scores.view', 'scores.manage', 'system.settings'],
            'get_system_navigation' => ['access_chatbot', 'system.settings'],
        ];

        return $user->hasAnyPermission($permissionMap[$toolName] ?? ['access_chatbot']);
    }

    private function sanitizeArguments(array $arguments): array
    {
        return collect($arguments)
            ->map(function ($value) {
                if (is_array($value)) {
                    return Arr::where($value, fn ($item) => is_scalar($item) || is_null($item));
                }

                if (is_string($value)) {
                    return trim(Str::limit($value, 160, ''));
                }

                return is_scalar($value) || is_null($value) ? $value : null;
            })
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }

    private function result(
        bool $success,
        mixed $data,
        ?string $message,
        ?string $errorType,
        float $startedAt,
        array $arguments = [],
    ): array {
        return [
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'error_type' => $errorType,
            'arguments' => $arguments,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
    }
}
