<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiReport extends Model
{
    use HasFactory, UsesUuid;

    protected $table = 'ai_reports';

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'semester_id',
        'summary',
        'trend',
        'created_at',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}

