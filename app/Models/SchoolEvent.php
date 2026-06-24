<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class SchoolEvent extends Model
{
    use UsesUuid;

    protected $fillable = [
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'is_published',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_published' => 'boolean',
    ];
}
