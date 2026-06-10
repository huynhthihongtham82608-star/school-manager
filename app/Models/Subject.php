<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'name',
        'credit',
        'is_weighted',
    ];

    protected $casts = [
        'is_weighted' => 'boolean',
    ];

    public function assignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }
}
