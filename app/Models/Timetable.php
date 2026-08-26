<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Timetable extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'school_year_id',
        'semester_id',
        'class_id',
        'week_start',
        'week_end',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
    ];

    protected static function booted(): void
    {
        if (Schema::hasColumn('timetables', 'timetable_record_type')) {
            static::addGlobalScope('timetable_records', fn ($query) => $query->where('timetable_record_type', 'timetable'));
            static::creating(fn (Timetable $timetable) => $timetable->timetable_record_type ??= 'timetable');
        }
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function entries()
    {
        return $this->hasMany(TimetableEntry::class);
    }
}
