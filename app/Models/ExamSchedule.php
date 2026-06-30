<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ExamSchedule extends Model
{
    use UsesUuid;

    public const EXAM_TYPES = [
        'Kiểm tra 15 phút',
        'Kiểm tra 1 tiết',
        'Giữa kỳ',
        'Cuối kỳ',
        'Khảo sát',
        'Khác',
    ];

    public const MANAGEMENT_STATUSES = ['draft', 'published', 'canceled'];

    private const META_PATTERN = '/\n?<!--school_manager_meta:(.*?)-->\s*$/s';

    protected $fillable = [
        'title',
        'class_id',
        'subject_id',
        'semester_id',
        'exam_date',
        'start_time',
        'end_time',
        'room',
        'note',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public function getNoteAttribute($value): ?string
    {
        return $this->stripMeta($value);
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
