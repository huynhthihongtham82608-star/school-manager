@php
    $studentPortal ??= [];
    $student = $studentPortal['student'] ?? auth()->user()->student;
    $announcements = $studentPortal['announcements'] ?? collect();
    $events = $studentPortal['events'] ?? collect();
    $upcomingExams = $studentPortal['upcoming_exams'] ?? collect();
    $unreadMessages = $studentPortal['unread_messages'] ?? 0;

    $quickActions = [
        [
            'title' => 'Thời khóa biểu',
            'desc' => 'Xem lịch học theo lớp trong tuần.',
            'icon' => 'bi-calendar3-week',
            'tone' => 'orange',
            'url' => route('timetable.index'),
        ],
        [
            'title' => 'Điểm số',
            'desc' => 'Theo dõi điểm thường xuyên, giữa kỳ và cuối kỳ.',
            'icon' => 'bi-bar-chart-line',
            'tone' => 'blue',
            'url' => route('scores.index'),
        ],
        [
            'title' => 'Lịch kiểm tra',
            'desc' => 'Xem các kỳ kiểm tra của lớp.',
            'icon' => 'bi-calendar2-check',
            'tone' => 'green',
            'url' => route('exam-schedules.index'),
        ],
        [
            'title' => 'Điểm danh',
            'desc' => 'Theo dõi chuyên cần của bản thân.',
            'icon' => 'bi-person-check',
            'tone' => 'yellow',
            'url' => route('attendance.index'),
        ],
        [
            'title' => 'Tin nhắn',
            'desc' => $unreadMessages > 0 ? $unreadMessages . ' tin nhắn chưa đọc' : 'Trao đổi với giáo viên và nhà trường.',
            'icon' => 'bi-chat-dots',
            'tone' => 'purple',
            'url' => route('messages.inbox'),
        ],
        [
            'title' => 'Hạnh kiểm',
            'desc' => 'Xem xếp loại và nhận xét của GVCN.',
            'icon' => 'bi-clipboard-check',
            'tone' => 'orange',
            'url' => route('conduct.index'),
        ],
    ];
@endphp

<div class="student-portal">
    <div class="student-portal-header card mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="student-portal-avatar">
                        {{ mb_substr($student?->name ?? auth()->user()->display_name, 0, 1, 'UTF-8') }}
                    </div>
                    <div>
                        <h5 class="mb-1">Xin chào, {{ $student?->name ?? auth()->user()->display_name }}</h5>
                        <div class="text-muted small">
                            {{ $student?->student_code ?? 'Chưa có mã học sinh' }}
                            <span class="mx-1">•</span>
                            {{ $student?->classRoom?->name ?? 'Chưa phân lớp' }}
                            <span class="mx-1">•</span>
                            GVCN: {{ $student?->classRoom?->homeroomTeacher?->name ?? 'Chưa phân công' }}
                        </div>
                    </div>
                </div>
                <span class="badge {{ $student?->statusBadgeClass() ?? 'bg-light text-muted border' }}">
                    {{ $student?->statusLabel() ?? 'Chưa cập nhật' }}
                </span>
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
