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
        <article class="info-card">
            <div class="d-flex justify-content-between gap-2 mb-2">
                <span class="badge bg-info">{{ $post->type === 'news' ? 'Tin tức' : 'Thông báo' }}</span>
                <span class="text-muted small">{{ optional($post->published_at)->format('d/m/Y') }}</span>
            </div>
            <h6>{{ $post->title }}</h6>
            <p>{{ $post->summary ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 160) }}</p>
        </article>
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
