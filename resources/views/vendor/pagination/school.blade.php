@if ($paginator->count() > 0)
    @php
        $elements = $elements ?? [];
        $pageName = method_exists($paginator, 'getPageName') ? $paginator->getPageName() : 'page';
        $label = match (true) {
            $pageName === 'posts_page' => 'thông báo',
            $pageName === 'events_page' => 'sự kiện',
            request()->routeIs('students.*') => 'học sinh',
            request()->routeIs('teachers.*') => 'giáo viên',
            request()->routeIs('parents.*') => 'phụ huynh',
            request()->routeIs('audit-logs.*') => 'nhật ký',
            request()->routeIs('school-years.*') => 'năm học',
            request()->routeIs('semesters.*') => 'học kỳ',
            request()->routeIs('subjects.*') => 'môn học',
            request()->routeIs('departments.*') => 'tổ chuyên môn',
            request()->routeIs('rooms.*') => 'phòng học',
            request()->routeIs('classes.*') => 'lớp học',
            request()->routeIs('assignments.*') => 'phân công',
            request()->routeIs('score-columns.*') => 'cột điểm',
            request()->routeIs('scores.*') => 'bảng điểm',
            request()->routeIs('conduct.*') => 'hạnh kiểm',
            request()->routeIs('conducts.*') => 'hạnh kiểm',
            request()->routeIs('announcements.*') => 'thông báo',
            request()->routeIs('events.*') => 'sự kiện',
            request()->routeIs('documents.*') => 'tài liệu',
            request()->routeIs('admin-users.*') => 'tài khoản',
            request()->routeIs('exam-schedules.*') => 'lịch kiểm tra',
            request()->routeIs('attendance.*') => 'điểm danh',
            default => 'bản ghi',
        };
        $total = method_exists($paginator, 'total') ? $paginator->total() : null;
        $perPage = method_exists($paginator, 'perPage') ? $paginator->perPage() : null;
        $hasTotal = $total !== null;
        $shouldShowPages = $paginator->hasPages() && (! $hasTotal || ! $perPage || $total > $perPage);
        $shownCount = $hasTotal ? min($paginator->count(), $total) : $paginator->count();
    @endphp

    <nav class="school-pagination" role="navigation" aria-label="Phân trang">
        <div class="school-pagination-summary">
            @if($shouldShowPages && method_exists($paginator, 'firstItem') && method_exists($paginator, 'lastItem'))
                Hiển thị {{ $paginator->firstItem() }} đến {{ $paginator->lastItem() }} trong tổng số {{ $total }} {{ $label }}
            @elseif($hasTotal)
                Hiển thị {{ $shownCount }} trong tổng số {{ $total }} {{ $label }}
            @else
                Hiển thị {{ $paginator->count() }} {{ $label }}
            @endif
        </div>

        @if ($shouldShowPages)
            <div class="school-pagination-pages">
                @if ($paginator->onFirstPage())
                    <span class="school-page-btn disabled">❮ Trước</span>
                @else
                    <a class="school-page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">❮ Trước</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="school-page-btn disabled">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="school-page-btn page-number active" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="school-page-btn page-number" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a class="school-page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Sau ❯</a>
                @else
                    <span class="school-page-btn disabled">Sau ❯</span>
                @endif
            </div>
        @endif
    </nav>
@endif
