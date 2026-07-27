<?php

namespace App\Http\Controllers;

use App\Models\HomePageContent;
use App\Models\LearningDocument;
use App\Models\SchoolEvent;
use App\Models\SchoolPost;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminHomePageController extends Controller
{
    public function index()
    {
        $tablesReady = Schema::hasTable('home_page_contents');

        $contents = $tablesReady
            ? HomePageContent::query()->get()->keyBy('key')
            : collect();

        return view('admin.home_page', compact('tablesReady', 'contents'));
    }

    public function saveContent(Request $request)
    {
        if (! Schema::hasTable('home_page_contents')) {
            return back()->with('error', 'Chưa có bảng home_page_contents. Vui lòng chạy migration trước.');
        }

        $data = $request->validate([
            'banner_title' => ['nullable', 'string', 'max:255'],
            'banner_subtitle' => ['nullable', 'string', 'max:500'],
            'banner_content' => ['nullable', 'string'],
            'banner_image_url' => ['nullable', 'string', 'max:1000'],
            'banner_image_file' => ['nullable', 'image', 'max:20480'],
            'about_title' => ['nullable', 'string', 'max:255'],
            'about_content' => ['nullable', 'string'],
        ]);

        $currentBanner = HomePageContent::where('key', 'banner')->first();
        $bannerImageUrl = $this->storeBannerImage($request, $currentBanner?->image_url);

        DB::transaction(function () use ($data, $bannerImageUrl): void {
            $this->upsertContent('banner', [
                'title' => $data['banner_title'] ?? null,
                'content' => $data['banner_content'] ?? null,
                'image_url' => $bannerImageUrl,
                'extra' => ['subtitle' => $data['banner_subtitle'] ?? null],
            ]);

            $this->upsertContent('about', [
                'title' => $data['about_title'] ?? null,
                'content' => $data['about_content'] ?? null,
            ]);
        });

        AuditLogger::log('home_page_content_updated', HomePageContent::class, null, 'Cập nhật nội dung trang chủ');

        return back()->with('success', 'Đã cập nhật nội dung trang chủ.');
    }

    public function storePost(Request $request)
    {
        if (! Schema::hasTable('school_posts')) {
            return back()->with('error', 'Chưa có bảng school_posts. Vui lòng chạy migration trước.');
        }

        $data = $request->validate([
            'type' => ['required', 'in:news,announcement'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $post = SchoolPost::create([
            ...$data,
            'published_at' => $data['published_at'] ?? now(),
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('school_post_created', SchoolPost::class, $post->id, 'Tạo tin tức/thông báo');

        return back()->with('success', 'Đã thêm tin tức hoặc thông báo.');
    }

    public function storeEvent(Request $request)
    {
        if (! Schema::hasTable('school_events')) {
            return back()->with('error', 'Chưa có bảng school_events. Vui lòng chạy migration trước.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $event = SchoolEvent::create([
            ...$data,
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('school_event_created', SchoolEvent::class, $event->id, 'Tạo sự kiện nhà trường');

        return back()->with('success', 'Đã thêm sự kiện.');
    }

    public function storeDocument(Request $request)
    {
        if (! Schema::hasTable('learning_documents')) {
            return back()->with('error', 'Chưa có bảng learning_documents. Vui lòng chạy migration trước.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'file_url' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'string', 'max:50'],
            'class_id' => ['nullable', 'string', 'max:50'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $document = LearningDocument::create([
            ...$data,
            'uploaded_by' => $request->user()->id,
            'is_published' => $request->boolean('is_published', true),
        ]);

        AuditLogger::log('learning_document_created', LearningDocument::class, $document->id, 'Thêm tài liệu học tập');

        return back()->with('success', 'Đã thêm tài liệu học tập.');
    }

    private function upsertContent(string $key, array $data): void
    {
        HomePageContent::updateOrCreate(['key' => $key], $data + ['key' => $key]);
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
