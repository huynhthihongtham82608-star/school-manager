<?php

namespace App\Services;

use App\Models\User;

class AdminProtectionService
{
    /**
     * Check if there is at least one active admin in the system
     */
    public static function hasActiveAdmin(): bool
    {
        return User::where('role', 'admin')
            ->where('is_active', 1)
            ->exists();
    }

    /**
     * Get count of active admins
     */
    public static function getActiveAdminCount(): int
    {
        return User::where('role', 'admin')
            ->where('is_active', 1)
            ->count();
    }

    /**
     * Check if user is the last active admin
     */
    public static function isLastActiveAdmin(User $user): bool
    {
        if ($user->role !== 'admin' || !$user->is_active) {
            return false;
        }

        return static::getActiveAdminCount() === 1;
    }

    /**
     * Validate if action is allowed (prevent deactivating last admin)
     * 
     * @param User $user
     * @param array $changes Array of changes like ['is_active' => 0]
     * @return array ['allowed' => bool, 'message' => string]
     */
    public static function validateAdminChange(User $user, array $changes): array
    {
        // Check if trying to deactivate last admin
        if (isset($changes['is_active']) && $changes['is_active'] === 0 || $changes['is_active'] === false) {
            if (static::isLastActiveAdmin($user)) {
                return [
                    'allowed' => false,
                    'message' => 'Hệ thống phải có ít nhất một tài khoản Admin đang hoạt động.'
                ];
            }
        }

        // Check if trying to change role of last admin
        if (isset($changes['role']) && $changes['role'] !== 'admin') {
            if (static::isLastActiveAdmin($user)) {
                return [
                    'allowed' => false,
                    'message' => 'Hệ thống phải có ít nhất một tài khoản Admin đang hoạt động.'
                ];
            }
        }

        return [
            'allowed' => true,
            'message' => ''
        ];
    }

    /**
     * Validate if deletion is allowed (prevent deleting last admin)
     */
    public static function validateAdminDeletion(User $user): array
    {
        if (static::isLastActiveAdmin($user)) {
            return [
                'allowed' => false,
                'message' => 'Hệ thống phải có ít nhất một tài khoản Admin đang hoạt động.'
            ];
        }

        return [
            'allowed' => true,
            'message' => ''
        ];
    }

    /**
     * Check if user can modify their own role
     */
    public static function canChangeOwnRole(User $user): bool
    {
        // Users cannot change their own role
        return false;
    }
}
