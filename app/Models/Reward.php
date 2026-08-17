<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory, UsesUuid;

    public const TYPE_OUTSTANDING = 'outstanding';
    public const TYPE_GOOD = 'good';
    public const TYPE_SUDDEN = 'sudden';

    protected $fillable = [
        'student_id',
        'class_id',
        'semester_id',
        'school_year_id',
        'reward_type',
        'detail',
        'decision_number',
        'created_by',
        'updated_by',
    ];

    public static function typeLabels(): array
    {
        return [
            self::TYPE_OUTSTANDING => 'Học sinh xuất sắc',
            self::TYPE_GOOD => 'Học sinh giỏi',
            self::TYPE_SUDDEN => 'Khen thưởng đột xuất',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->reward_type] ?? (string) $this->reward_type;
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
