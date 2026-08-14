<?php

namespace App\Http\Controllers;

use App\Models\HomePageContent;
use App\Models\SchoolYear;
use App\Models\SystemSetting;
use App\Support\AuditLogger;
use App\Support\CurrentAcademicContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SystemSettingController extends Controller
{
    public function edit()
    {
        $setting = SystemSetting::current();
        $schoolYears = Schema::hasTable('school_years')
            ? SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->get()
            : collect();
        $homePageTablesReady = Schema::hasTable('home_page_contents');
        $homePageContents = $homePageTablesReady
            ? HomePageContent::query()->whereIn('key', ['banner', 'about'])->get()->keyBy('key')
            : collect();

        return view('system.settings', compact('setting', 'schoolYears', 'homePageTablesReady', 'homePageContents'));
    }

    public function update(Request $request)
    {
        abort_unless(
            Schema::hasTable('system_settings'),
            500,
            'Chưa có bảng system_settings. Vui lòng chạy migration trước.'
        );

        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'default_school_year_id' => ['nullable', 'exists:school_years,id'],
            'banner_title' => ['nullable', 'string', 'max:255'],
            'banner_welcome' => ['nullable', 'string', 'max:500'],
            'banner_description' => ['nullable', 'string'],
            'banner_image_url' => ['nullable', 'string', 'max:1000'],
            'banner_image_file' => ['nullable', 'image', 'max:20480'],
            'intro_title' => ['nullable', 'string', 'max:255'],
            'intro_content' => ['nullable', 'string'],
        ]);

        $setting = SystemSetting::query()->first() ?: new SystemSetting(SystemSetting::defaults());

        DB::transaction(function () use ($request, $data, $setting): void {
            $settingData = [
                'school_name' => $data['school_name'],
                'short_name' => $data['short_name'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['website'] ?? null,
                'principal_name' => $data['principal_name'] ?? null,
                'default_school_year_id' => $data['default_school_year_id'] ?? null,
            ];

            if ($request->hasFile('logo')) {
                if ($setting->logo_path) {
                    Storage::disk('public')->delete($setting->logo_path);
                }

                $settingData['logo_path'] = $request->file('logo')->store('system', 'public');
            }

            $setting->fill($settingData)->save();

            if (! empty($settingData['default_school_year_id'])) {
                $schoolYear = SchoolYear::find($settingData['default_school_year_id']);

                if ($schoolYear && ! $schoolYear->isArchived()) {
                    SchoolYear::where('is_active', true)
                        ->whereKeyNot($schoolYear->getKey())
                        ->update(['is_active' => false]);

                    $schoolYear->update(['is_active' => true]);
                    app(CurrentAcademicContext::class)->syncSemesterForCurrentYear($schoolYear);
                }
            }

            if (Schema::hasTable('home_page_contents')) {
                $currentBanner = HomePageContent::where('key', 'banner')->first();
                $bannerImageUrl = $this->storeBannerImage(
                    $request,
                    $data['banner_image_url'] ?? $currentBanner?->image_url
                );

                HomePageContent::updateOrCreate(
                    ['key' => 'banner'],
                    [
                        'key' => 'banner',
                        'title' => $data['banner_title'] ?? null,
                        'content' => $data['banner_description'] ?? null,
                        'image_url' => $bannerImageUrl,
                        'extra' => ['subtitle' => $data['banner_welcome'] ?? null],
                    ]
                );

                HomePageContent::updateOrCreate(
                    ['key' => 'about'],
                    [
                        'key' => 'about',
                        'title' => $data['intro_title'] ?? null,
                        'content' => $data['intro_content'] ?? null,
                    ]
                );
            }
        });

        AuditLogger::log(
            'system_settings_updated',
            SystemSetting::class,
            (string) $setting->getKey(),
            'Cập nhật thông tin nhà trường và giao diện trang chủ'
        );

        return back()->with('success', 'Đã cập nhật thông tin nhà trường và giao diện trang chủ.');
    }

    private function storeBannerImage(Request $request, ?string $currentImageUrl): ?string
    {
        if (! $request->hasFile('banner_image_file')) {
            return $currentImageUrl;
        }

        $file = $request->file('banner_image_file');
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
        $filename = 'banner_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(10)) . '.' . $extension;
        $path = $file->storeAs('uploads/banners', $filename, 'public');

        if (! $path) {
            throw ValidationException::withMessages([
                'banner_image_file' => 'Không thể lưu ảnh banner. Vui lòng thử lại.',
            ]);
        }

        return Storage::url($path);
    }
}
