<?php

namespace App\Http\Controllers;

use App\Models\HomePageContent;
use App\Models\LearningDocument;
use App\Models\SchoolClass;
use App\Models\SchoolEvent;
use App\Models\SchoolPost;
use App\Models\Subject;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminHomePageController extends Controller
{
    public function index()
    {
        $tablesReady = Schema::hasTable('home_page_contents')
            && Schema::hasTable('school_posts')
            && Schema::hasTable('school_events')
            && Schema::hasTable('learning_documents');

        $contents = $tablesReady
            ? HomePageContent::query()->get()->keyBy('key')
            : collect();

        $posts = Schema::hasTable('school_posts')
            ? SchoolPost::latest()->limit(12)->get()
            : collect();

        $events = Schema::hasTable('school_events')
            ? SchoolEvent::latest('starts_at')->limit(12)->get()
            : collect();

        $documents = Schema::hasTable('learning_documents')
            ? LearningDocument::with(['subject', 'classRoom'])->latest()->limit(12)->get()
            : collect();

        $classes = Schema::hasTable('classes') ? SchoolClass::orderBy('name')->get() : collect();
        $subjects = Schema::hasTable('subjects') ? Subject::orderBy('name')->get() : collect();

        return view('admin.home_page', compact('tablesReady', 'contents', 'posts', 'events', 'documents', 'classes', 'subjects'));
    }

    public function saveContent(Request $request)
    {
        if (! Schema::hasTable('home_page_contents')) {
            return back()->with('error', 'Chưa có bảng home_page_contents. Vui lòng chạy migration trước.');
        }

        $data = $request->validate([
            'banner_title' => ['nullable', 'string', 'max:255'],
            'banner_subtitle' => ['nullable', 'string', 'max:255'],
            'banner_content' => ['nullable', 'string'],
            'banner_image_url' => ['nullable', 'string', 'max:255'],
            'about_title' => ['nullable', 'string', 'max:255'],
            'about_content' => ['nullable', 'string'],
            'contact_title' => ['nullable', 'string', 'max:255'],
            'contact_content' => ['nullable', 'string'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'string', 'max:255'],
            'contact_address' => ['nullable', 'string', 'max:255'],
        ]);

        $this->upsertContent('banner', [
            'title' => $data['banner_title'] ?? null,
            'content' => $data['banner_content'] ?? null,
            'image_url' => $data['banner_image_url'] ?? null,
            'extra' => ['subtitle' => $data['banner_subtitle'] ?? null],
        ]);

        $this->upsertContent('about', [
            'title' => $data['about_title'] ?? null,
            'content' => $data['about_content'] ?? null,
        ]);

        $this->upsertContent('contact', [
            'title' => $data['contact_title'] ?? null,
            'content' => $data['contact_content'] ?? null,
            'extra' => [
                'phone' => $data['contact_phone'] ?? null,
                'email' => $data['contact_email'] ?? null,
                'address' => $data['contact_address'] ?? null,
            ],
        ]);

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
            'is_published' => $request->boolean('is_published', true),
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
            'is_published' => $request->boolean('is_published', true),
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
}
