<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentProfile extends Model
{
    use HasFactory, UsesUuid;

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
