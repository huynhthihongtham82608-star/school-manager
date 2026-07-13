<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ExamSchedule extends Model
{
    use UsesUuid;

    public const TYPE_MIDTERM = 'midterm';
    public const TYPE_FINAL_TEST = 'final_test';
    public const TYPE_CUSTOM = 'custom';

    public const EXAM_TYPES = [
        self::TYPE_MIDTERM => 'Kiểm tra giữa kỳ',
        self::TYPE_FINAL_TEST => 'Kiểm tra cuối kỳ',
        self::TYPE_CUSTOM => 'Khác...',
    ];

    public const MANAGEMENT_STATUSES = ['draft', 'published', 'canceled'];

    private const META_PATTERN = '/\n?<!--school_manager_meta:(.*?)-->\s*$/s';

    protected $fillable = [
        'title',
        'type',
        'display_name',
        'class_id',
        'subject_id',
        'semester_id',
        'exam_date',
        'start_time',
        'end_time',
        'room',
        'score_input_opens_at',
        'score_input_closes_at',
        'note',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'score_input_opens_at' => 'date',
        'score_input_closes_at' => 'date',
    ];

    public function getNoteAttribute($value): ?string
    {
        return $this->stripMeta($value);
    }

    public function displayName(): string
    {
        return trim((string) ($this->display_name ?: $this->title)) ?: $this->typeLabel();
    }

    public function typeLabel(): string
    {
        return self::EXAM_TYPES[$this->type] ?? $this->display_name ?? $this->title ?? 'Khác...';
    }

    public function isCustomType(): bool
    {
        return $this->type === self::TYPE_CUSTOM;
    }

    public function isScoreInputOpen(?Carbon $date = null): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        $date ??= now();
        $opensAt = $this->score_input_opens_at?->copy()->startOfDay();
        $closesAt = $this->score_input_closes_at?->copy()->endOfDay();

        if (! $opensAt || ! $closesAt) {
            return false;
        }

        return $date->betweenIncluded($opensAt, $closesAt);
    }

    public function scoreInputStatusLabel(): string
    {
        if ($this->isCanceled()) {
            return 'Đã hủy';
        }

        if ($this->isDraft()) {
            return 'Chưa công bố';
        }

        if (! $this->score_input_opens_at || ! $this->score_input_closes_at) {
            return 'Chưa mở nhập điểm';
        }

        if (now()->lt($this->score_input_opens_at->copy()->startOfDay())) {
            return 'Chưa mở nhập điểm';
        }

        if (now()->gt($this->score_input_closes_at->copy()->endOfDay())) {
            return 'Đã khóa nhập điểm';
        }

        return 'Đang mở nhập điểm';
    }

    public function scoreInputBadgeClass(): string
    {
        return match ($this->scoreInputStatusLabel()) {
            'Đang mở nhập điểm' => 'bg-success',
            'Chưa mở nhập điểm' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    }

    public function schoolYearId(): ?string
    {
        return $this->meta()['school_year_id'] ?? $this->semester?->school_year_id;
    }

    public function statusValue(): string
    {
        $status = $this->meta()['status'] ?? 'published';

        return in_array($status, self::MANAGEMENT_STATUSES, true) ? $status : 'published';
    }

    public function isDraft(): bool
    {
        return $this->statusValue() === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->statusValue() === 'published';
    }

    public function isCanceled(): bool
    {
        return $this->statusValue() === 'canceled';
    }

    public function statusLabel(): string
    {
        if ($this->isDraft()) {
            return 'Bản nháp';
        }

        if ($this->isCanceled()) {
            return 'Đã hủy';
        }

        $startsAt = $this->startsAt();
        $endsAt = $this->endsAt();
        $now = now();

        if ($startsAt && $now->lt($startsAt)) {
            return 'Sắp diễn ra';
        }

        if ($endsAt && $now->gt($endsAt)) {
            return 'Đã kết thúc';
        }

        return 'Đang diễn ra';
    }

    public function timeRange(): string
    {
        return trim(($this->displayTime($this->start_time) ?: '') . ' - ' . ($this->displayTime($this->end_time) ?: ''), ' -') ?: 'Đang cập nhật';
    }

    public static function withMeta(?string $note, array $meta): string
    {
        $status = $meta['status'] ?? 'draft';
        $status = in_array($status, self::MANAGEMENT_STATUSES, true) ? $status : 'draft';
        $note = trim((string) self::stripMeta($note));

        return trim($note . "\n<!--school_manager_meta:" . json_encode([
            'school_year_id' => $meta['school_year_id'] ?? null,
            'status' => $status,
        ]) . '-->');
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    private function startsAt(): ?Carbon
    {
        if (! $this->exam_date) {
            return null;
        }

        $date = $this->exam_date->format('Y-m-d');
        $time = $this->displayTime($this->start_time);

        return $time
            ? Carbon::parse($date . ' ' . $time)
            : $this->exam_date->copy()->startOfDay();
    }

    private function endsAt(): ?Carbon
    {
        if (! $this->exam_date) {
            return null;
        }

        $date = $this->exam_date->format('Y-m-d');
        $time = $this->displayTime($this->end_time ?: $this->start_time);

        return $time
            ? Carbon::parse($date . ' ' . $time)
            : $this->exam_date->copy()->endOfDay();
    }

    private function displayTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }

    private function meta(): array
    {
        if (! preg_match(self::META_PATTERN, (string) $this->getRawOriginal('note'), $matches)) {
            return [];
        }

        $decoded = json_decode($matches[1], true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function stripMeta(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim((string) preg_replace(self::META_PATTERN, '', $value));
    }
}
