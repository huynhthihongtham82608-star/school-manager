<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubstituteTeaching extends Model
{
    use HasFactory, UsesUuid;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';

    public const SCOPE_PERIOD = 'period';
    public const SCOPE_DATE_RANGE = 'date_range';

    protected $fillable = [
        'substitute_date',
        'scope_type',
        'from_date',
        'to_date',
        'timetable_entry_id',
        'class_id',
        'semester_id',
        'school_year_id',
        'original_teacher_id',
        'substitute_teacher_id',
        'status',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'substitute_date' => 'date',
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ duyệt',
            self::STATUS_APPROVED => 'Đã duyệt',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? self::statusLabels()[self::STATUS_PENDING];
    }

    public static function scopeLabels(): array
    {
        return [
            self::SCOPE_PERIOD => 'Theo tiết',
            self::SCOPE_DATE_RANGE => 'Theo khoảng ngày',
        ];
    }

    public function timetableEntry()
    {
        return $this->belongsTo(TimetableEntry::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function originalTeacher()
    {
        return $this->belongsTo(Teacher::class, 'original_teacher_id');
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }
}
