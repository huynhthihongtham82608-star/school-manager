<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectGradeMapping extends Model
{
    use HasFactory, UsesUuid, UsesConsolidatedTable;

    protected $fillable = [
        'subject_id',
        'grade_level',
    ];

    protected $casts = [
        'grade_level' => 'integer',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('subjects', 'subject_grade_mappings', 'subject_record_type') ? 'subjects' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('subjects', 'subject_grade_mappings', 'subject_record_type')) {
            static::addGlobalScope('subject_grade_mapping_records', fn ($query) => $query->where('subject_record_type', 'grade_mapping'));
            static::creating(fn (SubjectGradeMapping $mapping) => $mapping->subject_record_type ??= 'grade_mapping');
        }
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
