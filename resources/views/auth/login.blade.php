<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sm-brand: #3f7d67;
            --sm-brand-strong: #2f5f4f;
            --sm-slate: #64748b;
            --sm-bg: #f7f5ef;
            --sm-surface: #fbfaf6;
            --sm-accent: #1e3a8a;

            --bs-primary: var(--sm-brand);
            --bs-primary-rgb: 63, 125, 103;
            --bs-secondary: var(--sm-slate);
            --bs-secondary-rgb: 100, 116, 139;
            --bs-body-bg: var(--sm-bg);
            --bs-body-color: #0f172a;
            --bs-link-color: var(--sm-accent);
            --bs-link-hover-color: #162f75;
        }
        body {
            background:
                radial-gradient(900px 520px at 18% 18%, rgba(63,125,103,0.24), transparent 60%),
                radial-gradient(700px 480px at 86% 22%, rgba(30,58,138,0.18), transparent 55%),
                linear-gradient(180deg, var(--sm-surface) 0%, var(--sm-bg) 55%);
            font-family: "Be Vietnam Pro", "Segoe UI", Arial, sans-serif;
        }
        body { color: #0f172a; }
        .login-card { border: 1px solid rgba(15, 23, 42, 0.08); border-radius: 18px; box-shadow: 0 22px 56px rgba(15, 23, 42, 0.12); background: rgba(251, 250, 246, 0.88); backdrop-filter: blur(8px); }
        .brand-pill { display:inline-flex; align-items:center; gap:.5rem; }
        .brand-dot { width:10px; height:10px; border-radius:999px; background: var(--sm-brand); box-shadow: 0 0 0 6px rgba(63,125,103,0.14); }
        .form-control { border-radius: 12px; border-color: rgba(15, 23, 42, 0.14); background: rgba(251, 250, 246, 0.92); }
        .form-control:focus { border-color: rgba(63,125,103,0.55); box-shadow: 0 0 0 .25rem rgba(63,125,103,0.16); }
        .btn-primary { border: none; background: linear-gradient(135deg, rgba(63,125,103,1) 0%, rgba(46,151,121,1) 55%, rgba(30,58,138,0.92) 140%); box-shadow: 0 10px 24px rgba(63,125,103,0.20); }
        .btn-primary:hover { filter: brightness(1.02); box-shadow: 0 12px 30px rgba(63,125,103,0.24); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card login-card" style="width: 380px;">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <div class="fw-bold brand-pill justify-content-center">
                    <span class="brand-dot"></span>
                    <span class="text-primary">{{ config('app.name') }}</span>
                </div>
                <div class="text-muted small">Quản lý trường THPT</div>
            </div>
            @include('partials.flash')
            <form method="POST" action="{{ route('login.perform') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Tài khoản</label>
                    <input type="text" name="username" class="form-control" value="{{ old('username') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Đăng nhập</button>
            </form>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
