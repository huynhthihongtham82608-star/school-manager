@extends('layouts.app')
@section('title', 'Hộp thư đến')

@section('content')
<div class="page-heading">
    <div>
        <h5>Hộp thư đến</h5>
        <div class="text-muted">Tin nhắn nhận được.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('messages.sent') }}">Đã gửi</a>
        <a class="btn btn-primary" href="{{ route('messages.create') }}"><i class="bi bi-plus-lg me-1"></i>Soạn</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Trạng thái</th>
                    <th>Người gửi</th>
                    <th>Tiêu đề</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
            @forelse($messages as $m)
                <tr>
                    <td>{!! $m->is_read ? '<span class="badge bg-secondary">Đã đọc</span>' : '<span class="badge bg-primary">Mới</span>' !!}</td>
                    <td>{{ $m->sender?->display_name ?? $m->sender?->username }}</td>
                    <td class="fw-semibold"><a href="{{ route('messages.show', $m) }}">{{ $m->title ?: '(Không tiêu đề)' }}</a></td>
                    <td class="text-muted">{{ $m->created_at }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4"><div class="empty-state"><i class="bi bi-inbox"></i>Chưa có tin nhắn.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
