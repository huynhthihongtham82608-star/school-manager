@extends('layouts.app')
@section('title', 'Phân công giảng dạy')

@section('content')
@php
    $roleFilters = ['all' => 'Tất cả vai trò'] + \App\Models\TeachingAssignment::ROLES;
    $statusFilters = ['all' => 'Tất cả trạng thái'] + \App\Models\TeachingAssignment::STATUSES;
    $teacherGroups = $assignments->groupBy(fn ($assignment) => (string) ($assignment->teacher_id ?? 'unknown'));
@endphp

<x-page-header
    title="Phân công giảng dạy"
    subtitle="Liên kết giáo viên, môn học và lớp học theo năm học đang làm việc để phục vụ thời khóa biểu và nhập điểm."
>
    <div class="d-flex align-items-center gap-2">
        @unless($readOnly)
            <a class="btn btn-primary" href="{{ route('assignments.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Thêm phân công
            </a>
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

<div class="card assignment-list-card">
    <div class="table-responsive">
        <table class="table align-middle assignment-table assignment-group-table">
            <thead>
                <tr>
                    <th style="width: 28%;">Giáo viên</th>
                    <th>Danh sách lớp & Định mức</th>
                    <th class="text-end action-column-header" style="width: 110px;" aria-label="Thao tác"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($teacherGroups as $teacherKey => $teacherAssignments)
                @php
                    $firstAssignment = $teacherAssignments->first();
                    $teacher = $firstAssignment?->teacher;
                    $modalId = 'assignmentTeacherModal' . md5((string) $teacherKey);
                @endphp
                <tr>
                    <td>
                        <div class="assignment-teacher-name">{{ $teacher?->name ?? 'Chưa xác định giáo viên' }}</div>
                        <div class="assignment-muted">{{ $teacher?->teacher_code ?? '-' }}</div>
                        <div class="assignment-muted">Tổ: {{ $teacher?->department?->name ?? 'Chưa phân tổ' }}</div>
                    </td>
                    <td>
                        <div class="assignment-class-stack">
                            @foreach($teacherAssignments as $assignment)
                                @php
                                    $period = $periodProgress[(string) $assignment->getKey()] ?? [
                                        'standard' => 0,
                                        'expected' => 0,
                                        'scheduled' => 0,
                                        'percent' => 0,
                                        'badge_class' => 'bg-light text-muted border',
                                        'progress_class' => 'bg-warning',
                                        'label' => 'Chưa cấu hình định mức',
                                    ];
                                    $subjectDepartments = $assignment->subject?->departments ?? collect();
                                    $teacherDepartmentId = $assignment->teacher?->department_id;
                                    $teacherOutsideDepartment = $subjectDepartments->isNotEmpty()
                                        && $teacherDepartmentId
                                        && ! $subjectDepartments->pluck('id')->contains($teacherDepartmentId);
                                @endphp
                                <div class="assignment-class-row">
                                    <div class="assignment-class-main">
                                        <span class="assignment-class-tag">{{ $assignment->classRoom?->name ?? '-' }}</span>
                                        <span class="assignment-subject-chip">{{ $assignment->subject?->name ?? '-' }}</span>
                                    </div>
                                    <div class="assignment-class-meta">
                                        <span>Định mức: <strong>{{ $period['expected'] ?: '-' }}</strong> tiết/tuần</span>
                                        <span>Đã xếp: <strong>{{ $period['scheduled'] }}/{{ $period['expected'] ?: '-' }}</strong> tiết</span>
                                        <span>Vai trò: <strong>{{ $assignment->roleLabel() }}</strong></span>
                                        <span>Trạng thái: <strong>{{ $assignment->statusLabel() }}</strong></span>
                                    </div>
                                    <div class="assignment-class-progress">
                                        <span class="badge {{ $period['badge_class'] }}">{{ $period['label'] }}</span>
                                        @if($assignment->hasWeeklyPeriodOverride())
                                            <span class="badge bg-info">Đã điều chỉnh</span>
                                        @endif
                                        @if($teacherOutsideDepartment)
                                            <span class="badge bg-warning text-dark">Khác tổ phụ trách</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end" data-action-synced="true">
                            @unless($readOnly)
                                <a href="{{ route('assignments.edit', $firstAssignment) }}" class="content-action-btn icon-only edit" title="Sửa phân công" aria-label="Sửa phân công">
                                    <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa phân công</span>
                                </a>
                            @endunless
                            <div class="dropdown">
                                <button type="button" class="content-action-btn icon-only dropdown-toggle-clean more" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end content-action-menu">
                                    <li>
                                        <button type="button" class="dropdown-item font-normal" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                            <i class="bi bi-eye"></i>Xem chi tiết
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3"><div class="empty-state"><i class="bi bi-diagram-3"></i>Chưa có phân công.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($teacherGroups as $teacherKey => $teacherAssignments)
    @php
        $firstAssignment = $teacherAssignments->first();
        $teacher = $firstAssignment?->teacher;
        $modalId = 'assignmentTeacherModal' . md5((string) $teacherKey);
    @endphp
    <div class="modal fade content-modal assignment-detail-modal academic-detail-center-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="academic-detail-header">
                        <div class="academic-detail-identity">
                            <h5 class="modal-title">{{ $teacher?->name ?? 'Chưa xác định giáo viên' }}</h5>
                            <div>{{ $teacher?->teacher_code ?? '-' }} | Tổ: {{ $teacher?->department?->name ?? 'Chưa phân tổ' }}</div>
                        </div>
                        <span class="badge bg-success">{{ $teacherAssignments->count() }} phân công</span>
                    </div>
                    <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <section class="academic-detail-section">
                        <h6>Danh sách lớp giảng dạy</h6>
                        <div class="assignment-detail-list">
                            @foreach($teacherAssignments as $assignment)
                                @php
                                    $assignmentPeriod = $periodProgress[(string) $assignment->getKey()] ?? ['standard' => 0, 'expected' => 0, 'scheduled' => 0, 'label' => 'Chưa cấu hình định mức'];
                                    $assignmentDepartments = $assignment->subject?->departments?->pluck('name')->filter()->join(', ');
                                @endphp
                                <article class="assignment-detail-item">
                                    <div class="assignment-detail-item-title">
                                        <span class="assignment-class-tag">{{ $assignment->classRoom?->name ?? '-' }}</span>
                                        <strong>{{ $assignment->subject?->name ?? '-' }}</strong>
                                        <span class="badge {{ $assignment->statusBadgeClass() }}">{{ $assignment->statusLabel() }}</span>
                                    </div>
                                    <div class="academic-detail-grid mt-2">
                                        <article>
                                            <span>Vai trò</span>
                                            <strong>{{ $assignment->roleLabel() }}</strong>
                                        </article>
                                        <article>
                                            <span>Tổ phụ trách môn</span>
                                            <strong>{{ $assignmentDepartments ?: '-' }}</strong>
                                        </article>
                                        <article>
                                            <span>Định mức tiết/tuần</span>
                                            <strong>{{ $assignmentPeriod['expected'] ?: '-' }}</strong>
                                        </article>
                                        <article>
                                            <span>Đã xếp thời khóa biểu</span>
                                            <strong>{{ $assignmentPeriod['scheduled'] ?? 0 }}/{{ $assignmentPeriod['expected'] ?: '-' }} tiết</strong>
                                        </article>
                                    </div>
                                    <div class="assignment-detail-flat-note mt-2">
                                        <span>Ghi chú</span>
                                        <p>{{ trim((string) $assignment->note) !== '' ? $assignment->note : '-' }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng cửa sổ</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
