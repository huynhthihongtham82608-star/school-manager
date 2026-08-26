<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentProfile extends Model
{
    use HasFactory, UsesUuid, UsesConsolidatedTable;

    public const RELATION_FATHER = 'father';
    public const RELATION_MOTHER = 'mother';
    public const RELATION_GUARDIAN = 'guardian';

    protected $table = 'parents';

    protected $fillable = [
        'parent_code',
        'name',
        'phone',
        'email',
        'address',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('users', 'parents', 'role_type') ? 'users' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('users', 'parents', 'role_type')) {
            static::addGlobalScope('parent_records', fn ($query) => $query->where('role_type', 'parent'));
            static::creating(function (ParentProfile $parent): void {
                $parent->role_type ??= 'parent';
                $parent->role ??= 'parent';
                $parent->full_name ??= $parent->name;
                $parent->username ??= $parent->parent_code ?: $parent->phone;
                $parent->password_hash ??= '$2y$12$Z2FJ9wVbk03D58EQ38Fn6O9z3.nBTeoNRZoh9c6uPfYRJrL46Y0wW';
                $parent->parent_id ??= $parent->id;
            });
        }
    }

    public static function relationLabels(): array
    {
        return [
            self::RELATION_FATHER => 'Cha',
            self::RELATION_MOTHER => 'Mẹ',
            self::RELATION_GUARDIAN => 'Người giám hộ',
        ];
    }

    public static function relationLabel(?string $relation): string
    {
        return self::relationLabels()[$relation] ?? match ($relation) {
            'PH' => 'Phụ huynh',
            default => $relation ?: '-',
        };
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot(['relation']);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'parent_id');
    }
}
