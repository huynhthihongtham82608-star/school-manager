@extends('layouts.app')
@section('title', 'Hồ sơ cá nhân')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Hồ sơ cá nhân</h5>
    <div class="btn-group">
        <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil"></i> Sửa
        </a>
        <a href="{{ route('profile.change-password') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-key"></i> Đổi mật khẩu
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        @if($user->isAdmin())
            {{-- Admin Profile --}}
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Tên đăng nhập:</label>
                <div class="col-sm-9">{{ $user->username }}</div>
            </div>
            <hr>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Trạng thái:</label>
                <div class="col-sm-9">
                    <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $user->is_active ? 'Hoạt động' : 'Vô hiệu' }}
                    </span>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Quyền:</label>
                <div class="col-sm-9">
                    <span class="badge bg-info">{{ ucfirst($user->role) }}</span>
                </div>
            </div>

        @elseif($user->isTeacher() && $teacher)
            {{-- Teacher/Homeroom Profile --}}
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Mã GV:</label>
                <div class="col-sm-9">{{ $teacher->teacher_code }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Họ tên:</label>
                <div class="col-sm-9">{{ $teacher->name }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Email:</label>
                <div class="col-sm-9">{{ $teacher->email ?? 'Chưa cập nhật' }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Số điện thoại:</label>
                <div class="col-sm-9">{{ $teacher->phone ?? 'Chưa cập nhật' }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Bộ môn:</label>
                <div class="col-sm-9">{{ $teacher->main_subject ?? 'Chưa cập nhật' }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Chức vụ:</label>
                <div class="col-sm-9">
                    @if($teacher->is_homeroom)
                        <span class="badge bg-warning text-dark">GVCN</span>
                    @else
                        <span class="badge bg-secondary">GV</span>
                    @endif
                </div>
            </div>
            <hr>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Tên đăng nhập:</label>
                <div class="col-sm-9">{{ $user->username }}</div>
            </div>

        @elseif($user->isStudent() && $student)
            {{-- Student Profile --}}
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Mã HS:</label>
                <div class="col-sm-9">{{ $student->student_code }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Họ tên:</label>
                <div class="col-sm-9">{{ $student->name }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Ngày sinh:</label>
                <div class="col-sm-9">
                    {{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d/m/Y') : 'Chưa cập nhật' }}
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Giới tính:</label>
                <div class="col-sm-9">
                    @if($student->gender === 'male')
                        Nam
                    @elseif($student->gender === 'female')
                        Nữ
                    @else
                        {{ $student->gender ?? 'Chưa cập nhật' }}
                    @endif
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Lớp:</label>
                <div class="col-sm-9">
                    {{ $student->classRoom?->name ?? 'Chưa cập nhật' }}
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Địa chỉ:</label>
                <div class="col-sm-9">{{ $student->address ?? 'Chưa cập nhật' }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Email:</label>
                <div class="col-sm-9">{{ $student->email ?? 'Chưa cập nhật' }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Số điện thoại PH:</label>
                <div class="col-sm-9">{{ $student->parent_phone ?? 'Chưa cập nhật' }}</div>
            </div>
            <hr>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Trạng thái:</label>
                <div class="col-sm-9">
                    <span class="badge bg-info">{{ ucfirst($student->status ?? 'studying') }}</span>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Tên đăng nhập:</label>
                <div class="col-sm-9">{{ $user->username }}</div>
            </div>

        @elseif($user->isParent() && $parent)
            {{-- Parent Profile --}}
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Họ tên:</label>
                <div class="col-sm-9">{{ $parent->name }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Số điện thoại:</label>
                <div class="col-sm-9">{{ $parent->phone ?? 'Chưa cập nhật' }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Email:</label>
                <div class="col-sm-9">{{ $parent->email ?? 'Chưa cập nhật' }}</div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Địa chỉ:</label>
                <div class="col-sm-9">{{ $parent->address ?? 'Chưa cập nhật' }}</div>
            </div>

            @if($children && $children->count() > 0)
                <hr>
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label fw-semibold">Danh sách con em:</label>
                    <div class="col-sm-9">
                        <ul class="list-group">
                            @foreach($children as $child)
                                <li class="list-group-item">
                                    <strong>{{ $child->name }}</strong>
                                    <span class="badge bg-secondary ms-2">{{ $child->student_code }}</span>
                                    <br>
                                    <small class="text-muted">
                                        Lớp: {{ $child->classRoom?->name ?? 'N/A' }} | 
                                        Trạng thái: {{ ucfirst($child->status ?? 'studying') }}
                                    </small>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <hr>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label fw-semibold">Tên đăng nhập:</label>
                <div class="col-sm-9">{{ $user->username }}</div>
            </div>

        @else
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Không có thông tin hồ sơ
            </div>
        @endif
    </div>
</div>
@endsection
