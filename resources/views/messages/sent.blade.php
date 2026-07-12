@extends('layouts.app')
@section('title', 'Tin đã gửi')

@section('content')
@include('messages._nav')
@include('messages._filters', ['action' => route('messages.sent'), 'filters' => $filters])

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Người nhận</th>
                    <th>Tiêu đề</th>
                    <th>Thời gian gửi</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($messages as $message)
                <tr>
                    <td>
                        @if($message->recipients->count() > 1)
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#recipients-{{ $message->id }}">
                                Đã gửi cho {{ $message->recipients->count() }} người
                            </button>
                        @else
                            {{ $message->recipients->first()?->receiver?->display_name ?? $message->recipient_summary }}
                        @endif
                    </td>
                    <td class="fw-semibold">
                        <a class="text-decoration-none text-dark" href="{{ route('messages.show', $message) }}?box=sent">{{ $message->title ?: '(Không tiêu đề)' }}</a>
                        @if($message->attachments->isNotEmpty())
                            <i class="bi bi-paperclip text-muted ms-1"></i>
                        @endif
                    </td>
                    <td class="text-muted">{{ $message->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <a class="content-action-btn view" href="{{ route('messages.show', $message) }}?box=sent" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                            <form method="POST" action="{{ route('messages.destroy', $message) }}">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="box" value="sent">
                                <button class="content-action-btn delete" type="submit" title="Xóa"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4"><div class="empty-state"><i class="bi bi-send"></i>Chưa gửi tin nhắn nào.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($messages, 'links'))
        <div class="card-footer bg-white">{{ $messages->links() }}</div>
    @endif
</div>

@foreach($messages as $message)
    @if($message->recipients->count() > 1)
        <div class="modal fade content-modal" id="recipients-{{ $message->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <div class="modal-kicker">Danh sách người nhận</div>
                            <h5 class="modal-title">{{ $message->title }}</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="list-group list-group-flush">
                            @foreach($message->recipients as $recipient)
                                <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                                    <span>{{ $recipient->receiver?->display_name ?? $recipient->receiver?->username }}</span>
                                    <span class="badge {{ $recipient->is_read ? 'bg-secondary' : 'bg-primary' }}">{{ $recipient->is_read ? 'Đã đọc' : 'Chưa đọc' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
