<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ config('app.name') }} - Trang chủ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="{{ asset('css/school-ui.css') }}" rel="stylesheet">
</head>
<body class="landing-page">
    <header class="landing-header">
        <div class="container d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('home') }}" class="landing-brand">
                <span class="brand-mark">TH</span>
                <span>{{ config('app.name') }}</span>
            </a>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary"><i class="bi bi-grid me-2"></i>Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập</a>
            @endauth
        </div>
    </header>

    <main>
        <section class="landing-hero">
            <div class="container">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <div class="landing-kicker">{{ data_get($banner, 'extra.subtitle', 'Chào mừng đến với cổng thông tin nhà trường') }}</div>
                        <h1>{{ $banner['title'] }}</h1>
                        <p>{{ $banner['content'] }}</p>
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            @auth
                                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg"><i class="bi bi-grid me-2"></i>Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-primary btn-lg"><i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập hệ thống</a>
                            @endauth
                            <a href="#contact" class="btn btn-outline-primary btn-lg"><i class="bi bi-telephone me-2"></i>Liên hệ</a>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="hero-panel">
                            @if(!empty($banner['image_url']))
                                <img src="{{ $banner['image_url'] }}" alt="Banner trường học">
                            @else
                                <div class="hero-illustration">
                                    <i class="bi bi-mortarboard"></i>
                                    <span>School Manager</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="container">
                <div class="row g-3">
                    @foreach([['Học sinh', $stats['students'], 'bi-people'], ['Giáo viên', $stats['teachers'], 'bi-person-badge'], ['Lớp học', $stats['classes'], 'bi-building'], ['Tài liệu', $stats['documents'], 'bi-journal-bookmark']] as [$label, $value, $icon])
                        <div class="col-6 col-lg-3">
                            <div class="landing-stat">
                                <i class="bi {{ $icon }}"></i>
                                <strong>{{ $value }}</strong>
                                <span>{{ $label }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="container">
                <div class="section-heading">
                    <span>Giới thiệu</span>
                    <h2>{{ $about['title'] }}</h2>
                    <p>{{ $about['content'] }}</p>
                </div>
            </div>
        </section>

        <section class="landing-section landing-muted">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="section-heading text-start mb-3">
                            <span>Tin tức và thông báo</span>
                            <h2>Cập nhật từ nhà trường</h2>
                        </div>
                        <div class="landing-list">
                            @forelse($news->concat($announcements)->take(6) as $post)
                                <article class="landing-list-item">
                                    <span class="badge bg-info">{{ $post->type === 'news' ? 'Tin tức' : 'Thông báo' }}</span>
                                    <h3>{{ $post->title }}</h3>
                                    <p>{{ $post->summary ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 120) }}</p>
                                </article>
                            @empty
                                <div class="empty-state"><i class="bi bi-inbox"></i>Chưa có tin tức hoặc thông báo.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="section-heading text-start mb-3">
                            <span>Sự kiện</span>
                            <h2>Sắp diễn ra</h2>
                        </div>
                        <div class="landing-list">
                            @forelse($events as $event)
                                <article class="landing-list-item">
                                    <h3>{{ $event->title }}</h3>
                                    <p><i class="bi bi-clock me-1"></i>{{ optional($event->starts_at)->format('d/m/Y H:i') ?: 'Đang cập nhật' }}</p>
                                    @if($event->location)<p><i class="bi bi-geo-alt me-1"></i>{{ $event->location }}</p>@endif
                                </article>
                            @empty
                                <div class="empty-state"><i class="bi bi-calendar-x"></i>Chưa có sự kiện sắp diễn ra.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="container">
                <div class="section-heading">
                    <span>Thư viện</span>
                    <h2>Tài liệu học tập</h2>
                </div>
                <div class="row g-3">
                    @forelse($documents as $document)
                        <div class="col-md-6 col-lg-3">
                            <div class="document-card h-100">
                                <i class="bi bi-file-earmark-text"></i>
                                <h3>{{ $document->title }}</h3>
                                <p>{{ $document->description ?: 'Tài liệu được nhà trường chia sẻ.' }}</p>
                                @if($document->file_url)
                                    <a href="{{ $document->file_url }}" target="_blank" class="btn btn-outline-primary btn-sm">Xem tài liệu</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="empty-state"><i class="bi bi-folder2-open"></i>Chưa có tài liệu được công khai.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="contact" class="landing-section landing-muted">
            <div class="container">
                <div class="contact-panel">
                    <div>
                        <span class="landing-kicker">Liên hệ</span>
                        <h2>{{ $contact['title'] }}</h2>
                        <p>{{ $contact['content'] }}</p>
                    </div>
                    <div class="contact-lines">
                        <div><i class="bi bi-telephone"></i>{{ data_get($contact, 'extra.phone', 'Đang cập nhật') }}</div>
                        <div><i class="bi bi-envelope"></i>{{ data_get($contact, 'extra.email', 'Đang cập nhật') }}</div>
                        <div><i class="bi bi-geo-alt"></i>{{ data_get($contact, 'extra.address', 'Đang cập nhật') }}</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <script>
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
