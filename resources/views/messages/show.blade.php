@extends('layouts.app')
@section('title', 'Chi tiết tin nhắn')

@section('content')
<div class="page-heading">
    <div>
        <h5>{{ $message->title ?: '(Không tiêu đề)' }}</h5>
        <div class="text-muted small">
            Từ: {{ $message->sender?->display_name ?? $message->sender?->username }}
            · Đến: {{ $message->receiver?->display_name ?? $message->receiver?->username }}
            · {{ $message->created_at }}
        </div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('messages.inbox') }}">Hộp thư đến</a>
        <a class="btn btn-outline-secondary" href="{{ route('messages.sent') }}">Đã gửi</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div style="white-space: pre-wrap;">{{ $message->content }}</div>
    </div>
</div>
@endsection
