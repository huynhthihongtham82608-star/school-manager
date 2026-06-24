@extends('layouts.app')
@section('title', 'Chatbot hỗ trợ')

@section('content')
<div class="page-heading">
    <div>
        <h5>Chatbot hỗ trợ</h5>
        <div class="text-muted">Hỏi nhanh về điểm, thời khóa biểu, lịch thi, điểm danh, tài liệu và thông báo.</div>
    </div>
</div>

<div class="chat-panel">
    <div class="chat-messages">
        @forelse($messages as $message)
            <div class="chat-row chat-question">
                <div class="chat-bubble">{{ $message->question }}</div>
            </div>
            <div class="chat-row chat-answer">
                <div class="chat-bubble">{{ $message->answer }}</div>
            </div>
        @empty
            <div class="empty-state"><i class="bi bi-robot"></i>Chưa có hội thoại. Hãy nhập câu hỏi để bắt đầu.</div>
        @endforelse
    </div>
    <form method="POST" action="{{ route('chatbot.ask') }}" class="chat-form">
        @csrf
        <input name="question" class="form-control" placeholder="Nhập câu hỏi cần hỗ trợ..." required>
        <button class="btn btn-primary"><i class="bi bi-send"></i></button>
    </form>
</div>
@endsection
