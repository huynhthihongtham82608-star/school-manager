@extends('layouts.app')
@section('title', 'Hộp thư đến')

@section('content')
<x-page-header
    title="Hộp thư điện tử"
    subtitle="Kênh tương tác, trao đổi thông tin chính thống giữa Nhà trường, Giáo viên và Phụ huynh học sinh."
>
    <a class="btn btn-primary" href="{{ route('messages.create') }}">
        <i class="bi bi-pencil-square me-1"></i>Soạn tin nhắn mới
    </a>
</x-page-header>

@include('messages._filters', ['action' => route('messages.inbox'), 'filters' => $filters, 'showStatus' => true])

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Trạng thái</th>
                    <th>Người gửi</th>
                    <th>Tiêu đề</th>
                    <th>Người nhận</th>
                    <th>Thời gian</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($messages as $recipient)
                @php($message = $recipient->message)
                <tr>
                    <td>
                        @if($recipient->is_read)
                            <span class="badge bg-secondary">Đã đọc</span>
                        @else
                            <span class="badge bg-primary">Chưa đọc</span>
                        @endif
                    </td>
                    <td>{{ $message->sender?->display_name ?? $message->sender?->username }}</td>
                    <td class="fw-semibold">
                        <a class="text-decoration-none text-dark" href="{{ route('messages.show', $message) }}">{{ $message->title ?: '(Không tiêu đề)' }}</a>
                        @if($message->attachments->isNotEmpty())
                            <i class="bi bi-paperclip text-muted ms-1"></i>
                        @endif
                    </td>
                    <td>{{ auth()->user()->display_name }}</td>
                    <td class="text-muted">{{ $message->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <a class="content-action-btn view" href="{{ route('messages.show', $message) }}" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                            <a class="content-action-btn edit" href="{{ route('messages.show', $message) }}#reply" title="Phản hồi"><i class="bi bi-reply"></i></a>
                            <form method="POST" action="{{ route('messages.destroy', $message) }}">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="box" value="inbox">
                                <button class="content-action-btn delete" type="submit" title="Xóa"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="empty-state"><i class="bi bi-inbox"></i>Chưa có tin nhắn.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($messages, 'links'))
        <div class="card-footer bg-white">{{ $messages->links() }}</div>
    @endif
</div>
@endsection
