<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Đăng nhập - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --sm-primary: #E67E22;
            --sm-primary-strong: #C96712;
            --sm-bg: #F5F5F5;
            --sm-border: #E7E2DD;
            --sm-ink: #222222;
        }
        body {
            min-height: 100vh;
            color: var(--sm-ink);
            background: linear-gradient(135deg, rgba(255, 242, 230, .94), rgba(255, 255, 255, .92)), var(--sm-bg);
            font-family: "Be Vietnam Pro", "Segoe UI", Arial, sans-serif;
        }
        .login-card {
            width: min(420px, calc(100vw - 2rem));
            border: 1px solid var(--sm-border);
            border-radius: 18px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 24px 64px rgba(31, 31, 31, .10);
        }
        .brand-mark {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            color: #fff;
            background: var(--sm-primary);
            font-weight: 700;
        }
        .brand-name {
            font-weight: 700;
        }
        .form-label {
            color: #334155;
            font-weight: 700;
        }
        .form-control {
            min-height: 44px;
            border-radius: 12px;
            border-color: #D8D2CB;
        }
        .form-control:focus {
            border-color: var(--sm-primary);
            box-shadow: 0 0 0 .22rem rgba(230, 126, 34, .16);
        }
        .btn-primary {
            min-height: 44px;
            border-color: var(--sm-primary);
            border-radius: 12px;
            background: var(--sm-primary);
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(230, 126, 34, .20);
        }
        .btn-primary:hover {
            border-color: var(--sm-primary-strong);
            background: var(--sm-primary-strong);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="card login-card">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <div class="brand-mark mb-3">TH</div>
                <div class="brand-name h5 mb-1">{{ config('app.name') }}</div>
                <div class="text-muted small">Quản lý trường THPT</div>
            </div>
            @include('partials.flash')
            <form method="POST" action="{{ route('login.perform') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Tài khoản</label>
                    <input type="text" name="username" class="form-control" value="{{ old('username') }}" required autofocus autocomplete="username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required autocomplete="current-password">
                </div>
                <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
