<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'name',
        'order',
        'school_year_id',
        'is_score_input_open',
    ];

    protected $casts = [
        'is_score_input_open' => 'boolean',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
