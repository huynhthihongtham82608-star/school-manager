@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $role = $user->role;
    $roleLabel = [
        'teacher' => 'Giáo viên',
        'homeroom' => 'Giáo viên chủ nhiệm',
        'student' => 'Học sinh',
        'parent' => 'Phụ huynh',
        'staff' => 'Nhân viên',
        'admin' => 'Quản trị viên',
    ][$role] ?? ucfirst($role);

    $cards = [];
    $addCard = function (string $icon, string $title, string $desc, string $url) use (&$cards) {
        $cards[] = compact('icon', 'title', 'desc', 'url');
    };

    if ($user->isTeacher()) {
        $addCard('bi-calendar3-week', 'Thời khóa biểu', 'Xem lịch dạy và lịch học trong tuần.', route('timetable.index'));
        $addCard('bi-table', 'Nhập điểm', 'Mở bảng điểm theo lớp, môn và học kỳ.', route('scores.index'));
        $addCard('bi-graph-up', 'Báo cáo lớp', 'Theo dõi tổng kết học tập của lớp.', route('reports.class-summary'));
        $addCard('bi-chat-dots', 'Tin nhắn', 'Trao đổi thông tin với học sinh, phụ huynh và nhà trường.', route('messages.inbox'));
    }

    if ($user->isHomeroom()) {
        $addCard('bi-clipboard-check', 'Hạnh kiểm', 'Cập nhật hạnh kiểm và nhận xét học sinh.', route('conduct.index'));
        $addCard('bi-person-check', 'Điểm danh', 'Ghi nhận tình trạng chuyên cần theo lớp.', route('attendance.index'));
        $addCard('bi-cpu', 'AI hỗ trợ học tập', 'Mở công cụ AI hỗ trợ học tập.', route('ai.run.form'));
    }

    if ($user->isStudent()) {
        $addCard('bi-calendar3-week', 'Thời khóa biểu', 'Xem lịch học theo lớp và học kỳ.', route('timetable.index'));
        $addCard('bi-bar-chart-line', 'Kết quả học tập', 'Theo dõi điểm trung bình và kết quả hiện có.', route('dashboard'));
        $addCard('bi-person-check', 'Điểm danh', 'Theo dõi tình trạng chuyên cần của bản thân.', route('attendance.index'));
        $addCard('bi-chat-dots', 'Tin nhắn', 'Xem thông báo và trao đổi từ nhà trường.', route('messages.inbox'));
    }

    if ($user->isParent()) {
        $addCard('bi-calendar3-week', 'Thời khóa biểu', 'Theo dõi lịch học của con em.', route('timetable.index'));
        $addCard('bi-person-check', 'Điểm danh', 'Theo dõi tình trạng chuyên cần của con em.', route('attendance.index'));
        $addCard('bi-chat-dots', 'Tin nhắn', 'Trao đổi thông tin với giáo viên và nhà trường.', route('messages.inbox'));
    }

    if (! ($user->isAdmin() || $user->isStaff())) {
        $addCard('bi-megaphone', 'Thông báo', 'Cập nhật tin tức và thông báo mới từ nhà trường.', route('announcements.index'));
        $addCard('bi-calendar-event', 'Sự kiện', 'Theo dõi các hoạt động và sự kiện sắp diễn ra.', route('events.index'));
        $addCard('bi-journal-bookmark', 'Tài liệu học tập', 'Truy cập thư viện tài liệu được chia sẻ.', route('documents.index'));
        $addCard('bi-calendar2-check', 'Lịch thi', 'Xem lịch kiểm tra, lịch thi và phòng thi.', route('exam-schedules.index'));
        $addCard('bi-cpu', 'AI hỗ trợ học tập', 'Theo dõi cảnh báo và nhận xét học tập được tổng hợp.', $user->isParent() ? route('ai.reports') : route('ai.alerts'));
        $addCard('bi-robot', 'Chatbot hỗ trợ', 'Hỏi nhanh về điểm, lịch học, tài liệu và thông báo.', route('chatbot.index'));
        $addCard('bi-person-circle', 'Hồ sơ cá nhân', 'Xem và cập nhật thông tin cá nhân.', route('profile.show'));
    }

    $adminCards = [
        ['title' => 'Học sinh', 'desc' => 'Quản lý thông tin học sinh.', 'icon' => 'bi-people', 'url' => route('students.index'), 'count' => $stats['students']],
        ['title' => 'Giáo viên', 'desc' => 'Quản lý thông tin giáo viên.', 'icon' => 'bi-person-badge', 'url' => route('teachers.index'), 'count' => $stats['teachers']],
        ['title' => 'Lớp học', 'desc' => 'Quản lý lớp học.', 'icon' => 'bi-building', 'url' => route('classes.index'), 'count' => $stats['classes']],
        ['title' => 'Môn học', 'desc' => 'Quản lý danh mục môn học.', 'icon' => 'bi-book', 'url' => route('subjects.index'), 'count' => null],
        ['title' => 'Phân công giảng dạy', 'desc' => 'Quản lý phân công giáo viên.', 'icon' => 'bi-diagram-3', 'url' => route('assignments.index'), 'count' => $stats['assignments']],
        ['title' => 'Thời khóa biểu', 'desc' => 'Quản lý lịch học và lịch dạy.', 'icon' => 'bi-calendar3-week', 'url' => route('timetable.manage'), 'count' => null],
        ['title' => 'Thông báo', 'desc' => 'Quản lý thông báo nhà trường.', 'icon' => 'bi-megaphone', 'url' => route('announcements.index'), 'count' => $stats['announcements']],
        ['title' => 'Sự kiện', 'desc' => 'Quản lý sự kiện nhà trường.', 'icon' => 'bi-calendar-event', 'url' => route('events.index'), 'count' => $stats['events']],
        ['title' => 'Tài liệu học tập', 'desc' => 'Quản lý thư viện tài liệu.', 'icon' => 'bi-journal-bookmark', 'url' => route('documents.index'), 'count' => $stats['documents']],
        ['title' => 'Điểm danh', 'desc' => 'Theo dõi và quản lý điểm danh.', 'icon' => 'bi-person-check', 'url' => route('attendance.index'), 'count' => $stats['attendance']],
        ['title' => 'AI hỗ trợ học tập', 'desc' => 'Mở công cụ AI hỗ trợ học tập.', 'icon' => 'bi-cpu', 'url' => route('ai.run.form'), 'count' => null],
        ['title' => 'Hồ sơ cá nhân', 'desc' => 'Xem và cập nhật thông tin cá nhân.', 'icon' => 'bi-person-circle', 'url' => route('profile.show'), 'count' => null],
    ];
