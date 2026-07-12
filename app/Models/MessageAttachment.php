<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'message_id',
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

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
