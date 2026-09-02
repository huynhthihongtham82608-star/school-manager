<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class ChatbotMessage extends Model
{
    use UsesUuid;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'question',
        'answer',
        'intent',
        'entities',
        'tool_name',
        'tool_args',
        'tool_result_summary',
        'model',
        'latency_ms',
        'error',
        'created_at',
    ];

    protected $casts = [
        'entities' => 'array',
        'tool_args' => 'array',
        'tool_result_summary' => 'array',
        'latency_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
