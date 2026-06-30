@extends('layouts.app')
@section('title', 'Thông báo')

@section('content')
<div class="page-heading">
    <div>
        <h5>Thông báo và tin tức</h5>
        <div class="text-muted">Theo dõi các cập nhật mới nhất từ nhà trường.</div>
    </div>
</div>

<div class="content-grid">
    @forelse($posts as $post)
        @php
            $detailId = 'announcement-detail-' . $loop->index;
            $postType = $post->type === 'news' ? 'Tin tức' : 'Thông báo';
            $summary = $post->summary ?: 'Chưa có tóm tắt.';
            $content = $post->content ?: 'Chưa có nội dung.';
        @endphp
        <article class="info-card">
            <div class="d-flex justify-content-between gap-2 mb-2">
                <span class="badge bg-info">{{ $postType }}</span>
                <span class="text-muted small">{{ optional($post->published_at)->format('d/m/Y') }}</span>
            </div>
            <h6>{{ $post->title }}</h6>
            <p>{{ $post->summary ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 160, '...') }}</p>
            <button type="button" class="content-action-btn icon-only detail mt-2" data-bs-toggle="modal" data-bs-target="#{{ $detailId }}" title="Xem chi tiết" aria-label="Xem chi tiết">
                <i class="bi bi-eye"></i><span class="visually-hidden">Xem chi tiết</span>
            </button>
        </article>

        <div class="modal fade content-modal" id="{{ $detailId }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <div class="modal-kicker">{{ $postType }}</div>
                            <h5 class="modal-title">{{ $post->title }}</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="content-detail-list">
                            <div>
                                <dt>Ngày đăng</dt>
                                <dd>{{ optional($post->published_at)->format('d/m/Y H:i') ?: 'Đang cập nhật' }}</dd>
                            </div>
                            <div>
                                <dt>Loại</dt>
                                <dd>{{ $postType }}</dd>
                            </div>
                            <div>
                                <dt>Tóm tắt</dt>
                                <dd>{{ $summary }}</dd>
                            </div>
                            <div>
                                <dt>Nội dung đầy đủ</dt>
                                <dd class="content-full-text">{!! nl2br(e($content)) !!}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="empty-state"><i class="bi bi-inbox"></i>Chưa có thông báo hoặc tin tức.</div>
        </div>
    @endforelse
</div>

@if(method_exists($posts, 'links'))
    <div class="mt-3">{{ $posts->links() }}</div>
@endif
@endsection
