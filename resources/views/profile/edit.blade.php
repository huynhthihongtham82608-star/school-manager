@extends('layouts.app')
@section('title', 'Sửa hồ sơ cá nhân')

@section('content')
<div class="page-heading">
    <div>
        <h5>Sửa hồ sơ cá nhân</h5>
        <div class="text-muted">Cập nhật các thông tin được phép thay đổi cho tài khoản hiện tại.</div>
    </div>
</div>

<form method="POST" action="{{ route('profile.update') }}" class="card">
    @csrf
    <div class="card-body">
        @if($user->isAdmin())
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-1"></i>Bạn có thể thay đổi tên đăng nhập. Quyền và trạng thái tài khoản cần được quản trị viên khác xử lý.
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $user->username) }}" required>
                    @error('username')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        @elseif($user->isTeacher() && $teacher)
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $teacher->name) }}" required>
                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $teacher->email) }}">
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $teacher->phone) }}">
                    @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">Bộ môn</label>
                    <input type="text" name="main_subject" class="form-control @error('main_subject') is-invalid @enderror" value="{{ old('main_subject', $teacher->main_subject) }}">
                    @error('main_subject')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        @elseif($user->isStudent() && $student)
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $student->name) }}" required>
                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ngày sinh</label>
                    <input type="date" name="dob" class="form-control @error('dob') is-invalid @enderror" value="{{ old('dob', $student->dob) }}">
                    @error('dob')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Giới tính</label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                        <option value="">-- Chọn --</option>
                        <option value="male" {{ old('gender', $student->gender) === 'male' ? 'selected' : '' }}>Nam</option>
                        <option value="female" {{ old('gender', $student->gender) === 'female' ? 'selected' : '' }}>Nữ</option>
                        <option value="other" {{ old('gender', $student->gender) === 'other' ? 'selected' : '' }}>Khác</option>
                    </select>
                    @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lớp</label>
                    <select name="class_id" class="form-select @error('class_id') is-invalid @enderror">
                        <option value="">-- Chọn lớp --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ old('class_id', $student->class_id) === $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('class_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $student->address) }}">
                    @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $student->email) }}">
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">Số điện thoại phụ huynh</label>
                    <input type="text" name="parent_phone" class="form-control @error('parent_phone') is-invalid @enderror" value="{{ old('parent_phone', $student->parent_phone) }}">
                    @error('parent_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        @elseif($user->isParent() && $parent)
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $parent->name) }}" required>
                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $parent->phone) }}">
                    @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $parent->email) }}">
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $parent->address) }}">
                    @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        @else
            <div class="empty-state"><i class="bi bi-info-circle"></i>Không có thông tin hồ sơ để sửa.</div>
        @endif
    </div>
    <div class="card-footer bg-white d-flex flex-wrap gap-2 justify-content-end">
        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Hủy
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i>Lưu
        </button>
    </div>
</form>
@endsection
