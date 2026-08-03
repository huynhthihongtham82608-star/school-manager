<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreSetting extends Model
{
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
