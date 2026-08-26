<?php

namespace App\Support\Rbac;

class PermissionCatalog
{
    public static function routePermission(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        foreach (self::routeMap() as $pattern => $permission) {
            if (self::matches($routeName, $pattern)) {
                return $permission;
            }
        }

        return null;
    }

    public static function menuPermission(string $routeName): ?string
    {
        return self::routePermission($routeName);
    }

    public static function adminRoleKeys(): array
    {
        return [
            'staff',
            'school_leadership',
            'academic_officer',
            'system_technician',
        ];
    }

    public static function lockedLegacyRoleKeys(): array
    {
        return [
            'super_admin',
            'admin',
            'staff',
            'teacher',
            'homeroom',
            'student',
            'parent',
        ];
    }

    private static function routeMap(): array
    {
        return [
            'dashboard' => 'dashboard.view',
            'school-years.*' => 'academic.manage',
            'academic-context.update' => 'dashboard.view',
            'semesters.*' => 'academic.manage',
            'classes.*' => 'classes.manage',
            'students.index' => 'view_users',
            'students.import-template' => 'view_users',
            'students.parent-lookup' => 'view_users',
            'students.create' => 'create_users',
            'students.store' => 'create_users',
            'students.import' => 'create_users',
            'students.edit' => 'edit_users',
            'students.update' => 'edit_users',
            'students.toggle-login' => 'edit_users',
            'students.reset-password' => 'edit_users',
            'students.destroy' => 'delete_users',
            'teachers.index' => 'view_users',
            'teachers.create' => 'create_users',
            'teachers.store' => 'create_users',
            'teachers.edit' => 'edit_users',
            'teachers.update' => 'edit_users',
            'teachers.toggle-login' => 'edit_users',
            'teachers.reset-password' => 'edit_users',
            'teachers.destroy' => 'delete_users',
            'parents.index' => 'view_users',
            'parents.create' => 'create_users',
            'parents.store' => 'create_users',
            'parents.edit' => 'edit_users',
            'parents.update' => 'edit_users',
            'parents.toggle-login' => 'edit_users',
            'parents.reset-password' => 'edit_users',
            'parents.destroy' => 'delete_users',
            'subjects.*' => 'subjects.manage',
            'rooms.*' => 'rooms.manage',
            'departments.*' => 'departments.manage',
            'assignments.*' => 'assignments.manage',
            'timetable.manage' => 'timetable.manage',
            'timetable.entries.save' => 'timetable.manage',
            'timetable.clone' => 'timetable.manage',
            'exam-schedules.scores.store' => 'input_scores',
            'exam-schedules.*' => 'exams.manage',
            'scores.index' => 'view_scores',
            'scores.report-card' => 'view_scores',
            'scores.report-card.export' => 'view_scores',
            'scores.cascade' => 'view_scores',
            'scores.admin-matrix' => 'view_scores',
            'scores.entry' => 'input_scores',
            'scores.store' => 'input_scores',
            'score-columns.*' => 'lock_score_window',
            'grade-windows.*' => 'lock_score_window',
            'attendance.index' => 'attendance.view',
            'attendance.store' => 'attendance.manage',
            'conduct.index' => 'conduct.view',
            'conduct.store' => 'conduct.manage',
            'rewards.index' => 'view_rewards',
            'rewards.store' => 'create_rewards',
            'rewards.update' => 'create_rewards',
            'rewards.scan' => 'create_rewards',
            'rewards.destroy' => 'delete_rewards',
            'teacher.leave-requests.index' => 'view_leave_requests',
            'teacher.leave-requests.approve' => 'approve_leave_requests',
            'teacher.leave-requests.reject' => 'approve_leave_requests',
            'parent.leave-requests.index' => 'view_leave_requests',
            'parent.leave-requests.store' => 'view_leave_requests',
            'admin.home-page.*' => 'content.manage',
            'announcements.*' => 'content.manage',
            'events.*' => 'content.manage',
            'documents.*' => 'documents.manage',
            'messages.*' => 'messages.manage',
            'reports.*' => 'reports.view',
            'chatbot.*' => 'access_chatbot',
            'system.tuition-levels.*' => 'setup_tuition_fees',
            'system.settings.*' => 'system.settings',
            'system.backups.*' => 'backups.manage',
            'audit-logs.*' => 'audit_logs.view',
            'tuition-fees.index' => 'view_tuition',
            'tuition-fees.update' => 'collect_tuition',
            'teacher.tuition-fees.homeroom' => 'view_tuition',
            'parent.tuition-fees.index' => 'view_tuition',
            'admin-users.index' => 'view_users',
            'admin-users.store' => 'create_users',
            'admin-users.update' => 'edit_users',
            'admin-users.toggle' => 'edit_users',
            'admin-users.reset-password' => 'edit_users',
            'admin-users.destroy' => 'delete_users',
            'rbac-roles.*' => 'manage_roles',
        ];
    }

    private static function matches(string $routeName, string $pattern): bool
    {
        if ($routeName === $pattern) {
            return true;
        }

        if (! str_contains($pattern, '*')) {
            return false;
        }

        $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';

        return (bool) preg_match($regex, $routeName);
    }
}
