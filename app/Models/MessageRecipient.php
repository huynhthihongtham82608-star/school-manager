<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageRecipient extends Model
{
    use HasFactory, UsesUuid, UsesConsolidatedTable;

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

    public function getTable()
    {
        return $this->usesConsolidatedTable('messages', 'message_recipients', 'message_record_type') ? 'messages' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('messages', 'message_recipients', 'message_record_type')) {
            static::addGlobalScope('message_recipient_records', fn ($query) => $query->where('message_record_type', 'recipient'));
            static::creating(fn (MessageRecipient $recipient) => $recipient->message_record_type ??= 'recipient');
        }
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }
}
