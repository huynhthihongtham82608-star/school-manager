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
    <link href="{{ asset('css/school-ui.css') }}?v=20260624-admin-groups" rel="stylesheet">
</head>
@php
    $currentUser = auth()->user();
    $showSidebar = $currentUser && ($currentUser->isAdmin() || $currentUser->isStaff());
    $showRoleMenu = $currentUser && ! $showSidebar;
    $schoolTitle = 'Trường Trung học Phổ thông';
    $aiUrl = ($currentUser->isAdmin() || $currentUser->isStaff() || $currentUser->isHomeroom()) ? route('ai.run.form') : route('ai.alerts');

    $roleMenuItems = [];
    $addRoleItem = function (string $icon, string $label, string $url, string $active = '') use (&$roleMenuItems) {
        $roleMenuItems[] = compact('icon', 'label', 'url', 'active');
    };

    $adminMenuGroups = [];
    $addAdminGroup = function (string $key, string $icon, string $title, array $items) use (&$adminMenuGroups) {
        $adminMenuGroups[] = compact('key', 'icon', 'title', 'items');
    };

    if ($showSidebar) {
        $academicItems = [
            ['bi-calendar-event', 'Năm học', route('school-years.index'), 'school-years*'],
            ['bi-calendar2-week', 'Học kỳ', route('semesters.index'), 'semesters*'],
            ['bi-building', 'Lớp học', route('classes.index'), 'classes*'],
            ['bi-book', 'Môn học', route('subjects.index'), 'subjects*'],
            ['bi-diagram-3', 'Phân công giảng dạy', route('assignments.index'), 'assignments*'],
            ['bi-calendar3-week', 'Thời khóa biểu', route('timetable.manage'), 'timetable/manage*'],
        ];

        if ($currentUser->role === 'admin') {
            $academicItems[] = ['bi-table', 'Điểm số', route('scores.index'), 'scores*'];
            $academicItems[] = ['bi-star', 'Hạnh kiểm', route('conduct.index'), 'conduct*'];
        }

        $academicItems[] = ['bi-person-check', 'Điểm danh', route('attendance.index'), 'attendance*'];

        $addAdminGroup('overview', 'bi-speedometer2', 'Tổng quan', [
            ['bi-house-door', 'Dashboard', route('dashboard'), 'dashboard'],
        ]);
        $addAdminGroup('academic', 'bi-building', 'Quản lý học vụ', $academicItems);
        $addAdminGroup('users', 'bi-people', 'Quản lý người dùng', [
            ['bi-person', 'Học sinh', route('students.index'), 'students*'],
            ['bi-person-badge', 'Giáo viên', route('teachers.index'), 'teachers*'],
            ['bi-people', 'Phụ huynh', route('parents.index'), 'parents*'],
        ]);
        $addAdminGroup('content', 'bi-megaphone', 'Nội dung hệ thống', [
            ['bi-window-stack', 'Quản lý trang chủ', route('admin.home-page.index'), 'admin/home-page*'],
            ['bi-megaphone', 'Thông báo', route('announcements.index'), 'announcements*'],
            ['bi-calendar-event', 'Sự kiện', route('events.index'), 'events*'],
            ['bi-journal-bookmark', 'Tài liệu học tập', route('documents.index'), 'documents*'],
            ['bi-calendar2-check', 'Lịch thi', route('exam-schedules.index'), 'exam-schedules*'],
            ['bi-chat-dots', 'Tin nhắn', route('messages.inbox'), 'messages*'],
        ]);
        $addAdminGroup('ai', 'bi-cpu', 'AI hỗ trợ', [
            ['bi-cpu', 'AI hỗ trợ học tập', $aiUrl, 'ai*'],
            ['bi-robot', 'Chatbot hỗ trợ', route('chatbot.index'), 'chatbot*'],
        ]);

        $reportItems = [];
        if ($currentUser->role === 'admin') {
            $reportItems[] = ['bi-bar-chart', 'Báo cáo', route('reports.class-summary'), 'reports*'];
        }
        $reportItems[] = ['bi-shield-check', 'Nhật ký hoạt động', route('audit-logs.index'), 'audit-logs*'];
        $addAdminGroup('reports', 'bi-graph-up', 'Báo cáo', $reportItems);

        $addAdminGroup('settings', 'bi-gear', 'Cài đặt', [
            ['bi-lock', 'Khóa nhập điểm', route('grade-windows.index'), 'grade-windows*'],
            ['bi-person-circle', 'Hồ sơ cá nhân', route('profile.show'), 'profile'],
            ['bi-key', 'Đổi mật khẩu', route('profile.change-password'), 'profile/change-password'],
        ]);
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
<body class="role-{{ $currentUser->role }} {{ $showSidebar ? 'has-sidebar' : 'no-sidebar' }}">
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
                <div class="admin-sidebar-title">Trường Trung học Phổ thông</div>
                <div class="admin-sidebar-subtitle">{{ $currentUser->display_name }}</div>
            </div>
        </div>

        <nav class="admin-menu" aria-label="Menu quản trị">
            @foreach($adminMenuGroups as $group)
                <div class="admin-menu-section" data-admin-menu-section data-group-key="{{ $group['key'] }}">
                    <button type="button" class="admin-menu-heading" data-admin-accordion-toggle aria-expanded="true" aria-controls="admin-menu-{{ $group['key'] }}">
                        <span class="admin-menu-heading-main">
                            <i class="bi {{ $group['icon'] }}"></i>
                            <span>{{ $group['title'] }}</span>
                        </span>
                        <i class="bi bi-chevron-down admin-menu-chevron"></i>
                    </button>
                    <div class="admin-menu-items" id="admin-menu-{{ $group['key'] }}">
                        @foreach($group['items'] as $item)
                            <div class="admin-menu-row">
                                <a href="{{ $item[2] }}" class="admin-nav-link {{ request()->routeIs($item[3]) || request()->is($item[3]) ? 'active' : '' }}">
                                    <i class="bi {{ $item[0] }}"></i>
                                    <span>{{ $item[1] }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
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
                <div class="d-flex align-items-center gap-2">
                    @if($showSidebar)
                    <button class="btn btn-outline-secondary d-lg-none" type="button" data-sidebar-toggle aria-label="Mở menu">
                        <i class="bi bi-list"></i>
                    </button>
                    @endif
                    <div class="page-title fs-6">@yield('title', $title ?? 'Dashboard')</div>
                </div>
            @endif

            <div class="d-flex align-items-center gap-3">
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
            @include('partials.flash')
            @yield('content')
        </main>
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
        const storageKey = 'school-manager:admin-sidebar-groups';
        const sections = document.querySelectorAll('[data-admin-menu-section]');

        if (!sections.length) {
            return;
        }

        let savedStates = {};
        try {
            savedStates = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
        } catch (error) {
            savedStates = {};
        }

        const setSectionState = (section, isOpen) => {
            const button = section.querySelector('[data-admin-accordion-toggle]');
            const items = section.querySelector('.admin-menu-items');

            section.classList.toggle('is-collapsed', !isOpen);
            button?.setAttribute('aria-expanded', String(isOpen));
            items?.setAttribute('aria-hidden', String(!isOpen));
            items?.querySelectorAll('a, button').forEach((element) => {
                if (isOpen) {
                    element.removeAttribute('tabindex');
                } else {
                    element.setAttribute('tabindex', '-1');
                }
            });
        };

        const persistStates = () => {
            localStorage.setItem(storageKey, JSON.stringify(savedStates));
        };

        sections.forEach((section) => {
            const groupKey = section.dataset.groupKey;
            const button = section.querySelector('[data-admin-accordion-toggle]');
            const isOpen = savedStates[groupKey] ?? true;

            setSectionState(section, isOpen);

            button?.addEventListener('click', () => {
                const nextState = section.classList.contains('is-collapsed');
                savedStates[groupKey] = nextState;
                setSectionState(section, nextState);
                persistStates();
            });
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
        document.querySelectorAll('.admin-nav-link').forEach((link) => {
            link.addEventListener('click', saveScroll, { capture: true });
        });
        scrollTargets.forEach(([, element]) => {
            element.addEventListener('scroll', saveScroll, { passive: true });
        });
    })();

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => document.body.classList.add('sidebar-open'));
    });
    document.querySelectorAll('[data-sidebar-close], .admin-nav-link').forEach((element) => {
        element.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
    });

    document.querySelectorAll('[data-role-menu-toggle]').forEach((button) => {
        button.addEventListener('click', () => document.body.classList.add('role-menu-open'));
    });
    document.querySelectorAll('[data-role-menu-close], .role-sidebar a').forEach((element) => {
        element.addEventListener('click', () => document.body.classList.remove('role-menu-open'));
    });

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
</script>
</body>
</html>
