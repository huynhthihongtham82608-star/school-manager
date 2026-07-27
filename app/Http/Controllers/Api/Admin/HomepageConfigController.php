<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomePageContent;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HomepageConfigController extends Controller
{
    public function show(): JsonResponse
    {
        if (! Schema::hasTable('home_page_contents')) {
            return response()->json([
                'message' => 'Chưa có bảng home_page_contents.',
                'data' => $this->emptyPayload(),
            ], 503);
        }

        return response()->json([
            'data' => $this->currentPayload(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        if (! Schema::hasTable('home_page_contents')) {
            return response()->json([
                'message' => 'Chưa có bảng home_page_contents. Vui lòng chạy migration trước.',
            ], 503);
        }

        $data = $request->validate([
            'banner_title' => ['nullable', 'string', 'max:255'],
            'banner_welcome' => ['nullable', 'string', 'max:500'],
            'banner_description' => ['nullable', 'string'],
            'banner_image_url' => ['nullable', 'string', 'max:1000'],
            'banner_image_file' => ['nullable', 'image', 'max:20480'],
            'intro_title' => ['nullable', 'string', 'max:255'],
            'intro_content' => ['nullable', 'string'],
        ]);

        $currentBanner = HomePageContent::where('key', 'banner')->first();
        $bannerImageUrl = $this->storeBannerImage($request, $currentBanner?->image_url);

        DB::transaction(function () use ($data, $bannerImageUrl): void {
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
        });

        AuditLogger::log('home_page_content_updated', HomePageContent::class, null, 'Cập nhật cấu hình nội dung trang chủ');

        return response()->json([
            'message' => 'Cập nhật cấu hình nội dung trang chủ thành công!',
            'data' => $this->currentPayload(),
        ]);
    }

    private function currentPayload(): array
    {
        $contents = HomePageContent::query()
            ->whereIn('key', ['banner', 'about'])
            ->get()
            ->keyBy('key');

        $banner = $contents->get('banner');
        $about = $contents->get('about');

        return [
            'banner_title' => $banner?->title,
            'banner_welcome' => data_get($banner, 'extra.subtitle'),
            'banner_description' => $banner?->content,
            'banner_image_url' => $banner?->image_url,
            'banner_image_preview_url' => $this->publicUrl($banner?->image_url),
            'intro_title' => $about?->title,
            'intro_content' => $about?->content,
        ];
    }

    private function emptyPayload(): array
    {
        return [
            'banner_title' => null,
            'banner_welcome' => null,
            'banner_description' => null,
            'banner_image_url' => null,
            'banner_image_preview_url' => null,
            'intro_title' => null,
            'intro_content' => null,
        ];
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

    private function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}
