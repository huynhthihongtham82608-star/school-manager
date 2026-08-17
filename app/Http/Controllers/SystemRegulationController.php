<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TuitionFee;
use App\Services\AcademicEvaluationService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SystemRegulationController extends Controller
{
    public function index()
    {
        return redirect()->route('system.academic-levels.index');
    }

    public function update(Request $request)
    {
        return $this->updateAcademicLevels($request);
    }

    public function academicLevels(AcademicEvaluationService $evaluationService)
    {
        return view('system.academic-levels', [
            'academicLevels' => $evaluationService->levels(),
            'settingsTableReady' => Schema::hasTable('settings'),
        ]);
    }

    public function updateAcademicLevels(Request $request)
    {
        $this->ensureSettingsTable();

        $data = $request->validate([
            'academic_levels' => ['required', 'array', 'min:1'],
            'academic_levels.*.label' => ['required', 'string', 'max:80'],
            'academic_levels.*.gpa_min' => ['required', 'numeric', 'min:0', 'max:10'],
            'academic_levels.*.subject_min' => ['required', 'numeric', 'min:0', 'max:10'],
        ]);

        $savedAcademicKeys = $this->storeAcademicLevels($data['academic_levels']);

        Setting::query()
            ->where('group', 'evaluation_rules')
            ->where('key', 'like', 'level_%')
            ->whereNotIn('key', $savedAcademicKeys)
            ->delete();

        AuditLogger::log('academic_levels_updated', Setting::class, null, 'Cập nhật mốc điểm học lực');

        return back()->with('success', 'Đã cập nhật mốc điểm học lực.');
    }

    public function conductLevels(AcademicEvaluationService $evaluationService)
    {
        return view('system.conduct-levels', [
            'conductLevels' => $evaluationService->conductLevels(),
            'settingsTableReady' => Schema::hasTable('settings'),
        ]);
    }

    public function updateConductLevels(Request $request)
    {
        $this->ensureSettingsTable();

        $data = $request->validate([
            'conduct_levels' => ['required', 'array', 'min:1'],
            'conduct_levels.*.label' => ['required', 'string', 'max:80'],
            'conduct_levels.*.max_unexcused_absence' => ['required', 'integer', 'min:0', 'max:365'],
            'conduct_levels.*.max_period_absence' => ['required', 'integer', 'min:0', 'max:500'],
            'conduct_levels.*.max_late' => ['required', 'integer', 'min:0', 'max:500'],
        ]);

        $savedConductKeys = $this->storeConductLevels($data['conduct_levels']);

        Setting::query()
            ->where('group', 'evaluation_rules')
            ->where('key', 'like', 'conduct_level_%')
            ->whereNotIn('key', $savedConductKeys)
            ->delete();

        $topConductLevel = collect($data['conduct_levels'])->values()->first();
        if ($topConductLevel) {
            Setting::putValue('conduct_unexcused_absence_limit', $topConductLevel['max_unexcused_absence'], 'evaluation_rules');
            Setting::putValue('conduct_period_absence_limit', $topConductLevel['max_period_absence'], 'evaluation_rules');
            Setting::putValue('conduct_late_limit', $topConductLevel['max_late'], 'evaluation_rules');
        }

        AuditLogger::log('conduct_levels_updated', Setting::class, null, 'Cập nhật định mức hạnh kiểm');

        return back()->with('success', 'Đã cập nhật định mức hạnh kiểm.');
    }

    public function tuitionLevels()
    {
        $qrImage = Setting::valueOf('tuition_qr_image');

        return view('system.tuition-levels', [
            'feeItems' => TuitionFee::configuredFeeItems(),
            'qrImageUrl' => $qrImage ? Storage::url($qrImage) : null,
            'settingsTableReady' => Schema::hasTable('settings'),
        ]);
    }

    public function updateTuitionLevels(Request $request)
    {
        $this->ensureSettingsTable();

        $data = $request->validate([
            'fee_items' => ['required', 'array', 'min:1'],
            'fee_items.*.key' => ['nullable', 'string', 'max:80'],
            'fee_items.*.label' => ['required', 'string', 'max:120'],
            'fee_items.*.amount' => ['required', 'numeric', 'min:0'],
            'tuition_qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        $items = collect($data['fee_items'])
            ->values()
            ->map(function (array $item, int $index) {
                $label = trim((string) $item['label']);
                $key = trim((string) ($item['key'] ?? ''));

                if ($key === '') {
                    $key = 'fee_' . Str::slug($label ?: 'khoan-thu') . '_' . ($index + 1);
                }

                return [
                    'key' => Str::limit(preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key), 80, ''),
                    'label' => $label,
                    'amount' => round((float) $item['amount'], 2),
                ];
            })
            ->unique('key')
            ->values()
            ->all();

        Setting::putValue('tuition_fee_items', json_encode($items, JSON_UNESCAPED_UNICODE), 'tuition_rules');
        if ($request->hasFile('tuition_qr_image')) {
            $currentQr = Setting::valueOf('tuition_qr_image');
            if ($currentQr) {
                Storage::disk('public')->delete($currentQr);
            }

            $path = $request->file('tuition_qr_image')->store('tuition-qr', 'public');
            Setting::putValue('tuition_qr_image', $path, 'tuition_rules');
        }
        AuditLogger::log('tuition_levels_updated', Setting::class, null, 'Cập nhật cấu hình mức thu học phí');

        return back()->with('success', 'Đã cập nhật cấu hình mức thu.');
    }

    public function classifyAcademicResult(?float $gpa, iterable $subjectScores, iterable $assessmentStatuses = []): array
    {
        return app(AcademicEvaluationService::class)->classify($gpa, $subjectScores, $assessmentStatuses);
    }

    private function storeAcademicLevels(array $levels): array
    {
        return collect($levels)
            ->values()
            ->sortByDesc(fn (array $level) => (float) $level['gpa_min'])
            ->values()
            ->map(function (array $level, int $index) {
                $key = 'level_' . ($index + 1);
                Setting::putValue($key, json_encode([
                    'key' => $key,
                    'label' => trim($level['label']),
                    'gpa_min' => number_format((float) $level['gpa_min'], 1, '.', ''),
                    'subject_min' => number_format((float) $level['subject_min'], 1, '.', ''),
                ], JSON_UNESCAPED_UNICODE), 'evaluation_rules');

                return $key;
            })
            ->all();
    }

    private function storeConductLevels(array $levels): array
    {
        return collect($levels)
            ->values()
            ->sortBy(fn (array $level) => (int) $level['max_unexcused_absence'])
            ->values()
            ->map(function (array $level, int $index) {
                $key = 'conduct_level_' . ($index + 1);
                Setting::putValue($key, json_encode([
                    'key' => $key,
                    'label' => trim($level['label']),
                    'max_unexcused_absence' => (string) (int) $level['max_unexcused_absence'],
                    'max_period_absence' => (string) (int) $level['max_period_absence'],
                    'max_late' => (string) (int) $level['max_late'],
                ], JSON_UNESCAPED_UNICODE), 'evaluation_rules');

                return $key;
            })
            ->all();
    }

    private function ensureSettingsTable(): void
    {
        abort_unless(
            Schema::hasTable('settings'),
            500,
            'Chưa có bảng settings. Vui lòng chạy migration trước khi lưu cấu hình.'
        );
    }
}
