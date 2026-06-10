@extends('layouts.app')
@section('title', 'Thêm phụ huynh')

@section('content')
<h5 class="mb-3">Thêm phụ huynh</h5>
<form method="POST" action="{{ route('parents.store') }}" class="card shadow-sm p-4">
    @csrf
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Họ tên</label>
            <input class="form-control" name="name" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">SĐT</label>
            <input class="form-control" name="phone">
        </div>
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input class="form-control" name="email" type="email">
        </div>
        <div class="col-12">
            <label class="form-label">Địa chỉ</label>
            <input class="form-control" name="address">
        </div>
        <div class="col-md-6">
            <label class="form-label">Liên kết học sinh</label>
            <select class="form-select" name="student_ids[]" multiple>
                @foreach($students as $st)
                    <option value="{{ $st->id }}">{{ $st->student_code }} - {{ $st->name }}</option>
                @endforeach
            </select>
            <div class="text-muted small mt-1">Giữ Ctrl để chọn nhiều.</div>
        </div>
        <hr>
        <div class="col-md-4">
            <label class="form-label">Tài khoản</label>
            <input class="form-control" name="username" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Mật khẩu</label>
            <input class="form-control" name="password" type="password" required>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Lưu</button>
        <a class="btn btn-link" href="{{ route('parents.index') }}">Hủy</a>
    </div>
</form>
@endsection
