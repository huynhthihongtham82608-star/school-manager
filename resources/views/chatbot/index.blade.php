@extends('layouts.app')
@section('title', 'Chatbot hỗ trợ')

@section('content')
<div class="page-heading">
    <div>
        <h5>Chatbot hỗ trợ</h5>
        <div class="text-muted">Hỏi nhanh theo dữ liệu thật trong hệ thống và đúng phạm vi tài khoản của bạn.</div>
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

    @if(!empty($suggestions))
        <div class="d-flex flex-wrap gap-2 my-3">
            @foreach($suggestions as $suggestion)
                <button type="button" class="btn btn-outline-primary btn-sm font-normal" data-chatbot-page-suggestion="{{ $suggestion }}">
                    {{ $suggestion }}
                </button>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('chatbot.ask') }}" class="chat-form">
        @csrf
        <input name="question" class="form-control" placeholder="Nhập câu hỏi cần hỗ trợ..." required data-chatbot-page-input>
        <button class="btn btn-primary" aria-label="Gửi câu hỏi"><i class="bi bi-send"></i></button>
    </form>
</div>
@push('scripts')
<script>
document.querySelectorAll('[data-chatbot-page-suggestion]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.querySelector('[data-chatbot-page-input]');
        if (!input) {
            return;
        }

        input.value = button.dataset.chatbotPageSuggestion || '';
        input.closest('form')?.submit();
    });
});
</script>
@endpush
@endsection