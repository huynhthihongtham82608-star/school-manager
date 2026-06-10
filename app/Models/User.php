<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, UsesUuid;

    protected $fillable = [
        'username',
        'role',
        'teacher_id',
        'student_id',
        'parent_id',
        'password_hash',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function parentProfile()
    {
        return $this->belongsTo(ParentProfile::class, 'parent_id');
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->teacher) {
            return $this->teacher->name;
        }
        if ($this->student) {
            return $this->student->name;
        }
        if ($this->parentProfile) {
            return $this->parentProfile->name;
        }
        return $this->username;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'staff'], true);
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isTeacher(): bool
    {
        return in_array($this->role, ['teacher', 'homeroom'], true);
    }

    public function isHomeroom(): bool
    {
        return $this->role === 'homeroom';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }
}
