@extends('layouts.app')
@section('title', 'Thùng rác')

@section('content')
@include('messages._nav')
@include('messages._filters', ['action' => route('messages.trash'), 'filters' => $filters])

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Loại</th>
                    <th>Tiêu đề</th>
                    <th>Người liên quan</th>
                    <th>Thời gian xóa</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($messages as $item)
                @php($message = $item['message'])
                <tr>
                    <td><span class="badge bg-light text-dark border">{{ $item['type'] === 'sent' ? 'Đã gửi' : 'Đã nhận' }}</span></td>
                    <td class="fw-semibold">{{ $message->title ?: '(Không tiêu đề)' }}</td>
                    <td>{{ $item['type'] === 'sent' ? ($message->recipientNames() ?: $message->recipient_summary) : ($message->sender?->display_name ?? $message->sender?->username) }}</td>
                    <td class="text-muted">{{ $item['deleted_at']?->format('d/m/Y H:i') }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <form method="POST" action="{{ route('messages.restore', $message) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="box" value="{{ $item['type'] === 'sent' ? 'sent' : 'inbox' }}">
                                <button class="content-action-btn view" type="submit" title="Khôi phục"><i class="bi bi-arrow-counterclockwise"></i></button>
                            </form>
                            <form method="POST" action="{{ route('messages.force-destroy', $message) }}">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="box" value="{{ $item['type'] === 'sent' ? 'sent' : 'inbox' }}">
                                <button class="content-action-btn delete" type="submit" title="Xóa vĩnh viễn"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"><div class="empty-state"><i class="bi bi-trash3"></i>Thùng rác trống.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
