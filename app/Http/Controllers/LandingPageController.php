<?php

namespace App\Http\Controllers;

use App\Models\HomePageContent;
use App\Models\LearningDocument;
use App\Models\SchoolClass;
use App\Models\SchoolEvent;
use App\Models\SchoolPost;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\Teacher;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function index()
    {
        $contents = $this->homeContents();
        $settings = SystemSetting::current();

        $banner = $contents['banner'] ?? [
            'title' => 'Trường học hiện đại, kết nối và phát triển',
            'content' => 'Hệ thống School Manager hỗ trợ nhà trường quản lý học tập, thông báo, sự kiện và tài liệu một cách tập trung.',
            'image_url' => null,
            'extra' => ['subtitle' => 'Chào mừng đến với cổng thông tin nhà trường'],
        ];

        $bannerImageSrc = $this->bannerImageUrl($banner['image_url'] ?? null) ?? $this->defaultBannerImage();

        $about = $contents['about'] ?? [
            'title' => 'Giới thiệu trường học',
            'content' => 'Nhà trường hướng đến môi trường học tập an toàn, chuyên nghiệp và lấy học sinh làm trung tâm.',
        ];

        $contact = [
            'title' => 'Thông tin liên hệ',
            'content' => 'Vui lòng liên hệ văn phòng nhà trường để được hỗ trợ.',
            'extra' => [
                'phone' => $settings->phone,
                'email' => $settings->email,
                'address' => $settings->address,
            ],
        ];

        $news = $this->posts(SchoolPost::TYPE_NEWS, 3);
        $announcements = $this->posts(SchoolPost::TYPE_ANNOUNCEMENT, 4);
        $events = $this->events(4);
        $documents = $this->documents(4);
        $stats = $this->stats();

        return view('home', compact('settings', 'banner', 'bannerImageSrc', 'about', 'contact', 'news', 'announcements', 'events', 'documents', 'stats'));
    }

    private function bannerImageUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $relativePath = ltrim($path, '/');
        $relativePath = Str::after($relativePath, 'storage/');

        if (Storage::disk('public')->exists($relativePath)) {
            return asset('storage/' . $relativePath);
        }

        return file_exists(public_path(ltrim($path, '/'))) ? asset(ltrim($path, '/')) : null;
    }

    private function defaultBannerImage(): string
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="760" viewBox="0 0 1200 760" role="img" aria-label="Banner truong hoc mac dinh">
  <defs>
    <linearGradient id="banner" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#fff7ed"/>
      <stop offset="48%" stop-color="#fed7aa"/>
      <stop offset="100%" stop-color="#fb7185"/>
    </linearGradient>
    <radialGradient id="glow" cx="68%" cy="24%" r="58%">
      <stop offset="0%" stop-color="#fef3c7" stop-opacity=".95"/>
      <stop offset="100%" stop-color="#fef3c7" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="1200" height="760" rx="44" fill="url(#banner)"/>
  <rect width="1200" height="760" rx="44" fill="url(#glow)"/>
  <g fill="none" stroke="#c2410c" stroke-width="12" stroke-linecap="round" stroke-linejoin="round" opacity=".78">
    <path d="M350 422h500"/>
    <path d="M392 422V302l208-96 208 96v120"/>
    <path d="M468 422V328h264v94"/>
    <path d="M545 422v-52h110v52"/>
    <path d="M600 206v-58"/>
    <path d="M600 148h118"/>
  </g>
  <g fill="#7c2d12" font-family="Inter, Arial, sans-serif" text-anchor="middle">
    <text x="600" y="520" font-size="54" font-weight="600">Cong thong tin nha truong</text>
    <text x="600" y="578" font-size="28" font-weight="400">Quan ly hoc vu hien dai, an toan va ket noi</text>
  </g>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function homeContents(): array
    {
        if (! Schema::hasTable('system_settings') || ! Schema::hasColumn('system_settings', 'setting_record_type')) {
            return [];
        }

        return HomePageContent::query()
            ->whereIn('key', ['banner', 'about'])
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
            ->get()
            ->filter(fn (SchoolPost $post) => $post->isVisibleToRole(null))
            ->take($limit)
            ->values();
    }

    private function events(int $limit)
    {
        if (! $this->eventsTableReady()) {
            return collect();
        }

        $query = SchoolEvent::query();

        if (Schema::hasColumn('school_events', 'is_published')) {
            $query->where('is_published', true);
        }

        return $query
            ->orderByRaw(
                'case when starts_at is null then 2 when starts_at >= ? then 0 else 1 end',
                [now()->startOfDay()]
            )
            ->orderBy('starts_at')
            ->latest()
            ->get()
            ->filter(fn (SchoolEvent $event) => $event->isVisibleToRole(null))
            ->take($limit)
            ->values();
    }

    private function eventsTableReady(): bool
    {
        return Schema::hasTable('school_events')
            || (Schema::hasTable('school_posts') && Schema::hasColumn('school_posts', 'post_type'));
    }

    private function documents(int $limit)
    {
        if (! $this->documentsTableReady()) {
            return collect();
        }

        return LearningDocument::query()
            ->where('is_published', true)
            ->latest()
            ->get()
            ->filter(fn (LearningDocument $document) => $document->isVisibleToRole(null))
            ->take($limit)
            ->values();
    }

    private function documentsTableReady(): bool
    {
        return Schema::hasTable('learning_documents')
            || (Schema::hasTable('school_posts') && Schema::hasColumn('school_posts', 'post_type'));
    }

    private function stats(): array
    {
        return [
            'students' => Schema::hasColumn('users', 'role_type') ? Student::count() : 0,
            'teachers' => Schema::hasColumn('users', 'role_type') ? Teacher::count() : 0,
            'classes' => Schema::hasTable('classes') ? SchoolClass::count() : 0,
            'documents' => $this->documentsTableReady()
                ? LearningDocument::where('is_published', true)->get()->filter(fn (LearningDocument $document) => $document->isVisibleToRole(null))->count()
                : 0,
        ];
    }
}
