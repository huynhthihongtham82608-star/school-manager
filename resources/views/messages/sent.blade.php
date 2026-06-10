@extends('layouts.app')
@section('title', 'Tin đã gửi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Tin đã gửi</h5>
        <div class="text-muted">Tin nhắn bạn đã gửi</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('messages.inbox') }}">Hộp thư đến</a>
        <a class="btn btn-primary" href="{{ route('messages.create') }}"><i class="bi bi-plus-lg me-1"></i>Soạn</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
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
                    <td><a href="{{ route('messages.show', $m) }}">{{ $m->title ?: '(Không tiêu đề)' }}</a></td>
                    <td class="text-muted">{{ $m->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted p-4">Chưa gửi tin nhắn nào.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
