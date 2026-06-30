<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class SchoolEvent extends Model
{
    use UsesUuid;

    public const TARGET_ROLES = ['all', 'admin', 'teacher', 'homeroom', 'student', 'parent'];
    private const META_PATTERN = '/\n?<!--school_manager_meta:(.*?)-->\s*$/s';

    protected $fillable = [
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'is_published',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function getDescriptionAttribute($value): ?string
    {
        return $this->stripMeta($value);
    }

    public function targetRoles(): array
    {
        $meta = $this->metaFrom($this->getRawOriginal('description'));
        $roles = $meta['target_roles'] ?? ['all'];

        if (! is_array($roles)) {
            return ['all'];
        }

        $roles = array_values(array_intersect($roles, self::TARGET_ROLES));

        return $roles ?: ['all'];
    }

    public function isVisibleToRole(?string $role): bool
    {
        $targets = $this->targetRoles();

        if (in_array('all', $targets, true)) {
            return true;
        }

        $role = $role === 'staff' ? 'admin' : $role;

        return in_array($role, $targets, true);
    }

    public static function withMeta(?string $description, array $targetRoles): string
    {
        $targetRoles = self::normalizeTargetRoles($targetRoles);
        $description = trim((string) self::stripMeta($description));

        return trim($description . "\n<!--school_manager_meta:" . json_encode(['target_roles' => $targetRoles]) . '-->');
    }

    public static function normalizeTargetRoles(array $targetRoles): array
    {
        $targetRoles = array_values(array_intersect($targetRoles, self::TARGET_ROLES));

        if (! $targetRoles || in_array('all', $targetRoles, true)) {
            return ['all'];
        }

        return $targetRoles;
    }

    private function metaFrom(?string $value): array
    {
        if (! preg_match(self::META_PATTERN, (string) $value, $matches)) {
            return [];
        }

        $decoded = json_decode($matches[1], true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function stripMeta(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim((string) preg_replace(self::META_PATTERN, '', $value));
    }
}
