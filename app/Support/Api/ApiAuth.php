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
            'is_active' => $user->is_active,
        ];
    }

    public static function hasRole(?User $user, array $roles): bool
    {
        return $user !== null && in_array($user->role, $roles, true);
    }
}
