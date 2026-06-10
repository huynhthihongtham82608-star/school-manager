<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreHeader extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'student_id',
        'subject_id',
        'semester_id',
        'school_year_id',
        'average',
    ];

    protected $casts = [
        'average' => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function details()
    {
        return $this->hasMany(ScoreDetail::class);
    }
}
