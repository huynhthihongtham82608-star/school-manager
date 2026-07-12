@extends('layouts.app')
@section('title', 'Thêm giáo viên')

@section('content')
<form method="POST" action="{{ route('teachers.store') }}" class="card p-4 shadow-sm">
    @csrf
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã giáo viên</label>
            <input type="text" name="teacher_code" class="form-control" value="{{ old('teacher_code') }}" required>
            @error('teacher_code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5">
            <label class="form-label">Họ tên</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Môn chính</label>
            <select name="primary_subject_id" class="form-select" required>
                <option value="">Chọn môn chính</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected(old('primary_subject_id') === $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
            @error('primary_subject_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Ngày sinh</label>
            <input type="date" name="dob" class="form-control" value="{{ old('dob') }}">
            @error('dob')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Giới tính</label>
            <select name="gender" class="form-select">
                <option value="">Chọn giới tính</option>
                @foreach(\App\Models\Teacher::genderLabels() as $value => $label)
                    <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('gender')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Ngày vào trường</label>
            <input type="date" name="joined_at" class="form-control" value="{{ old('joined_at') }}">
            @error('joined_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái làm việc</label>
            <select name="work_status" class="form-select" required>
                @foreach(\App\Models\Teacher::workStatuses() as $value => $label)
                    <option value="{{ $value }}" @selected(old('work_status', \App\Models\Teacher::STATUS_WORKING) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('work_status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Thư điện tử</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Số điện thoại</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
            @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Trình độ</label>
            <input type="text" name="qualification" class="form-control" value="{{ old('qualification') }}">
            @error('qualification')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Địa chỉ</label>
            <input type="text" name="address" class="form-control" value="{{ old('address') }}">
            @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="mt-4 d-flex justify-content-end gap-2">
        <a href="{{ route('teachers.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>
@endsection
