<?php

namespace App\Http\Controllers;

use App\Models\SchoolEvent;
use App\Models\SchoolPost;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        if ($this->canManageContent($request)) {
            $posts = Schema::hasTable('school_posts')
                ? SchoolPost::query()->latest('published_at')->latest()->paginate(10, ['*'], 'posts_page')->withQueryString()
                : collect();

            $events = Schema::hasTable('school_events')
                ? SchoolEvent::query()->latest('starts_at')->latest()->paginate(10, ['*'], 'events_page')->withQueryString()
                : collect();

            $activeTab = $request->query('tab') === 'events' ? 'events' : 'announcements';

            return view('announcements.manage', compact('posts', 'events', 'activeTab'));
        }

        if (Schema::hasTable('school_posts')) {
            $posts = SchoolPost::where('is_published', true)->latest('published_at')->latest()->paginate(10);
            $posts->setCollection($posts->getCollection()
                ->filter(fn (SchoolPost $post) => $post->isVisibleToRole($request->user()->role))
                ->values());
        } else {
            $posts = collect();
        }

        return view('announcements.index', compact('posts'));
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('school_posts')) {
            return back()->with('error', 'Chưa có bảng school_posts. Vui lòng import SQL tạo bảng trước.');
        }

        $data = $request->validate($this->rules());
        $targetRoles = $request->input('target_roles', ['all']);
        unset($data['target_roles']);

        $post = SchoolPost::create([
            ...$data,
            'content' => SchoolPost::withMeta($data['content'] ?? null, $targetRoles),
            'published_at' => $data['published_at'] ?? now(),
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('school_post_created', SchoolPost::class, $post->id, 'Tạo thông báo hoặc tin tức');

        return redirect()
            ->route('announcements.index', ['tab' => 'announcements'])
            ->with('success', 'Đã thêm thông báo.');
    }

    public function update(Request $request, SchoolPost $post)
    {
        if (! Schema::hasTable('school_posts')) {
            return back()->with('error', 'Chưa có bảng school_posts. Vui lòng import SQL tạo bảng trước.');
        }

        $data = $request->validate($this->rules());
        $targetRoles = $request->input('target_roles', ['all']);
        unset($data['target_roles']);

        $post->update([
            ...$data,
            'content' => SchoolPost::withMeta($data['content'] ?? null, $targetRoles),
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('school_post_updated', SchoolPost::class, $post->id, 'Cập nhật thông báo hoặc tin tức');

        return redirect()
            ->route('announcements.index', ['tab' => 'announcements'])
            ->with('success', 'Đã cập nhật thông báo.');
    }

    public function destroy(SchoolPost $post)
    {
        if (! Schema::hasTable('school_posts')) {
            return back()->with('error', 'Chưa có bảng school_posts. Vui lòng import SQL tạo bảng trước.');
        }

        $postId = $post->id;
        $post->delete();

        AuditLogger::log('school_post_deleted', SchoolPost::class, $postId, 'Xóa thông báo hoặc tin tức');

        return redirect()
            ->route('announcements.index', ['tab' => 'announcements'])
            ->with('success', 'Đã xóa thông báo.');
    }

    private function rules(): array
    {
        return [
            'type' => ['required', 'in:news,announcement'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:all,admin,teacher,homeroom,student,parent'],
        ];
    }

    private function canManageContent(Request $request): bool
    {
        $user = $request->user();

        return $user && ($user->isAdmin() || $user->isStaff());
    }
}
