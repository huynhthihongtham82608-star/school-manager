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

    if ($user->isTeacher()) {
        $roleLabel = $user->isHomeroom()
            ? 'Giáo viên bộ môn kiêm Giáo viên chủ nhiệm'
            : 'Giáo viên bộ môn';
    }

    $cards = [];
    $addCard = function (string $icon, string $title, string $desc, string $url) use (&$cards) {
        $cards[] = compact('icon', 'title', 'desc', 'url');
    };

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
        @php
            $attendanceTask = $adminOverview['tasks'][0] ?? [
                'count' => 0,
                'detail' => '',
            ];
            $pendingAttendanceClasses = collect(explode(',', (string) ($attendanceTask['detail'] ?? '')))
                ->map(fn ($className) => trim($className))
                ->filter()
                ->values();
            if ($pendingAttendanceClasses->isEmpty() && (($attendanceTask['count'] ?? 0) > 0)) {
                $pendingAttendanceClasses = collect(['10A1', '11A1', '11A2']);
            }
            $attendanceIndexUrl = \Illuminate\Support\Facades\Route::has('attendance.index')
                ? route('attendance.index')
                : url('/attendance');
        @endphp

        <div class="admin-kpi-grid">
            @foreach($adminCards as $card)
                <div class="admin-kpi-card">
                    <div class="admin-kpi-icon"><i class="bi {{ $card['icon'] }}"></i></div>
                    <div class="admin-kpi-meta">
                        <div class="admin-kpi-value">{{ $card['count'] }}</div>
                        <div class="admin-kpi-title">{{ $card['title'] }}</div>
                        <div class="admin-kpi-desc">{{ $card['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="admin-center-grid">
            <div class="admin-charts-panel">
                <div class="admin-section-heading">
                    <div>
                        <h5>Biểu đồ tổng quan</h5>
                        <p>Dữ liệu được tổng hợp theo năm học đang làm việc.</p>
                    </div>
                </div>

                <div class="admin-chart-grid">
                    <div class="card admin-chart-card">
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

                    <div class="card admin-chart-card">
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

                    <div class="card admin-chart-card admin-chart-card-wide">
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

            <div class="admin-side-panel">
                <div class="admin-alert-card">
                    <div class="admin-alert-title">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>
                            @if(($attendanceTask['count'] ?? 0) > 0)
                                Hôm nay còn {{ $attendanceTask['count'] }} lớp chưa điểm danh
                            @else
                                Hôm nay không còn lớp chưa điểm danh
                            @endif
                        </span>
                    </div>
                    @if($pendingAttendanceClasses->isNotEmpty())
                        <div class="admin-alert-badges">
                            @foreach($pendingAttendanceClasses->take(6) as $className)
                                <a href="{{ $attendanceIndexUrl }}?q={{ urlencode($className) }}" class="admin-alert-badge">{{ $className }}</a>
                            @endforeach
                        </div>
                    @else
                        <p>Toàn bộ lớp đã có dữ liệu điểm danh hôm nay.</p>
                    @endif
                </div>

                <div class="admin-info-card">
                    <div class="admin-info-title">Thông tin nhanh</div>
                    <div class="admin-info-list">
                        @foreach($adminOverview['quickInfo'] as $item)
                            <div class="admin-info-item">
                                <span class="admin-info-icon"><i class="bi {{ $item['icon'] }}"></i></span>
                                <span class="admin-info-label">{{ $item['label'] }}</span>
                                <span class="admin-info-badge">{{ $item['value'] }}</span>
                            </div>
                        @endforeach
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
@elseif($user->isStudent())
    @include('dashboard.student', ['studentPortal' => $studentPortal])
@elseif($user->isParent())
    @include('dashboard.parent', [
        'parentPortal' => $parentPortal,
        'parentChildren' => $parentChildren,
        'selectedParentStudent' => $selectedParentStudent,
    ])
@else
    <div class="role-hero mb-3 {{ $user->isTeacher() ? 'teacher-portal-hero' : '' }}">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
            <div>
                <h5 class="role-hero-title">Xin chào, {{ $user->display_name }}</h5>
                <div class="role-hero-meta">
                    {{ $user->isTeacher() ? 'Hệ thống quản lý học vụ giảng dạy' : $roleLabel . ' · Truy cập nhanh các chức năng thường dùng' }}
                </div>
            </div>
            <span class="badge badge-role align-self-start">{{ $roleLabel }}</span>
        </div>
    </div>

    @if($user->isTeacher() && $teacherDashboard)
        @php
            $teacherStats = [
                ['label' => 'Lớp đang dạy', 'value' => $teacherDashboard['class_count'], 'icon' => 'bi-people'],
                ['label' => 'Số tiết hôm nay', 'value' => $teacherDashboard['today_period_count'], 'icon' => 'bi-calendar-check'],
                ['label' => 'Thông báo mới', 'value' => $teacherDashboard['announcements']->count(), 'icon' => 'bi-megaphone'],
            ];

            if ($user->isHomeroom()) {
                $teacherStats[] = [
                    'label' => 'Đơn nghỉ học chờ duyệt',
                    'value' => $teacherDashboard['pending_leave_requests'] ?? 0,
                    'icon' => 'bi-envelope-paper',
                ];
            }
        @endphp

        <div class="teacher-stat-grid mb-3">
            @foreach($teacherStats as $stat)
                <div class="teacher-stat-card">
                    <span class="teacher-stat-icon"><i class="bi {{ $stat['icon'] }}"></i></span>
                    <span>
                        <span class="teacher-stat-label">{{ $stat['label'] }}</span>
                        <strong class="teacher-stat-value">{{ $stat['value'] }}</strong>
                    </span>
                </div>
            @endforeach
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <div class="card h-100 teacher-widget-card">
                    <div class="card-header">Thời khóa biểu hôm nay</div>
                    <div class="table-responsive">
                        <table class="table align-middle">
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
                                        <td colspan="4">
                                            <div class="empty-state teacher-empty-state">
                                                <i class="bi bi-calendar-check"></i>Hôm nay thầy/cô chưa có tiết dạy.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="teacher-widget-stack">
                    <div class="card teacher-widget-card">
                        <div class="card-header">Thông báo từ nhà trường</div>
                        <div class="list-group list-group-flush">
                            @forelse($teacherDashboard['announcements'] as $post)
                                <a href="{{ route('announcements.index') }}" class="list-group-item list-group-item-action">
                                    <div class="fw-semibold">{{ $post->title }}</div>
                                    <div class="text-muted small">{{ optional($post->published_at)->format('d/m/Y') ?? $post->created_at?->format('d/m/Y') }}</div>
                                </a>
                            @empty
                                <div class="list-group-item text-muted">Chưa có thông báo mới.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="card teacher-widget-card">
                        <div class="card-header">Lịch kiểm tra sắp diễn ra</div>
                        <div class="list-group list-group-flush">
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
        </div>
    @endif

    @unless($user->isTeacher())
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
    @endunless
@endif

@if(false && $user->isStudent())
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

@if(false && $user->isParent() && ($selectedParentStudent ?? null))
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
