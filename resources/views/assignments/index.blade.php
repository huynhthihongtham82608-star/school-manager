@extends('layouts.app')
@section('title', 'Phân công giảng dạy')

@section('content')
@php
    $roleFilters = ['all' => 'Tất cả vai trò'] + \App\Models\TeachingAssignment::ROLES;
    $statusFilters = ['all' => 'Tất cả trạng thái'] + \App\Models\TeachingAssignment::STATUSES;
@endphp

<div class="page-heading">
    <div>
        <h5>Phân công giảng dạy</h5>
        <div class="text-muted">Gán giáo viên theo năm học, học kỳ, lớp và môn học.</div>
    </div>
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
                    @if($selectedYearId)
                        <input type="hidden" name="school_year_id" value="{{ $selectedYearId }}">
                    @endif
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
                                <option value="{{ $teacher->id }}" @selected($filters['teacher_id'] === $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small">Môn học</label>
                        <select name="subject_id" class="form-select">
                            <option value="all">Tất cả môn học</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected($filters['subject_id'] === $subject->id)>{{ $subject->name }}</option>
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
                        <a href="{{ route('assignments.index', array_filter(['school_year_id' => $selectedYearId])) }}" class="btn btn-secondary">Xóa lọc</a>
                        <button class="btn btn-primary">Áp dụng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Năm học</th>
                    <th>Học kỳ</th>
                    <th>Lớp</th>
                    <th>Môn học</th>
                    <th>Giáo viên</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($assignments as $assignment)
                @php($deleteCheck = $deleteChecks[(string) $assignment->getKey()] ?? ['allowed' => false, 'message' => null])
                <tr>
                    <td>{{ $assignment->schoolYear->name ?? '-' }}</td>
                    <td>{{ $assignment->semester?->normalizedName() ?? '-' }}</td>
                    <td class="fw-semibold">{{ $assignment->classRoom->name ?? '-' }}</td>
                    <td>{{ $assignment->subject->name ?? '-' }}</td>
                    <td>{{ $assignment->teacher->name ?? '-' }}</td>
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
                    <td colspan="8"><div class="empty-state"><i class="bi bi-diagram-3"></i>Chưa có phân công.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
