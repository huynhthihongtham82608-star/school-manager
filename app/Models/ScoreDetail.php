<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreDetail extends Model
{
    use HasFactory, UsesUuid, UsesConsolidatedTable;

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

    public function getTable()
    {
        return $this->usesConsolidatedTable('student_scores', 'score_details', 'score_record_type') ? 'student_scores' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('student_scores', 'score_details', 'score_record_type')) {
            static::addGlobalScope('score_detail_records', fn ($query) => $query->where('score_record_type', 'detail'));
            static::creating(function (ScoreDetail $detail): void {
                $detail->score_record_type ??= 'detail';
                $detail->score_type ??= $detail->type;
                $detail->score_name ??= $detail->name;
                $detail->score_value ??= $detail->value;
            });
        }
    }

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
