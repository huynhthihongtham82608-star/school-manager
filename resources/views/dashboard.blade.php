@extends('layouts.app')
@section('title', 'Bảng điều khiển')

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
        $addCard('bi-people', 'Lớp đang giảng dạy', 'Xem danh sách lớp và học sinh đang giảng dạy.', route('teacher.classes'));
        $addCard('bi-table', 'Nhập điểm', 'Mở bảng điểm theo lớp, môn và học kỳ.', route('scores.index'));
        $addCard('bi-graph-up', 'Báo cáo lớp', 'Theo dõi tổng kết học tập của lớp.', route('reports.index'));
        $addCard('bi-chat-dots', 'Tin nhắn', 'Trao đổi thông tin với học sinh, phụ huynh và nhà trường.', route('messages.inbox'));
    }

    if ($user->isHomeroom()) {
        $addCard('bi-person-lines-fill', 'Lớp chủ nhiệm', 'Xem danh sách học sinh và hồ sơ lớp chủ nhiệm.', route('teacher.classes'));
        $addCard('bi-clipboard-data', 'Theo dõi điểm toàn lớp', 'Theo dõi kết quả học tập của lớp chủ nhiệm.', route('reports.index'));
        $addCard('bi-calendar-check', 'Theo dõi điểm danh', 'Theo dõi chuyên cần của lớp chủ nhiệm.', route('attendance.index'));
        $addCard('bi-clipboard-check', 'Hạnh kiểm', 'Cập nhật hạnh kiểm và nhận xét học sinh.', route('conduct.index'));
        $addCard('bi-person-check', 'Điểm danh', 'Ghi nhận tình trạng chuyên cần theo lớp.', route('attendance.index'));
        $addCard('bi-journal-text', 'Sổ chủ nhiệm', 'Tổng hợp thông tin học sinh, điểm danh và hạnh kiểm.', route('reports.index'));
        $addCard('bi-send', 'Liên hệ phụ huynh', 'Gửi tin nhắn trao đổi với phụ huynh khi cần.', route('messages.create'));
        $addCard('bi-cpu', 'AI hỗ trợ học tập', 'Mở công cụ AI hỗ trợ học tập.', route('ai.run.form'));
    }

    if ($user->isStudent()) {
        $addCard('bi-calendar3-week', 'Thời khóa biểu', 'Xem lịch học theo lớp và học kỳ.', route('timetable.index'));
        $addCard('bi-bar-chart-line', 'Điểm số', 'Theo dõi điểm thành phần và điểm trung bình của bản thân.', route('scores.index'));
        $addCard('bi-person-check', 'Điểm danh', 'Theo dõi tình trạng chuyên cần của bản thân.', route('attendance.index'));
        $addCard('bi-clipboard-check', 'Hạnh kiểm', 'Xem hạnh kiểm và nhận xét theo học kỳ.', route('conduct.index'));
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
        $addCard('bi-calendar2-check', 'Lịch kiểm tra', 'Xem lịch kiểm tra của lớp, môn học và phòng kiểm tra.', route('exam-schedules.index'));
        $addCard('bi-cpu', 'AI hỗ trợ học tập', 'Theo dõi cảnh báo và nhận xét học tập được tổng hợp.', $user->isParent() ? route('ai.reports') : route('ai.alerts'));
    }

    $adminCards = [
        ['title' => 'Học sinh', 'desc' => 'Tổng số học sinh đang quản lý.', 'icon' => 'bi-people', 'count' => $stats['students']],
        ['title' => 'Giáo viên', 'desc' => 'Tổng số giáo viên trong hệ thống.', 'icon' => 'bi-person-badge', 'count' => $stats['teachers']],
        ['title' => 'Lớp học', 'desc' => 'Tổng số lớp học hiện có.', 'icon' => 'bi-building', 'count' => $stats['classes']],
        ['title' => 'Môn học', 'desc' => 'Tổng số môn học đang sử dụng.', 'icon' => 'bi-book', 'count' => $stats['subjects']],
    ];

    $studentsChartData = [
        'labels' => $adminOverview['studentsByGrade']->pluck('label')->values()->all(),
        'values' => $adminOverview['studentsByGrade']->pluck('value')->values()->all(),
    ];
    $attendanceChartData = [
        'labels' => $adminOverview['attendanceByStatus']->pluck('label')->values()->all(),
        'values' => $adminOverview['attendanceByStatus']->pluck('count')->values()->all(),
    ];
    $scoreChartData = [
        'labels' => $adminOverview['scoreLevels']->pluck('label')->values()->all(),
        'values' => $adminOverview['scoreLevels']->pluck('count')->values()->all(),
    ];
