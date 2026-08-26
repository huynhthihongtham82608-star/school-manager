<?php

namespace App\Models;

use App\Models\Concerns\UsesConsolidatedTable;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RbacRole extends Model
{
    use UsesUuid, UsesConsolidatedTable;

    protected $table = 'rbac_roles';

    protected $fillable = [
        'key',
        'name',
        'description',
        'permissions_map',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'permissions_map' => 'array',
        ];
    }

    public function getTable()
    {
        return $this->usesConsolidatedTable('rbac_matrix', 'rbac_roles', 'record_type') ? 'rbac_matrix' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('rbac_matrix', 'rbac_roles', 'record_type')) {
            static::addGlobalScope('role_records', fn ($query) => $query->where('record_type', 'role'));
            static::creating(function (RbacRole $role): void {
                $role->record_type ??= 'role';
                $role->permissions_map ??= ['ids' => [], 'keys' => []];
            });
        }
    }

    public function getPermissionsAttribute(): EloquentCollection
    {
        if ($this->usesNormalizedRbacTables()) {
            $permissionIds = DB::table('rbac_permission_role')
                ->where('role_id', $this->getKey())
                ->pluck('permission_id')
                ->all();

            return RbacPermission::whereIn('id', $permissionIds)->orderBy('group')->orderBy('name')->get();
        }

        $ids = $this->permissionIds();

        return RbacPermission::whereIn('id', $ids)->orderBy('group')->orderBy('name')->get();
    }

    public function getUsersAttribute(): EloquentCollection
    {
        return $this->assignedUsers();
    }

    public function permissionIds(): array
    {
        if ($this->usesNormalizedRbacTables()) {
            return DB::table('rbac_permission_role')
                ->where('role_id', $this->getKey())
                ->pluck('permission_id')
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values()
                ->all();
        }

        $map = $this->permissions_map;
        if (is_string($map)) {
            $map = json_decode($map, true) ?: [];
        }

        return collect($map['ids'] ?? [])
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function permissionKeys(): array
    {
        if ($this->usesNormalizedRbacTables()) {
            return DB::table('rbac_permission_role')
                ->join('rbac_permissions', 'rbac_permissions.id', '=', 'rbac_permission_role.permission_id')
                ->where('rbac_permission_role.role_id', $this->getKey())
                ->pluck('rbac_permissions.key')
                ->map(fn ($key) => (string) $key)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        $map = $this->permissions_map;
        if (is_string($map)) {
            $map = json_decode($map, true) ?: [];
        }

        return collect($map['keys'] ?? [])
            ->map(fn ($key) => (string) $key)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function syncPermissionIds(array $permissionIds): void
    {
        $permissionIds = RbacPermission::whereIn('id', $permissionIds)->pluck('id')->map(fn ($id) => (string) $id)->unique()->values();
        $permissionKeys = RbacPermission::whereIn('id', $permissionIds)->pluck('key')->map(fn ($key) => (string) $key)->unique()->sort()->values();

        if (Schema::hasColumn($this->getTable(), 'permissions_map')) {
            $this->update([
                'permissions_map' => [
                    'ids' => $permissionIds->all(),
                    'keys' => $permissionKeys->all(),
                ],
            ]);
        }

        if ($this->usesConsolidatedTable('rbac_matrix', 'rbac_roles', 'record_type')) {
            DB::table('rbac_matrix')->where('record_type', 'role_permission')->where('role_id', $this->getKey())->delete();
            foreach ($permissionIds as $permissionId) {
                DB::table('rbac_matrix')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'record_type' => 'role_permission',
                    'role_id' => (string) $this->getKey(),
                    'permission_id' => (string) $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            DB::table('rbac_permission_role')->where('role_id', $this->getKey())->delete();
            foreach ($permissionIds as $permissionId) {
                DB::table('rbac_permission_role')->insert([
                    'role_id' => (string) $this->getKey(),
                    'permission_id' => (string) $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        User::refreshPermissionSnapshotsForRole((string) $this->getKey());
    }

    public function assignedUsers(): EloquentCollection
    {
        if (! $this->usesConsolidatedTable('rbac_matrix', 'rbac_roles', 'record_type')) {
            $userIds = DB::table('rbac_role_user')->where('role_id', $this->getKey())->pluck('user_id')->all();
        } else {
            $userIds = DB::table('rbac_matrix')->where('record_type', 'user_role')->where('role_id', $this->getKey())->pluck('user_id')->all();
        }

        return User::whereIn('id', $userIds)->get();
    }

    public function isLocked(): bool
    {
        return (bool) $this->is_system;
    }

    private function usesNormalizedRbacTables(): bool
    {
        return ! $this->usesConsolidatedTable('rbac_matrix', 'rbac_roles', 'record_type')
            && Schema::hasTable('rbac_permission_role')
            && Schema::hasTable('rbac_permissions');
    }
}
