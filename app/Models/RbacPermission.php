<?php

namespace App\Models;

use App\Models\Concerns\UsesConsolidatedTable;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class RbacPermission extends Model
{
    use UsesUuid, UsesConsolidatedTable;

    protected $table = 'rbac_permissions';

    protected $fillable = [
        'key',
        'name',
        'group',
        'description',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('rbac_matrix', 'rbac_permissions', 'record_type') ? 'rbac_matrix' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('rbac_matrix', 'rbac_permissions', 'record_type')) {
            static::addGlobalScope('permission_records', fn ($query) => $query->where('record_type', 'permission'));
            static::creating(fn (RbacPermission $permission) => $permission->record_type ??= 'permission');
        }
    }
}
