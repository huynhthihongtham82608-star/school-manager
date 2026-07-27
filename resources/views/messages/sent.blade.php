@extends('layouts.app')
@section('title', 'Tin đã gửi')

@section('content')
@include('messages._filters', ['action' => route('messages.sent'), 'filters' => $filters])

<div class="card">
    <div class="table-responsive">
        <table class="table message-table" data-no-auto-toolbar>
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
                @php
                    $modalId = 'sent-message-thread-modal-' . $message->id;
                    $threadMessages = $messageThreads->get($message->conversationKey(), collect([$message]));
                    $canReply = (bool) ($canReplyMap[(string) $message->id] ?? false);
                    $messageTitle = $message->title ?: '(Không tiêu đề)';
                @endphp
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
                        <button type="button" class="message-title-button" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                            {{ $messageTitle }}
                        </button>
                        @if($message->attachments->isNotEmpty())
                            <i class="bi bi-paperclip text-muted ms-1"></i>
                        @endif
                    </td>
                    <td class="text-muted">{{ $message->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <button type="button" class="content-action-btn edit" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" title="Gửi tiếp" aria-label="Gửi tiếp">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <div class="dropdown">
                                <button type="button" class="content-action-btn icon-only more" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" title="Thao tác khác" aria-label="Thao tác khác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end content-action-menu">
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                        <i class="bi bi-eye"></i>Xem chi tiết
                                    </button>
                                    <form method="POST" action="{{ route('messages.destroy', $message) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin nhắn này vào thùng rác?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="box" value="sent">
                                        <button class="dropdown-item danger" type="submit">
                                            <i class="bi bi-trash"></i>Xóa vào thùng rác
                                        </button>
                                    </form>
                                </div>
                            </div>
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
    @php
        $modalId = 'sent-message-thread-modal-' . $message->id;
        $threadMessages = $messageThreads->get($message->conversationKey(), collect([$message]));
        $canReply = (bool) ($canReplyMap[(string) $message->id] ?? false);
        $messageTitle = $message->title ?: '(Không tiêu đề)';
    @endphp
    <div class="modal fade message-thread-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('messages.reply', $message) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="redirect_box" value="sent">
                    <div class="modal-header message-thread-modal-header">
                        <div class="message-thread-title-group">
                            <h5 class="modal-title">{{ $messageTitle }}</h5>
                            <div>Chuỗi hội thoại đã gửi</div>
                        </div>
                        <span class="message-thread-status sent ms-auto">
                            <i class="bi bi-envelope-paper"></i>Thư đã gửi
                        </span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>

                    <div class="modal-body">
                        <section class="message-thread-section">
                            <div class="message-thread-section-title">Chuỗi diễn biến tin nhắn lịch sử</div>
                            <div class="message-thread-list">
                                @foreach($threadMessages as $item)
                                    @php
                                        $isMine = $item->sender_user_id === auth()->id();
                                        $senderName = $item->sender?->display_name ?? $item->sender?->username;
                                        $recipientText = $item->recipientNames() ?: $item->recipient_summary;
                                    @endphp
                                    <article class="message-thread-bubble {{ $isMine ? 'is-mine' : '' }}">
                                        <div class="message-thread-meta">
                                            <strong>{{ $senderName }} @if($isMine)<span>(Bạn)</span>@endif</strong>
                                            <span>Đến: {{ $recipientText ?: 'Không xác định' }}</span>
                                            <time>{{ $item->created_at?->format('d/m/Y H:i') }}</time>
                                        </div>
                                        <div class="message-thread-content">{{ $item->content }}</div>

                                        @if($item->attachments->isNotEmpty())
                                            <div class="message-thread-attachments">
                                                @foreach($item->attachments as $attachment)
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

                        <section class="message-thread-section mt-3">
                            <div class="message-thread-section-title">Gửi thư phản hồi tiếp theo</div>
                            @if($canReply)
                                <div class="message-inline-reply">
                                    <label class="form-label">Nội dung phản hồi</label>
                                    <textarea class="form-control" name="content" rows="4" required placeholder="Nhập nội dung phản hồi...">{{ old('content') }}</textarea>
                                    <label class="form-label mt-3">Tệp đính kèm</label>
                                    <input class="form-control" type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                                    <div class="form-text">Hỗ trợ PDF, DOC, DOCX, XLS, XLSX, PNG, JPG, JPEG.</div>
                                </div>
                            @else
                                <div class="alert alert-light border mb-0">
                                    Bạn chỉ có thể phản hồi các tin nhắn được phép theo vai trò hiện tại.
                                </div>
                            @endif
                        </section>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary message-thread-close" data-bs-dismiss="modal">Đóng hộp thư</button>
                        @if($canReply)
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Gửi thư tiếp tục</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

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
