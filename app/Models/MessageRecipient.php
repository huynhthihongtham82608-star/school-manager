<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageRecipient extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'message_id',
        'receiver_user_id',
        'is_read',
        'read_at',
        'deleted_at',
        'permanently_deleted_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'deleted_at' => 'datetime',
        'permanently_deleted_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }
}
