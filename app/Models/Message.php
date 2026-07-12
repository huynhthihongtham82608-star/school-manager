<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory, UsesUuid;

    public $timestamps = false;

    protected $fillable = [
        'sender_user_id',
        'receiver_user_id',
        'conversation_id',
        'parent_message_id',
        'title',
        'content',
        'target_type',
        'recipient_summary',
        'is_read',
        'created_at',
        'sender_deleted_at',
        'sender_permanently_deleted_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'sender_deleted_at' => 'datetime',
        'sender_permanently_deleted_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_message_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_message_id');
    }

    public function recipients()
    {
        return $this->hasMany(MessageRecipient::class);
    }

    public function activeRecipients()
    {
        return $this->hasMany(MessageRecipient::class)
            ->whereNull('permanently_deleted_at');
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function recipientNames(int $limit = 3): string
    {
        $recipients = $this->relationLoaded('recipients') ? $this->recipients : $this->recipients()->with('receiver')->get();
        $names = $recipients
            ->filter(fn ($recipient) => $recipient->receiver)
            ->map(fn ($recipient) => $recipient->receiver->display_name)
            ->values();

        if ($names->count() > $limit) {
            return $names->take($limit)->implode(', ') . ' +' . ($names->count() - $limit) . ' người nhận';
        }

        return $names->implode(', ');
    }

    public function conversationKey(): string
    {
        return $this->conversation_id ?: $this->id;
    }
}
