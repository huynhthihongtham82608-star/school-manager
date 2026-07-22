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
            'students.*' => 'students.manage',
            'teachers.*' => 'teachers.manage',
            'parents.*' => 'parents.manage',
            'subjects.*' => 'subjects.manage',
            'rooms.*' => 'rooms.manage',
            'departments.*' => 'departments.manage',
            'assignments.*' => 'assignments.manage',
            'timetable.manage' => 'timetable.manage',
            'timetable.entries.save' => 'timetable.manage',
            'timetable.clone' => 'timetable.manage',
            'exam-schedules.*' => 'exams.manage',
            'scores.index' => 'scores.view',
            'scores.entry' => 'scores.manage',
            'scores.store' => 'scores.manage',
            'score-columns.*' => 'scores.manage',
            'grade-windows.*' => 'scores.manage',
            'attendance.index' => 'attendance.view',
            'attendance.store' => 'attendance.manage',
            'conduct.index' => 'conduct.view',
            'conduct.store' => 'conduct.manage',
            'admin.home-page.*' => 'content.manage',
            'announcements.*' => 'content.manage',
            'events.*' => 'content.manage',
            'documents.*' => 'documents.manage',
            'messages.*' => 'messages.manage',
            'reports.*' => 'reports.view',
            'system.settings.*' => 'system.settings',
            'system.backups.*' => 'backups.manage',
            'audit-logs.*' => 'audit_logs.view',
            'admin-users.*' => 'manage_admin_accounts',
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
