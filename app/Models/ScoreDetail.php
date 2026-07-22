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
        'exam_schedule_id',
        'score_column_id',
        'type',
        'name',
        'value',
        'weight_group',
    ];

    public function examSchedule()
    {
        return $this->belongsTo(ExamSchedule::class);
    }

    public function scoreColumn()
    {
        return $this->belongsTo(ScoreColumn::class);
    }

    protected $casts = [
        'value' => 'float',
    ];

    public function scoreHeader()
    {
        return $this->belongsTo(ScoreHeader::class);
    }
}
