<?php

namespace App\Models;

use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ScoreSetting extends Model
{
    use UsesConsolidatedTable;

    public const DEFAULT_WEIGHT_GDTX = 1;
    public const DEFAULT_WEIGHT_DGGK = 2;
    public const DEFAULT_WEIGHT_DGCK = 3;

    protected $fillable = [
        'weight_gdtx',
        'weight_dggk',
        'weight_dgck',
    ];

    protected $casts = [
        'weight_gdtx' => 'integer',
        'weight_dggk' => 'integer',
        'weight_dgck' => 'integer',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('student_scores', 'score_settings', 'score_record_type') ? 'student_scores' : parent::getTable();
    }

    public function getIncrementing()
    {
        return ! $this->usesConsolidatedTable('student_scores', 'score_settings', 'score_record_type');
    }

    public function getKeyType()
    {
        return $this->usesConsolidatedTable('student_scores', 'score_settings', 'score_record_type') ? 'string' : 'int';
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('student_scores', 'score_settings', 'score_record_type')) {
            static::addGlobalScope('score_setting_records', fn ($query) => $query->where('score_record_type', 'setting'));
            static::creating(function (ScoreSetting $setting): void {
                $setting->id ??= (string) Str::uuid();
                $setting->score_record_type ??= 'setting';
            });
        }
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'weight_gdtx' => self::DEFAULT_WEIGHT_GDTX,
            'weight_dggk' => self::DEFAULT_WEIGHT_DGGK,
            'weight_dgck' => self::DEFAULT_WEIGHT_DGCK,
        ]);
    }

    public function weightForScoreType(?string $type): int
    {
        return match ($type) {
            ScoreColumn::TYPE_MIDTERM, 'midterm', 'midterm_test' => max(1, (int) $this->weight_dggk),
            ScoreColumn::TYPE_FINAL, 'final', 'final_test' => max(1, (int) $this->weight_dgck),
            default => max(1, (int) $this->weight_gdtx),
        };
    }

    public function formulaLabel(): string
    {
        return '(Tổng ĐGTX x ' . $this->weight_gdtx
            . ' + Tổng ĐGGK x ' . $this->weight_dggk
            . ' + Tổng ĐGCK x ' . $this->weight_dgck
            . ') / (Số cột ĐGTX x ' . $this->weight_gdtx
            . ' + Số cột ĐGGK x ' . $this->weight_dggk
            . ' + Số cột ĐGCK x ' . $this->weight_dgck . ')';
    }
}
