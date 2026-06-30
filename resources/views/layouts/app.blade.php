<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', config('app.name')) - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="{{ asset('css/school-ui.css') }}?v=20260701-history-readonly" rel="stylesheet">
</head>
@php
    $currentUser = auth()->user();
    $showSidebar = $currentUser && ($currentUser->isAdmin() || $currentUser->isStaff());
    $showRoleMenu = $currentUser && ! $showSidebar;
    $schoolTitle = 'Trường Trung học Phổ thông';
    $aiUrl = ($currentUser->isAdmin() || $currentUser->isStaff() || $currentUser->isHomeroom()) ? route('ai.run.form') : route('ai.alerts');
    $headerSchoolYear = null;
    $headerSemester = null;
    $historySchoolYear = null;
    $historySchoolYearId = session('viewing_mode') === 'archive'
        ? session('viewing_school_year_id', session('history_school_year_id'))
        : session('history_school_year_id');

    if ($showSidebar && \Illuminate\Support\Facades\Schema::hasTable('school_years')) {
        $headerSchoolYear = \App\Models\SchoolYear::where('is_active', true)->first()
            ?? \App\Models\SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->first();
        $historySchoolYear = $historySchoolYearId
            ? \App\Models\SchoolYear::find($historySchoolYearId)
            : null;

        if ($historySchoolYear && ! $historySchoolYear->isArchived()) {
            $historySchoolYear = null;
        }

        if ($headerSchoolYear && \Illuminate\Support\Facades\Schema::hasTable('semesters')) {
            $headerSemester = \App\Models\Semester::where('school_year_id', $headerSchoolYear->id)
                ->orderByDesc('is_score_input_open')
                ->orderBy('order')
                ->orderBy('name')
                ->first();
        }

    }

    $roleMenuItems = [];
    $addRoleItem = function (string $icon, string $label, string $url, string $active = '') use (&$roleMenuItems) {
        $roleMenuItems[] = compact('icon', 'label', 'url', 'active');
    };

    $adminMenuGroups = [];
    $addAdminGroup = function (string $key, string $icon, string $title, array $items) use (&$adminMenuGroups) {
        $adminMenuGroups[] = [
            'key' => $key,
            'icon' => $icon,
            'title' => $title,
            'items' => $items,
            'url' => $items[0]['url'] ?? route('dashboard'),
        ];
    };

    $adminItem = fn (string $icon, string $label, string $url, array $active) => compact('icon', 'label', 'url', 'active');

    $matchesAdminItem = function (array $item): bool {
        if (request()->routeIs('announcements.index') && request('tab') === 'events') {
            return $item['label'] === 'Sự kiện';
        }

        foreach ($item['active'] as $pattern) {
            if (request()->routeIs($pattern) || request()->is($pattern)) {
                return true;
            }
        }

        return false;
    };

    $activeAdminGroup = null;
    $activeAdminItem = null;

    if ($showSidebar) {
        $schoolYearMenuUrl = $historySchoolYear
            ? route('school-years.detail', $historySchoolYear)
            : route('school-years.index');

        $academicItems = [
            $adminItem('bi-calendar-event', 'Năm học', $schoolYearMenuUrl, ['school-years.*', 'school-years*']),
            $adminItem('bi-calendar2-week', 'Học kỳ', route('semesters.index'), ['semesters.*', 'semesters*']),
            $adminItem('bi-building', 'Lớp học', route('classes.index'), ['classes.*', 'classes*']),
            $adminItem('bi-book', 'Môn học', route('subjects.index'), ['subjects.*', 'subjects*']),
            $adminItem('bi-diagram-3', 'Phân công giảng dạy', route('assignments.index'), ['assignments.*', 'assignments*']),
            $adminItem('bi-calendar3-week', 'Thời khóa biểu', route('timetable.manage'), ['timetable.manage', 'timetable/manage*']),
            $adminItem('bi-calendar2-check', 'Lịch thi', route('exam-schedules.index'), ['exam-schedules.*', 'exam-schedules*']),
        ];

        if ($currentUser->role === 'admin') {
            $academicItems[] = $adminItem('bi-table', 'Điểm số', route('scores.index'), ['scores.*', 'scores*', 'grade-windows.*', 'grade-windows*']);
            $academicItems[] = $adminItem('bi-star', 'Hạnh kiểm', route('conduct.index'), ['conduct.*', 'conduct*']);
        }

        $academicItems[] = $adminItem('bi-person-check', 'Điểm danh', route('attendance.index'), ['attendance.*', 'attendance*']);

        $addAdminGroup('overview', 'bi-speedometer2', 'Tổng quan', [
            $adminItem('bi-house-door', 'Dashboard', route('dashboard'), ['dashboard']),
        ]);

        $addAdminGroup('academic', 'bi-building', 'Quản lý học vụ', $academicItems);

        $addAdminGroup('users', 'bi-people', 'Quản lý người dùng', [
            $adminItem('bi-person', 'Học sinh', route('students.index'), ['students.*', 'students*']),
            $adminItem('bi-person-badge', 'Giáo viên', route('teachers.index'), ['teachers.*', 'teachers*']),
            $adminItem('bi-people', 'Phụ huynh', route('parents.index'), ['parents.*', 'parents*']),
        ]);

        $addAdminGroup('content', 'bi-megaphone', 'Nội dung hệ thống', [
            $adminItem('bi-window-stack', 'Trang chủ', route('admin.home-page.index'), ['admin.home-page.*', 'admin/home-page*']),
            $adminItem('bi-megaphone', 'Thông báo', route('announcements.index'), ['announcements.*', 'announcements*']),
            $adminItem('bi-calendar-event', 'Sự kiện', route('events.index'), ['events.*', 'events*']),
            $adminItem('bi-journal-bookmark', 'Tài liệu học tập', route('documents.index'), ['documents.*', 'documents*']),
        ]);

        $addAdminGroup('communication', 'bi-chat-dots', 'Giao tiếp', [
            $adminItem('bi-chat-dots', 'Tin nhắn', route('messages.inbox'), ['messages.*', 'messages*']),
        ]);

        $addAdminGroup('ai', 'bi-cpu', 'AI hỗ trợ', [
            $adminItem('bi-bar-chart-line', 'Phân tích', route('ai.run.form'), ['ai.run.form', 'ai/run']),
            $adminItem('bi-exclamation-triangle', 'Cảnh báo', route('ai.alerts'), ['ai.alerts', 'ai/alerts']),
            $adminItem('bi-pencil-square', 'Nhận xét', route('ai.reports'), ['ai.reports', 'ai/reports']),
        ]);

        $addAdminGroup('chatbot', 'bi-robot', 'Chatbot', [
            $adminItem('bi-robot', 'Chatbot hỗ trợ', route('chatbot.index'), ['chatbot.*', 'chatbot*']),
        ]);

        $reportItems = [];
        if ($currentUser->role === 'admin') {
            $reportItems[] = $adminItem('bi-bar-chart', 'Báo cáo', route('reports.class-summary'), ['reports.*', 'reports*']);
        }
        $reportItems[] = $adminItem('bi-shield-check', 'Nhật ký hoạt động', route('audit-logs.index'), ['audit-logs.*', 'audit-logs*']);
        $addAdminGroup('reports', 'bi-graph-up', 'Báo cáo', $reportItems);

        foreach ($adminMenuGroups as $group) {
            foreach ($group['items'] as $item) {
                if ($matchesAdminItem($item)) {
                    $activeAdminGroup = $group;
                    $activeAdminItem = $item;
                    break 2;
                }
            }
        }

        $activeAdminGroup ??= $adminMenuGroups[0] ?? null;
        $activeAdminItem ??= $activeAdminGroup['items'][0] ?? null;
    }

    if ($showRoleMenu) {
        $addRoleItem('bi-house-door', 'Trang chủ', route('dashboard'), 'dashboard');
        if ($currentUser->isStudent()) {
            $addRoleItem('bi-bar-chart-line', 'Kết quả học tập', route('dashboard'), 'dashboard');
            $addRoleItem('bi-calendar3-week', 'Thời khóa biểu', route('timetable.index'), 'timetable*');
            $addRoleItem('bi-person-check', 'Điểm danh', route('attendance.index'), 'attendance*');
            $addRoleItem('bi-clipboard-check', 'Hạnh kiểm', route('dashboard'), 'dashboard');
            $addRoleItem('bi-cpu', 'AI hỗ trợ học tập', route('ai.alerts'), 'ai*');
            $addRoleItem('bi-chat-dots', 'Tin nhắn', route('messages.inbox'), 'messages*');
            $addRoleItem('bi-person-circle', 'Hồ sơ cá nhân', route('profile.show'), 'profile*');
        } elseif ($currentUser->isHomeroom()) {
            $addRoleItem('bi-calendar3-week', 'Thời khóa biểu', route('timetable.index'), 'timetable*');
            $addRoleItem('bi-people', 'Quản lý lớp chủ nhiệm', route('dashboard'), 'dashboard');
            $addRoleItem('bi-clipboard-check', 'Hạnh kiểm', route('conduct.index'), 'conduct*');
            $addRoleItem('bi-table', 'Nhập điểm', route('scores.index'), 'scores*');
            $addRoleItem('bi-chat-dots', 'Tin nhắn', route('messages.inbox'), 'messages*');
            $addRoleItem('bi-cpu', 'AI hỗ trợ học tập', route('ai.run.form'), 'ai*');
            $addRoleItem('bi-person-circle', 'Hồ sơ cá nhân', route('profile.show'), 'profile*');
        } elseif ($currentUser->isTeacher()) {
            $addRoleItem('bi-calendar3-week', 'Thời khóa biểu', route('timetable.index'), 'timetable*');
            $addRoleItem('bi-table', 'Nhập điểm', route('scores.index'), 'scores*');
            $addRoleItem('bi-people', 'Danh sách lớp', route('dashboard'), 'dashboard');
            $addRoleItem('bi-chat-dots', 'Tin nhắn', route('messages.inbox'), 'messages*');
            $addRoleItem('bi-cpu', 'AI hỗ trợ học tập', route('ai.alerts'), 'ai*');
            $addRoleItem('bi-person-circle', 'Hồ sơ cá nhân', route('profile.show'), 'profile*');
        } elseif ($currentUser->isParent()) {
            $addRoleItem('bi-bar-chart-line', 'Theo dõi kết quả học tập', route('dashboard'), 'dashboard');
            $addRoleItem('bi-calendar3-week', 'Thời khóa biểu', route('timetable.index'), 'timetable*');
            $addRoleItem('bi-clipboard-check', 'Hạnh kiểm', route('dashboard'), 'dashboard');
            $addRoleItem('bi-cpu', 'AI hỗ trợ học tập', route('ai.reports'), 'ai*');
            $addRoleItem('bi-chat-dots', 'Tin nhắn', route('messages.inbox'), 'messages*');
            $addRoleItem('bi-person-circle', 'Hồ sơ cá nhân', route('profile.show'), 'profile*');
        }
    }
