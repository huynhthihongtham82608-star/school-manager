<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectGradeMapping extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'subject_id',
        'grade_level',
    ];

    protected $casts = [
        'grade_level' => 'integer',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
