<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Model;

class HomePageContent extends Model
{
    use UsesUuid, UsesConsolidatedTable;

    protected $fillable = [
        'key',
        'title',
        'content',
        'image_url',
        'extra',
    ];

    protected $casts = [
        'extra' => 'array',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('system_settings', 'home_page_contents', 'setting_record_type') ? 'system_settings' : parent::getTable();
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('system_settings', 'home_page_contents', 'setting_record_type')) {
            static::addGlobalScope('home_page_records', fn ($query) => $query->where('setting_record_type', 'home_page'));
            static::creating(fn (HomePageContent $content) => $content->setting_record_type ??= 'home_page');
        }
    }
}
