<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, UsesUuid;

    protected $authPasswordName = 'password_hash';

    protected $fillable = [
        'username',
        'full_name',
        'email',
        'phone',
        'role',
        'role_type',
        'source_profile_id',
        'teacher_id',
        'student_id',
        'parent_id',
        'student_code',
        'teacher_code',
        'parent_code',
        'name',
        'gender',
        'dob',
        'address',
        'place_of_birth',
        'ethnicity',
        'religion',
        'parent_phone',
        'enrollment_date',
        'admission_type',
        'previous_school',
        'transfer_grade_level',
        'previous_class',
        'avatar',
        'note',
        'class_id',
        'school_year_id',
        'status',
        'joined_at',
        'work_status',
        'qualification',
        'main_subject',
        'primary_subject_id',
        'department_id',
        'is_homeroom',
        'rbac_role_ids',
        'permissions_map',
        'password_hash',
        'is_active',
        'login_status',
        'is_super_admin',
        'force_change_password',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'login_status' => 'boolean',
            'is_super_admin' => 'boolean',
            'force_change_password' => 'boolean',
            'dob' => 'date',
            'enrollment_date' => 'date',
            'joined_at' => 'date',
            'is_homeroom' => 'boolean',
            'rbac_role_ids' => 'array',
            'permissions_map' => 'array',
        ];
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function parentProfile()
    {
        return $this->belongsTo(ParentProfile::class, 'parent_id');
    }

    public function rbacRoles()
    {
        return $this->belongsToMany(RbacRole::class, 'rbac_role_user', 'user_id', 'role_id')
            ->withTimestamps();
    }

    public function getDisplayNameAttribute(): string
    {
        if (trim((string) $this->full_name) !== '') {
            return $this->full_name;
        }

        if ($this->teacher) {
            return $this->teacher->name;
        }
        if ($this->student) {
            return $this->student->name;
        }
        if ($this->parentProfile) {
            return $this->parentProfile->name;
        }

        if (trim((string) $this->username) !== '') {
            return $this->username;
        }

        if (trim((string) $this->email) !== '') {
            return $this->email;
        }

        return 'Người dùng';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'staff'], true);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin || ($this->role === 'admin' && $this->username === 'admin');
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isTeacher(): bool
    {
        return in_array($this->role, ['teacher', 'homeroom'], true)
            || in_array($this->role_type, ['teacher', 'homeroom'], true);
    }

    public function isHomeroom(): bool
    {
        return $this->isTeacher()
            && ((bool) $this->is_homeroom || (bool) $this->teacher?->is_homeroom || $this->role === 'homeroom' || $this->role_type === 'homeroom');
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isSuperAdmin() || $this->role === 'admin') {
            return true;
        }

        if (! in_array($this->role, ['staff'], true)) {
            return false;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('rbac_matrix') && ! \Illuminate\Support\Facades\Schema::hasTable('rbac_roles')) {
            return true;
        }

        $permissionKeys = collect($this->permissionKeys());

        if ($permissionKeys->contains($permission)) {
            return true;
        }

        foreach ($this->permissionAliases($permission) as $alias) {
            if ($permissionKeys->contains($alias)) {
                return true;
            }
        }

        if (str_ends_with($permission, '.view')) {
            return $permissionKeys->contains(str_replace('.view', '.manage', $permission));
        }

        return false;
    }

    public function getRbacRolesAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        $roleIds = $this->rbacRoleIds();

        return RbacRole::whereIn('id', $roleIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function rbacRoleIds(): array
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('rbac_role_user')) {
            $ids = \Illuminate\Support\Facades\DB::table('rbac_role_user')
                ->where('user_id', $this->getKey())
                ->pluck('role_id')
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values()
                ->all();

            if ($ids) {
                return $ids;
            }
        }

        $ids = $this->rbac_role_ids;
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?: [];
        }

        if (is_array($ids) && $ids) {
            return collect($ids)->map(fn ($id) => (string) $id)->unique()->values()->all();
        }

        return [];
    }

    public function permissionKeys(): array
    {
        if (
            \Illuminate\Support\Facades\Schema::hasTable('rbac_role_user')
            && \Illuminate\Support\Facades\Schema::hasTable('rbac_permission_role')
            && \Illuminate\Support\Facades\Schema::hasTable('rbac_permissions')
        ) {
            $roleIds = $this->rbacRoleIds();

            if ($roleIds !== []) {
                return \Illuminate\Support\Facades\DB::table('rbac_roles')
                    ->join('rbac_permission_role', 'rbac_permission_role.role_id', '=', 'rbac_roles.id')
                    ->join('rbac_permissions', 'rbac_permissions.id', '=', 'rbac_permission_role.permission_id')
                    ->whereIn('rbac_roles.id', $roleIds)
                    ->where('rbac_roles.is_active', true)
                    ->pluck('rbac_permissions.key')
                    ->map(fn ($key) => (string) $key)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            }
        }

        $keys = $this->permissions_map;
        if (is_string($keys)) {
            $keys = json_decode($keys, true) ?: [];
        }

        if (is_array($keys) && $keys) {
            return collect($keys)->map(fn ($key) => (string) $key)->unique()->sort()->values()->all();
        }

        return RbacRole::whereIn('id', $this->rbacRoleIds())
            ->where('is_active', true)
            ->get()
            ->flatMap(fn (RbacRole $role) => $role->permissionKeys())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function syncRbacRoleIds(array $roleIds): void
    {
        $roleIds = RbacRole::whereIn('id', $roleIds)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        $permissionKeys = RbacRole::whereIn('id', $roleIds)
            ->where('is_active', true)
            ->get()
            ->flatMap(fn (RbacRole $role) => $role->permissionKeys())
            ->unique()
            ->sort()
            ->values();

        $this->forceFill([
            'rbac_role_ids' => $roleIds->all(),
            'permissions_map' => $permissionKeys->all(),
        ])->save();

        if (\Illuminate\Support\Facades\Schema::hasTable('rbac_role_user')) {
            \Illuminate\Support\Facades\DB::table('rbac_role_user')->where('user_id', $this->getKey())->delete();
            foreach ($roleIds as $roleId) {
                \Illuminate\Support\Facades\DB::table('rbac_role_user')->insert([
                    'user_id' => (string) $this->getKey(),
                    'role_id' => (string) $roleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } elseif (\Illuminate\Support\Facades\Schema::hasTable('rbac_matrix')) {
            \Illuminate\Support\Facades\DB::table('rbac_matrix')->where('record_type', 'user_role')->where('user_id', $this->getKey())->delete();
            foreach ($roleIds as $roleId) {
                \Illuminate\Support\Facades\DB::table('rbac_matrix')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'record_type' => 'user_role',
                    'user_id' => (string) $this->getKey(),
                    'role_id' => (string) $roleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function refreshPermissionSnapshotFromAssignedRoles(): void
    {
        $roleIds = collect($this->rbacRoleIds())
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->unique()
            ->values();

        $permissionKeys = RbacRole::whereIn('id', $roleIds)
            ->where('is_active', true)
            ->get()
            ->flatMap(fn (RbacRole $role) => $role->permissionKeys())
            ->unique()
            ->sort()
            ->values();

        $this->forceFill([
            'rbac_role_ids' => $roleIds->all(),
            'permissions_map' => $permissionKeys->all(),
        ])->save();
    }

    public static function refreshPermissionSnapshotsForRole(string $roleId): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('rbac_role_user')) {
            $userIds = \Illuminate\Support\Facades\DB::table('rbac_role_user')
                ->where('role_id', $roleId)
                ->pluck('user_id');

            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'rbac_role_ids')) {
                $jsonUserIds = \Illuminate\Support\Facades\DB::table('users')
                    ->whereRaw("JSON_CONTAINS(COALESCE(rbac_role_ids, '[]'), JSON_QUOTE(?))", [$roleId])
                    ->pluck('id');

                $userIds = $userIds->merge($jsonUserIds)->unique()->values();
            }

            static::whereIn('id', $userIds)->get()->each(fn (User $user) => $user->refreshPermissionSnapshotFromAssignedRoles());

            return;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('rbac_matrix')) {
            return;
        }

        $userIds = \Illuminate\Support\Facades\DB::table('rbac_matrix')
            ->where('record_type', 'user_role')
            ->where('role_id', $roleId)
            ->pluck('user_id');

        static::whereIn('id', $userIds)->get()->each(fn (User $user) => $user->refreshPermissionSnapshotFromAssignedRoles());
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    private function permissionAliases(string $permission): array
    {
        return match ($permission) {
            'students.manage', 'teachers.manage', 'parents.manage', 'manage_admin_accounts' => [
                'view_users',
                'create_users',
                'edit_users',
                'delete_users',
            ],
            'view_users', 'create_users', 'edit_users', 'delete_users' => [
                'students.manage',
                'teachers.manage',
                'parents.manage',
                'manage_admin_accounts',
            ],
            'scores.view' => ['view_scores', 'scores.manage'],
            'view_scores' => ['scores.view', 'scores.manage'],
            'scores.manage' => ['input_scores', 'lock_score_window'],
            'input_scores', 'lock_score_window' => ['scores.manage'],
            'view_tuition' => ['system.settings'],
            'collect_tuition' => ['system.settings'],
            'setup_tuition_fees' => ['system.settings'],
            'view_rewards' => ['conduct.view', 'conduct.manage'],
            'create_rewards', 'delete_rewards' => ['conduct.manage'],
            'view_leave_requests' => ['attendance.view', 'attendance.manage'],
            'approve_leave_requests' => ['attendance.manage'],
            'access_chatbot', 'configure_gemini_api' => ['system.settings'],
            default => [],
        };
    }
}
