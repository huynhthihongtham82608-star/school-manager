<?php

namespace App\Http\Controllers;

use App\Models\SchoolEvent;
use App\Models\SchoolPost;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SchoolEventController extends Controller
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

            $activeTab = 'events';

            return view('announcements.manage', compact('posts', 'events', 'activeTab'));
        }

        if (Schema::hasTable('school_events')) {
            $events = SchoolEvent::where('is_published', true)->orderBy('starts_at')->paginate(10);
            $events->setCollection($events->getCollection()
                ->filter(fn (SchoolEvent $event) => $event->isVisibleToRole($request->user()->role))
                ->values());
        } else {
            $events = collect();
        }

        return view('events.index', compact('events'));
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('school_events')) {
            return back()->with('error', 'Chưa có bảng school_events. Vui lòng import SQL tạo bảng trước.');
        }

        $data = $request->validate($this->rules());
        $targetRoles = $request->input('target_roles', ['all']);
        unset($data['target_roles']);

        $event = SchoolEvent::create([
            ...$data,
            'description' => SchoolEvent::withMeta($data['description'] ?? null, $targetRoles),
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('school_event_created', SchoolEvent::class, $event->id, 'Tạo sự kiện nhà trường');

        return redirect()
            ->route('announcements.index', ['tab' => 'events'])
            ->with('success', 'Đã thêm sự kiện.');
    }

    public function update(Request $request, SchoolEvent $event)
    {
        if (! Schema::hasTable('school_events')) {
            return back()->with('error', 'Chưa có bảng school_events. Vui lòng import SQL tạo bảng trước.');
        }

        $data = $request->validate($this->rules());
        $targetRoles = $request->input('target_roles', ['all']);
        unset($data['target_roles']);

        $event->update([
            ...$data,
            'description' => SchoolEvent::withMeta($data['description'] ?? null, $targetRoles),
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('school_event_updated', SchoolEvent::class, $event->id, 'Cập nhật sự kiện nhà trường');

        return redirect()
            ->route('announcements.index', ['tab' => 'events'])
            ->with('success', 'Đã cập nhật sự kiện.');
    }

    public function destroy(SchoolEvent $event)
    {
        if (! Schema::hasTable('school_events')) {
            return back()->with('error', 'Chưa có bảng school_events. Vui lòng import SQL tạo bảng trước.');
        }

        $eventId = $event->id;
        $event->delete();

        AuditLogger::log('school_event_deleted', SchoolEvent::class, $eventId, 'Xóa sự kiện nhà trường');

        return redirect()
            ->route('announcements.index', ['tab' => 'events'])
            ->with('success', 'Đã xóa sự kiện.');
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
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
