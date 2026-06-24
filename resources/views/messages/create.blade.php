@extends('layouts.app')
@section('title', 'Soạn tin nhắn')

@section('content')
<div class="page-heading">
    <div>
        <h5>Soạn tin nhắn</h5>
        <div class="text-muted">Gửi tin nhắn nội bộ đến tài khoản đang hoạt động.</div>
    </div>
</div>

<form method="POST" action="{{ route('messages.store') }}" class="card">
    @csrf
    <div class="card-body">
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
    </div>
    <div class="card-footer bg-white d-flex flex-wrap gap-2 justify-content-end">
        <a class="btn btn-outline-secondary" href="{{ route('messages.inbox') }}">Hủy</a>
        <button class="btn btn-primary">Gửi</button>
    </div>
</form>
@endsection
