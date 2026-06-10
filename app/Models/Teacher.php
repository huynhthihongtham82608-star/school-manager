<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'teacher_code',
        'name',
        'phone',
        'email',
        'qualification',
        'main_subject',
        'is_homeroom',
    ];

    public function assignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function homeroomClasses()
    {
        return $this->hasMany(SchoolClass::class, 'homeroom_teacher_id');
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
