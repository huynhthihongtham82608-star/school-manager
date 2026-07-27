@extends('layouts.app')
@section('title', 'Thùng rác')

@section('content')
@include('messages._filters', ['action' => route('messages.trash'), 'filters' => $filters])

<div class="card">
    <div class="table-responsive">
        <table class="table message-table" data-no-auto-toolbar>
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
                @php
                    $message = $item['message'];
                    $modalId = 'trash-message-thread-modal-' . $item['type'] . '-' . $message->id;
                    $threadMessages = $messageThreads->get($message->conversationKey(), collect([$message]));
                    $messageTitle = $message->title ?: '(Không tiêu đề)';
                @endphp
                <tr>
                    <td><span class="badge bg-light text-dark border">{{ $item['type'] === 'sent' ? 'Đã gửi' : 'Đã nhận' }}</span></td>
                    <td class="fw-semibold">
                        <button type="button" class="message-title-button" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                            {{ $messageTitle }}
                        </button>
                    </td>
                    <td>{{ $item['type'] === 'sent' ? ($message->recipientNames() ?: $message->recipient_summary) : ($message->sender?->display_name ?? $message->sender?->username) }}</td>
                    <td class="text-muted">{{ $item['deleted_at']?->format('d/m/Y H:i') }}</td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <form method="POST" action="{{ route('messages.restore', $message) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="box" value="{{ $item['type'] === 'sent' ? 'sent' : 'inbox' }}">
                                <button class="content-action-btn icon-only detail" type="submit" title="Khôi phục"><i class="bi bi-arrow-counterclockwise"></i></button>
                            </form>
                            <div class="dropdown">
                                <button type="button" class="content-action-btn icon-only more" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" title="Thao tác khác" aria-label="Thao tác khác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end content-action-menu">
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                        <i class="bi bi-eye"></i>Xem chi tiết
                                    </button>
                                    <form method="POST" action="{{ route('messages.force-destroy', $message) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn tin nhắn này? Hành động này không thể hoàn tác!')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="box" value="{{ $item['type'] === 'sent' ? 'sent' : 'inbox' }}">
                                        <button class="dropdown-item danger" type="submit">
                                            <i class="bi bi-trash"></i>Xóa vĩnh viễn
                                        </button>
                                    </form>
                                </div>
                            </div>
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

@foreach($messages as $item)
    @php
        $message = $item['message'];
        $modalId = 'trash-message-thread-modal-' . $item['type'] . '-' . $message->id;
        $threadMessages = $messageThreads->get($message->conversationKey(), collect([$message]));
        $messageTitle = $message->title ?: '(Không tiêu đề)';
        $deletedBy = auth()->user()->display_name;
        $boxValue = $item['type'] === 'sent' ? 'sent' : 'inbox';
    @endphp
    <div class="modal fade message-thread-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header message-thread-modal-header">
                    <div class="message-thread-title-group">
                        <h5 class="modal-title">{{ $messageTitle }}</h5>
                        <div>Thư mục: Thùng rác</div>
                    </div>
                    <span class="message-thread-status trash ms-auto">
                        <i class="bi bi-trash3"></i>Đã xóa
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <div class="message-trash-audit">
                        <i class="bi bi-clock-history"></i>
                        Người xóa: {{ $deletedBy }} <span>|</span> Thời gian xóa: {{ $item['deleted_at']?->format('d/m/Y H:i') }}
                    </div>

                    <section class="message-thread-section">
                        <div class="message-thread-section-title">Chuỗi diễn biến tin nhắn lịch sử</div>
                        <div class="message-thread-list">
                            @foreach($threadMessages as $threadItem)
                                @php
                                    $isMine = $threadItem->sender_user_id === auth()->id();
                                    $senderName = $threadItem->sender?->display_name ?? $threadItem->sender?->username;
                                    $recipientText = $threadItem->recipientNames() ?: $threadItem->recipient_summary;
                                @endphp
                                <article class="message-thread-bubble {{ $isMine ? 'is-mine' : '' }}">
                                    <div class="message-thread-meta">
                                        <strong>{{ $senderName }} @if($isMine)<span>(Bạn)</span>@endif</strong>
                                        <span>Đến: {{ $recipientText ?: 'Không xác định' }}</span>
                                        <time>{{ $threadItem->created_at?->format('d/m/Y H:i') }}</time>
                                    </div>
                                    <div class="message-thread-content">{{ $threadItem->content }}</div>

                                    @if($threadItem->attachments->isNotEmpty())
                                        <div class="message-thread-attachments">
                                            @foreach($threadItem->attachments as $attachment)
                                                <a href="{{ $attachment->downloadUrl() }}" target="_blank">
                                                    <i class="bi bi-paperclip"></i>{{ $attachment->original_name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="modal-footer message-trash-footer">
                    <form method="POST" action="{{ route('messages.restore', $message) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="box" value="{{ $boxValue }}">
                        <button class="btn btn-outline-success message-trash-restore" type="submit">
                            <i class="bi bi-archive"></i>Khôi phục về Hộp thư
                        </button>
                    </form>
                    <form method="POST" action="{{ route('messages.force-destroy', $message) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn tin nhắn này? Hành động này không thể hoàn tác!')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="box" value="{{ $boxValue }}">
                        <button class="btn btn-link message-trash-force" type="submit">
                            <i class="bi bi-trash"></i>Xóa vĩnh viễn
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-secondary message-thread-close" data-bs-dismiss="modal">Đóng cửa sổ</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
