<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    use UsesUuid;

    protected $fillable = [
        'title',
        'class_id',
        'subject_id',
        'semester_id',
        'exam_date',
        'start_time',
        'end_time',
        'room',
        'note',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
