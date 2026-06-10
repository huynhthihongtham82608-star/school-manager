<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name')) - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            /* Fresh, modern, not-generic: Sage + Slate on warm off-white */
            --sm-brand: #3f7d67;
            --sm-brand-strong: #2f5f4f;
            --sm-brand-soft: rgba(63,125,103,0.14);
            --sm-slate: #64748b;
            --sm-bg: #f7f5ef;
            --sm-surface: #fbfaf6;
            --sm-ink: #0f172a;
            --sm-accent: #1e3a8a;
            --sm-wood: #b07d4f;

            --sm-radius: 16px;
            --sm-radius-sm: 12px;
            --sm-shadow: 0 18px 46px rgba(15, 23, 42, 0.10);
            --sm-shadow-soft: 0 10px 26px rgba(15, 23, 42, 0.06);

            --sm-font-body: "Be Vietnam Pro", "Segoe UI", Arial, sans-serif;
            --sm-font-head: "Sora", "Be Vietnam Pro", "Segoe UI", Arial, sans-serif;

            /* Bootstrap 5.3 variables override */
            --bs-primary: var(--sm-brand);
            --bs-primary-rgb: 63, 125, 103;
            --bs-secondary: var(--sm-slate);
            --bs-secondary-rgb: 100, 116, 139;
            --bs-body-bg: var(--sm-bg);
            --bs-body-color: var(--sm-ink);
            --bs-link-color: var(--sm-accent);
            --bs-link-hover-color: #162f75;
        }

        * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        body {
            font-family: var(--sm-font-body);
            color: var(--sm-ink);
            background:
                radial-gradient(900px 520px at 14% 16%, rgba(63,125,103,0.20), transparent 60%),
                radial-gradient(780px 520px at 86% 18%, rgba(30,58,138,0.14), transparent 55%),
                radial-gradient(680px 520px at 70% 92%, rgba(176,125,79,0.10), transparent 55%),
                linear-gradient(180deg, var(--sm-surface) 0%, var(--sm-bg) 58%);
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: 0.06;
            background-image: radial-gradient(circle at 1px 1px, rgba(15,23,42,0.95) 1px, transparent 0);
            background-size: 22px 22px;
            mix-blend-mode: multiply;
        }

        .sidebar {
            height: 100vh;
            position: sticky;
            top: 0;
            overflow-y: auto;
            background:
                radial-gradient(700px 520px at 30% -10%, rgba(255,255,255,0.18), transparent 60%),
                radial-gradient(640px 520px at 96% 28%, rgba(176,125,79,0.18), transparent 55%),
                linear-gradient(180deg, var(--sm-brand) 0%, var(--sm-brand-strong) 100%);
            color: #fff;
            width: 260px;
            border-right: 1px solid rgba(255,255,255,0.14);
        }
        .brand-mark {
            background: rgba(251, 250, 246, 0.92);
            color: var(--sm-brand-strong);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.16);
        }
        .sidebar a {
            color: rgba(255, 255, 255, 0.92);
            text-decoration: none;
            display: block;
            padding: 0.6rem 1rem;
            border-radius: 12px;
            position: relative;
            transition: transform 160ms ease, background-color 160ms ease, box-shadow 160ms ease;
        }
        .sidebar a.active, .sidebar a:hover { background: rgba(255, 255, 255, 0.14); color: #fff; }
        .sidebar a:hover { transform: translateY(-1px); }
        .sidebar a.active { box-shadow: inset 0 0 0 1px rgba(255,255,255,0.10); }
        .sidebar a.active::before {
            content: "";
            position: absolute;
            left: 10px;
            top: 50%;
            width: 6px;
            height: 22px;
            transform: translateY(-50%);
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(255,255,255,0.35));
            opacity: 0.85;
        }
        .sidebar a i { opacity: 0.95; transition: transform 160ms ease, opacity 160ms ease; }
        .sidebar a:hover i { transform: translateY(-1px); opacity: 1; }
        .sidebar .menu-title { letter-spacing: .12em; }

        .content { padding: 1.5rem; }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 1020;
            background: rgba(251, 250, 246, 0.86);
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            backdrop-filter: blur(8px);
        }
        .page-title { font-family: var(--sm-font-head); letter-spacing: -0.02em; }

        .table thead { background: rgba(63, 125, 103, 0.10); }
        .table tbody tr { transition: background-color 140ms ease; }
        .table tbody tr:hover { background: rgba(63,125,103,0.06); }

        .card {
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: var(--sm-radius);
            background: rgba(251, 250, 246, 0.86);
            backdrop-filter: blur(6px);
        }
        .shadow-sm { box-shadow: var(--sm-shadow-soft) !important; }

        .badge-role { text-transform: uppercase; letter-spacing: .04em; }
        .btn-outline-secondary { border-color: rgba(100, 116, 139, 0.55); }
        .btn-primary {
            border: none;
            background: linear-gradient(135deg, rgba(63,125,103,1) 0%, rgba(46,151,121,1) 55%, rgba(30,58,138,0.92) 140%);
            box-shadow: 0 10px 24px rgba(63,125,103,0.20);
        }
        .btn-primary:hover { filter: brightness(1.02); box-shadow: 0 12px 30px rgba(63,125,103,0.24); }
        .btn-primary:active { transform: translateY(1px); }

        .form-control, .form-select {
            border-radius: 12px;
            border-color: rgba(15, 23, 42, 0.14);
            background: rgba(251, 250, 246, 0.92);
        }
        .form-control:focus, .form-select:focus {
            border-color: rgba(63,125,103,0.55);
            box-shadow: 0 0 0 .25rem rgba(63,125,103,0.16);
        }

        @media (prefers-reduced-motion: no-preference) {
            .content { opacity: 0; transform: translateY(6px); }
            body.is-loaded .content { opacity: 1; transform: translateY(0); transition: opacity 240ms ease, transform 240ms ease; }
        }
        @media (max-width: 992px) { .sidebar { width: 220px; } }
    </style>
