<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreDetail extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'score_header_id',
        'type',
        'value',
        'weight_group',
    ];

    protected $casts = [
        'value' => 'float',
    ];

    public function scoreHeader()
    {
        return $this->belongsTo(ScoreHeader::class);
    }
}
