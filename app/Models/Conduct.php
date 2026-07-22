<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conduct extends Model
{
    use HasFactory, UsesUuid;

    public const LEVEL_GOOD = 'excellent';
    public const LEVEL_FAIR = 'good';
    public const LEVEL_PASS = 'average';
    public const LEVEL_NOT_PASS = 'weak';

    public const LEVELS = [
        self::LEVEL_GOOD => 'Tốt',
        self::LEVEL_FAIR => 'Khá',
        self::LEVEL_PASS => 'Đạt',
        self::LEVEL_NOT_PASS => 'Chưa đạt',
    ];

    protected $fillable = [
        'student_id',
        'class_id',
        'semester_id',
        'school_year_id',
        'conduct_level',
        'comment',
    ];

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

    public function levelLabel(): string
    {
        return self::LEVELS[$this->conduct_level] ?? '-';
    }
}
