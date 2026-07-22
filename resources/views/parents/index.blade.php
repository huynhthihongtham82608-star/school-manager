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
        <a class="btn btn-primary" href="{{ route('parents.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Thêm phụ huynh
        </a>
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
                            <span class="badge bg-success">Đang hoạt động</span>
                        @else
                            <span class="badge bg-secondary">Chưa kích hoạt</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <a class="content-action-btn icon-only edit" href="{{ route('parents.edit', $parent) }}" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                            </a>
                            <div class="dropdown">
                                <button class="content-action-btn icon-only" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#parentDetailModal{{ $parent->id }}">
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
                                        <form method="POST" action="{{ route('parents.destroy', $parent) }}">
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

@foreach($parents as $parent)
    @php
        $relations = $parent->students
            ->map(fn ($student) => \App\Models\ParentProfile::relationLabel($student->pivot?->relation))
            ->filter()
            ->unique()
            ->implode(', ');
    @endphp
    <div class="modal fade" id="parentDetailModal{{ $parent->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ $parent->name }}</h5>
                        <div class="text-muted small">{{ $parent->parent_code ?: 'Chưa có mã phụ huynh' }}</div>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Quan hệ</div>
                                <div class="info-value">{{ $relations ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Số điện thoại</div>
                                <div class="info-value">{{ $parent->phone ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Địa chỉ</div>
                                <div class="info-value">{{ $parent->address ?: '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="fw-bold mb-3">Học sinh liên kết</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
