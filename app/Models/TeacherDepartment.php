<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherDepartment extends Model
{
    use HasFactory, UsesUuid;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Hoạt động',
        self::STATUS_INACTIVE => 'Ngưng sử dụng',
    ];

    protected $fillable = [
        'code',
        'name',
        'leader_teacher_id',
        'description',
        'status',
    ];

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_department_subject', 'department_id', 'subject_id')
            ->withTimestamps();
    }

    public function leader()
    {
        return $this->belongsTo(Teacher::class, 'leader_teacher_id');
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class, 'department_id');
    }

    public function activeTeachers()
    {
        return $this->teachers()->where('work_status', Teacher::STATUS_WORKING);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES[self::STATUS_ACTIVE];
    }

    public function statusBadgeClass(): string
    {
        return $this->status === self::STATUS_INACTIVE
            ? 'bg-secondary'
            : 'bg-success';
    }

    public function subjectNames(): string
    {
        $subjects = $this->relationLoaded('subjects')
            ? $this->subjects
            : $this->subjects()->orderBy('name')->get();

        return $subjects->pluck('name')->filter()->join(', ') ?: 'Chưa gán môn';
    }
}
