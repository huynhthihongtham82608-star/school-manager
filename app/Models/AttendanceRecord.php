<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use UsesUuid;

    protected $fillable = [
        'student_id',
        'class_id',
        'semester_id',
        'attendance_date',
        'status',
        'note',
        'recorded_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
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

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
