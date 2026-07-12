<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use App\Models\SystemSetting;
use App\Support\AuditLogger;
use App\Support\CurrentAcademicContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    public function edit()
    {
        $setting = SystemSetting::current();
        $schoolYears = Schema::hasTable('school_years')
            ? SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->get()
            : collect();
        $supportsAiContent = Schema::hasColumn('system_settings', 'ai_encouragements');

        return view('system.settings', compact('setting', 'schoolYears', 'supportsAiContent'));
    }

    public function update(Request $request)
    {
        abort_unless(Schema::hasTable('system_settings'), 500, 'Chưa có bảng system_settings. Vui lòng chạy migration trước.');

        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'default_school_year_id' => ['nullable', 'exists:school_years,id'],
            'ai_encouragements' => ['nullable', 'array'],
            'ai_encouragements.*.content' => ['nullable', 'string', 'max:500'],
            'ai_encouragements.*.enabled' => ['nullable', 'boolean'],
        ]);

        $setting = SystemSetting::query()->first() ?: new SystemSetting(SystemSetting::defaults());

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('system', 'public');
        }

        unset($data['logo']);

        if (Schema::hasColumn('system_settings', 'ai_encouragements')) {
            $data['ai_encouragements'] = collect($request->input('ai_encouragements', []))
                ->map(function ($item) {
                    $content = trim((string) ($item['content'] ?? ''));

                    if ($content === '') {
                        return null;
                    }

                    return [
                        'content' => $content,
                        'enabled' => (bool) ($item['enabled'] ?? false),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        } else {
            unset($data['ai_encouragements']);
        }

        $setting->fill($data)->save();

        if (! empty($data['default_school_year_id'])) {
            $schoolYear = SchoolYear::find($data['default_school_year_id']);

            if ($schoolYear && ! $schoolYear->isArchived()) {
                SchoolYear::where('is_active', true)
                    ->whereKeyNot($schoolYear->getKey())
                    ->update(['is_active' => false]);
                $schoolYear->update(['is_active' => true]);
                app(CurrentAcademicContext::class)->syncSemesterForCurrentYear($schoolYear);
            }
        }

        AuditLogger::log('system_settings_updated', SystemSetting::class, (string) $setting->getKey(), 'Cập nhật cài đặt hệ thống');

        return back()->with('success', 'Đã cập nhật cài đặt hệ thống.');
    }
}