</head>
<body>
<div class="d-flex">
    <nav class="sidebar p-3">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="brand-mark fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;">TH</div>
            <div>
                <div class="fw-semibold">{{ config('app.name') }}</div>
                <div class="small text-light">Hệ thống quản lý</div>
            </div>
        </div>
        <div class="mb-2 text-uppercase small opacity-75 menu-title">Chức năng</div>
        <div class="d-flex flex-column gap-1">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>

            @if(auth()->user()->isAdmin())
                <a href="{{ route('school-years.index') }}" class="{{ request()->is('school-years*') ? 'active' : '' }}"><i class="bi bi-calendar-event me-2"></i>Năm học - Học kỳ</a>
                <a href="{{ route('classes.index') }}" class="{{ request()->is('classes*') ? 'active' : '' }}"><i class="bi bi-building me-2"></i>Lớp học</a>
                <a href="{{ route('students.index') }}" class="{{ request()->is('students*') ? 'active' : '' }}"><i class="bi bi-people me-2"></i>Học sinh</a>
                <a href="{{ route('teachers.index') }}" class="{{ request()->is('teachers*') ? 'active' : '' }}"><i class="bi bi-person-badge me-2"></i>Giáo viên</a>
                <a href="{{ route('parents.index') }}" class="{{ request()->is('parents*') ? 'active' : '' }}"><i class="bi bi-people-fill me-2"></i>Phụ huynh</a>
                <a href="{{ route('subjects.index') }}" class="{{ request()->is('subjects*') ? 'active' : '' }}"><i class="bi bi-book me-2"></i>Môn học</a>
                <a href="{{ route('assignments.index') }}" class="{{ request()->is('assignments*') ? 'active' : '' }}"><i class="bi bi-diagram-3 me-2"></i>Phân công</a>
                <a href="{{ route('grade-windows.index') }}" class="{{ request()->is('grade-windows*') ? 'active' : '' }}"><i class="bi bi-lock me-2"></i>Khóa nhập điểm</a>
                <a href="{{ route('timetable.manage') }}" class="{{ request()->is('timetable/manage*') ? 'active' : '' }}"><i class="bi bi-calendar3-week me-2"></i>Quản lý thời khóa biểu</a>
            @endif

            <a href="{{ route('timetable.index') }}" class="{{ request()->is('timetable*') && !request()->is('timetable/manage*') ? 'active' : '' }}"><i class="bi bi-grid-3x3-gap me-2"></i>Thời khóa biểu</a>

            @if(auth()->user()->isTeacher())
                <a href="{{ route('scores.index') }}" class="{{ request()->is('scores*') ? 'active' : '' }}"><i class="bi bi-table me-2"></i>Nhập điểm</a>
            @endif

            @if(auth()->user()->isHomeroom() || auth()->user()->isAdmin())
                <a href="{{ route('conduct.index') }}" class="{{ request()->is('conduct*') ? 'active' : '' }}"><i class="bi bi-clipboard-check me-2"></i>Hạnh kiểm</a>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->isHomeroom())
                <a href="{{ route('ai.run.form') }}" class="{{ request()->is('ai/run*') ? 'active' : '' }}"><i class="bi bi-cpu me-2"></i>AI phân tích</a>
            @endif

            <a href="{{ route('ai.alerts') }}" class="{{ request()->is('ai/alerts*') ? 'active' : '' }}"><i class="bi bi-exclamation-triangle me-2"></i>AI cảnh báo</a>
            <a href="{{ route('ai.reports') }}" class="{{ request()->is('ai/reports*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text me-2"></i>AI nhận xét</a>
            <a href="{{ route('messages.inbox') }}" class="{{ request()->is('messages*') ? 'active' : '' }}"><i class="bi bi-chat-dots me-2"></i>Tin nhắn</a>

            @if(auth()->user()->isAdmin() || auth()->user()->isTeacher() || auth()->user()->isHomeroom())
                <a href="{{ route('reports.class-summary') }}" class="{{ request()->is('reports*') ? 'active' : '' }}"><i class="bi bi-graph-up me-2"></i>Báo cáo</a>
            @endif
        </div>
    </nav>
    <div class="flex-grow-1">
        <header class="topbar px-4 py-3 d-flex justify-content-between align-items-center">
            <div class="page-title fs-6">@yield('title', $title ?? 'Dashboard')</div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-secondary badge-role">{{ auth()->user()->role }}</span>
                <div class="dropdown">
                    <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-2"></i><span class="fw-semibold">{{ auth()->user()->display_name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person-circle me-2"></i>Thông tin cá nhân</a></li>
                        <li><a class="dropdown-item" href="{{ route('profile.change-password') }}"><i class="bi bi-key me-2"></i>Đổi mật khẩu</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
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
    // Add a class after first paint for lightweight entrance animations.
    window.requestAnimationFrame(() => document.body.classList.add('is-loaded'));
</script>
</body>
</html>
