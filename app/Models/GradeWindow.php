<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\SchoolYear;
use App\Models\Subject;

class GradeWindow extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'class_id',
        'subject_id',
        'semester_id',
        'school_year_id',
        'is_open',
    ];

    protected $casts = [
        'is_open' => 'boolean',
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

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
