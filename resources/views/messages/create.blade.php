@extends('layouts.app')
@section('title', 'Soạn tin nhắn')

@section('content')
<h5 class="mb-3">Soạn tin nhắn</h5>
<form method="POST" action="{{ route('messages.store') }}" class="card shadow-sm p-4">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Người nhận</label>
            <select class="form-select" name="receiver_user_id" required>
                <option value="">-- Chọn --</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->username }} ({{ $u->role }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Tiêu đề (tùy chọn)</label>
            <input class="form-control" name="title" value="{{ old('title') }}">
        </div>
        <div class="col-12">
            <label class="form-label">Nội dung</label>
            <textarea class="form-control" name="content" rows="6" required>{{ old('content') }}</textarea>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Gửi</button>
        <a class="btn btn-link" href="{{ route('messages.inbox') }}">Hủy</a>
    </div>
</form>
@endsection
