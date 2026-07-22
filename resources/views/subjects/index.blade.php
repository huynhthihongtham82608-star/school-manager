@extends('layouts.app')
@section('title', 'Môn học')

@section('content')
@php($statusFilters = ['all' => 'Tất cả'] + \App\Models\Subject::STATUSES)

<x-page-header
    title="Quản lý môn học"
    subtitle="Khai báo danh mục môn học chính khóa, chủ nhiệm, hoạt động và định mức tiết học theo khối."
>
    <div class="d-flex align-items-center gap-2">
        @unless($readOnly)
            <a class="btn btn-primary" href="{{ route('subjects.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm môn</a>
        @endunless
        <div class="dropdown">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Lọc trạng thái" aria-label="Lọc trạng thái">
                <i class="bi bi-funnel"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($statusFilters as $value => $label)
                    <li>
                        <a class="dropdown-item {{ $selectedStatus === $value ? 'active' : '' }}" href="{{ route('subjects.index', $value === 'all' ? [] : ['status' => $value]) }}">
                            @if($selectedStatus === $value)
                                <i class="bi bi-check2 me-2"></i>
                            @else
                                <i class="bi bi-circle me-2 text-muted"></i>
                            @endif
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã môn</th>
                    <th>Tên môn</th>
                    <th>Hệ số môn</th>
                    <th>Loại môn</th>
                    <th>Tổ phụ trách</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($subjects as $subject)
                @php($canDelete = $subject->canDelete())
                <tr>
                    <td class="fw-semibold">{{ $subject->code }}</td>
                    <td>{{ $subject->name }}</td>
                    <td>{{ $subject->credit }}</td>
                    <td>{{ $subject->typeLabel() }}</td>
                    <td>
                        @if($subject->isScorable())
                            {{ $subject->departments->pluck('name')->join(', ') ?: 'Chưa phân tổ' }}
                        @else
                            <span class="text-muted">Không áp dụng</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $subject->statusBadgeClass() }}">{{ $subject->statusLabel() }}</span></td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            @unless($readOnly)
                                <a href="{{ route('subjects.edit', $subject) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                </a>
                            @endunless
                            <div class="dropdown">
                                <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#subjectNormModal{{ $subject->id }}">
                                            <i class="bi bi-clock-history me-2"></i>Xem định mức tiết
                                        </button>
                                    </li>
                                    @unless($readOnly)
                                        @if($canDelete)
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('subjects.destroy', $subject) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa môn học này? Hành động này không thể hoàn tác.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash me-2"></i>Xóa
                                                    </button>
                                                </form>
                                            </li>
                                        @else
                                            <li><hr class="dropdown-divider"></li>
                                            <li><span class="dropdown-item text-muted">Đã phát sinh dữ liệu</span></li>
                                        @endif
                                    @endunless
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7"><div class="empty-state"><i class="bi bi-book"></i>Chưa có môn học.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($subjects as $subject)
    <div class="modal fade" id="subjectNormModal{{ $subject->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Định mức tiết học</h5>
                        <div class="text-muted small">{{ $subject->code }} - {{ $subject->name }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    @if($subject->requiresTeachingAssignment())
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Khối</th>
                                        <th>Số tiết/tuần</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gradeLevels as $gradeLevel)
                                        @php($norm = $subject->periodNormForGrade($gradeLevel))
                                        <tr>
                                            <td class="fw-semibold">Khối {{ $gradeLevel }}</td>
                                            <td>{{ $norm?->periods_per_week ?? 'Chưa cấu hình' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state py-4">
                            <i class="bi bi-calendar-event"></i>
                            Môn {{ $subject->typeLabel() }} không áp dụng định mức tiết/tuần và không cần phân công giáo viên bộ môn.
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    @unless($readOnly)
                        <a href="{{ route('subjects.edit', $subject) }}" class="btn btn-primary">Cấu hình định mức tiết</a>
                    @endunless
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
