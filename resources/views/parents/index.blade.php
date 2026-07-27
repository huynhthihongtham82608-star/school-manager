@extends('layouts.app')
@section('title', 'Phụ huynh')

@section('content')
<x-page-header
    title="Quản lý tài khoản phụ huynh"
    subtitle="Giám sát danh sách tài khoản của cha mẹ, quản lý liên kết định danh giữa phụ huynh và học sinh."
>
    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
            <i class="bi bi-bar-chart me-1"></i>Xuất file liên kết
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#parentCreateModal">
            <i class="bi bi-plus-lg me-1"></i>Thêm phụ huynh
        </button>
    </div>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Mã phụ huynh</th>
                    <th>Họ tên</th>
                    <th>Quan hệ</th>
                    <th>Học sinh</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($parents as $parent)
                @php
                    $relations = $parent->students
                        ->map(fn ($student) => \App\Models\ParentProfile::relationLabel($student->pivot?->relation))
                        ->filter()
                        ->unique()
                        ->implode(', ');
                    $studentLabels = $parent->students->map(fn ($student) => ($student->student_code ?: '-') . ' - ' . $student->name);
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $parent->parent_code ?: 'Chưa có mã' }}</td>
                    <td>
                        <div class="fw-semibold">{{ $parent->name }}</div>
                        <div class="text-muted small">{{ $parent->phone ?: 'Chưa cập nhật số điện thoại' }}</div>
                    </td>
                    <td>{{ $relations ?: '-' }}</td>
                    <td>
                        @forelse($studentLabels as $label)
                            <div class="small">{{ $label }}</div>
                        @empty
                            <span class="text-muted">Chưa liên kết</span>
                        @endforelse
                    </td>
                    <td>
                        @if($parent->user?->is_active)
                            <span class="badge bg-success">Hoạt động</span>
                        @else
                            <span class="badge bg-secondary">Chưa kích hoạt</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <button type="button" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="modal" data-bs-target="#parentEdit{{ $parent->id }}">
                                <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                            </button>
                            <div class="dropdown">
                                <button class="content-action-btn icon-only" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#parentDetail{{ $parent->id }}">
                                            <i class="bi bi-eye me-2"></i>Xem chi tiết
                                        </button>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('parents.reset-password', $parent) }}" onsubmit="return confirm('Bạn có chắc muốn đặt lại mật khẩu cho phụ huynh này?');">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-key me-2"></i>Đặt lại mật khẩu
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('parents.destroy', $parent) }}" onsubmit="return confirm('Bạn có chắc muốn xóa phụ huynh này? Hành động này không thể hoàn tác.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i>Xóa
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="empty-state"><i class="bi bi-people"></i>Chưa có phụ huynh.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade student-form-modal parent-form-modal" id="parentCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Thêm phụ huynh mới</h5>
                    <div class="text-muted small">Nhập thông tin tài khoản, liên hệ và liên kết học sinh.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <?php echo $__env->make('parents.partials.form', [
                    'action' => route('parents.store'),
                    'parent' => null,
                    'students' => $students,
                    'nextParentCode' => $nextParentCode,
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </div>
</div>

@foreach($parents as $parent)
    @php
        $relations = $parent->students
            ->map(fn ($student) => \App\Models\ParentProfile::relationLabel($student->pivot?->relation))
            ->filter()
            ->unique()
            ->implode(', ');
        $isActive = (bool) $parent->user?->is_active;
    @endphp

    <div class="modal fade student-form-modal parent-form-modal" id="parentEdit{{ $parent->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Chỉnh sửa thông tin phụ huynh</h5>
                        <div class="text-muted small">{{ $parent->parent_code ?: 'Chưa có mã' }} - {{ $parent->name }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <?php echo $__env->make('parents.partials.form', [
                        'action' => route('parents.update', $parent),
                        'parent' => $parent,
                        'students' => $students,
                        'nextParentCode' => $nextParentCode,
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade content-modal profile-detail-modal parent-detail-modal" id="parentDetail{{ $parent->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header parent-drawer-top">
                    <div>
                        <div class="parent-drawer-kicker">Hồ sơ phụ huynh</div>
                        <h5 class="modal-title" id="parentDetailLabel{{ $parent->id }}">Xem chi tiết</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="parent-drawer-header">
                        <div class="parent-drawer-left">
                            <div class="parent-drawer-avatar">P</div>
                            <div class="parent-drawer-identity">
                                <h5>{{ $parent->name }}</h5>
                                <div>Mã số: {{ $parent->parent_code ?: '-' }}</div>
                            </div>
                        </div>
                        <span class="parent-drawer-status badge {{ $isActive ? 'bg-success' : 'bg-secondary' }}">{{ $isActive ? 'Hoạt động' : 'Chưa kích hoạt' }}</span>
                    </div>

                    <div class="profile-detail-section-title">
                        <h6>Thông tin liên hệ</h6>
                    </div>
                    <div class="parent-drawer-grid">
                        <article>
                            <span>Quan hệ</span>
                            <strong class="{{ $relations ? '' : 'text-muted fw-normal' }}">{{ $relations ?: '-' }}</strong>
                        </article>
                        <article>
                            <span>Số điện thoại</span>
                            <strong class="{{ $parent->phone ? '' : 'text-muted fw-normal' }}">{{ $parent->phone ?: '-' }}</strong>
                        </article>
                        <article class="wide">
                            <span>Địa chỉ</span>
                            <strong class="{{ $parent->address ? '' : 'text-muted fw-normal' }}">{{ $parent->address ?: '-' }}</strong>
                        </article>
                    </div>

                    <div class="parent-linked-box">
                        <div class="parent-linked-title"><i class="bi bi-people"></i> Con em đang theo học</div>
                        <div class="table-responsive">
                            <table class="table table-sm parent-linked-table">
                                <thead>
                                    <tr>
                                        <th>Mã học sinh</th>
                                        <th>Họ tên</th>
                                        <th>Lớp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($parent->students as $student)
                                        <tr>
                                            <td class="fw-semibold">{{ $student->student_code }}</td>
                                            <td>{{ $student->name }}</td>
                                            <td>{{ $student->classRoom?->name ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3"><div class="empty-state"><i class="bi bi-person"></i>Chưa liên kết học sinh.</div></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng hồ sơ</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@include('parents.partials.student-picker')

@if($errors->any() && old('_parent_form_modal'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById(@json(old('_parent_form_modal')));
            if (modalElement && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        });
    </script>
@endif
@endsection
