<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentClassAssignment extends Model
{
    use HasFactory, UsesUuid;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(SchoolYear::class, 'academic_year_id');
    }
}
