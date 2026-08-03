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
        'is_retest',
        'original_value',
        'retest_updated_at',
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
        'is_retest' => 'boolean',
        'original_value' => 'float',
        'retest_updated_at' => 'datetime',
    ];

    public function scoreHeader()
    {
        return $this->belongsTo(ScoreHeader::class);
    }
}
