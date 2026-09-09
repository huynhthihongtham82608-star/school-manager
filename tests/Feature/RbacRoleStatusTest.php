<?php

namespace Tests\Feature;

use App\Models\RbacPermission;
use App\Models\RbacRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RbacRoleStatusTest extends TestCase
{
    use DatabaseTransactions;

    public function test_deactivating_role_refreshes_effective_permissions_without_removing_assignment(): void
    {
        $permission = RbacPermission::create([
            'key' => 'testing.role-status.' . Str::lower(Str::random(8)),
            'name' => 'Testing role status permission',
            'group' => 'Testing',
            'description' => 'Temporary permission for role status regression test.',
        ]);

        $role = RbacRole::create([
            'key' => 'testing_role_status_' . Str::lower(Str::random(8)),
            'name' => 'Testing role status',
            'description' => 'Temporary role for role status regression test.',
            'is_system' => false,
            'is_active' => true,
        ]);
        $role->syncPermissionIds([$permission->id]);

        $user = User::create([
            'username' => 'staff_' . Str::lower(Str::random(10)),
            'full_name' => 'Staff role status test',
            'email' => 'staff_' . Str::lower(Str::random(10)) . '@example.test',
            'role' => 'staff',
            'role_type' => 'staff',
            'password_hash' => Hash::make('12345678'),
            'is_active' => true,
            'login_status' => true,
        ]);
        $user->syncRbacRoleIds([$role->id]);

        $this->assertTrue($user->fresh()->hasPermission($permission->key));

        $role->update(['is_active' => false]);
        User::refreshPermissionSnapshotsForRole((string) $role->id);

        $inactiveUser = $user->fresh();
        $this->assertContains((string) $role->id, $inactiveUser->rbacRoleIds());
        $this->assertFalse($inactiveUser->hasPermission($permission->key));

        $role->update(['is_active' => true]);
        User::refreshPermissionSnapshotsForRole((string) $role->id);

        $activeUser = $user->fresh();
        $this->assertContains((string) $role->id, $activeUser->rbacRoleIds());
        $this->assertTrue($activeUser->hasPermission($permission->key));
    }
}
