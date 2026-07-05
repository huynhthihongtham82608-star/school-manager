<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory, UsesUuid;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'order',
        'school_year_id',
        'is_score_input_open',
        'status',
        'locked_at',
        'archived_at',
    ];

    protected $casts = [
        'is_score_input_open' => 'boolean',
        'locked_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public static function termOptions(): array
    {
        return [
            'Học kỳ 1' => 'Học kỳ 1',
            'Học kỳ 2' => 'Học kỳ 2',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Bản nháp',
            self::STATUS_INACTIVE => 'Chưa hoạt động',
            self::STATUS_ACTIVE => 'Hoạt động',
            self::STATUS_LOCKED => 'Khóa',
            self::STATUS_ARCHIVED => 'Lưu trữ',
        ];
    }

    public function normalizedName(): string
    {
        $name = trim((string) $this->name);

        return match (true) {
            in_array($name, ['HK1', 'Hoc ky 1', 'Học kì 1', 'Học kỳ 1'], true), str_contains($name, '1') => 'Học kỳ 1',
            in_array($name, ['HK2', 'Hoc ky 2', 'Học kì 2', 'Học kỳ 2'], true), str_contains($name, '2') => 'Học kỳ 2',
            default => $name,
        };
    }

    public function termIndex(): int
    {
        return $this->normalizedName() === 'Học kỳ 1' ? 1 : 2;
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status ?: self::STATUS_INACTIVE] ?? 'Chưa hoạt động';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-warning text-dark',
            self::STATUS_ACTIVE => 'bg-success',
            self::STATUS_LOCKED => 'bg-secondary',
            self::STATUS_ARCHIVED => 'bg-light text-muted border',
            default => 'bg-info text-dark',
        };
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isInactive(): bool
    {
        return ! $this->status || $this->status === self::STATUS_INACTIVE;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED || $this->archived_at !== null;
    }

    public function isReadOnly(): bool
    {
        return $this->isLocked() || $this->isArchived();
    }

    public function canEdit(): bool
    {
        return $this->isDraft() || $this->isInactive();
    }

    public function canMoveToInactive(): bool
    {
        return $this->isDraft();
    }

    public function canActivate(): bool
    {
        return $this->isInactive();
    }

    public function canLock(): bool
    {
        return $this->isActive();
    }

    public function canArchive(): bool
    {
        return $this->isLocked();
    }
}
