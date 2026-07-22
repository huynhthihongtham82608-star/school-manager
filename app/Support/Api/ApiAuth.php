<?php

namespace App\Support\Api;

use App\Models\RbacPermission;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ApiAuth
{
    public static function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'role' => $user->role,
            'teacher_id' => $user->teacher_id,
            'student_id' => $user->student_id,
            'parent_id' => $user->parent_id,
            'is_homeroom_teacher' => $user->isHomeroom(),
            'is_active' => $user->is_active,
            'force_change_password' => $user->force_change_password,
            'must_change_password' => $user->force_change_password,
            'permissions' => self::permissionKeys($user),
        ];
    }

    public static function hasRole(?User $user, array $roles): bool
    {
        if ($user === null) {
            return false;
        }

        foreach ($roles as $role) {
            if ($role === 'teacher' && $user->isTeacher()) {
                return true;
            }

            if ($role === 'homeroom' && $user->isHomeroom()) {
                return true;
            }

            if ($user->role === $role) {
                return true;
            }
        }

        return false;
    }

    private static function permissionKeys(User $user): array
    {
        if (! Schema::hasTable('rbac_permissions')) {
            return [];
        }

        if ($user->isSuperAdmin() || $user->role === 'admin') {
            return RbacPermission::orderBy('key')->pluck('key')->all();
        }

        if ($user->role !== 'staff' || ! Schema::hasTable('rbac_role_user')) {
            return [];
        }

        return $user->rbacRoles()
            ->where('rbac_roles.is_active', true)
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions)
            ->pluck('key')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
