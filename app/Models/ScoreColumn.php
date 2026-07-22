<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ScoreColumn extends Model
{
    use UsesUuid;

    public const TYPE_REGULAR = 'regular';
    public const TYPE_MIDTERM = 'midterm';
    public const TYPE_FINAL = 'final';

    public const TYPES = [
        self::TYPE_REGULAR => 'Đánh giá thường xuyên',
        self::TYPE_MIDTERM => 'Đánh giá giữa kỳ',
        self::TYPE_FINAL => 'Đánh giá cuối kỳ',
    ];

    protected $fillable = [
        'school_year_id',
        'subject_id',
        'grade_level',
        'name',
        'type',
        'weight_group',
        'input_opens_at',
        'input_closes_at',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'grade_level' => 'integer',
        'weight_group' => 'integer',
        'input_opens_at' => 'date',
        'input_closes_at' => 'date',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function details()
    {
        return $this->hasMany(ScoreDetail::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isInputOpen(?Carbon $date = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $date ??= now();

        if ($this->input_opens_at && $date->lt($this->input_opens_at->copy()->startOfDay())) {
            return false;
        }

        if ($this->input_closes_at && $date->gt($this->input_closes_at->copy()->endOfDay())) {
            return false;
        }

        return true;
    }

    public function inputStatusLabel(): string
    {
        if (! $this->is_active) {
            return 'Ngưng sử dụng';
        }

        if ($this->input_opens_at && now()->lt($this->input_opens_at->copy()->startOfDay())) {
            return 'Chưa mở nhập điểm';
        }

        if ($this->input_closes_at && now()->gt($this->input_closes_at->copy()->endOfDay())) {
            return 'Đã khóa nhập điểm';
        }

        return 'Đang mở nhập điểm';
    }

    public function inputStatusBadgeClass(): string
    {
        return match ($this->inputStatusLabel()) {
            'Đang mở nhập điểm' => 'bg-success',
            'Chưa mở nhập điểm' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    }
}
