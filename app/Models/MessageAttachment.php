<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    use HasFactory, UsesUuid, UsesConsolidatedTable;

    protected $fillable = [
        'message_id',
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('messages', 'message_attachments', 'message_record_type') ? 'messages' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('messages', 'message_attachments', 'message_record_type')) {
            static::addGlobalScope('message_attachment_records', fn ($query) => $query->where('message_record_type', 'attachment'));
            static::creating(fn (MessageAttachment $attachment) => $attachment->message_record_type ??= 'attachment');
        }
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function downloadUrl(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function sizeLabel(): string
    {
        if ($this->size >= 1048576) {
            return number_format($this->size / 1048576, 1) . ' MB';
        }

        return number_format(max(1, $this->size / 1024), 0) . ' KB';
    }
}
