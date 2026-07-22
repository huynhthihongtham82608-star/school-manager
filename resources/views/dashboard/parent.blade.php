@php
    $parentPortal ??= [];
    $children = $parentPortal['children'] ?? ($parentChildren ?? collect());
    $selectedStudent = $parentPortal['selected_student'] ?? ($selectedParentStudent ?? null);
    $announcements = $parentPortal['announcements'] ?? collect();
    $events = $parentPortal['events'] ?? collect();
    $upcomingExams = $parentPortal['upcoming_exams'] ?? collect();
    $pendingLeaveRequests = $parentPortal['pending_leave_requests'] ?? 0;

    $quickActions = [
        [
            'title' => 'Điểm số',
            'desc' => 'Theo dõi điểm thường xuyên, giữa kỳ, cuối kỳ của con.',
            'icon' => 'bi-bar-chart-line',
            'url' => route('scores.index'),
        ],
        [
            'title' => 'Thời khóa biểu',
            'desc' => 'Xem lịch học theo lớp của học sinh đang chọn.',
            'icon' => 'bi-calendar3-week',
            'url' => route('timetable.index'),
        ],
        [
            'title' => 'Lịch kiểm tra',
            'desc' => 'Theo dõi lịch kiểm tra của lớp và môn học.',
            'icon' => 'bi-calendar2-check',
            'url' => route('exam-schedules.index'),
        ],
        [
            'title' => 'Điểm danh',
            'desc' => 'Xem chuyên cần, đi muộn và các ngày nghỉ học.',
            'icon' => 'bi-person-check',
            'url' => route('attendance.index'),
        ],
        [
            'title' => 'Hạnh kiểm',
            'desc' => 'Xem xếp loại và nhận xét của giáo viên chủ nhiệm.',
            'icon' => 'bi-clipboard-check',
            'url' => route('conduct.index'),
        ],
        [
            'title' => 'Xin nghỉ học',
            'desc' => $pendingLeaveRequests > 0 ? $pendingLeaveRequests . ' đơn đang chờ duyệt' : 'Gửi đơn xin nghỉ đến giáo viên chủ nhiệm.',
            'icon' => 'bi-envelope-paper',
            'url' => route('parent.leave-requests.index'),
        ],
    ];
@endphp

<div class="parent-portal">
    <div class="student-portal-header card mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h5 class="mb-1">Xin chào, {{ auth()->user()->display_name }}</h5>
                    <div class="text-muted">Phụ huynh · Theo dõi thông tin học tập của học sinh</div>
                </div>
                <form method="POST" action="{{ route('parent.select-child') }}" class="parent-child-select-form">
                    @csrf
                    <label class="form-label small fw-semibold mb-1">Chọn học sinh</label>
                    <select name="student_id" class="form-select" onchange="this.form.submit()" @disabled($children->isEmpty())>
                        @forelse($children as $child)
                            <option value="{{ $child->id }}" @selected(($selectedStudent?->id ?? null) === $child->id)>
                                {{ $child->student_code }} - {{ $child->name }}{{ $child->classRoom ? ' - ' . $child->classRoom->name : '' }}
                            </option>
                        @empty
                            <option value="">Chưa liên kết học sinh</option>
                        @endforelse
                    </select>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-3 align-items-start">
        <div class="col-lg-8">
            <div class="role-dashboard">
                @foreach($quickActions as $action)
                    <a href="{{ $action['url'] }}" class="feature-card">
                        <span class="feature-card-icon"><i class="bi {{ $action['icon'] }}"></i></span>
                        <span>
                            <span class="feature-card-title d-block">{{ $action['title'] }}</span>
                            <span class="feature-card-desc d-block">{{ $action['desc'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            @if($upcomingExams->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-header">Lịch kiểm tra gần nhất</div>
                    <div class="list-group list-group-flush">
                        @foreach($upcomingExams as $exam)
                            <a href="{{ route('exam-schedules.index') }}" class="list-group-item list-group-item-action">
                                <div class="fw-semibold">{{ $exam->displayName() }} - {{ $exam->subject?->name ?? 'Môn học' }}</div>
                                <div class="text-muted small">
                                    {{ $exam->exam_date?->format('d/m/Y') ?? 'Chưa có ngày' }}
                                    <span class="mx-1">•</span>
                                    {{ $exam->timeRange() }}
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="student-widget-stack">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>Thông báo mới nhất</span>
                        <a href="{{ route('announcements.index') }}" class="small text-decoration-none">Xem tất cả</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse($announcements as $post)
                            <a href="{{ route('announcements.index') }}" class="list-group-item list-group-item-action">
                                <div class="fw-semibold">{{ $post->title }}</div>
                                <div class="text-muted small">{{ $post->published_at?->format('d/m/Y') ?? $post->created_at?->format('d/m/Y') }}</div>
                            </a>
                        @empty
                            <div class="list-group-item text-muted">Chưa có thông báo mới.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>Sự kiện trường</span>
                        <a href="{{ route('events.index') }}" class="small text-decoration-none">Xem tất cả</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse($events as $event)
                            <a href="{{ route('events.index') }}" class="list-group-item list-group-item-action">
                                <div class="fw-semibold">{{ $event->title }}</div>
                                <div class="text-muted small">
                                    {{ $event->starts_at?->format('d/m/Y H:i') ?? 'Đang cập nhật' }}
                                    @if($event->location)
                                        <span class="mx-1">•</span>{{ $event->location }}
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="list-group-item text-muted">Chưa có sự kiện sắp diễn ra.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