@endphp
<body class="role-{{ $currentUser->role }} {{ $showSidebar ? 'has-sidebar admin-hide-duplicate-heading' : 'no-sidebar' }} {{ $historySchoolYear ? 'history-readonly' : '' }}">
@if($showSidebar)
<div class="sidebar-overlay" data-sidebar-close></div>
@elseif($showRoleMenu)
<div class="sidebar-overlay role-menu-overlay" data-role-menu-close></div>
@endif

<div class="app-shell d-flex">
    @if($showSidebar)
    <aside class="admin-sidebar">
        <div class="admin-sidebar-head">
            <div class="brand-mark fw-bold rounded-3">TH</div>
            <div>
                <div class="admin-sidebar-title">{{ $schoolTitle }}</div>
                <div class="admin-sidebar-subtitle">{{ $currentUser->display_name }}</div>
            </div>
        </div>

        <nav class="admin-menu admin-menu-groups" aria-label="Menu quản trị">
            @foreach($adminMenuGroups as $group)
                <a href="{{ $group['url'] }}" class="admin-group-link {{ $activeAdminGroup && $activeAdminGroup['key'] === $group['key'] ? 'active' : '' }}">
                    <span class="admin-group-link-main">
                        <i class="bi {{ $group['icon'] }}"></i>
                        <span>{{ $group['title'] }}</span>
                    </span>
                    <i class="bi bi-chevron-right admin-group-link-arrow"></i>
                </a>
            @endforeach
        </nav>
    </aside>
    @elseif($showRoleMenu)
    <nav class="role-sidebar" aria-label="Menu chức năng">
        <div class="role-sidebar-head">
            <div class="brand-mark fw-bold rounded-3">TH</div>
            <div>
                <div class="role-sidebar-title">{{ $schoolTitle }}</div>
                <div class="role-sidebar-subtitle">{{ $currentUser->display_name }}</div>
            </div>
            <button type="button" class="btn btn-light role-sidebar-close" data-role-menu-close aria-label="Đóng menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="role-sidebar-nav">
            @foreach($roleMenuItems as $item)
                <a href="{{ $item['url'] }}" class="role-nav-link {{ ($loop->first && request()->routeIs('dashboard')) || (! request()->routeIs('dashboard') && (request()->is($item['active']) || request()->routeIs($item['active']))) ? 'active' : '' }}">
                    <i class="bi {{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <form method="POST" action="{{ route('logout') }}" data-logout-home>
                @csrf
                <button type="submit" class="role-nav-link role-nav-button">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Đăng xuất</span>
                </button>
            </form>
        </div>
    </nav>
    @endif

    <div class="main-panel flex-grow-1">
        <header class="topbar px-4 py-3 d-flex justify-content-between align-items-center">
            @if($showRoleMenu)
                <div class="role-topbar-left">
                    <button class="btn menu-trigger" type="button" data-role-menu-toggle aria-label="Mở menu">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="school-heading">{{ $schoolTitle }}</div>
                    <form class="topbar-search" role="search" onsubmit="return false;">
                        <i class="bi bi-search"></i>
                        <input type="search" placeholder="Tìm kiếm chức năng..." aria-label="Tìm kiếm">
                    </form>
                </div>
            @else
                <div class="admin-topbar-left d-flex align-items-center gap-2">
                    @if($showSidebar)
                    <button class="btn btn-outline-secondary d-lg-none" type="button" data-sidebar-toggle aria-label="Mở menu">
                        <i class="bi bi-list"></i>
                    </button>
                    @endif
                    @unless($showSidebar)
                        <div class="page-title fs-6">@yield('title', $title ?? 'Dashboard')</div>
                    @endunless
                    @if($showSidebar)
                        <div class="admin-period-meta" aria-label="Năm học và học kỳ hiện tại">
                            <span>{{ $headerSchoolYear?->name ?? 'Chưa thiết lập' }}</span>
                            <span>{{ $headerSemester?->name ?? 'Chưa thiết lập' }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <div class="topbar-actions d-flex align-items-center gap-3">
                <span class="badge badge-role">{{ $currentUser->role }}</span>
                <div class="dropdown">
                    <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-2"></i><span class="fw-semibold">{{ $currentUser->display_name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person-circle me-2"></i>Thông tin cá nhân</a></li>
                        <li><a class="dropdown-item" href="{{ route('profile.change-password') }}"><i class="bi bi-key me-2"></i>Đổi mật khẩu</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline" data-logout-home>
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        <main class="content">
            @if($showSidebar && $activeAdminGroup && $activeAdminGroup['key'] !== 'overview' && count($activeAdminGroup['items']) > 1)
                <div class="admin-context-bar">
                    <div class="admin-section-tabs" aria-label="{{ $activeAdminGroup['title'] }}">
                        @foreach($activeAdminGroup['items'] as $item)
                            <a href="{{ $item['url'] }}" class="admin-section-tab {{ $matchesAdminItem($item) ? 'active' : '' }}">
                                <i class="bi {{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($showSidebar && $historySchoolYear)
                <div class="history-readonly-banner" role="status">
                    <div class="history-readonly-banner-icon">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <div class="history-readonly-banner-content">
                        <div class="history-readonly-banner-title">Đang xem năm học: <strong>{{ $historySchoolYear->name }}</strong></div>
                        <div class="history-readonly-banner-subtitle">Chỉ xem dữ liệu lịch sử</div>
                    </div>
                    <a href="{{ route('school-years.history.clear') }}" class="btn btn-primary history-readonly-back">
                        Quay lại
                    </a>
                </div>
            @endif

            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
<div class="modal fade content-modal" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="modal-kicker">Xác nhận xóa</div>
                    <h5 class="modal-title">Bạn có chắc chắn muốn xóa dữ liệu này không?</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-muted">Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" data-confirm-delete-submit>
                    <i class="bi bi-trash"></i>
                    Xác nhận xóa
                </button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.requestAnimationFrame(() => document.body.classList.add('is-loaded'));

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
        }
    });

    (() => {
        const hideDropdown = (toggle) => {
            if (!toggle) {
                return;
            }

            const instance = bootstrap.Dropdown.getInstance(toggle);

            if (instance) {
                instance.hide();
                return;
            }

            const wrapper = toggle.closest('.dropdown');
            wrapper?.querySelector('.dropdown-menu.show')?.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');
        };

        const hideAllFloatingMenus = (exceptToggle = null) => {
            document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((toggle) => {
                if (exceptToggle && toggle === exceptToggle) {
                    return;
                }

                hideDropdown(toggle);
            });

            document.querySelectorAll('[data-bs-toggle="popover"]').forEach((toggle) => {
                bootstrap.Popover.getInstance(toggle)?.hide();
            });
        };

        document.addEventListener('show.bs.dropdown', (event) => {
            hideAllFloatingMenus(event.target);
        });

        document.addEventListener('click', (event) => {
            const target = event.target;

            if (!(target instanceof Element)) {
                return;
            }

            if (target.closest('.dropdown-menu .dropdown-item')) {
                hideAllFloatingMenus();
                return;
            }

            if (!target.closest('.dropdown, .popover')) {
                hideAllFloatingMenus();
            }
        }, true);

        document.addEventListener('submit', (event) => {
            if (event.target instanceof HTMLFormElement && event.target.closest('.dropdown-menu')) {
                hideAllFloatingMenus();
            }
        }, true);

        ['pagehide', 'beforeunload', 'popstate'].forEach((eventName) => {
            window.addEventListener(eventName, () => hideAllFloatingMenus());
        });
    })();

    (() => {
        const scrollTargets = [
            ['school-manager:admin-sidebar-scroll', document.querySelector('.admin-sidebar')],
            ['school-manager:admin-menu-scroll', document.querySelector('.admin-menu')],
        ].filter(([, element]) => element);

        if (!scrollTargets.length) {
            return;
        }

        const restoreScroll = () => {
            scrollTargets.forEach(([key, element]) => {
                const saved = Number.parseInt(localStorage.getItem(key) || '0', 10);
                if (!Number.isNaN(saved)) {
                    element.scrollTop = saved;
                }
            });
        };

        const saveScroll = () => {
            scrollTargets.forEach(([key, element]) => {
                localStorage.setItem(key, String(element.scrollTop));
            });
        };

        window.requestAnimationFrame(restoreScroll);
        window.addEventListener('pagehide', saveScroll);
        window.addEventListener('beforeunload', saveScroll);
        document.querySelectorAll('.admin-group-link, .admin-section-tab').forEach((link) => {
            link.addEventListener('click', saveScroll, { capture: true });
        });
        scrollTargets.forEach(([, element]) => {
            element.addEventListener('scroll', saveScroll, { passive: true });
        });
    })();

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => document.body.classList.add('sidebar-open'));
    });
    document.querySelectorAll('[data-sidebar-close], .admin-group-link, .admin-section-tab').forEach((element) => {
        element.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
    });

    document.querySelectorAll('[data-role-menu-toggle]').forEach((button) => {
        button.addEventListener('click', () => document.body.classList.add('role-menu-open'));
    });
    document.querySelectorAll('[data-role-menu-close], .role-sidebar a').forEach((element) => {
        element.addEventListener('click', () => document.body.classList.remove('role-menu-open'));
    });

    document.querySelectorAll('[data-target-role-group]').forEach((group) => {
        const allBox = group.querySelector('[data-target-role="all"]');
        const roleBoxes = [...group.querySelectorAll('[data-target-role]')].filter((box) => box !== allBox);

        if (!allBox) {
            return;
        }

        allBox.addEventListener('change', () => {
            if (allBox.checked) {
                roleBoxes.forEach((box) => box.checked = false);
            }
        });

        roleBoxes.forEach((box) => {
            box.addEventListener('change', () => {
                if (box.checked) {
                    allBox.checked = false;
                }
                if (!roleBoxes.some((item) => item.checked)) {
                    allBox.checked = true;
                }
            });
        });
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"], .content-action-btn[title]').forEach((element) => {
        new bootstrap.Tooltip(element, { trigger: 'hover focus' });
    });

    (() => {
        const modalElement = document.getElementById('deleteConfirmModal');
        const confirmButton = document.querySelector('[data-confirm-delete-submit]');

        if (!modalElement || !confirmButton) {
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        let pendingForm = null;

        document.addEventListener('submit', (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const methodInput = form.querySelector('input[name="_method"]');

            if (!methodInput || methodInput.value.toUpperCase() !== 'DELETE') {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            pendingForm = form;
            modal.show();
        }, true);

        modalElement.addEventListener('hidden.bs.modal', () => {
            pendingForm = null;
        });

        confirmButton.addEventListener('click', () => {
            if (!pendingForm) {
                return;
            }

            const form = pendingForm;
            pendingForm = null;
            modal.hide();
            HTMLFormElement.prototype.submit.call(form);
        });
    })();

    document.querySelectorAll('form[data-logout-home]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const token = form.querySelector('input[name="_token"]')?.value;
            try {
                await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': token || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            } finally {
                window.location.href = '{{ route('home') }}';
            }
        });
    });

    @if($historySchoolYear)
    (() => {
        const allowedFormNames = ['logout', 'school-years.history.clear'];

        document.querySelectorAll('form').forEach((form) => {
            const method = (form.querySelector('input[name="_method"]')?.value || form.method || 'GET').toUpperCase();
            const isGet = method === 'GET';
            const isLogout = form.matches('[data-logout-home]') || form.action.includes('/logout');
            const isHistoryClear = form.action.includes('/school-years/history/clear');

            if (isGet || isLogout || isHistoryClear) {
                return;
            }

            form.querySelectorAll('input, select, textarea, button').forEach((element) => {
                element.disabled = true;
            });
        });

        const blockedLinkPatterns = ['/create', '/edit', '/initialize'];
        document.querySelectorAll('a[href]').forEach((link) => {
            if (blockedLinkPatterns.some((pattern) => link.href.includes(pattern))) {
                link.classList.add('d-none');
            }
        });

        document.querySelectorAll('.content-action-btn.edit, .content-action-btn.delete, [data-bs-target*="edit"], [data-activate-school-year], [data-mark-all-present]').forEach((element) => {
            element.classList.add('d-none');
        });
    })();
    @endif
</script>
</body>
</html>
