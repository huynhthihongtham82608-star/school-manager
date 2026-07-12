<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemSetting extends Model
{
    use UsesUuid;

    protected $fillable = [
        'school_name',
        'short_name',
        'logo_path',
        'address',
        'phone',
        'email',
        'website',
        'principal_name',
        'default_school_year_id',
        'ai_encouragements',
    ];

    protected $casts = [
        'ai_encouragements' => 'array',
    ];

    public static function current(): self
    {
        if (! Schema::hasTable('system_settings')) {
            return new self(static::defaults());
        }

        return static::query()->first() ?: new self(static::defaults());
    }

    public static function defaults(): array
    {
        return [
            'school_name' => config('app.name', 'Quản lý trường THPT'),
            'short_name' => 'TH',
            'logo_path' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'website' => null,
            'principal_name' => null,
            'default_school_year_id' => null,
            'ai_encouragements' => [],
        ];
    }

    public function activeAiEncouragements(): array
    {
        return collect($this->ai_encouragements ?: [])
            ->filter(fn ($item) => is_array($item) && ($item['enabled'] ?? false) && filled($item['content'] ?? null))
            ->pluck('content')
            ->map(fn ($content) => trim((string) $content))
            ->filter()
            ->values()
            ->all();
    }

    public function defaultSchoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'default_school_year_id');
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }
}
