<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class HomePageContent extends Model
{
    use UsesUuid;

    protected $fillable = [
        'key',
        'title',
        'content',
        'image_url',
        'extra',
    ];

    protected $casts = [
        'extra' => 'array',
    ];
}
