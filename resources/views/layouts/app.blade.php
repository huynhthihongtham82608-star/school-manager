<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', config('app.name')) - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="{{ asset('css/school-ui.css') }}?v=20260727-academic-interactions" rel="stylesheet">
</head>
@php
    $currentUser = auth()->user();
    $showSidebar = $currentUser && ($currentUser->isAdmin() || $currentUser->isStaff());
    $showRoleMenu = $currentUser && ! $showSidebar;
    $systemSetting = \App\Models\SystemSetting::current();
    $schoolTitle = 'Trường Trung học Phổ thông';
    $schoolTitle = $systemSetting->school_name ?: $schoolTitle;
    $schoolShortName = $systemSetting->short_name ?: 'TH';
    $schoolLogoUrl = $systemSetting->logoUrl();
    $headerSchoolYears = collect();
    $headerSemesters = collect();
    $headerSchoolYear = null;
    $headerSemester = null;
    $historySchoolYear = null;
    $historySchoolYearId = session('viewing_mode') === 'history'
        ? session('viewing_school_year_id', session('history_school_year_id'))
        : session('history_school_year_id');
    $showFloatingChatbot = (bool) $currentUser;
    $floatingChatMessages = collect();

    if ($showFloatingChatbot && \Illuminate\Support\Facades\Schema::hasTable('chatbot_messages')) {
        $floatingChatMessages = \App\Models\ChatbotMessage::where('user_id', $currentUser->id)
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();
    }

    if ($showSidebar && \Illuminate\Support\Facades\Schema::hasTable('school_years')) {
        $headerSchoolYears = \App\Models\SchoolYear::orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->get();
        $headerSchoolYear = $headerSchoolYears->firstWhere('id', $historySchoolYearId)
            ?? $headerSchoolYears->firstWhere('id', session('working_school_year_id'))
            ?? $headerSchoolYears->firstWhere('is_active', true)
            ?? $headerSchoolYears->first();
        $historySchoolYear = $historySchoolYearId
            ? \App\Models\SchoolYear::find($historySchoolYearId)
            : null;

        if ($headerSchoolYear && \Illuminate\Support\Facades\Schema::hasTable('semesters')) {
            $headerSemesters = \App\Models\Semester::where('school_year_id', $headerSchoolYear->id)
                ->when(! $historySchoolYear, fn ($query) => $query->where('status', '!=', \App\Models\Semester::STATUS_ARCHIVED))
                ->orderByRaw("case when status = 'active' then 0 when is_score_input_open = 1 then 1 else 2 end")
                ->orderBy('order')
                ->orderBy('name')
                ->get();
            $headerSemester = $headerSemesters->firstWhere('id', session('working_semester_id'))
                ?? $headerSemesters->firstWhere('status', \App\Models\Semester::STATUS_ACTIVE)
                ?? $headerSemesters->first();
        }

    }

    $roleMenuItems = [];
    $roleMenuGroups = [];
    $addRoleItem = function (string $icon, string $label, string $url, string $active = '') use (&$roleMenuItems) {
        $roleMenuItems[] = compact('icon', 'label', 'url', 'active');
    };
    $makeRoleItem = fn (string $icon, string $label, string $url, string|array $active = '', array $options = []) => array_merge(compact('icon', 'label', 'url', 'active'), $options);
    $addRoleGroup = function (string $title, array $items) use (&$roleMenuGroups) {
        $items = array_values(array_filter($items));

        if ($items) {
            $roleMenuGroups[] = compact('title', 'items');
        }
    };

    $adminMenuGroups = [];
    $filterAdminItems = function (array $items) use ($currentUser) {
        return array_values(array_filter($items, function (array $item) use ($currentUser) {
            $permission = $item['permission'] ?? null;

            return ! $permission || $currentUser?->hasPermission($permission);
        }));
    };
    $addAdminGroup = function (string $key, string $icon, string $title, array $items, array $subgroups = []) use (&$adminMenuGroups, $filterAdminItems) {
        $items = $filterAdminItems($items);
        $subgroups = collect($subgroups)
            ->map(function (array $subgroup) use ($filterAdminItems) {
                $subgroup['items'] = $filterAdminItems($subgroup['items'] ?? []);

                return $subgroup;
            })
            ->filter(fn (array $subgroup) => ! empty($subgroup['items']))
            ->values()
            ->all();
        $flatSubgroupItems = collect($subgroups)->flatMap(fn (array $subgroup) => $subgroup['items'])->values()->all();
        $groupItems = $items ?: $flatSubgroupItems;

        if (empty($groupItems)) {
            return;
        }

        $adminMenuGroups[] = [
            'key' => $key,
            'icon' => $icon,
            'title' => $title,
            'items' => $groupItems,
            'subgroups' => $subgroups,
            'url' => $groupItems[0]['url'] ?? route('dashboard'),
        ];
    };

    $adminItem = fn (string $icon, string $label, string $url, array $active, ?string $permission = null) => compact('icon', 'label', 'url', 'active', 'permission');

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

        $academicConfigItems = [
            $adminItem('bi-calendar-event', 'Năm học', $schoolYearMenuUrl, ['school-years.*', 'school-years*'], 'academic.manage'),
            $adminItem('bi-calendar2-week', 'Học kỳ', route('semesters.index'), ['semesters.*', 'semesters*'], 'academic.manage'),
            $adminItem('bi-book', 'Môn học', route('subjects.index'), ['subjects.*', 'subjects*'], 'subjects.manage'),
            $adminItem('bi-diagram-2', 'Tổ chuyên môn', route('departments.index'), ['departments.*', 'departments*'], 'departments.manage'),
            $adminItem('bi-door-open', 'Phòng học', route('rooms.index'), ['rooms.*', 'rooms*'], 'rooms.manage'),
        ];

        $academicTeachingItems = [
            $adminItem('bi-building', 'Lớp học', route('classes.index'), ['classes.*', 'classes*'], 'classes.manage'),
            $adminItem('bi-diagram-3', 'Phân công giảng dạy', route('assignments.index'), ['assignments.*', 'assignments*'], 'assignments.manage'),
            $adminItem('bi-calendar3-week', 'Thời khóa biểu', route('timetable.manage'), ['timetable.manage', 'timetable/manage*'], 'timetable.manage'),
            $adminItem('bi-calendar2-check', 'Lịch kiểm tra', route('exam-schedules.index'), ['exam-schedules.*', 'exam-schedules*'], 'exams.manage'),
        ];

        $academicResultItems = [
            $adminItem('bi-person-check', 'Điểm danh', route('attendance.index'), ['attendance.*', 'attendance*'], 'attendance.view'),
        ];

        if ($currentUser->hasAnyPermission(['scores.view', 'scores.manage'])) {
            $academicResultItems[] = $adminItem('bi-table', 'Điểm số', route('scores.index'), ['scores.*', 'scores*', 'score-columns.*', 'score-columns*', 'grade-windows.*', 'grade-windows*'], 'scores.view');
        }

        if ($currentUser->hasAnyPermission(['conduct.view', 'conduct.manage'])) {
            $academicResultItems[] = $adminItem('bi-star', 'Hạnh kiểm', route('conduct.index'), ['conduct.*', 'conduct*'], 'conduct.view');
        }

        $academicSubgroups = [
            [
                'key' => 'academic-config',
                'icon' => 'bi-sliders',
                'title' => 'Cấu hình hệ thống',
                'items' => $academicConfigItems,
            ],
            [
                'key' => 'academic-teaching',
                'icon' => 'bi-easel2',
                'title' => 'Tổ chức giảng dạy',
                'items' => $academicTeachingItems,
            ],
            [
                'key' => 'academic-results',
                'icon' => 'bi-clipboard-data',
                'title' => 'Quản lý kết quả',
                'items' => $academicResultItems,
            ],
        ];

        $academicItems = array_merge($academicConfigItems, $academicTeachingItems, $academicResultItems);

        $addAdminGroup('overview', 'bi-speedometer2', 'Tổng quan', [
            $adminItem('bi-house-door', 'Bảng điều khiển', route('dashboard'), ['dashboard'], 'dashboard.view'),
        ]);

        $addAdminGroup('academic', 'bi-building', 'Quản lý học vụ', $academicItems, $academicSubgroups);

        $addAdminGroup('users', 'bi-people', 'Quản lý người dùng', [
            $adminItem('bi-person', 'Học sinh', route('students.index'), ['students.*', 'students*'], 'students.manage'),
            $adminItem('bi-person-badge', 'Giáo viên', route('teachers.index'), ['teachers.*', 'teachers*'], 'teachers.manage'),
            $adminItem('bi-people', 'Phụ huynh', route('parents.index'), ['parents.*', 'parents*'], 'parents.manage'),
        ]);

        $addAdminGroup('content', 'bi-megaphone', 'Nội dung hệ thống', [
            $adminItem('bi-window-stack', 'Trang chủ', route('admin.home-page.index'), ['admin.home-page.*', 'admin/home-page*'], 'content.manage'),
            $adminItem('bi-megaphone', 'Thông báo', route('announcements.index'), ['announcements.*', 'announcements*'], 'content.manage'),
            $adminItem('bi-calendar-event', 'Sự kiện', route('events.index'), ['events.*', 'events*'], 'content.manage'),
            $adminItem('bi-journal-bookmark', 'Tài liệu học tập', route('documents.index'), ['documents.*', 'documents*'], 'documents.manage'),
        ]);

        $addAdminGroup('communication', 'bi-chat-dots', 'Giao tiếp', [
            $adminItem('bi-inbox', 'Hộp thư đến', route('messages.inbox'), ['messages.inbox'], 'messages.manage'),
            $adminItem('bi-send', 'Đã gửi', route('messages.sent'), ['messages.sent'], 'messages.manage'),
            $adminItem('bi-pencil-square', 'Soạn tin', route('messages.create'), ['messages.create'], 'messages.manage'),
            $adminItem('bi-trash3', 'Thùng rác', route('messages.trash'), ['messages.trash'], 'messages.manage'),
        ]);

        $addAdminGroup('system', 'bi-gear', 'Hệ thống', [
            $adminItem('bi-sliders', 'Cài đặt hệ thống', route('system.settings.edit'), ['system.settings.*', 'system/settings*'], 'system.settings'),
            $adminItem('bi-database-down', 'Sao lưu & Khôi phục dữ liệu', route('system.backups.index'), ['system.backups.*', 'system/backups*'], 'backups.manage'),
            $adminItem('bi-shield-check', 'Nhật ký hoạt động', route('audit-logs.index'), ['audit-logs.*', 'audit-logs*'], 'audit_logs.view'),
            $adminItem('bi-shield-lock', 'Vai trò & quyền', route('rbac-roles.index'), ['rbac-roles.*', 'rbac-roles*'], 'manage_roles'),
        ]);

        $reportItems = [];
        if ($currentUser->hasPermission('reports.view')) {
            $reportItems[] = $adminItem('bi-bar-chart', 'Báo cáo', route('reports.index'), ['reports.*', 'reports*'], 'reports.view');
        }
        if ($reportItems) {
        $addAdminGroup('reports', 'bi-graph-up', 'Báo cáo', $reportItems);
        }

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
        if ($currentUser->isTeacher()) {
            $addRoleItem('bi-house-door', 'Trang chủ', route('dashboard'), 'dashboard');

            $teachingItems = [
                $makeRoleItem('bi-calendar3-week', 'Thời khóa biểu & Lịch kiểm tra', route('timetable.index'), ['timetable*', 'exam-schedules*']),
                $makeRoleItem('bi-people', 'Danh sách lớp dạy', route('teacher.classes'), 'teacher/classes*', ['query_not' => ['scope' => 'homeroom']]),
                $makeRoleItem('bi-table', 'Nhập điểm số', route('scores.index'), 'scores*'),
                $makeRoleItem('bi-person-check', 'Điểm danh tiết dạy', route('attendance.index', ['scope' => 'teaching']), 'attendance*', ['query_not' => ['scope' => 'homeroom']]),
                $makeRoleItem('bi-journal-bookmark', 'Tài liệu học tập', route('documents.index'), 'documents*'),
            ];

            if ($currentUser->teacher?->leadingDepartment()?->exists()) {
                $teachingItems[] = $makeRoleItem('bi-diagram-2', 'Tổng quan tổ chuyên môn', route('teacher.department'), 'teacher/department*');
            }

            $addRoleGroup('Giảng dạy', $teachingItems);

            if ($currentUser->isHomeroom()) {
                $addRoleGroup('Chủ nhiệm', [
                    $makeRoleItem('bi-person-vcard', 'Hồ sơ lớp chủ nhiệm', route('teacher.classes', ['scope' => 'homeroom']), 'teacher/classes*', ['query' => ['scope' => 'homeroom']]),
                    $makeRoleItem('bi-clipboard-data', 'Theo dõi điểm toàn lớp', route('reports.index'), 'reports*'),
                    $makeRoleItem('bi-calendar-check', 'Điểm danh & Duyệt nghỉ học', route('attendance.index', ['scope' => 'homeroom']), 'attendance*', ['query' => ['scope' => 'homeroom']]),
                    $makeRoleItem('bi-clipboard-check', 'Đánh giá hạnh kiểm', route('conduct.index'), 'conduct*'),
                ]);
            }

            $addRoleGroup('Tương tác', [
                $makeRoleItem('bi-chat-dots', 'Tin nhắn & Liên hệ phụ huynh', route('messages.inbox'), 'messages*'),
                $makeRoleItem('bi-megaphone', 'Thông báo & Sự kiện', route('announcements.index'), ['announcements*', 'events*']),
            ]);
        } else {
            $addRoleItem('bi-house-door', 'Trang chủ', route('dashboard'), 'dashboard');
        }

        if ($currentUser->isStudent()) {
            $addRoleItem('bi-calendar3-week', 'Thời khóa biểu', route('timetable.index'), 'timetable*');
            $addRoleItem('bi-bar-chart-line', 'Điểm số', route('scores.index'), 'scores*');
            $addRoleItem('bi-person-check', 'Điểm danh', route('attendance.index'), 'attendance*');
            $addRoleItem('bi-clipboard-check', 'Hạnh kiểm', route('conduct.index'), 'conduct*');
            $addRoleItem('bi-calendar2-check', 'Lịch kiểm tra', route('exam-schedules.index'), 'exam-schedules*');
            $addRoleItem('bi-journal-bookmark', 'Tài liệu học tập', route('documents.index'), 'documents*');
            $addRoleItem('bi-megaphone', 'Thông báo', route('announcements.index'), 'announcements*');
            $addRoleItem('bi-chat-dots', 'Tin nhắn', route('messages.inbox'), 'messages*');
        } elseif ($currentUser->isParent()) {
            $addRoleItem('bi-bar-chart-line', 'Điểm số', route('scores.index'), 'scores*');
            $addRoleItem('bi-calendar3-week', 'Thời khóa biểu', route('timetable.index'), 'timetable*');
            $addRoleItem('bi-calendar2-check', 'Lịch kiểm tra', route('exam-schedules.index'), 'exam-schedules*');
            $addRoleItem('bi-person-check', 'Điểm danh', route('attendance.index'), 'attendance*');
            $addRoleItem('bi-clipboard-check', 'Hạnh kiểm', route('conduct.index'), 'conduct*');
            $addRoleItem('bi-envelope-paper', 'Xin nghỉ học', route('parent.leave-requests.index'), 'parent/leave-requests*');
            $addRoleItem('bi-chat-dots', 'Tin nhắn', route('messages.inbox'), 'messages*');
            $addRoleItem('bi-megaphone', 'Thông báo', route('announcements.index'), 'announcements*');
        }
    }

    $functionSearchItems = [];
    if ($showSidebar) {
        foreach ($adminMenuGroups as $group) {
            foreach ($group['items'] as $item) {
                $functionSearchItems[] = [
                    'label' => $item['label'],
                    'group' => $group['title'],
                    'url' => $item['url'],
                    'icon' => $item['icon'],
                    'keywords' => trim($item['label'] . ' ' . $group['title']),
                ];
            }
        }
    } elseif ($showRoleMenu) {
        $searchRoleItems = collect($roleMenuGroups)
            ->flatMap(fn ($group) => collect($group['items'])->map(fn ($item) => $item + ['group' => $group['title']]))
            ->values();

        if ($searchRoleItems->isEmpty()) {
            $searchRoleItems = collect($roleMenuItems)->map(fn ($item) => $item + ['group' => 'Chức năng'])->values();
        }

        foreach ($searchRoleItems as $item) {
            $functionSearchItems[] = [
                'label' => $item['label'],
                'group' => $item['group'],
                'url' => $item['url'],
                'icon' => $item['icon'],
                'keywords' => $item['label'],
            ];
        }
    }

    $roleItemIsActive = function (array $item): bool {
        $patterns = (array) ($item['active'] ?? []);

        foreach ($patterns as $pattern) {
            if ($pattern && (request()->is($pattern) || request()->routeIs($pattern))) {
                foreach (($item['query'] ?? []) as $key => $value) {
                    if ((string) request()->query($key) !== (string) $value) {
                        return false;
                    }
                }

                foreach (($item['query_not'] ?? []) as $key => $value) {
                    if ((string) request()->query($key) === (string) $value) {
                        return false;
                    }
                }

                foreach (($item['without_query'] ?? []) as $key) {
                    if (request()->query->has($key)) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    };

    $visibleAdminSidebar = $showSidebar
        && ! in_array($activeAdminGroup['key'] ?? '', ['overview', 'reports'], true);
@endphp
<body class="role-{{ $currentUser->role }} {{ $visibleAdminSidebar ? 'has-sidebar admin-hide-duplicate-heading' : 'no-sidebar' }} {{ $historySchoolYear ? 'history-readonly' : '' }}">
@if($visibleAdminSidebar)
<div class="sidebar-overlay" data-sidebar-close></div>
@elseif($showRoleMenu)
<div class="sidebar-overlay role-menu-overlay" data-role-menu-close></div>
@endif

<div class="app-shell d-flex">
    @if($visibleAdminSidebar)
    <aside class="admin-sidebar">
        <nav class="admin-menu admin-submenu" aria-label="Chức năng con quản trị">
            @if($activeAdminGroup)
                @if($activeAdminGroup['key'] === 'academic' && ! empty($activeAdminGroup['subgroups']))
                    @foreach($activeAdminGroup['subgroups'] as $subgroup)
                        @php
                            $adminSubgroupTitle = match ($subgroup['key']) {
                                'academic-config' => 'Cấu hình hệ thống',
                                'academic-teaching' => 'Tổ chức giảng dạy',
                                'academic-results' => 'Quản lý kết quả',
                                default => $subgroup['title'],
                            };
                            $subgroupIsActive = collect($subgroup['items'])->contains(fn ($item) => $matchesAdminItem($item));
                        @endphp
                        <section class="admin-menu-section {{ $subgroupIsActive ? 'is-open' : 'is-collapsed' }}" data-admin-submenu-section>
                            <button
                                type="button"
                                class="admin-menu-heading admin-submenu-heading"
                                data-admin-submenu-toggle
                                aria-expanded="{{ $subgroupIsActive ? 'true' : 'false' }}"
                            >
                                <span class="admin-menu-heading-main">
                                    <i class="bi {{ $subgroup['icon'] }}"></i>
                                    <span>{{ $adminSubgroupTitle }}</span>
                                </span>
                                <i class="bi bi-chevron-down admin-menu-chevron"></i>
                            </button>
                            <div class="admin-menu-items">
                                @foreach($subgroup['items'] as $item)
                                    <a href="{{ $item['url'] }}" class="admin-nav-link {{ $matchesAdminItem($item) ? 'active' : '' }}">
                                        <i class="bi {{ $item['icon'] }}"></i>
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                @else
                    <section class="admin-menu-section">
                        <div class="admin-menu-items">
                            @foreach($activeAdminGroup['items'] as $item)
                                <a href="{{ $item['url'] }}" class="admin-nav-link {{ $matchesAdminItem($item) ? 'active' : '' }}">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endif
        </nav>

        <button type="button" class="admin-sidebar-collapse" data-admin-sidebar-collapse aria-label="Thu gọn menu chức năng">
            <i class="bi bi-layout-sidebar-inset"></i>
            <span>Thu gọn menu</span>
        </button>
    </aside>
    @elseif($showRoleMenu)
    <nav class="role-sidebar" aria-label="Menu chức năng">
        <div class="role-sidebar-head">
            @if($schoolLogoUrl)
                <img src="{{ $schoolLogoUrl }}" alt="{{ $schoolTitle }}" class="brand-mark rounded-3 object-fit-cover">
            @else
                <div class="brand-mark fw-bold rounded-3">{{ $schoolShortName }}</div>
            @endif
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
                <a href="{{ $item['url'] }}" class="role-nav-link {{ $roleItemIsActive($item) ? 'active' : '' }}">
                    <i class="bi {{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach

            @if(! empty($roleMenuGroups))
                @foreach($roleMenuGroups as $group)
                    <?php
                        $groupIsActive = collect($group['items'])->contains(function ($item) use ($roleItemIsActive) {
                            return $roleItemIsActive($item);
                        });
                    ?>
                    <div class="role-nav-group {{ $groupIsActive ? 'is-open' : 'is-collapsed' }}" data-role-nav-group>
                        <button
                            type="button"
                            class="role-nav-group-title"
                            data-role-group-toggle
                            aria-expanded="{{ $groupIsActive ? 'true' : 'false' }}"
                        >
                            <span>{{ $group['title'] }}</span>
                            <i class="bi bi-chevron-down role-nav-group-toggle-icon"></i>
                        </button>
                        <div class="role-nav-group-items">
                            @foreach($group['items'] as $item)
                                <a href="{{ $item['url'] }}" class="role-nav-link {{ $roleItemIsActive($item) ? 'active' : '' }}">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </nav>
    @endif

    <div class="main-panel flex-grow-1">
        @if($showSidebar)
        <header class="topbar admin-header-stacked">
                <div class="admin-info-row px-4 py-3 d-flex justify-content-between align-items-center">
                <div class="admin-topbar-left d-flex align-items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="admin-school-heading" aria-label="{{ $schoolTitle }}">
                        @if($schoolLogoUrl)
                            <img src="{{ $schoolLogoUrl }}" alt="{{ $schoolTitle }}" class="admin-school-heading-logo object-fit-cover">
                        @else
                            <span class="admin-school-heading-logo admin-school-heading-logo-text">{{ $schoolShortName }}</span>
                        @endif
                        <span>{{ $schoolTitle }}</span>
                    </a>
                    <form class="topbar-search function-search admin-topbar-search" role="search" onsubmit="return false;" data-function-search>
                        <i class="bi bi-search"></i>
                        <input type="search" placeholder="Tìm kiếm..." aria-label="Tìm kiếm chức năng" autocomplete="off">
                    </form>
                </div>

                <div class="topbar-actions d-flex align-items-center gap-3">
                    @if($historySchoolYear)
                        <span class="badge text-bg-warning admin-history-context-badge">
                            Đang xem dữ liệu năm học {{ $historySchoolYear->name }}
                        </span>
                    @endif
                    <form method="POST" action="{{ route('academic-context.update') }}" class="admin-period-meta admin-period-form" aria-label="Năm học và học kỳ đang làm việc">
                        @csrf
                        <label class="admin-period-field">
                            <span class="visually-hidden">Năm học đang làm việc</span>
                            <select name="school_year_id" class="admin-period-select" onchange="this.form.submit()" @disabled($headerSchoolYears->isEmpty())>
                                @forelse($headerSchoolYears as $year)
                                    <option value="{{ $year->id }}" @selected((string) $headerSchoolYear?->id === (string) $year->id)>{{ $year->name }}</option>
                                @empty
                                    <option value="">Chưa thiết lập</option>
                                @endforelse
                            </select>
                        </label>
                        <label class="admin-period-field">
                            <span class="visually-hidden">Học kỳ hiện hành</span>
                            <select name="semester_id" class="admin-period-select" onchange="this.form.submit()" @disabled($headerSemesters->isEmpty())>
                                @forelse($headerSemesters as $semester)
                                    <option value="{{ $semester->id }}" @selected((string) $headerSemester?->id === (string) $semester->id)>{{ $semester->normalizedName() }}</option>
                                @empty
                                    <option value="">Chưa thiết lập</option>
                                @endforelse
                            </select>
                        </label>
                    </form>
                    <div class="dropdown">
                        <button class="btn btn-link text-dark text-decoration-none dropdown-toggle admin-user-dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <span class="admin-user-dropdown-text">
                                <span class="admin-user-dropdown-name">{{ $currentUser->display_name }}</span>
                                <span class="admin-user-dropdown-role">Quản trị viên</span>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person-circle me-2"></i>Thông tin cá nhân</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.change-password') }}"><i class="bi bi-key me-2"></i>Đổi mật khẩu</a></li>
                            @if($currentUser->hasPermission('manage_admin_accounts'))
                                <li><a class="dropdown-item" href="{{ route('admin-users.index') }}"><i class="bi bi-person-gear me-2"></i>Quản lý Admin</a></li>
                            @endif
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
            </div>

            <div class="admin-primary-nav-row">
                <nav class="admin-top-nav" aria-label="Phân hệ quản trị">
                    @foreach($adminMenuGroups as $group)
                        @php
                            $groupUrl = $group['key'] === 'academic' ? route('academic.index') : $group['url'];
                            $groupLabel = match ($group['key']) {
                                'overview' => 'Tổng quan',
                                'academic' => 'Học vụ',
                                'users' => 'Người dùng',
                                'content' => 'Nội dung',
                                'communication' => 'Giao tiếp',
                                'system' => 'Hệ thống',
                                'reports' => 'Báo cáo',
                                default => $group['title'],
                            };
                        @endphp
                        <a href="{{ $groupUrl }}" class="admin-top-nav-link {{ $activeAdminGroup && $activeAdminGroup['key'] === $group['key'] ? 'active' : '' }}">
                            <i class="bi {{ $group['icon'] }}"></i>
                            <span>{{ $groupLabel }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>
        @else
        <header class="topbar px-4 py-3 d-flex justify-content-between align-items-center">
            @if($showRoleMenu)
                <div class="role-topbar-left">
                    <div class="school-heading">{{ $schoolTitle }}</div>
                    <form class="topbar-search function-search" role="search" onsubmit="return false;" data-function-search>
                        <i class="bi bi-search"></i>
                        <input type="search" placeholder="Tìm kiếm chức năng..." aria-label="Tìm kiếm">
                    </form>
                </div>
            @else
                <div class="admin-topbar-left d-flex align-items-center gap-2">
                    @unless($showSidebar)
                        <div class="page-title fs-6">@yield('title', $title ?? 'Bảng điều khiển')</div>
                    @endunless
                </div>
            @endif

            <div class="topbar-actions d-flex align-items-center gap-3">
                @if($currentUser->isParent() && $currentUser->parentProfile)
                    @php
                        $headerParentChildren = $currentUser->parentProfile->students()
                            ->with('classRoom')
                            ->orderBy('student_code')
                            ->get();
                        $headerSelectedChild = $headerParentChildren->firstWhere('id', session('selected_parent_student_id'))
                            ?: $headerParentChildren->first();
                    @endphp
                    <form method="POST" action="{{ route('parent.select-child') }}" class="parent-header-child-select d-flex align-items-center gap-2">
                        @csrf
                        <label class="small text-muted fw-semibold mb-0">Chọn học sinh</label>
                        <select name="student_id" class="form-select form-select-sm" onchange="this.form.submit()" @disabled($headerParentChildren->isEmpty())>
                            @forelse($headerParentChildren as $child)
                                <option value="{{ $child->id }}" @selected(($headerSelectedChild?->id ?? null) === $child->id)>
                                    {{ $child->student_code }} - {{ $child->name }}
                                </option>
                            @empty
                                <option value="">Chưa có học sinh</option>
                            @endforelse
                        </select>
                    </form>
                @endif
                <span class="badge badge-role">
                    @if($currentUser->isTeacher())
                        {{ $currentUser->isHomeroom() ? 'Giáo viên bộ môn kiêm GVCN' : 'Giáo viên bộ môn' }}
                    @elseif($currentUser->isStudent())
                        Học sinh
                    @elseif($currentUser->isParent())
                        Phụ huynh
                    @elseif($currentUser->isAdmin())
                        Quản trị viên
                    @else
                        Nhân sự
                    @endif
                </span>
                <div class="dropdown">
                    <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-2"></i><span class="fw-semibold">{{ $currentUser->display_name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person-circle me-2"></i>Thông tin cá nhân</a></li>
                        <li><a class="dropdown-item" href="{{ route('profile.change-password') }}"><i class="bi bi-key me-2"></i>Đổi mật khẩu</a></li>
                        @if($currentUser->hasPermission('manage_admin_accounts'))
                            <li><a class="dropdown-item" href="{{ route('admin-users.index') }}"><i class="bi bi-person-gear me-2"></i>Quản lý Admin</a></li>
                        @endif
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
        @endif
        <main class="content">
            @if($showSidebar && $historySchoolYear)
                <div class="history-readonly-banner" role="status">
                    <div class="history-readonly-banner-icon">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <div class="history-readonly-banner-content">
                        <div class="history-readonly-banner-title">Đang xem dữ liệu năm học <strong>{{ $historySchoolYear->name }}</strong></div>
                        <div class="history-readonly-banner-subtitle">Chế độ chỉ xem: chỉ được xem, tìm kiếm, lọc, xuất dữ liệu hoặc in.</div>
                    </div>
                    <a href="{{ route('school-years.history.clear') }}" class="btn btn-primary history-readonly-back">
                        ← Quay về năm học hiện hành
                    </a>
                </div>
            @endif

            @if(request()->routeIs('messages.*'))
                <x-page-header
                    title="Hộp thư điện tử"
                    subtitle="Kênh tương tác, trao đổi thông tin chính thống giữa Nhà trường, Giáo viên và Phụ huynh học sinh."
                >
                    @if(request()->routeIs('messages.create'))
                        <a class="btn btn-outline-primary" href="{{ route('messages.inbox') }}">
                            <i class="bi bi-arrow-left me-1"></i>Quay lại hộp thư
                        </a>
                    @else
                        <a class="btn btn-primary" href="{{ route('messages.create') }}">
                            <i class="bi bi-pencil-square me-1"></i>Soạn tin nhắn mới
                        </a>
                    @endif
                </x-page-header>
            @endif

            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
@if($showFloatingChatbot)
<div class="floating-chatbot {{ session('chatbot_open') || old('chatbot_widget') ? 'open' : '' }}" data-floating-chatbot>
    <button type="button" class="floating-chatbot-toggle" data-floating-chatbot-toggle aria-label="Mở chatbot hỗ trợ" aria-expanded="{{ session('chatbot_open') || old('chatbot_widget') ? 'true' : 'false' }}">
        <i class="bi bi-robot"></i>
    </button>
    <section class="floating-chatbot-panel" aria-label="Chatbot hỗ trợ">
        <div class="floating-chatbot-header">
            <div>
                <div class="floating-chatbot-title">Chatbot hỗ trợ</div>
                <div class="floating-chatbot-subtitle">Hỏi nhanh về thông tin trong hệ thống</div>
            </div>
            <button type="button" class="floating-chatbot-close" data-floating-chatbot-close aria-label="Đóng chatbot">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="floating-chatbot-messages" data-floating-chatbot-messages>
            @if(! \Illuminate\Support\Facades\Schema::hasTable('chatbot_messages'))
                <div class="floating-chatbot-empty">
                    <i class="bi bi-info-circle"></i>
                    Chưa sẵn sàng dữ liệu chatbot.
                </div>
            @else
                @forelse($floatingChatMessages as $message)
                    <div class="chat-row chat-question">
                        <div class="chat-bubble">{{ $message->question }}</div>
                    </div>
                    <div class="chat-row chat-answer">
                        <div class="chat-bubble">{{ $message->answer }}</div>
                    </div>
                @empty
                    <div class="floating-chatbot-empty">
                        <i class="bi bi-robot"></i>
                        Nhập câu hỏi để bắt đầu trao đổi.
                    </div>
                @endforelse
            @endif
        </div>
        <form method="POST" action="{{ route('chatbot.ask') }}" class="floating-chatbot-form">
            @csrf
            <input type="hidden" name="chatbot_widget" value="1">
            <input name="question" class="form-control" placeholder="Nhập câu hỏi..." required maxlength="1000" autocomplete="off">
            <button class="btn btn-primary" aria-label="Gửi câu hỏi">
                <i class="bi bi-send"></i>
            </button>
        </form>
    </section>
</div>
@endif
<div class="modal fade content-modal academic-crud-modal" id="academicCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="modal-kicker" data-academic-crud-kicker>Biểu mẫu học vụ</div>
                    <h5 class="modal-title" data-academic-crud-title>Thông tin dữ liệu</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body" data-academic-crud-body>
                <div class="academic-interaction-loading">
                    <span class="spinner-border text-primary" aria-hidden="true"></span>
                    <span>Đang tải biểu mẫu...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade content-modal academic-detail-modal" id="academicDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="modal-kicker">Chi tiết học vụ</div>
                    <h5 class="modal-title" data-academic-detail-title>Thông tin chi tiết</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body" data-academic-detail-body>
                <div class="academic-interaction-loading">
                    <span class="spinner-border text-primary" aria-hidden="true"></span>
                    <span>Đang tải dữ liệu...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng hồ sơ</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade content-modal" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="modal-kicker">Xác nhận xóa</div>
                    <h5 class="modal-title">Bạn có chắc chắn muốn xóa dữ liệu này?</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-muted">Hành động này không thể hoàn tác!</p>
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
<script type="application/json" id="function-search-data">@json($functionSearchItems)</script>
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
        const collapseKey = 'school-manager:admin-sidebar-collapsed';
        const collapseButton = document.querySelector('[data-admin-sidebar-collapse]');
        const collapseIcon = collapseButton?.querySelector('i');

        const setAdminSidebarCollapsed = (collapsed) => {
            document.body.classList.toggle('admin-sidebar-collapsed', collapsed);
            collapseButton?.setAttribute('aria-label', collapsed ? 'Mở rộng menu chức năng' : 'Thu gọn menu chức năng');

            if (collapseIcon) {
                collapseIcon.className = collapsed ? 'bi bi-layout-sidebar-inset-reverse' : 'bi bi-layout-sidebar-inset';
            }
        };

        setAdminSidebarCollapsed(localStorage.getItem(collapseKey) === '1');

        collapseButton?.addEventListener('click', () => {
            const collapsed = !document.body.classList.contains('admin-sidebar-collapsed');
            localStorage.setItem(collapseKey, collapsed ? '1' : '0');
            setAdminSidebarCollapsed(collapsed);
        });
    })();

    (() => {
        const sections = [...document.querySelectorAll('[data-admin-submenu-section]')];

        if (!sections.length) {
            return;
        }

        const setSectionOpen = (section, isOpen) => {
            section.classList.toggle('is-collapsed', !isOpen);
            section.classList.toggle('is-open', isOpen);
            section.querySelector('[data-admin-submenu-toggle]')?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };

        sections.forEach((section) => {
            section.querySelector('[data-admin-submenu-toggle]')?.addEventListener('click', () => {
                setSectionOpen(section, section.classList.contains('is-collapsed'));
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
        document.querySelectorAll('.admin-top-nav-link, .admin-group-link, .admin-section-tab, .admin-nav-link').forEach((link) => {
            link.addEventListener('click', saveScroll, { capture: true });
        });
        scrollTargets.forEach(([, element]) => {
            element.addEventListener('scroll', saveScroll, { passive: true });
        });
    })();

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => document.body.classList.add('sidebar-open'));
    });
    document.querySelectorAll('[data-sidebar-close], .admin-top-nav-link, .admin-group-link, .admin-section-tab, .admin-nav-link').forEach((element) => {
        element.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
    });

    document.querySelectorAll('[data-role-menu-toggle]').forEach((button) => {
        button.addEventListener('click', () => document.body.classList.add('role-menu-open'));
    });
    document.querySelectorAll('[data-role-menu-close], .role-sidebar a').forEach((element) => {
        element.addEventListener('click', () => document.body.classList.remove('role-menu-open'));
    });

    (() => {
        if (!window.bootstrap?.Dropdown) {
            return;
        }

        document.querySelectorAll('[data-academic-dropdown-toggle]').forEach((toggle) => {
            window.bootstrap.Dropdown.getOrCreateInstance(toggle, {
                boundary: 'viewport',
                popperConfig(defaultConfig) {
                    return {
                        ...defaultConfig,
                        strategy: 'fixed',
                    };
                },
            });
        });
    })();

    (() => {
        const groups = [...document.querySelectorAll('[data-role-nav-group]')];

        if (!groups.length) {
            return;
        }

        const setGroupOpen = (group, isOpen) => {
            group.classList.toggle('is-collapsed', !isOpen);
            group.classList.toggle('is-open', isOpen);
            group.querySelector('[data-role-group-toggle]')?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };

        groups.forEach((group) => {
            const toggle = group.querySelector('[data-role-group-toggle]');

            toggle?.addEventListener('click', () => {
                const shouldOpen = group.classList.contains('is-collapsed');

                groups.forEach((otherGroup) => setGroupOpen(otherGroup, otherGroup === group ? shouldOpen : false));
            });
        });
    })();

    (() => {
        const chatbot = document.querySelector('[data-floating-chatbot]');

        if (!chatbot) {
            return;
        }

        const toggle = chatbot.querySelector('[data-floating-chatbot-toggle]');
        const closeButton = chatbot.querySelector('[data-floating-chatbot-close]');
        const messages = chatbot.querySelector('[data-floating-chatbot-messages]');

        const setOpen = (isOpen) => {
            chatbot.classList.toggle('open', isOpen);
            toggle?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            if (isOpen && messages) {
                window.requestAnimationFrame(() => {
                    messages.scrollTop = messages.scrollHeight;
                });
            }
        };

        toggle?.addEventListener('click', () => setOpen(!chatbot.classList.contains('open')));
        closeButton?.addEventListener('click', () => setOpen(false));

        if (chatbot.classList.contains('open') && messages) {
            messages.scrollTop = messages.scrollHeight;
        }
    })();

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
        const dataElement = document.getElementById('function-search-data');
        const searchForms = document.querySelectorAll('[data-function-search]');

        if (!dataElement || !searchForms.length) {
            return;
        }

        let items = [];
        try {
            items = JSON.parse(dataElement.textContent || '[]');
        } catch (error) {
            items = [];
        }

        if (!items.length) {
            searchForms.forEach((form) => form.hidden = true);
            return;
        }

        const normalizeText = (value) => (value || '')
            .toString()
            .toLocaleLowerCase('vi')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();

        const escapeHtml = (value) => (value || '')
            .toString()
            .replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));

        const searchableItems = items.map((item) => ({
            ...item,
            searchText: normalizeText(`${item.label || ''} ${item.group || ''} ${item.keywords || ''}`),
            labelText: normalizeText(item.label || ''),
        }));

        const closeAllPanels = (except = null) => {
            searchForms.forEach((form) => {
                if (except && form === except) {
                    return;
                }

                form.querySelector('[data-function-search-panel]')?.classList.remove('show');
                form.removeAttribute('data-active-index');
            });
        };

        const findMatches = (keyword) => {
            const normalizedKeyword = normalizeText(keyword);
            const source = normalizedKeyword
                ? searchableItems.filter((item) => item.searchText.includes(normalizedKeyword))
                : searchableItems;

            return [...source]
                .sort((left, right) => {
                    if (!normalizedKeyword) {
                        return String(left.label || '').localeCompare(String(right.label || ''), 'vi');
                    }

                    const leftStarts = left.labelText.startsWith(normalizedKeyword) ? 0 : 1;
                    const rightStarts = right.labelText.startsWith(normalizedKeyword) ? 0 : 1;

                    if (leftStarts !== rightStarts) {
                        return leftStarts - rightStarts;
                    }

                    return left.labelText.indexOf(normalizedKeyword) - right.labelText.indexOf(normalizedKeyword);
                })
                .slice(0, 8);
        };

        const ensurePanel = (form) => {
            let panel = form.querySelector('[data-function-search-panel]');
            if (panel) {
                return panel;
            }

            panel = document.createElement('div');
            panel.className = 'function-search-panel';
            panel.dataset.functionSearchPanel = '';
            form.appendChild(panel);

            return panel;
        };

        const updateActiveResult = (form) => {
            const activeIndex = Number.parseInt(form.dataset.activeIndex || '0', 10);
            form.querySelectorAll('[data-function-search-result]').forEach((button, index) => {
                button.classList.toggle('active', index === activeIndex);
            });
        };

        const renderPanel = (form) => {
            const input = form.querySelector('input[type="search"]');
            const panel = ensurePanel(form);
            const matches = findMatches(input.value);

            if (!matches.length) {
                panel.innerHTML = '<div class="function-search-empty"><i class="bi bi-search"></i>Không tìm thấy chức năng phù hợp.</div>';
                panel.classList.add('show');
                form.removeAttribute('data-active-index');
                return;
            }

            panel.innerHTML = matches.map((item, index) => `
                <button type="button" class="function-search-item" data-function-search-result data-index="${index}" data-url="${escapeHtml(item.url)}">
                    <span class="function-search-icon"><i class="bi ${escapeHtml(item.icon || 'bi-grid')}"></i></span>
                    <span class="function-search-text">
                        <strong>${escapeHtml(item.label)}</strong>
                        <small>${escapeHtml(item.group)}</small>
                    </span>
                </button>
            `).join('');

            form.dataset.activeIndex = '0';
            panel.classList.add('show');
            updateActiveResult(form);
        };

        const navigateActive = (form) => {
            const activeIndex = Number.parseInt(form.dataset.activeIndex || '0', 10);
            const target = form.querySelector(`[data-function-search-result][data-index="${activeIndex}"]`)
                || form.querySelector('[data-function-search-result]');

            if (target?.dataset.url) {
                window.location.href = target.dataset.url;
            }
        };

        searchForms.forEach((form) => {
            const input = form.querySelector('input[type="search"]');
            const panel = ensurePanel(form);

            if (!input) {
                return;
            }

            input.setAttribute('autocomplete', 'off');
            input.setAttribute('aria-label', 'Tìm kiếm chức năng');
            input.setAttribute('placeholder', 'Tìm kiếm chức năng...');

            input.addEventListener('focus', () => {
                closeAllPanels(form);
                renderPanel(form);
            });

            input.addEventListener('input', () => {
                closeAllPanels(form);
                renderPanel(form);
            });

            input.addEventListener('keydown', (event) => {
                const results = [...form.querySelectorAll('[data-function-search-result]')];

                if (event.key === 'Escape') {
                    panel.classList.remove('show');
                    return;
                }

                if (!results.length) {
                    return;
                }

                let activeIndex = Number.parseInt(form.dataset.activeIndex || '0', 10);

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    activeIndex = Math.min(activeIndex + 1, results.length - 1);
                    form.dataset.activeIndex = String(activeIndex);
                    updateActiveResult(form);
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    activeIndex = Math.max(activeIndex - 1, 0);
                    form.dataset.activeIndex = String(activeIndex);
                    updateActiveResult(form);
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    navigateActive(form);
                }
            });

            panel.addEventListener('mousedown', (event) => {
                const result = event.target.closest('[data-function-search-result]');
                if (!result) {
                    return;
                }

                event.preventDefault();
                window.location.href = result.dataset.url;
            });
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element) || !event.target.closest('[data-function-search]')) {
                closeAllPanels();
            }
        });
    })();

    @if($showSidebar && (! $activeAdminGroup || $activeAdminGroup['key'] !== 'overview'))
    (() => {
        const normalizeText = (value) => (value || '')
            .toString()
            .toLocaleLowerCase('vi')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();

        const isEmptyDataRow = (row, columnCount) => {
            if (!row) {
                return false;
            }

            return Boolean(row.querySelector('.empty-state'))
                || (row.cells.length === 1 && row.cells[0].colSpan >= columnCount);
        };

        const parseSortableValue = (value) => {
            const raw = (value || '').replace(/\s+/g, ' ').trim();
            const dateMatch = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);

            if (dateMatch) {
                return {
                    type: 'date',
                    value: new Date(Number(dateMatch[3]), Number(dateMatch[2]) - 1, Number(dateMatch[1])).getTime(),
                };
            }

            const numericValue = raw.replace(/\./g, '').replace(',', '.');

            if (/^-?\d+(\.\d+)?$/.test(numericValue)) {
                return {
                    type: 'number',
                    value: Number(numericValue),
                };
            }

            return {
                type: 'text',
                value: normalizeText(raw),
            };
        };

        const compareValues = (left, right, direction) => {
            const a = parseSortableValue(left);
            const b = parseSortableValue(right);
            let result = 0;

            if (a.type === b.type && a.type !== 'text') {
                result = a.value - b.value;
            } else {
                result = String(a.value).localeCompare(String(b.value), 'vi', { numeric: true, sensitivity: 'base' });
            }

            return direction === 'asc' ? result : -result;
        };

        const getCellText = (row, indexes) => indexes
            .map((index) => row.cells[index]?.innerText || '')
            .join(' ');

        const debounce = (callback, delay = 300) => {
            let timeoutId;

            return (...args) => {
                window.clearTimeout(timeoutId);
                timeoutId = window.setTimeout(() => callback(...args), delay);
            };
        };

        const findPreviousPageHeading = (element) => {
            let current = element;

            while (current && current !== document.body) {
                let previous = current.previousElementSibling;

                while (previous) {
                    if (previous.classList?.contains('page-heading')) {
                        return previous;
                    }

                    previous = previous.previousElementSibling;
                }

                current = current.parentElement;
            }

            return null;
        };

        const isFilterAction = (element) => {
            const text = normalizeText(element.innerText || element.getAttribute('title') || element.getAttribute('aria-label') || '');

            return text.includes('loc')
                || text.includes('bo loc')
                || Boolean(element.querySelector('.bi-funnel'));
        };

        const isAddAction = (element) => {
            const text = normalizeText(element.innerText || element.getAttribute('title') || element.getAttribute('aria-label') || '');

            return text.includes('them')
                || Boolean(element.querySelector('.bi-plus, .bi-plus-lg'));
        };

        const movePageActionsToToolbar = (toolbar, insertionTarget) => {
            const pageHeading = findPreviousPageHeading(insertionTarget);

            if (!pageHeading || pageHeading.dataset.adminToolbarMoved === 'true') {
                return;
            }

            const actionContainer = Array.from(pageHeading.children)
                .slice(1)
                .find((child) => child.matches?.('a, button, form, .dropdown, .btn') || child.querySelector?.('a, button, form, .dropdown'));

            if (!actionContainer) {
                return;
            }

            const actions = actionContainer.matches?.('a, button, form, .dropdown, .btn')
                ? [actionContainer]
                : Array.from(actionContainer.children)
                    .filter((child) => child.matches?.('a, button, form, .dropdown, .btn') || child.querySelector?.('a, button, form, .dropdown'));

            if (!actions.length) {
                return;
            }

            const filterSlot = toolbar.querySelector('[data-admin-toolbar-filters]');
            const actionSlot = toolbar.querySelector('[data-admin-toolbar-actions]');
            const filterActions = actions.filter(isFilterAction);
            const addActions = actions.filter((action) => !isFilterAction(action) && isAddAction(action));
            const otherActions = actions.filter((action) => !filterActions.includes(action) && !addActions.includes(action));

            filterActions.forEach((action) => {
                filterSlot.appendChild(action);
            });

            [...addActions, ...otherActions].forEach((action) => {
                actionSlot.appendChild(action);
            });

            if (actionContainer.parentElement === pageHeading || !actionContainer.children.length) {
                actionContainer.remove();
            }

            pageHeading.dataset.adminToolbarMoved = 'true';
        };

        const adminToolbarMarkup = () => `
            <div class="admin-table-tools-left">
                <div class="admin-table-search">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" placeholder="Tìm kiếm..." aria-label="Tìm kiếm dữ liệu">
                </div>
                <div class="admin-table-filters" data-admin-toolbar-filters></div>
            </div>
            <div class="admin-table-actions" data-admin-toolbar-actions></div>
        `;

        const createAdminToolbar = (parent, insertionTarget) => {
            if (!parent) {
                return null;
            }

            const existingToolbar = parent.querySelector(':scope > .admin-table-tools');
            if (existingToolbar) {
                return existingToolbar;
            }

            const toolbar = document.createElement('div');
            toolbar.className = 'admin-table-tools';
            toolbar.innerHTML = `
                <div class="admin-table-search">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" placeholder="Tìm kiếm..." aria-label="Tìm kiếm dữ liệu">
                </div>
                <div class="admin-table-actions" data-admin-toolbar-actions></div>
            `;
            toolbar.innerHTML = adminToolbarMarkup();
            parent.insertBefore(toolbar, insertionTarget);
            movePageActionsToToolbar(toolbar, insertionTarget);

            return toolbar;
        };

        const isActionHeader = (header) => {
            const label = normalizeText(header.innerText);

            return !label
                || label === 'thao tac'
                || header.classList.contains('text-end')
                || header.querySelector('button, a, input, select, textarea');
        };

        const normalizeActionHeaders = () => {
            document.querySelectorAll('.content table.table thead th').forEach((header) => {
                const label = normalizeText(header.innerText);
                if (label === 'thao tac') {
                    header.textContent = '';
                    header.setAttribute('aria-label', 'Thao tác');
                    header.classList.add('text-end', 'action-column-header');
                }
            });
        };

        const isDeleteForm = (form) => {
            const method = (form.querySelector('input[name="_method"]')?.value || form.method || '').toUpperCase();
            return method === 'DELETE';
        };

        const makeMenuItem = (element, type = 'default') => {
            element.classList.remove('content-action-btn', 'icon-only', 'detail', 'edit', 'delete', 'more', 'dropdown-toggle-clean', 'btn', 'btn-sm', 'btn-outline-secondary', 'btn-outline-danger');
            element.classList.add('dropdown-item');
            if (type === 'danger') {
                element.classList.add('danger');
                element.classList.remove('text-danger');
            }

            return element;
        };

        const ensureActionDropdown = (group) => {
            let dropdown = group.querySelector(':scope > .dropdown');
            if (!dropdown) {
                dropdown = document.createElement('div');
                dropdown.className = 'dropdown';
                dropdown.innerHTML = `
                    <button type="button" class="content-action-btn icon-only more" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" title="Thao tác khác" aria-label="Thao tác khác">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end content-action-menu"></div>
                `;
                group.appendChild(dropdown);
            }

            const toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
            toggle?.classList.add('content-action-btn', 'icon-only', 'more');
            toggle?.classList.remove('detail', 'edit', 'delete');
            if (toggle && !toggle.querySelector('.bi-three-dots-vertical')) {
                toggle.innerHTML = '<i class="bi bi-three-dots-vertical"></i>';
            }
            toggle?.setAttribute('data-bs-boundary', 'viewport');

            let menu = dropdown.querySelector('.dropdown-menu');
            if (!menu) {
                menu = document.createElement('div');
                menu.className = 'dropdown-menu dropdown-menu-end';
                dropdown.appendChild(menu);
            }
            if (menu.tagName === 'UL') {
                const replacement = document.createElement('div');
                replacement.className = menu.className;
                while (menu.firstChild) {
                    replacement.appendChild(menu.firstChild);
                }
                menu.replaceWith(replacement);
                menu = replacement;
            }
            menu.classList.add('content-action-menu');

            return { dropdown, menu };
        };

        const normalizeActionGroups = () => {
            document.querySelectorAll('.content-action-group').forEach((group) => {
                if (group.dataset.actionSynced === 'true') {
                    return;
                }

                group.classList.add('justify-content-end');

                const edit = group.querySelector(':scope > .content-action-btn.edit, :scope > a.content-action-btn[href*="/edit"], :scope > button.content-action-btn[data-bs-target*="Edit"], :scope > button.content-action-btn[data-bs-target*="edit"]');
                if (edit) {
                    edit.classList.add('content-action-btn', 'icon-only', 'edit');
                    edit.classList.remove('more', 'delete', 'detail');
                }

                const { menu } = ensureActionDropdown(group);

                group.querySelectorAll(':scope > .content-action-btn.detail, :scope > button.content-action-btn[data-bs-target*="Detail"], :scope > button.content-action-btn[data-bs-target*="detail"], :scope > a.content-action-btn[href*="/detail"], :scope > a.content-action-btn[href*="/show"]').forEach((detail) => {
                    if (detail.closest('.dropdown')) {
                        return;
                    }

                    makeMenuItem(detail);
                    const text = normalizeText(detail.innerText);
                    if (!text || !text.includes('xem')) {
                        detail.innerHTML = '<i class="bi bi-eye"></i>Xem chi tiết';
                    }
                    menu.prepend(detail);
                });

                group.querySelectorAll(':scope > form').forEach((form) => {
                    if (!isDeleteForm(form) || form.closest('.dropdown')) {
                        return;
                    }

                    const button = form.querySelector('button');
                    if (button) {
                        makeMenuItem(button, 'danger');
                        button.innerHTML = '<i class="bi bi-trash"></i>Xóa bỏ';
                        if (!form.getAttribute('onsubmit')) {
                            form.setAttribute('onsubmit', "return confirm('Bạn có chắc chắn muốn xóa dữ liệu này? Hành động này không thể hoàn tác!')");
                        }
                    }
                    menu.appendChild(form);
                });

                menu.querySelectorAll('.dropdown-item').forEach((item) => {
                    const text = normalizeText(item.innerText);
                    if (text === 'xoa' || text === 'xoa bo' || item.classList.contains('text-danger')) {
                        item.classList.add('danger');
                        item.classList.remove('text-danger');
                        if (text === 'xoa') {
                            item.innerHTML = '<i class="bi bi-trash"></i>Xóa bỏ';
                        }
                    }
                    if (text.includes('xem chi tiet')) {
                        item.innerHTML = '<i class="bi bi-eye"></i>Xem chi tiết';
                    }
                });

                const hasDetailItem = [...menu.querySelectorAll('.dropdown-item')]
                    .some((item) => normalizeText(item.innerText).includes('xem chi tiet'));

                if (!hasDetailItem) {
                    const firstCell = group.closest('tr')?.cells?.[0];
                    const summary = firstCell?.innerText?.trim() || 'Dữ liệu';
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'dropdown-item';
                    item.innerHTML = '<i class="bi bi-eye"></i>Xem chi tiết';
                    item.addEventListener('click', () => {
                        const modal = document.getElementById('academicDetailModal');
                        const title = modal?.querySelector('[data-academic-detail-title]');
                        const body = modal?.querySelector('[data-academic-detail-body]');
                        if (!modal || !body || !window.bootstrap) {
                            return;
                        }
                        if (title) {
                            title.textContent = 'Thông tin chi tiết';
                        }
                        body.innerHTML = `<div class="content-detail-list"><div><dt>Dữ liệu</dt><dd>${summary.replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]))}</dd></div></div>`;
                        bootstrap.Modal.getOrCreateInstance(modal).show();
                    });
                    menu.prepend(item);
                }

                group.dataset.actionSynced = 'true';
            });
        };

        const openCenteredDetailModal = async (url) => {
            const modalElement = document.getElementById('academicDetailModal');
            const title = modalElement?.querySelector('[data-academic-detail-title]');
            const body = modalElement?.querySelector('[data-academic-detail-body]');

            if (!modalElement || !body || !window.bootstrap) {
                window.location.href = url;
                return;
            }

            if (title) {
                title.textContent = 'Thông tin chi tiết';
            }
            body.innerHTML = `
                <div class="academic-interaction-loading">
                    <span class="spinner-border text-primary" aria-hidden="true"></span>
                    <span>Đang tải dữ liệu...</span>
                </div>
            `;
            bootstrap.Modal.getOrCreateInstance(modalElement).show();

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Không thể tải dữ liệu.');
                }

                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const loadedTitle = doc.querySelector('.page-header h1, .page-heading h5, h1, h5, title')?.textContent?.trim();
                const content = doc.querySelector('.content') || doc.querySelector('main') || doc.body;
                const clone = content.cloneNode(true);
                clone.querySelectorAll('script, .page-header, .page-heading, .form-actions, .admin-table-tools').forEach((element) => element.remove());

                if (title) {
                    title.textContent = loadedTitle || 'Thông tin chi tiết';
                }
                body.innerHTML = '';
                body.appendChild(clone);
            } catch (error) {
                body.innerHTML = `<div class="alert alert-danger mb-0">${error.message || 'Không thể tải dữ liệu.'}</div>`;
            }
        };

        const enhanceTable = (table) => {
            if (table.dataset.adminEnhanced === 'true') {
                return;
            }

            if (table.closest('.modal, .timetable-grid, .score-sheet, [data-admin-table-skip]')) {
                return;
            }

            if (table.querySelector('tbody input:not([type="hidden"]), tbody select, tbody textarea')) {
                return;
            }

            const headerRow = table.tHead?.rows?.[0];
            const body = table.tBodies?.[0];

            if (!headerRow || !body) {
                return;
            }

            const headers = Array.from(headerRow.cells);
            const dataRows = Array.from(body.rows).filter((row) => !isEmptyDataRow(row, headers.length));
            const wrapper = table.closest('.table-responsive');
            const insertionTarget = wrapper || table;
            const parent = insertionTarget.parentElement;
            const skipAutoToolbar = table.hasAttribute('data-no-auto-toolbar');

            if (!dataRows.length) {
                if (!skipAutoToolbar) {
                    createAdminToolbar(parent, insertionTarget);
                }
                table.dataset.adminEnhanced = 'true';
                return;
            }

            const searchableIndexes = headers
                .map((header, index) => ({ header, index }))
                .filter(({ header }) => !isActionHeader(header))
                .map(({ index }) => index);

            if (!searchableIndexes.length) {
                table.dataset.adminEnhanced = 'true';
                return;
            }

            let noResultsRow = body.querySelector('[data-admin-no-results]');

            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.className = 'admin-table-empty-row';
                noResultsRow.dataset.adminNoResults = 'true';
                noResultsRow.hidden = true;
                noResultsRow.innerHTML = `<td colspan="${headers.length}"><div class="empty-state"><i class="bi bi-search"></i>Không tìm thấy dữ liệu phù hợp.</div></td>`;
                noResultsRow.innerHTML = `<td colspan="${headers.length}"><div class="empty-state"><i class="bi bi-search"></i>Không tìm thấy dữ liệu phù hợp.</div></td>`;
                body.appendChild(noResultsRow);
            }

            if (!skipAutoToolbar && parent && !parent.querySelector(':scope > .admin-table-tools')) {
                const toolbar = document.createElement('div');
                toolbar.className = 'admin-table-tools';
                toolbar.innerHTML = `
                    <div class="admin-table-search">
                        <i class="bi bi-search"></i>
                    <input type="search" class="form-control" placeholder="Tìm kiếm..." aria-label="Tìm kiếm dữ liệu">
                    </div>
                    <div class="admin-table-actions" data-admin-toolbar-actions></div>
                `;
                toolbar.innerHTML = adminToolbarMarkup();
                parent.insertBefore(toolbar, insertionTarget);
                movePageActionsToToolbar(toolbar, insertionTarget);

                const searchInput = toolbar.querySelector('input[type="search"]');
                const applyTableSearch = () => {
                    const keyword = normalizeText(searchInput.value);
                    let visibleCount = 0;

                    dataRows.forEach((row) => {
                        const matched = !keyword || normalizeText(getCellText(row, searchableIndexes)).includes(keyword);
                        row.hidden = !matched;
                        if (matched) {
                            visibleCount += 1;
                        }
                    });

                    noResultsRow.hidden = visibleCount > 0;
                };

                searchInput.addEventListener('input', debounce(applyTableSearch, 300));
            }

            headers.forEach((header, index) => {
                if (isActionHeader(header)) {
                    return;
                }

                const label = header.innerText.trim();
                header.innerHTML = '';

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'admin-sort-button';
                button.innerHTML = `<span>${label}</span><span class="admin-sort-indicator" aria-hidden="true">↕</span>`;
                header.appendChild(button);

                button.addEventListener('click', () => {
                    const direction = header.dataset.sortDirection === 'asc' ? 'desc' : 'asc';

                    headers.forEach((item) => {
                        item.classList.remove('admin-sort-active');
                        delete item.dataset.sortDirection;
                        const indicator = item.querySelector('.admin-sort-indicator');
                        if (indicator) {
                            indicator.textContent = '↕';
                        }
                    });

                    header.dataset.sortDirection = direction;
                    header.classList.add('admin-sort-active');
                    header.querySelector('.admin-sort-indicator').textContent = direction === 'asc' ? '▲' : '▼';

                    [...dataRows]
                        .sort((left, right) => compareValues(left.cells[index]?.innerText || '', right.cells[index]?.innerText || '', direction))
                        .forEach((row) => body.insertBefore(row, noResultsRow));
                });
            });

            table.dataset.adminEnhanced = 'true';
        };

        normalizeActionHeaders();
        normalizeActionGroups();
        document.querySelectorAll('.content table.table').forEach(enhanceTable);

        document.addEventListener('click', (event) => {
            const link = event.target.closest('.content-action-menu a.dropdown-item[href]');
            if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            const label = normalizeText(link.textContent || link.getAttribute('title') || '');
            if (!label.includes('xem chi tiet')) {
                return;
            }

            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) {
                return;
            }

            event.preventDefault();
            openCenteredDetailModal(url.href);
        });
    })();
    @endif

    (() => {
        const academicRouteFragments = [
            '/school-years',
            '/semesters',
            '/subjects',
            '/departments',
            '/rooms',
            '/classes',
            '/assignments',
            '/exam-schedules',
            '/timetable',
            '/scores',
            '/conduct',
            '/attendance'
        ];

        const crudModalElement = document.getElementById('academicCrudModal');
        const crudModalTitle = crudModalElement?.querySelector('[data-academic-crud-title]');
        const crudModalKicker = crudModalElement?.querySelector('[data-academic-crud-kicker]');
        const crudModalBody = crudModalElement?.querySelector('[data-academic-crud-body]');
        const crudModal = crudModalElement && window.bootstrap ? new bootstrap.Modal(crudModalElement) : null;
        const detailModalElement = document.getElementById('academicDetailModal');
        const detailModalTitle = detailModalElement?.querySelector('[data-academic-detail-title]');
        const detailModalBody = detailModalElement?.querySelector('[data-academic-detail-body]');
        const detailModal = detailModalElement && window.bootstrap ? new bootstrap.Modal(detailModalElement) : null;

        const normalizeText = (value) => (value || '')
            .toString()
            .toLocaleLowerCase('vi')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();

        const isAcademicUrl = (url) => {
            try {
                const parsed = new URL(url, window.location.href);
                return parsed.origin === window.location.origin
                    && academicRouteFragments.some((fragment) => parsed.pathname.includes(fragment));
            } catch (_) {
                return false;
            }
        };

        const loadingHtml = (label) => `
            <div class="academic-interaction-loading">
                <span class="spinner-border text-primary" aria-hidden="true"></span>
                <span>${label}</span>
            </div>
        `;

        const fetchDocument = async (url) => {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Không thể tải dữ liệu.');
            }

            const html = await response.text();
            return new DOMParser().parseFromString(html, 'text/html');
        };

        const normalizeModalForm = (form) => {
            form.classList.remove('card', 'shadow-sm', 'p-4');
            form.classList.add('academic-modal-form');
            form.querySelectorAll('.form-actions a, .form-actions button[type="button"]').forEach((element) => {
                if (element.matches('a')) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-secondary';
                    button.dataset.bsDismiss = 'modal';
                    button.textContent = 'Hủy';
                    element.replaceWith(button);
                } else {
                    element.setAttribute('data-bs-dismiss', 'modal');
                }
            });

            let footer = form.querySelector('.form-actions');
            if (!footer) {
                footer = [...form.children]
                    .reverse()
                    .find((element) => element.matches?.('div') && element.querySelector?.('button, a.btn'));

                if (footer) {
                    footer.classList.add('form-actions');
                }
            }

            if (footer) {
                footer.querySelectorAll('a.btn').forEach((element) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-secondary';
                    button.dataset.bsDismiss = 'modal';
                    button.textContent = 'Hủy';
                    element.replaceWith(button);
                });

                const submit = footer.querySelector('button[type="submit"], button:not([type])');
                submit?.classList.remove('btn-outline-primary');
                submit?.classList.add('btn-primary');
                if (submit) {
                    submit.textContent = 'Lưu kết quả';
                }
            }
        };

        const findBusinessForm = (doc) => {
            const content = doc.querySelector('.content') || doc.querySelector('main') || doc.body;
            const forms = [...content.querySelectorAll('form')];

            return forms.find((form) => {
                const method = (form.getAttribute('method') || 'GET').toUpperCase();
                const action = form.getAttribute('action') || '';

                return method !== 'GET'
                    && !form.matches('[data-function-search], [data-logout-home]')
                    && !form.closest('.app-header, .admin-account-menu, .floating-chatbot, .modal, .dropdown-menu')
                    && !action.includes('/logout')
                    && !action.includes('/chatbot')
                    && !action.includes('/academic-context');
            }) || null;
        };

        const initSchoolYearFields = (root) => {
            root.querySelectorAll('[data-school-year-form]').forEach((form) => {
                if (form.dataset.modalInitialized === 'true') {
                    return;
                }

                form.dataset.modalInitialized = 'true';
                const startYearInput = form.querySelector('[data-start-year]');
                const endYearInput = form.querySelector('[data-end-year]');
                const startDateInput = form.querySelector('[data-start-date]');
                const endDateInput = form.querySelector('[data-end-date]');
                const activeInput = form.querySelector('input[name="is_active"]');
                const confirmInput = form.querySelector('[data-confirm-activation]');
                const activeYear = form.dataset.activeYear || '';
                let startDateTouched = Boolean(startDateInput?.value);
                let endDateTouched = Boolean(endDateInput?.value);

                const padYear = (value) => String(value).padStart(4, '0');
                const syncYears = () => {
                    const startYear = Number.parseInt(startYearInput?.value || '', 10);
                    if (!Number.isNaN(startYear) && endYearInput && (!endYearInput.value || Number.parseInt(endYearInput.value, 10) !== startYear + 1)) {
                        endYearInput.value = String(startYear + 1);
                    }
                    syncDates();
                };

                const syncDates = () => {
                    const startYear = Number.parseInt(startYearInput?.value || '', 10);
                    const endYear = Number.parseInt(endYearInput?.value || '', 10);

                    if (Number.isNaN(startYear) || Number.isNaN(endYear)) {
                        return;
                    }

                    if (!startDateTouched && startDateInput) {
                        startDateInput.value = `${padYear(startYear)}-08-01`;
                    }

                    if (!endDateTouched && endDateInput) {
                        endDateInput.value = `${padYear(endYear)}-05-31`;
                    }
                };

                startYearInput?.addEventListener('input', syncYears);
                endYearInput?.addEventListener('input', syncDates);
                startDateInput?.addEventListener('input', () => startDateTouched = true);
                endDateInput?.addEventListener('input', () => endDateTouched = true);

                form.addEventListener('submit', (event) => {
                    if (!activeInput?.checked || !activeYear || confirmInput?.value === '1') {
                        return;
                    }

                    const accepted = window.confirm(`Đang có năm học hiện hành. Bạn có muốn chuyển sang năm học mới không?`);
                    if (accepted && confirmInput) {
                        confirmInput.value = '1';
                        return;
                    }

                    event.preventDefault();
                });

                syncDates();
            });
        };

        const initSubjectTypeToggle = (root) => {
            const typeSelect = root.querySelector('#subjectTypeSelect, [name="type"]');
            const periodSection = root.querySelector('#periodNormSection');
            if (!typeSelect || !periodSection) {
                return;
            }

            const toggle = () => {
                const officialValue = '{{ \App\Models\Subject::TYPE_OFFICIAL }}';
                periodSection.classList.toggle('d-none', typeSelect.value !== officialValue);
            };

            typeSelect.addEventListener('change', toggle);
            toggle();
        };

        const initAssignmentFields = (root) => {
            const teacherSelect = root.querySelector('[data-assignment-teacher]');
            const subjectSelect = root.querySelector('[data-assignment-subject-select]');
            const departmentFilter = root.querySelector('[data-assignment-department-filter]');
            const departmentWarning = root.querySelector('[data-assignment-department-warning]');
            const subjectDepartmentsText = root.querySelector('[data-assignment-subject-departments]');

            if (!teacherSelect || !subjectSelect) {
                return;
            }

            const selectedSubjectDepartmentIds = () => {
                const selected = subjectSelect.selectedOptions?.[0];
                return (selected?.dataset?.departments || '').split(',').filter(Boolean);
            };

            const updateSubjectDepartmentText = () => {
                const selected = subjectSelect.selectedOptions?.[0];
                const names = selected?.dataset?.departmentNames || '';
                if (subjectDepartmentsText) {
                    subjectDepartmentsText.textContent = names
                        ? `Tổ phụ trách: ${names}`
                        : 'Môn học này chưa được gán tổ phụ trách.';
                }
            };

            const warnIfTeacherOutsideDepartment = () => {
                const teacher = teacherSelect.selectedOptions?.[0];
                const teacherDepartment = teacher?.dataset?.department || '';
                const subjectDepartments = selectedSubjectDepartmentIds();
                const shouldWarn = subjectDepartments.length > 0
                    && Boolean(teacher?.value)
                    && (!teacherDepartment || !subjectDepartments.includes(teacherDepartment));

                departmentWarning?.classList.toggle('d-none', !shouldWarn);
            };

            const filterTeachers = () => {
                const departmentId = departmentFilter?.value || '';
                [...teacherSelect.options].forEach((option) => {
                    option.hidden = Boolean(departmentId) && option.value && option.dataset.department !== departmentId;
                });

                if (teacherSelect.selectedOptions[0]?.hidden) {
                    teacherSelect.value = '';
                }

                warnIfTeacherOutsideDepartment();
            };

            const syncDepartmentFromSubject = () => {
                updateSubjectDepartmentText();
                const subjectDepartments = selectedSubjectDepartmentIds();

                if (subjectDepartments.length === 1 && departmentFilter) {
                    departmentFilter.value = subjectDepartments[0];
                }

                filterTeachers();
            };

            subjectSelect.addEventListener('change', syncDepartmentFromSubject);
            teacherSelect.addEventListener('change', warnIfTeacherOutsideDepartment);
            departmentFilter?.addEventListener('change', filterTeachers);
            syncDepartmentFromSubject();
        };

        const initMultiSelectPickers = (root) => {
            const normalizeText = (value) => (value || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
            const escapeHtml = (value) => (value || '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));

            root.querySelectorAll('[data-multi-select-picker-select]').forEach((select) => {
                const picker = select.nextElementSibling;
                if (!picker || !picker.matches('[data-multi-select-picker]') || picker.dataset.initialized === 'true') {
                    return;
                }

                picker.dataset.initialized = 'true';
                const tags = picker.querySelector('[data-multi-select-tags]');
                const search = picker.querySelector('[data-multi-select-search]');
                const dropdown = picker.querySelector('[data-multi-select-dropdown]');
                if (!tags || !search || !dropdown) {
                    return;
                }

                const placeholder = picker.dataset.placeholder || 'Chưa chọn dữ liệu';
                const emptyText = picker.dataset.emptyText || 'Không tìm thấy dữ liệu phù hợp.';
                const selectedText = picker.dataset.selectedText || 'Đã chọn';
                const maxVisibleTags = Number.parseInt(picker.dataset.maxVisibleTags || '0', 10);
                const options = [...select.options].map((option) => ({
                    option,
                    value: option.value,
                    label: option.textContent.replace(/\s+/g, ' ').trim(),
                    searchText: normalizeText(option.textContent),
                }));

                const render = () => {
                    const selected = options.filter((item) => item.option.selected);
                    tags.innerHTML = '';
                    dropdown.innerHTML = '';

                    if (!selected.length) {
                        tags.innerHTML = `<span class="parent-student-placeholder">${escapeHtml(placeholder)}</span>`;
                    } else {
                        const visible = maxVisibleTags > 0 ? selected.slice(0, maxVisibleTags) : selected;
                        visible.forEach((item) => {
                            const tag = document.createElement('span');
                            tag.className = 'parent-student-tag';
                            tag.innerHTML = `<span>${escapeHtml(item.label)}</span><button type="button" aria-label="Bỏ chọn">×</button>`;
                            tag.querySelector('button').addEventListener('click', (event) => {
                                event.stopPropagation();
                                item.option.selected = false;
                                render();
                            });
                            tags.appendChild(tag);
                        });
                        if (selected.length > visible.length) {
                            const more = document.createElement('span');
                            more.className = 'parent-student-tag';
                            more.title = selected.slice(visible.length).map((item) => item.label).join('\n');
                            more.textContent = `+${selected.length - visible.length} mục khác`;
                            tags.appendChild(more);
                        }
                    }

                    const keyword = normalizeText(search.value);
                    const matched = options.filter((item) => !keyword || item.searchText.includes(keyword)).slice(0, 80);
                    if (!matched.length) {
                        dropdown.innerHTML = `<div class="parent-student-empty">${escapeHtml(emptyText)}</div>`;
                        return;
                    }

                    matched.forEach((item) => {
                        const row = document.createElement('button');
                        row.type = 'button';
                        row.className = `parent-student-option${item.option.selected ? ' selected' : ''}`;
                        row.innerHTML = `<span>${escapeHtml(item.label)}</span>${item.option.selected ? `<strong>${escapeHtml(selectedText)}</strong>` : ''}`;
                        row.addEventListener('click', () => {
                            item.option.selected = !item.option.selected;
                            search.value = '';
                            picker.classList.add('open');
                            render();
                            search.focus();
                        });
                        dropdown.appendChild(row);
                    });
                };

                picker.addEventListener('click', (event) => {
                    event.stopPropagation();
                    document.querySelectorAll('[data-multi-select-picker].open').forEach((item) => item !== picker && item.classList.remove('open'));
                    picker.classList.add('open');
                    search.focus();
                });
                search.addEventListener('focus', () => picker.classList.add('open'));
                search.addEventListener('input', render);
                render();
            });
        };

        const initInjectedForm = (root) => {
            initSchoolYearFields(root);
            initSubjectTypeToggle(root);
            initAssignmentFields(root);
            initMultiSelectPickers(root);
            root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => new bootstrap.Tooltip(element));
        };

        const inferModalSize = (url, form) => {
            if (form?.dataset?.academicModalSize) {
                return form.dataset.academicModalSize;
            }

            return ['/school-years', '/semesters', '/rooms'].some((fragment) => url.includes(fragment))
                ? 'xl'
                : '2xl';
        };

        const openCrudModal = async (url) => {
            if (!crudModal || !crudModalBody) {
                window.location.href = url;
                return;
            }

            crudModalBody.innerHTML = loadingHtml('Đang tải biểu mẫu...');
            crudModalTitle.textContent = 'Thông tin dữ liệu';
            crudModalKicker.textContent = url.includes('/edit') ? 'Chỉnh sửa dữ liệu' : 'Thêm mới dữ liệu';
            crudModal.show();

            try {
                crudModalElement.classList.remove('academic-modal-xl', 'academic-modal-2xl');
                const doc = await fetchDocument(url);
                const title = doc.querySelector('.page-header h1, .page-heading h5, h1, h5, title')?.textContent?.trim();
                const form = findBusinessForm(doc);

                if (!form) {
                    throw new Error('Không tìm thấy biểu mẫu.');
                }

                const modalSize = inferModalSize(url, form);
                crudModalElement.classList.add(modalSize === 'xl' ? 'academic-modal-xl' : 'academic-modal-2xl');
                normalizeModalForm(form);
                const normalizedTitle = title || (url.includes('/edit') ? 'Chỉnh sửa dữ liệu' : 'Thêm mới dữ liệu');
                crudModalTitle.textContent = url.includes('/edit')
                    ? normalizedTitle.replace(/^Sửa\s+/i, 'Chỉnh sửa ')
                    : normalizedTitle;
                crudModalBody.innerHTML = '';
                crudModalBody.appendChild(form);
                initInjectedForm(crudModalBody);
            } catch (error) {
                crudModalBody.innerHTML = `<div class="alert alert-danger mb-0">${error.message || 'Không thể tải biểu mẫu.'}</div>`;
            }
        };

        const openDetailModal = async (url) => {
            if (!detailModal || !detailModalBody) {
                window.location.href = url;
                return;
            }

            detailModalBody.innerHTML = loadingHtml('Đang tải dữ liệu...');
            detailModalTitle.textContent = 'Thông tin chi tiết';
            detailModal.show();

            try {
                const doc = await fetchDocument(url);
                const title = doc.querySelector('.page-header h1, .page-heading h5, h1, h5, title')?.textContent?.trim();
                const content = doc.querySelector('.content') || doc.querySelector('main') || doc.body;
                const clone = content.cloneNode(true);
                clone.querySelectorAll('script, .page-header, .page-heading, .form-actions').forEach((element) => element.remove());

                detailModalTitle.textContent = title || 'Thông tin chi tiết';
                detailModalBody.innerHTML = '';
                detailModalBody.appendChild(clone);
            } catch (error) {
                detailModalBody.innerHTML = `<div class="alert alert-danger mb-0">${error.message || 'Không thể tải dữ liệu.'}</div>`;
            }
        };

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            const href = link.href;
            if (!isAcademicUrl(href)) {
                return;
            }

            const linkLabel = normalizeText(link.textContent || link.getAttribute('title') || link.getAttribute('aria-label') || '');
            const isCrudFormLink = href.includes('/create') || href.includes('/edit');
            const isDetailLink = href.includes('/detail')
                || href.includes('/show')
                || linkLabel.includes('xem chi tiet');

            if (!isCrudFormLink && !isDetailLink) {
                return;
            }

            event.preventDefault();
            if (isCrudFormLink) {
                openCrudModal(href);
            } else {
                openDetailModal(href);
            }
        });
    })();

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
            const isAcademicContext = form.action.includes('/academic-context');
            const isChatbot = form.action.includes('/chatbot');

            if (isGet || isLogout || isHistoryClear || isAcademicContext || isChatbot) {
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
@stack('scripts')
</body>
</html>
