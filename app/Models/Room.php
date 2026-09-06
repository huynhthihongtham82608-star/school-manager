<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Room extends Model
{
    use HasFactory, UsesUuid;

    public const TYPE_STANDARD = 'standard';
    public const TYPE_COMPUTER = 'computer';
    public const TYPE_LAB = 'lab';
    public const TYPE_MULTIPURPOSE = 'multipurpose';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_STANDARD => 'Phòng học thường',
        self::TYPE_COMPUTER => 'Phòng máy',
        self::TYPE_LAB => 'Phòng thí nghiệm',
        self::TYPE_MULTIPURPOSE => 'Phòng đa năng',
        self::TYPE_OTHER => 'Khác...',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Hoạt động',
        self::STATUS_INACTIVE => 'Ngưng sử dụng',
        self::STATUS_MAINTENANCE => 'Bảo trì',
    ];

    protected $fillable = [
        'name',
        'type',
        'custom_type',
        'capacity',
        'fixed_class_id',
        'status',
        'note',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function timetableEntries()
    {
        return $this->hasMany(TimetableEntry::class, 'room_id');
    }

    public function fixedClass()
    {
        return $this->belongsTo(SchoolClass::class, 'fixed_class_id');
    }

    public function typeLabel(): string
    {
        if ($this->type === self::TYPE_OTHER && trim((string) $this->custom_type) !== '') {
            return (string) $this->custom_type;
        }

        return self::TYPES[$this->type] ?? self::TYPES[self::TYPE_STANDARD];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES[self::STATUS_ACTIVE];
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_INACTIVE => 'bg-secondary',
            self::STATUS_MAINTENANCE => 'bg-warning text-dark',
            default => 'bg-success',
        };
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isUsed(): bool
    {
        return $this->timetableEntries()->exists()
            || $this->isReferencedByExamSchedule();
    }

    public function canEditName(): bool
    {
        return ! $this->isUsed();
    }

    public function canDelete(): bool
    {
        return ! $this->isUsed();
    }

    private function isReferencedByExamSchedule(): bool
    {
        if (! Schema::hasTable('exam_schedules') || ! Schema::hasColumn('exam_schedules', 'room')) {
            return false;
        }

        $values = collect([$this->name, $this->getKey()])
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values();

        return $values->isNotEmpty()
            && DB::table('exam_schedules')->whereIn('room', $values->all())->exists();
    }
}