@endphp

@if($user->isAdmin() || $user->isStaff())
    <div class="admin-dashboard">
        <div class="admin-feature-grid">
            @foreach($adminCards as $card)
                <div class="feature-card admin-feature-card admin-stat-card">
                    <span class="feature-card-icon"><i class="bi {{ $card['icon'] }}"></i></span>
                    <span class="admin-feature-content">
                        <span class="feature-card-title d-block">{{ $card['title'] }}</span>
                        <span class="feature-card-desc d-block">{{ $card['desc'] }}</span>
                        <span class="admin-feature-count">{{ $card['count'] }}</span>
                    </span>
                </div>
            @endforeach
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card h-100 admin-chart-card">
                    <div class="card-header">Học sinh theo khối</div>
                    <div class="card-body">
                        @if($adminOverview['studentsByGrade']->sum('value') > 0)
                            <div class="dashboard-chart-wrap">
                                <canvas id="studentsByGradeChart"></canvas>
                            </div>
                        @else
                            <div class="dashboard-chart-empty"><i class="bi bi-bar-chart"></i>Chưa có dữ liệu</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100 admin-chart-card">
                    <div class="card-header">Tình trạng điểm danh</div>
                    <div class="card-body">
                        @if($adminOverview['attendanceByStatus']->sum('count') > 0)
                            <div class="dashboard-chart-wrap">
                                <canvas id="attendanceStatusChart"></canvas>
                            </div>
                        @else
                            <div class="dashboard-chart-empty"><i class="bi bi-person-check"></i>Chưa có dữ liệu</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100 admin-chart-card">
                    <div class="card-header">Kết quả học tập</div>
                    <div class="card-body">
                        @if($adminOverview['scoreLevels']->sum('count') > 0)
                            <div class="dashboard-chart-wrap">
                                <canvas id="scoreLevelChart"></canvas>
                            </div>
                        @else
                            <div class="dashboard-chart-empty"><i class="bi bi-clipboard-data"></i>Chưa có dữ liệu</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header">Thông tin nhanh</div>
                    <div class="card-body">
                        <div class="admin-quick-grid">
                            @foreach($adminOverview['quickInfo'] as $item)
                                <div class="admin-quick-item">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                    <div>
                                        <div class="admin-quick-value">{{ $item['value'] }}</div>
                                        <div class="admin-quick-label">{{ $item['label'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header">Việc cần xử lý</div>
                    <div class="card-body">
                        @php($actionTasks = collect($adminOverview['tasks'])->filter(fn ($task) => $task['count'] > 0))
                        @if($actionTasks->isNotEmpty())
                            <div class="admin-task-list">
                                @foreach($actionTasks as $task)
                                    <div class="admin-task-item needs-action">
                                        <span class="admin-task-icon"><i class="bi {{ $task['icon'] ?? 'bi-exclamation-triangle' }}"></i></span>
                                        <div class="admin-task-content">
                                            <div class="fw-semibold">{{ $task['title'] }}</div>
                                            <div class="text-muted small">{{ $task['detail'] ?: 'Cần kiểm tra lại dữ liệu.' }}</div>
                                            @if($task['detail'] && str_contains($task['title'], 'lớp'))
                                                <ul class="admin-task-sublist">
                                                    @foreach(array_filter(array_map('trim', explode(',', $task['detail']))) as $className)
                                                        <li>{{ $className }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                        <span class="admin-task-count">{{ $task['count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="admin-task-empty">
                                <i class="bi bi-check2-circle"></i>
                                <div>
                                    <div class="fw-semibold">Không có công việc cần xử lý.</div>
                                    <div class="text-muted small">Các dữ liệu quan trọng hiện không có cảnh báo.</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.Chart) {
                return;
            }

            const palette = ['#E67E22', '#F2A65A', '#4B5563', '#FCD34D', '#9CA3AF', '#FED7AA'];
            const gridColor = 'rgba(107, 114, 128, .16)';
            const textColor = '#374151';

            const makeBarChart = (id, labels, values) => {
                const element = document.getElementById(id);
                if (!element) {
                    return;
                }

                new Chart(element, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: '#E67E22',
                            borderRadius: 10,
                            maxBarThickness: 42
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { displayColors: false }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: textColor, font: { weight: 600 } }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0, color: textColor },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            };

            const makeDoughnutChart = (id, labels, values) => {
                const element = document.getElementById(id);
                if (!element) {
                    return;
                }

                new Chart(element, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: palette,
                            borderColor: '#FFFFFF',
                            borderWidth: 4,
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    boxHeight: 10,
                                    color: textColor,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            }
                        }
                    }
                });
            };

            makeBarChart('studentsByGradeChart', @json($studentsChartData['labels']), @json($studentsChartData['values']));
            makeDoughnutChart('attendanceStatusChart', @json($attendanceChartData['labels']), @json($attendanceChartData['values']));
            makeBarChart('scoreLevelChart', @json($scoreChartData['labels']), @json($scoreChartData['values']));
        });
    </script>
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

    @if($user->isTeacher() && $teacherDashboard)
        <div class="row g-3 mb-3">
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Lớp đang dạy</div>
                        <div class="fs-3 fw-bold">{{ $teacherDashboard['class_count'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Số tiết hôm nay</div>
                        <div class="fs-3 fw-bold">{{ $teacherDashboard['today_period_count'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Thông báo mới</div>
                        <div class="fs-3 fw-bold">{{ $teacherDashboard['announcements']->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Lịch kiểm tra gần nhất</div>
                        <div class="fs-3 fw-bold">{{ $teacherDashboard['upcoming_exams']->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header">Thời khóa biểu hôm nay</div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Buổi / Tiết</th>
                                    <th>Môn</th>
                                    <th>Lớp</th>
                                    <th>Phòng</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($teacherDashboard['today_entries'] as $entry)
                                    <tr>
                                        <td class="fw-semibold">{{ $entry->displayPeriod() }}</td>
                                        <td>{{ $entry->assignment?->subject?->name ?? $entry->subject?->name ?? '-' }}</td>
                                        <td>{{ $entry->timetable?->classRoom?->name ?? '-' }}</td>
                                        <td>{{ $entry->displayRoom() ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4"><div class="empty-state"><i class="bi bi-calendar-check"></i>Hôm nay chưa có tiết dạy.</div></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">Thông báo và lịch kiểm tra</div>
                    <div class="list-group list-group-flush">
                        @forelse($teacherDashboard['announcements'] as $post)
                            <a href="{{ route('announcements.index') }}" class="list-group-item list-group-item-action">
                                <div class="fw-semibold">{{ $post->title }}</div>
                                <div class="text-muted small">{{ optional($post->published_at)->format('d/m/Y') }}</div>
                            </a>
                        @empty
                            <div class="list-group-item text-muted">Chưa có thông báo mới.</div>
                        @endforelse
                        @forelse($teacherDashboard['upcoming_exams'] as $exam)
                            <a href="{{ route('exam-schedules.index') }}" class="list-group-item list-group-item-action">
                                <div class="fw-semibold">{{ $exam->subject?->name ?? $exam->title }} - {{ $exam->classRoom?->name }}</div>
                                <div class="text-muted small">{{ optional($exam->exam_date)->format('d/m/Y') }} · {{ $exam->timeRange() }}</div>
                            </a>
                        @empty
                            <div class="list-group-item text-muted">Chưa có lịch kiểm tra sắp diễn ra.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($user->isParent() && ($parentChildren ?? collect())->count() > 1)
        <div class="card mb-3">
            <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <div class="fw-semibold">Chọn học sinh</div>
                    <div class="text-muted small">Dữ liệu phụ huynh sẽ ưu tiên hiển thị theo học sinh đang chọn.</div>
                </div>
                <form method="POST" action="{{ route('parent.select-child') }}" class="d-flex gap-2 align-items-center">
                    @csrf
                    <select name="student_id" class="form-select" onchange="this.form.submit()">
                        @foreach($parentChildren as $child)
                            <option value="{{ $child->id }}" @selected(($selectedParentStudent?->id ?? null) === $child->id)>
                                {{ $child->student_code }} - {{ $child->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    @endif

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

@if($user->isParent() && ($selectedParentStudent ?? null))
    <div class="row g-3 mt-2">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">Điểm của {{ $selectedParentStudent->name }}</div>
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
                        @forelse(($parentScores ?? collect()) as $score)
                            <tr>
                                <td class="fw-semibold">{{ $score->subject->name }}</td>
                                <td>{{ $score->semester->name }}</td>
                                <td><span class="badge bg-info">{{ $score->average }}</span></td>
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
                        @forelse(($parentConduct ?? collect()) as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item->semester->name }}</td>
                                <td>{{ $item->conduct_level }}</td>
                                <td>{{ $item->comment }}</td>
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
