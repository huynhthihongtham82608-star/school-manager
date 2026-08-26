<?php

namespace App\Models;

use App\Models\Concerns\UsesConsolidatedTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Setting extends Model
{
    use UsesConsolidatedTable;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public function getTable()
    {
        return $this->usesConsolidatedTable('system_settings', 'settings', 'setting_record_type') ? 'system_settings' : parent::getTable();
    }

    public function getIncrementing()
    {
        return ! $this->usesConsolidatedTable('system_settings', 'settings', 'setting_record_type');
    }

    public function getKeyType()
    {
        return $this->usesConsolidatedTable('system_settings', 'settings', 'setting_record_type') ? 'string' : 'int';
    }

    protected static function booted(): void
    {
        if (static::shouldScopeConsolidatedTable('system_settings', 'settings', 'setting_record_type')) {
            static::addGlobalScope('setting_records', fn ($query) => $query->where('setting_record_type', 'setting'));
            static::creating(function (Setting $setting): void {
                $setting->id ??= (string) Str::uuid();
                $setting->setting_record_type ??= 'setting';
            });
        }
    }

    public static function valueOf(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings') && ! Schema::hasColumn('system_settings', 'setting_record_type')) {
            return $default;
        }

        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function putValue(string $key, mixed $value, string $group = 'system'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'group' => $group,
            ]
        );
    }

    public static function valuesFor(array $defaults): array
    {
        if (! Schema::hasTable('settings') && ! Schema::hasColumn('system_settings', 'setting_record_type')) {
            return $defaults;
        }

        $stored = static::query()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->all();

        return array_merge($defaults, $stored);
    }
}
