@extends('layouts.app')
@section('title', 'Sửa giáo viên')

@section('content')
@if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form method="POST" action="{{ route('teachers.update', $teacher) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã giáo viên</label>
            <input type="text" name="teacher_code" class="form-control" value="{{ old('teacher_code', $teacher->teacher_code) }}" required>
            @error('teacher_code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5">
            <label class="form-label">Họ tên</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $teacher->name) }}" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Môn chính</label>
            <input type="text" name="main_subject" class="form-control" value="{{ old('main_subject', $teacher->main_subject) }}">
            @error('main_subject')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Ngày sinh</label>
            <input type="date" name="dob" class="form-control" value="{{ old('dob', $teacher->dob?->format('Y-m-d')) }}">
            @error('dob')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Giới tính</label>
            <select name="gender" class="form-select">
                <option value="">Chọn giới tính</option>
                @foreach(\App\Models\Teacher::genderLabels() as $value => $label)
                    <option value="{{ $value }}" @selected(old('gender', $teacher->gender) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('gender')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Ngày vào trường</label>
            <input type="date" name="joined_at" class="form-control" value="{{ old('joined_at', $teacher->joined_at?->format('Y-m-d')) }}">
            @error('joined_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái làm việc</label>
            <select name="work_status" class="form-select" required>
                @foreach(\App\Models\Teacher::workStatuses() as $value => $label)
                    <option value="{{ $value }}" @selected(old('work_status', $teacher->work_status ?: \App\Models\Teacher::STATUS_WORKING) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('work_status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $teacher->email) }}">
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Số điện thoại</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $teacher->phone) }}">
            @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Trình độ</label>
            <input type="text" name="qualification" class="form-control" value="{{ old('qualification', $teacher->qualification) }}">
            @error('qualification')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Địa chỉ</label>
            <input type="text" name="address" class="form-control" value="{{ old('address', $teacher->address) }}">
            @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 d-flex align-items-center">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="is_homeroom" value="1" id="is_homeroom" @checked(old('is_homeroom', $teacher->is_homeroom))>
                <label class="form-check-label" for="is_homeroom">Cho phép làm GVCN</label>
            </div>
        </div>
        <hr>
        <div class="col-md-4">
            <label class="form-label">Đặt lại mật khẩu (bỏ trống nếu giữ nguyên)</label>
            <input type="password" name="password" class="form-control">
            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="mt-4 d-flex justify-content-end gap-2">
        <a href="{{ route('teachers.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Cập nhật</button>
    </div>
</form>
@endsection
