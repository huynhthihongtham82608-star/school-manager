@extends('layouts.app')
@section('title', 'Chi tiết tin nhắn')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between gap-3">
        <div>
            <h5 class="mb-1">{{ $message->title ?: '(Không tiêu đề)' }}</h5>
            <div class="text-muted small">
                Chuỗi hội thoại
                <span class="mx-1">•</span>
                {{ $threadMessages->count() }} tin nhắn
            </div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('messages.inbox') }}">Hộp thư đến</a>
            <a class="btn btn-outline-secondary" href="{{ route('messages.sent') }}">Đã gửi</a>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex flex-column gap-3">
            @foreach($threadMessages as $item)
                @php
                    $isMine = $item->sender_user_id === auth()->id();
                    $senderName = $item->sender?->display_name ?? $item->sender?->username;
                    $recipientText = $item->recipientNames() ?: $item->recipient_summary;
                @endphp
                <div class="message-thread-item border rounded-4 p-3 {{ $isMine ? 'bg-light' : 'bg-white' }}">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <div>
                            <div class="fw-semibold">
                                {{ $senderName }}
                                @if($isMine)
                                    <span class="badge bg-primary ms-1">Bạn</span>
                                @endif
                            </div>
                            <div class="text-muted small">
                                Đến: {{ $recipientText }}
                            </div>
                        </div>
                        <div class="text-muted small">{{ $item->created_at?->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="mb-0" style="white-space: pre-wrap;">{{ $item->content }}</div>

                    @if($item->attachments->isNotEmpty())
                        <div class="border-top mt-3 pt-3">
                            <div class="fw-semibold small mb-2"><i class="bi bi-paperclip me-1"></i>Tệp đính kèm</div>
                            <div class="d-flex flex-column gap-2">
                                @foreach($item->attachments as $attachment)
                                    <a class="d-flex align-items-center justify-content-between gap-3 border rounded-3 p-2 text-decoration-none text-dark" href="{{ $attachment->downloadUrl() }}" target="_blank">
                                        <span><i class="bi bi-file-earmark me-2 text-primary"></i>{{ $attachment->original_name }}</span>
                                        <span class="text-muted small">{{ $attachment->sizeLabel() }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if($canReply)
            <form id="reply" method="POST" action="{{ route('messages.reply', $message) }}" enctype="multipart/form-data" class="mt-4 border-top pt-4">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Phản hồi</label>
                    <textarea class="form-control" name="content" rows="4" required placeholder="Nhập nội dung phản hồi...">{{ old('content') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tệp đính kèm</label>
                    <input class="form-control" type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                    <div class="form-text">Hỗ trợ PDF, DOC, DOCX, XLS, XLSX, PNG, JPG, JPEG.</div>
                </div>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary"><i class="bi bi-reply me-1"></i>Gửi phản hồi</button>
                </div>
            </form>
        @else
            <div class="alert alert-light border mt-4 mb-0">
                Bạn chỉ có thể phản hồi các tin nhắn được phép theo vai trò hiện tại.
            </div>
        @endif
    </div>
</div>
@endsection
