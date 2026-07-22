<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class RbacPermission extends Model
{
    use UsesUuid;

    protected $table = 'rbac_permissions';

    protected $fillable = [
        'key',
        'name',
        'group',
        'description',
    ];

    public function roles()
    {
        return $this->belongsToMany(RbacRole::class, 'rbac_permission_role', 'permission_id', 'role_id')
            ->withTimestamps();
    }
}
