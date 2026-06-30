<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
        'archived_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function statusLabel(): string
    {
        if ($this->isArchived()) {
            return 'Lưu trữ';
        }

        return $this->is_active ? 'Hoạt động' : 'Chưa hoạt động';
    }

    public function statusBadgeClass(): string
    {
        if ($this->isArchived()) {
            return 'bg-light text-muted border';
        }

        return $this->is_active ? 'bg-success' : 'bg-warning text-dark';
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }
}
