<?php

namespace App\Http\Controllers;

use App\Models\HomePageContent;
use App\Models\LearningDocument;
use App\Models\SchoolClass;
use App\Models\SchoolEvent;
use App\Models\SchoolPost;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Schema;

class LandingPageController extends Controller
{
    public function index()
    {
        $contents = $this->homeContents();

        $banner = $contents['banner'] ?? [
            'title' => 'Trường học hiện đại, kết nối và phát triển',
            'content' => 'Hệ thống School Manager hỗ trợ nhà trường quản lý học tập, thông báo, sự kiện và tài liệu một cách tập trung.',
            'image_url' => null,
            'extra' => ['subtitle' => 'Chào mừng đến với cổng thông tin nhà trường'],
        ];

        $about = $contents['about'] ?? [
            'title' => 'Giới thiệu trường học',
            'content' => 'Nhà trường hướng đến môi trường học tập an toàn, chuyên nghiệp và lấy học sinh làm trung tâm.',
        ];

        $contact = $contents['contact'] ?? [
            'title' => 'Thông tin liên hệ',
            'content' => 'Vui lòng liên hệ văn phòng nhà trường để được hỗ trợ.',
            'extra' => [],
        ];

        $news = $this->posts(SchoolPost::TYPE_NEWS, 3);
        $announcements = $this->posts(SchoolPost::TYPE_ANNOUNCEMENT, 4);
        $events = $this->events(4);
        $documents = $this->documents(4);
        $stats = $this->stats();

        return view('home', compact('banner', 'about', 'contact', 'news', 'announcements', 'events', 'documents', 'stats'));
    }

    private function homeContents(): array
    {
        if (! Schema::hasTable('home_page_contents')) {
            return [];
        }

        return HomePageContent::query()
            ->get()
            ->mapWithKeys(fn ($item) => [$item->key => $item->toArray()])
            ->all();
    }

    private function posts(string $type, int $limit)
    {
        if (! Schema::hasTable('school_posts')) {
            return collect();
        }

        return SchoolPost::query()
            ->where('type', $type)
            ->where('is_published', true)
            ->latest('published_at')
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function events(int $limit)
    {
        if (! Schema::hasTable('school_events')) {
            return collect();
        }

        return SchoolEvent::query()
            ->where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '>=', now()->startOfDay());
            })
            ->orderByRaw('starts_at is null')
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
    }

    private function documents(int $limit)
    {
        if (! Schema::hasTable('learning_documents')) {
            return collect();
        }

        return LearningDocument::query()
            ->where('is_published', true)
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function stats(): array
    {
        return [
            'students' => Schema::hasTable('students') ? Student::count() : 0,
            'teachers' => Schema::hasTable('teachers') ? Teacher::count() : 0,
            'classes' => Schema::hasTable('classes') ? SchoolClass::count() : 0,
            'documents' => Schema::hasTable('learning_documents') ? LearningDocument::where('is_published', true)->count() : 0,
        ];
    }
}
