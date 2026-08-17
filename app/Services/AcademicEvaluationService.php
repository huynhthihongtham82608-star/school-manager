<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AcademicEvaluationService
{
    private const LEGACY_KEYS = [
        'level_1' => 'excellent',
        'level_2' => 'good',
        'level_3' => 'average',
    ];

    public static function defaultLevels(): array
    {
        return [
            'level_1' => [
                'key' => 'level_1',
                'label' => 'Tốt',
                'gpa_min' => '8.0',
                'subject_min' => '6.5',
                'badge_class' => 'bg-green-50 text-green-700 border border-green-200',
            ],
            'level_2' => [
                'key' => 'level_2',
                'label' => 'Khá',
                'gpa_min' => '6.5',
                'subject_min' => '5.0',
                'badge_class' => 'bg-blue-50 text-blue-700 border border-blue-200',
            ],
            'level_3' => [
                'key' => 'level_3',
                'label' => 'Đạt',
                'gpa_min' => '5.0',
                'subject_min' => '3.5',
                'badge_class' => 'bg-orange-50 text-orange-700 border border-orange-200',
            ],
        ];
    }

    public static function defaultConductLevels(): array
    {
        return [
            'conduct_level_1' => [
                'key' => 'conduct_level_1',
                'label' => 'Tốt',
                'max_unexcused_absence' => '5',
                'max_period_absence' => '3',
                'max_late' => '5',
            ],
            'conduct_level_2' => [
                'key' => 'conduct_level_2',
                'label' => 'Khá',
                'max_unexcused_absence' => '8',
                'max_period_absence' => '6',
                'max_late' => '10',
            ],
            'conduct_level_3' => [
                'key' => 'conduct_level_3',
                'label' => 'Đạt',
                'max_unexcused_absence' => '12',
                'max_period_absence' => '10',
                'max_late' => '15',
            ],
        ];
    }

    public static function defaults(): array
    {
        return array_merge(
            self::encodedDefaults(self::defaultLevels()),
            self::encodedDefaults(self::defaultConductLevels()),
            [
                'conduct_unexcused_absence_limit' => '5',
                'conduct_period_absence_limit' => '3',
                'conduct_late_limit' => '5',
            ]
        );
    }

    public function rules(): array
    {
        return Setting::valuesFor(self::defaults());
    }

    public function levels(): array
    {
        return $this->metadataLevels('level_', self::defaultLevels())
            ->sortByDesc(fn (array $level) => (float) $level['gpa_min'])
            ->values()
            ->mapWithKeys(fn (array $level, int $index) => [$level['key'] => array_merge($level, [
                'legacy_key' => self::LEGACY_KEYS[$level['key']] ?? $level['key'],
                'sort_order' => $index + 1,
            ])])
            ->all();
    }

    public function conductLevels(): array
    {
        return $this->metadataLevels('conduct_level_', self::defaultConductLevels())
            ->sortBy(fn (array $level) => (int) $level['max_unexcused_absence'])
            ->values()
            ->mapWithKeys(fn (array $level, int $index) => [$level['key'] => array_merge($level, [
                'sort_order' => $index + 1,
            ])])
            ->all();
    }

    public function labelFor(?string $key): string
    {
        if ($key === 'no_data') {
            return 'Chưa có dữ liệu';
        }

        if ($key === 'needs_support') {
            return 'Chưa Đạt';
        }

        $levels = $this->levels();

        if (isset($levels[$key])) {
            return $levels[$key]['label'];
        }

        foreach ($levels as $level) {
            if (($level['legacy_key'] ?? null) === $key) {
                return $level['label'];
            }
        }

        return 'Chưa có dữ liệu';
    }

    public function classify(?float $gpa, iterable $subjectScores = [], iterable $assessmentStatuses = []): array
    {
        $numericScores = $this->numericScores($subjectScores);
        $assessmentsPassed = $this->allAssessmentsPassed($assessmentStatuses);

        foreach ($this->levels() as $key => $level) {
            if ($this->passesLevel($gpa, $numericScores, $assessmentsPassed, (float) $level['gpa_min'], (float) $level['subject_min'])) {
                return $this->result($key, $level['label'], $level['badge_class'], $level['legacy_key']);
            }
        }

        return $this->result('needs_support', 'Chưa Đạt', 'bg-red-50 text-red-700 border border-red-200', 'needs_support');
    }

    public function classifyFromScoreHeaders(?float $gpa, iterable $scoreHeaders): array
    {
        $headers = collect($scoreHeaders);

        $numericScores = $headers
            ->filter(fn ($header) => $header->subject?->usesNumericAssessment())
            ->pluck('average')
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (float) $value)
            ->values();

        $assessmentStatuses = $headers
            ->filter(fn ($header) => $header->subject?->usesPassFailAssessment())
            ->map(fn ($header) => $header->average)
            ->values();

        return $this->classify($gpa, $numericScores, $assessmentStatuses);
    }

    public function suggestConductLabel(int $unexcusedAbsence, int $periodAbsence, int $late): string
    {
        foreach ($this->conductLevels() as $level) {
            if (
                $unexcusedAbsence <= (int) $level['max_unexcused_absence']
                && $periodAbsence <= (int) $level['max_period_absence']
                && $late <= (int) $level['max_late']
            ) {
                return $level['label'];
            }
        }

        return 'Chưa đạt';
    }

    public function legacyKeyFor(string $key): string
    {
        return self::LEGACY_KEYS[$key] ?? $key;
    }

    public function passingRankKeys(): array
    {
        return collect($this->levels())
            ->map(fn (array $level) => $level['legacy_key'] ?? $level['key'])
            ->values()
            ->all();
    }

    public function topRankKeys(int $take = 2): array
    {
        return collect($this->levels())
            ->take($take)
            ->map(fn (array $level) => $level['legacy_key'] ?? $level['key'])
            ->values()
            ->all();
    }

    public function failingRankKey(): string
    {
        return 'needs_support';
    }

    private static function encodedDefaults(array $defaults): array
    {
        return collect($defaults)
            ->map(fn (array $level) => json_encode($level, JSON_UNESCAPED_UNICODE))
            ->all();
    }

    private function metadataLevels(string $prefix, array $defaults): Collection
    {
        $defaultKeys = array_keys($defaults);
        $rows = collect();

        if (Schema::hasTable('settings')) {
            $rows = Setting::query()
                ->where('group', 'evaluation_rules')
                ->where('key', 'like', $prefix . '%')
                ->orderByRaw('LENGTH(`key`), `key`')
                ->pluck('value', 'key');
        }

        foreach ($defaults as $key => $value) {
            if (! $rows->has($key)) {
                $rows->put($key, json_encode($value, JSON_UNESCAPED_UNICODE));
            }
        }

        return $rows
            ->map(fn ($value, string $key) => $this->levelFromSetting($key, $value, $defaults[$key] ?? $this->blankLevel($prefix, $key)))
            ->filter(fn (array $level) => in_array($level['key'], $defaultKeys, true) || trim($level['label']) !== '')
            ->values();
    }

    private function levelFromSetting(string $key, ?string $value, array $default): array
    {
        $decoded = is_string($value) ? json_decode($value, true) : null;
        $level = is_array($decoded) ? array_merge($default, $decoded) : $default;

        return [
            'key' => $key,
            'label' => trim((string) ($level['label'] ?? $default['label'])) ?: $default['label'],
            'gpa_min' => (string) ($level['gpa_min'] ?? $default['gpa_min'] ?? '0.0'),
            'subject_min' => (string) ($level['subject_min'] ?? $default['subject_min'] ?? '0.0'),
            'max_unexcused_absence' => (string) ($level['max_unexcused_absence'] ?? $default['max_unexcused_absence'] ?? '0'),
            'max_period_absence' => (string) ($level['max_period_absence'] ?? $default['max_period_absence'] ?? '0'),
            'max_late' => (string) ($level['max_late'] ?? $default['max_late'] ?? '0'),
            'badge_class' => $default['badge_class'] ?? 'bg-orange-50 text-orange-700 border border-orange-200',
        ];
    }

    private function blankLevel(string $prefix, string $key): array
    {
        return str_starts_with($key, 'conduct_')
            ? [
                'key' => $key,
                'label' => '',
                'max_unexcused_absence' => '0',
                'max_period_absence' => '0',
                'max_late' => '0',
            ]
            : [
                'key' => $key,
                'label' => '',
                'gpa_min' => '0.0',
                'subject_min' => '0.0',
                'badge_class' => 'bg-orange-50 text-orange-700 border border-orange-200',
            ];
    }

    private function passesLevel(?float $gpa, Collection $numericScores, bool $assessmentsPassed, float $gpaFloor, float $subjectFloor): bool
    {
        return $gpa !== null
            && $gpa >= $gpaFloor
            && $assessmentsPassed
            && $numericScores->every(fn (float $score) => $score >= $subjectFloor);
    }

    private function numericScores(iterable $subjectScores): Collection
    {
        return collect($subjectScores)
            ->map(function ($item) {
                if (is_array($item)) {
                    return $item['average'] ?? $item['score'] ?? $item['value'] ?? null;
                }

                if (is_object($item)) {
                    return $item->average ?? $item->score ?? $item->value ?? null;
                }

                return $item;
            })
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (float) $value)
            ->values();
    }

    private function allAssessmentsPassed(iterable $assessmentStatuses): bool
    {
        return collect($assessmentStatuses)
            ->reject(fn ($status) => $status === null || $status === '')
            ->every(fn ($status) => $this->isPassingAssessment($status));
    }

    private function isPassingAssessment(mixed $status): bool
    {
        if (is_bool($status)) {
            return $status;
        }

        if (is_numeric($status)) {
            return (float) $status >= 0.5;
        }

        $normalized = Str::lower(Str::ascii(trim((string) $status)));

        return in_array($normalized, [
            'dat',
            'd',
            'pass',
            'passed',
            'true',
        ], true);
    }

    private function result(string $key, string $label, string $badgeClass, string $legacyKey): array
    {
        return compact('key', 'label', 'badgeClass', 'legacyKey');
    }
}
