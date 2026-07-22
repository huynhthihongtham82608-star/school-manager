<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class RbacRole extends Model
{
    use UsesUuid;

    protected $table = 'rbac_roles';

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function permissions()
    {
        return $this->belongsToMany(RbacPermission::class, 'rbac_permission_role', 'role_id', 'permission_id')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'rbac_role_user', 'role_id', 'user_id')
            ->withTimestamps();
    }

    public function isLocked(): bool
    {
        return (bool) $this->is_system;
    }
}
