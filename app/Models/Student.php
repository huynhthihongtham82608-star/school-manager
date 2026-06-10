<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'student_code',
        'name',
        'gender',
        'dob',
        'address',
        'parent_phone',
        'email',
        'class_id',
        'school_year_id',
        'status',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function scoreHeaders()
    {
        return $this->hasMany(ScoreHeader::class);
    }

    public function conductRecords()
    {
        return $this->hasMany(Conduct::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function parents()
    {
        return $this->belongsToMany(ParentProfile::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot(['relation']);
    }
}
