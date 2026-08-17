<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimetableEntry extends Model
{
    use HasFactory, UsesUuid;

    public $timestamps = false;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Hoạt động',
        self::STATUS_PAUSED => 'Tạm dừng',
        self::STATUS_ARCHIVED => 'Lưu trữ',
    ];

    protected $fillable = [
        'timetable_id',
        'assignment_id',
        'day_of_week',
        'period',
        'subject_id',
        'teacher_id',
        'room',
        'room_id',
        'note',
        'status',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function timetable()
    {
        return $this->belongsTo(Timetable::class);
    }

    public function assignment()
    {
        return $this->belongsTo(TeachingAssignment::class, 'assignment_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function approvedSubstitutes()
    {
        return $this->hasMany(SubstituteTeaching::class, 'timetable_entry_id')
            ->where('status', SubstituteTeaching::STATUS_APPROVED);
    }

    public function roomInfo()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function displayRoom(): ?string
    {
        return $this->roomInfo?->name ?: $this->room;
    }

    public function displaySubjectName(): string
    {
        return (string) ($this->assignment?->subject?->name ?? $this->subject?->name ?? '');
    }

    public function displayTeacherName(): string
    {
        $subject = $this->assignment?->subject ?? $this->subject;

        if ($this->teacher?->name && (string) $this->teacher_id !== (string) $this->assignment?->teacher_id) {
            return $this->teacher->name;
        }

        if ($this->assignment?->teacher?->name) {
            return $this->assignment->teacher->name;
        }

        if ($subject?->isHomeroomSubject()) {
            $this->loadMissing('timetable.classRoom.homeroomTeacher');

            return $this->timetable?->classRoom?->homeroomTeacher?->name
                ?: ($this->teacher?->name ?: 'Giáo viên chủ nhiệm');
        }

        if ($this->teacher?->name) {
            return $this->teacher->name;
        }

        if ($subject?->isActivitySubject()) {
            return 'Toàn trường';
        }

        return '';
    }

    public function hasApprovedSubstitute(): bool
    {
        if ($this->relationLoaded('approvedSubstitutes')) {
            return $this->approvedSubstitutes->isNotEmpty();
        }

        return $this->approvedSubstitutes()->exists();
    }

    public function displaySubstituteMarker(): string
    {
        return $this->hasApprovedSubstitute() ? '(Dạy thay)' : '';
    }

    public function displayRoomLabel(): ?string
    {
        $room = $this->displayRoom();

        if ($room) {
            return $room;
        }

        $subject = $this->assignment?->subject ?? $this->subject;

        return $subject?->isActivitySubject() ? 'Sân trường' : null;
    }

    public function sessionLabel(): string
    {
        return (int) $this->period <= 5 ? 'Buổi sáng' : 'Buổi chiều';
    }

    public function periodInSession(): int
    {
        return (int) $this->period <= 5 ? (int) $this->period : (int) $this->period - 5;
    }

    public function displayPeriod(): string
    {
        return $this->sessionLabel() . ' - Tiết ' . $this->periodInSession();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES[self::STATUS_ACTIVE];
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAUSED => 'bg-warning text-dark',
            self::STATUS_ARCHIVED => 'bg-light text-muted border',
            default => 'bg-success',
        };
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED || $this->archived_at !== null;
    }
}
