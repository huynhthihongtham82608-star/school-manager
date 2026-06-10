<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentProfile extends Model
{
    use HasFactory, UsesUuid;

    protected $table = 'parents';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
    ];

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
