<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class SchoolPost extends Model
{
    use UsesUuid;

    public const TYPE_NEWS = 'news';
    public const TYPE_ANNOUNCEMENT = 'announcement';

    protected $fillable = [
        'type',
        'title',
        'summary',
        'content',
        'published_at',
        'is_published',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];
}
