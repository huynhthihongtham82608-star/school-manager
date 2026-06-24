@extends('layouts.app')
@section('title', 'Tin đã gửi')

@section('content')
<div class="page-heading">
    <div>
        <h5>Tin đã gửi</h5>
        <div class="text-muted">Tin nhắn bạn đã gửi.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('messages.inbox') }}">Hộp thư đến</a>
        <a class="btn btn-primary" href="{{ route('messages.create') }}"><i class="bi bi-plus-lg me-1"></i>Soạn</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Người nhận</th>
                    <th>Tiêu đề</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
            @forelse($messages as $m)
                <tr>
                    <td>{{ $m->receiver?->display_name ?? $m->receiver?->username }}</td>
                    <td class="fw-semibold"><a href="{{ route('messages.show', $m) }}">{{ $m->title ?: '(Không tiêu đề)' }}</a></td>
                    <td class="text-muted">{{ $m->created_at }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3"><div class="empty-state"><i class="bi bi-send"></i>Chưa gửi tin nhắn nào.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
