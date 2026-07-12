<?php

namespace App\Support\Api;

use App\Models\User;

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
}
