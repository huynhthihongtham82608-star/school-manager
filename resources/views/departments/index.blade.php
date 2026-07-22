@extends('layouts.app')
@section('title', 'Tổ chuyên môn')

@section('content')
<x-page-header
    title="Quản lý tổ chuyên môn"
    subtitle="Phân loại giáo viên theo tổ chuyên môn, môn phụ trách và tổ trưởng để hỗ trợ phân công giảng dạy."
>
    <div class="d-flex align-items-center gap-2">
        @unless($readOnly)
            <a class="btn btn-primary" href="{{ route('departments.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm tổ</a>
        @endunless
        <div class="dropdown">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Lọc tổ chuyên môn" aria-label="Lọc tổ chuyên môn">
                <i class="bi bi-funnel"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 320px;">
                <form method="GET" action="{{ route('departments.index') }}" class="d-grid gap-3">
                    <div>
                        <label class="form-label small">Tìm kiếm</label>
                        <input type="search" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Mã tổ, tên tổ, môn, tổ trưởng">
                    </div>
                    <div>
                        <label class="form-label small">Trạng thái</label>
                        <select name="status" class="form-select">
                            @foreach($statusFilters as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('departments.index') }}" class="btn btn-secondary">Xóa lọc</a>
                        <button class="btn btn-primary">Áp dụng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã tổ</th>
                    <th>Tên tổ</th>
                    <th>Số môn phụ trách</th>
                    <th>Số giáo viên</th>
                    <th>Tổ trưởng</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($departments as $department)
                <tr>
                    <td class="fw-semibold">{{ $department->code }}</td>
                    <td>
                        <div class="fw-semibold">{{ $department->name }}</div>
                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($department->description ?: 'Chưa có mô tả', 70) }}</div>
                    </td>
                    <td><span class="badge bg-light text-dark border">{{ $department->subjects_count }}</span></td>
                    <td><span class="badge bg-light text-dark border">{{ $department->teachers_count }}</span></td>
                    <td>{{ $department->leader?->name ?? 'Chưa phân công' }}</td>
                    <td><span class="badge {{ $department->statusBadgeClass() }}">{{ $department->statusLabel() }}</span></td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <button type="button" class="content-action-btn icon-only" title="Xem chi tiết" aria-label="Xem chi tiết" data-bs-toggle="modal" data-bs-target="#departmentDetail{{ $department->id }}">
                                <i class="bi bi-eye"></i><span class="visually-hidden">Xem chi tiết</span>
                            </button>
                            @unless($readOnly)
                                <a href="{{ route('departments.edit', $department) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                </a>
                                <div class="dropdown">
                                    <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if($department->teachers_count === 0)
                                            <li>
                                                <form action="{{ route('departments.destroy', $department) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa tổ chuyên môn này?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash me-2"></i>Xóa
                                                    </button>
                                                </form>
                                            </li>
                                        @else
                                            <li><span class="dropdown-item text-muted">Còn giáo viên trong tổ</span></li>
                                        @endif
                                    </ul>
                                </div>
                            @else
                                <span class="text-muted small">Chỉ xem</span>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7"><div class="empty-state"><i class="bi bi-diagram-2"></i>Chưa có tổ chuyên môn.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($departments as $department)
    <div class="modal fade" id="departmentDetail{{ $department->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ $department->name }}</h5>
                        <div class="text-muted small">{{ $department->code }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-3">Thông tin chung</h6>
                                    <div class="d-grid gap-2 small">
                                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Mã tổ</span><span class="fw-semibold text-end">{{ $department->code }}</span></div>
                                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Tên tổ</span><span class="fw-semibold text-end">{{ $department->name }}</span></div>
                                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Tổ trưởng</span><span class="fw-semibold text-end">{{ $department->leader?->name ?? 'Chưa phân công' }}</span></div>
                                        <div class="d-flex justify-content-between gap-3"><span class="text-muted">Trạng thái</span><span class="badge {{ $department->statusBadgeClass() }}">{{ $department->statusLabel() }}</span></div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="text-muted small mb-1">Mô tả</div>
                                        <div>{{ $department->description ?: 'Chưa có mô tả.' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-3">Môn phụ trách</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse($department->subjects as $subject)
                                            <span class="badge bg-light text-dark border">{{ $subject->name }}</span>
                                        @empty
                                            <span class="text-muted">Chưa gán môn phụ trách.</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border mt-3">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">Danh sách giáo viên</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Mã GV</th>
                                            <th>Họ tên</th>
                                            <th>Môn chuyên môn</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($department->teachers as $teacher)
                                            <tr>
                                                <td class="fw-semibold">{{ $teacher->teacher_code }}</td>
                                                <td>{{ $teacher->name }}</td>
                                                <td>{{ $teacher->primarySubject?->name ?? $teacher->main_subject ?? 'Chưa cấu hình' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-muted">Chưa có giáo viên thuộc tổ.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
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
