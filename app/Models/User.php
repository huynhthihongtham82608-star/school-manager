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

    protected $fillable = [
        'username',
        'full_name',
        'email',
        'phone',
        'role',
        'teacher_id',
        'student_id',
        'parent_id',
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
            ->with('permissions')
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
        return $this->username;
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
        return $this->role === 'teacher';
    }

    public function isHomeroom(): bool
    {
        return $this->isTeacher() && (bool) $this->teacher?->is_homeroom;
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

        if (! \Illuminate\Support\Facades\Schema::hasTable('rbac_roles') || ! \Illuminate\Support\Facades\Schema::hasTable('rbac_role_user')) {
            return true;
        }

        $roles = $this->relationLoaded('rbacRoles')
            ? $this->rbacRoles
            : $this->rbacRoles()->get();

        $permissionKeys = $roles
            ->filter(fn (RbacRole $role) => $role->is_active)
            ->flatMap(fn (RbacRole $role) => $role->permissions)
            ->pluck('key')
            ->unique()
            ->values();

        if ($permissionKeys->contains($permission)) {
            return true;
        }

        if (str_ends_with($permission, '.view')) {
            return $permissionKeys->contains(str_replace('.view', '.manage', $permission));
        }

        return false;
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
}
