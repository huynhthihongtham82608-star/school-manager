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
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
