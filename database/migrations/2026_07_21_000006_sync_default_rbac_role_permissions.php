<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('rbac_permissions')->where('key', 'classes.manage')->value('id');

        if (! $permissionId) {
            return;
        }

        $now = now();
        DB::table('rbac_roles')
            ->whereIn('key', ['staff', 'school_leadership', 'academic_officer'])
            ->pluck('id')
            ->each(function ($roleId) use ($permissionId, $now) {
                DB::table('rbac_permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            });
    }

    public function down(): void
    {
        $permissionId = DB::table('rbac_permissions')->where('key', 'classes.manage')->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('rbac_permission_role')
            ->where('permission_id', $permissionId)
            ->whereIn('role_id', DB::table('rbac_roles')->whereIn('key', ['staff', 'school_leadership', 'academic_officer'])->pluck('id'))
            ->delete();
    }
};