@endphp

<div class="page-heading">
    <div>
        <h5>{{ $user->isAdmin() || $user->isStaff() ? 'Dashboard quản trị' : 'Chức năng chính' }}</h5>
        <div class="text-muted">{{ $user->isAdmin() || $user->isStaff() ? 'Tổng quan vận hành và các tác vụ quản lý thường dùng.' : 'Chọn nhanh chức năng theo vai trò đang đăng nhập.' }}</div>
    </div>
    @if($activeYear)
        <span class="badge bg-info px-3 py-2">Năm học: {{ $activeYear->name }}</span>
    @endif
</div>

@if($user->isAdmin() || $user->isStaff())
    <div class="admin-dashboard">
        <div class="admin-feature-grid">
            @foreach($adminCards as $card)
                <a href="{{ $card['url'] }}" class="feature-card admin-feature-card">
                    <span class="feature-card-icon"><i class="bi {{ $card['icon'] }}"></i></span>
                    <span class="admin-feature-content">
                        <span class="feature-card-title d-block">{{ $card['title'] }}</span>
                        <span class="feature-card-desc d-block">{{ $card['desc'] }}</span>
                        @if(! is_null($card['count']))
                            <span class="admin-feature-count">{{ $card['count'] }}</span>
                        @endif
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@else
    <div class="role-hero mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
            <div>
                <h5 class="role-hero-title">Xin chào, {{ $user->display_name }}</h5>
                <div class="role-hero-meta">{{ $roleLabel }} · Truy cập nhanh các chức năng thường dùng</div>
            </div>
            <span class="badge badge-role align-self-start">{{ $roleLabel }}</span>
        </div>
    </div>

    <div class="role-dashboard">
        @forelse($cards as $card)
            <a href="{{ $card['url'] }}" class="feature-card">
                <span class="feature-card-icon"><i class="bi {{ $card['icon'] }}"></i></span>
                <span>
                    <span class="feature-card-title d-block">{{ $card['title'] }}</span>
                    <span class="feature-card-desc d-block">{{ $card['desc'] }}</span>
                </span>
            </a>
        @empty
            <div class="card">
                <div class="empty-state"><i class="bi bi-grid"></i>Chưa có chức năng nhanh cho vai trò này.</div>
            </div>
        @endforelse
    </div>
@endif

@if($user->isTeacher())
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Lớp được phân công</span>
            @if($homeroomClass)<span class="badge bg-info">GVCN: {{ $homeroomClass->name }}</span>@endif
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Lớp</th>
                        <th>Môn</th>
                        <th>Năm học</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teacherAssignments as $assign)
                        <tr>
                            <td class="fw-semibold">{{ $assign->classRoom->name }}</td>
                            <td>{{ $assign->subject->name }}</td>
                            <td>{{ $assign->schoolYear->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="empty-state"><i class="bi bi-inbox"></i>Chưa có phân công giảng dạy.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($user->isHomeroom() && $homeroomClass)
    <div class="card mt-4">
        <div class="card-header">Lớp chủ nhiệm {{ $homeroomClass->name }}</div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã HS</th>
                        <th>Họ tên</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($homeroomClass->students as $st)
                    <tr>
                        <td class="fw-semibold">{{ $st->student_code }}</td>
                        <td>{{ $st->name }}</td>
                        <td><span class="badge bg-success">{{ $st->status }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            <div class="empty-state"><i class="bi bi-person-dash"></i>Lớp chủ nhiệm chưa có học sinh.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($user->isStudent())
    <div class="row g-3 mt-2">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">Điểm của tôi</div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Môn</th>
                                <th>Học kỳ</th>
                                <th>TB</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($studentScores as $sc)
                            <tr>
                                <td class="fw-semibold">{{ $sc->subject->name }}</td>
                                <td>{{ $sc->semester->name }}</td>
                                <td><span class="badge bg-info">{{ $sc->average }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state"><i class="bi bi-clipboard-data"></i>Chưa có dữ liệu điểm.</div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Hạnh kiểm</div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Học kỳ</th>
                                <th>Mức</th>
                                <th>Nhận xét</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse(($conduct ?? collect()) as $c)
                            <tr>
                                <td class="fw-semibold">{{ $c->semester->name }}</td>
                                <td>{{ $c->conduct_level }}</td>
                                <td>{{ $c->comment }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state"><i class="bi bi-clipboard-check"></i>Chưa có dữ liệu hạnh kiểm.</div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
