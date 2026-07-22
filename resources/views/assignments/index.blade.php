@extends('layouts.app')
@section('title', 'Phân công giảng dạy')

@section('content')
@php
    $roleFilters = ['all' => 'Tất cả vai trò'] + \App\Models\TeachingAssignment::ROLES;
    $statusFilters = ['all' => 'Tất cả trạng thái'] + \App\Models\TeachingAssignment::STATUSES;
@endphp

<x-page-header
    title="Phân công giảng dạy"
    subtitle="Liên kết giáo viên, môn học và lớp học theo năm học đang làm việc để phục vụ thời khóa biểu và nhập điểm."
>
    <div class="d-flex align-items-center gap-2">
        @unless($readOnly)
            <a class="btn btn-primary" href="{{ route('assignments.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm phân công</a>
        @endunless
        <div class="dropdown">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Lọc phân công" aria-label="Lọc phân công">
                <i class="bi bi-funnel"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 320px;">
                <form method="GET" action="{{ route('assignments.index') }}" class="d-grid gap-3">
                    <div>
                        <label class="form-label small">Lớp</label>
                        <select name="class_id" class="form-select">
                            <option value="all">Tất cả lớp</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected($filters['class_id'] === $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small">Giáo viên</label>
                        <select name="teacher_id" class="form-select">
                            <option value="all">Tất cả giáo viên</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected($filters['teacher_id'] === $teacher->id)>{{ $teacher->teacher_code }} - {{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small">Tổ chuyên môn</label>
                        <select name="department_id" class="form-select">
                            <option value="all">Tất cả tổ</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected($filters['department_id'] === $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small">Vai trò</label>
                        <select name="role" class="form-select">
                            @foreach($roleFilters as $value => $label)
                                <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
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
                        <a href="{{ route('assignments.index') }}" class="btn btn-secondary">Xóa lọc</a>
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
                    <th>Giáo viên</th>
                    <th>Lớp</th>
                    <th>Tiến độ tiết</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($assignments as $assignment)
                @php
                    $deleteCheck = $deleteChecks[(string) $assignment->getKey()] ?? ['allowed' => false, 'message' => null];
                    $subjectDepartments = $assignment->subject?->departments ?? collect();
                    $teacherDepartmentId = $assignment->teacher?->department_id;
                    $teacherOutsideDepartment = $subjectDepartments->isNotEmpty()
                        && $teacherDepartmentId
                        && ! $subjectDepartments->pluck('id')->contains($teacherDepartmentId);
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $assignment->teacher->name ?? '-' }}</div>
                        <div class="text-muted small">{{ $assignment->teacher->teacher_code ?? '-' }} · {{ $assignment->subject->name ?? '-' }}</div>
                        <div class="text-muted small">Tổ: {{ $assignment->teacher?->department?->name ?? 'Chưa phân tổ' }}</div>
                        @if($teacherOutsideDepartment)
                            <div class="small text-warning">Giáo viên này không thuộc tổ phụ trách môn học.</div>
                        @endif
                    </td>
                    <td class="fw-semibold">{{ $assignment->classRoom->name ?? '-' }}</td>
                    <td>
                        @php($period = $periodProgress[(string) $assignment->getKey()] ?? ['standard' => 0, 'expected' => 0, 'scheduled' => 0, 'percent' => 0, 'badge_class' => 'bg-light text-muted border', 'progress_class' => 'bg-warning', 'label' => 'Chưa cấu hình định mức'])
                        @if($period['expected'] > 0)
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge {{ $period['badge_class'] }}">{{ $period['label'] }}</span>
                                @if($assignment->hasWeeklyPeriodOverride())
                                    <span class="badge bg-info">Đã điều chỉnh</span>
                                @endif
                            </div>
                            <div class="small mt-2">
                                <div>Định mức: <strong>{{ $period['expected'] }}</strong> tiết{{ $assignment->hasWeeklyPeriodOverride() && $period['standard'] ? ' (chuẩn: ' . $period['standard'] . ')' : '' }}</div>
                                <div>Đã xếp: <strong>{{ $period['scheduled'] }}/{{ $period['expected'] }}</strong> tiết</div>
                            </div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar {{ $period['progress_class'] }}" style="width: {{ $period['percent'] }}%"></div>
                            </div>
                        @else
                            <span class="badge {{ $period['badge_class'] }}">{{ $period['label'] }}</span>
                        @endif
                    </td>
                    <td>{{ $assignment->roleLabel() }}</td>
                    <td><span class="badge {{ $assignment->statusBadgeClass() }}">{{ $assignment->statusLabel() }}</span></td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            @unless($readOnly)
                                <a href="{{ route('assignments.edit', $assignment) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                </a>
                                <div class="dropdown">
                                    <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if($deleteCheck['allowed'])
                                            <li>
                                                <form action="{{ route('assignments.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa phân công này? Hành động này không thể hoàn tác.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash me-2"></i>Xóa
                                                    </button>
                                                </form>
                                            </li>
                                        @else
                                            <li><span class="dropdown-item text-muted">{{ $deleteCheck['message'] ?? 'Đã phát sinh dữ liệu' }}</span></li>
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
                    <td colspan="6"><div class="empty-state"><i class="bi bi-diagram-3"></i>Chưa có phân công.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
