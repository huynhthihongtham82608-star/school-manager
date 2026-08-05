@extends('layouts.app')
@section('title', 'Lớp học')

@section('content')
@php
    $gradeFilters = ['all' => 'Tất cả', '10' => 'Khối 10', '11' => 'Khối 11', '12' => 'Khối 12'];
@endphp

<style>
    .class-index-table {
        width: 100%;
        margin: 0;
        table-layout: fixed;
    }

    .class-index-table th,
    .class-index-table td {
        color: #1f2937;
        font-size: 1rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: normal;
    }

    .class-index-table th {
        color: #111827;
        font-weight: 500;
        background: #fff7ed;
    }

    .class-name-main {
        color: #111827;
        font-size: 1rem;
        font-weight: 600;
    }

    .class-empty-center {
        display: block;
        width: 100%;
        color: #9ca3af;
        text-align: center;
        font-weight: 400;
    }

    .class-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .34rem .68rem;
        border-radius: 999px;
        font-size: .9rem;
        font-weight: 400;
        white-space: nowrap;
    }

    .class-status-pill.active {
        color: #15803d;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .class-status-pill.draft {
        color: #a16207;
        background: #fefce8;
        border: 1px solid #fde68a;
    }

    .class-status-pill.locked,
    .class-status-pill.archived {
        color: #6b7280;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
    }

    .class-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .75rem 1rem;
        border-top: 1px solid #f3f4f6;
        color: #6b7280;
        font-size: .88rem;
        font-weight: 400;
    }

    .class-action-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: .5rem;
        color: #374151;
        font-size: .88rem;
        font-weight: 400;
    }

    .class-action-menu .dropdown-item.danger {
        color: #b91c1c;
    }

    .class-edit-modal .form-label {
        color: #374151;
        font-size: .9rem;
        font-weight: 400;
    }

    .class-edit-modal .form-control,
    .class-edit-modal .form-select {
        border-color: #e5e7eb;
        border-radius: 8px;
        color: #374151;
        font-size: 1rem;
        font-weight: 400;
    }

    .class-edit-modal .form-control:focus,
    .class-edit-modal .form-select:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .22rem rgba(255, 237, 213, .82);
    }
</style>

<x-page-header
    title="Quản lý lớp học"
    subtitle="Quản lý lớp theo năm học, khối, sĩ số và phân công giáo viên chủ nhiệm."
>
    <div class="d-flex align-items-center gap-2">
        @if(! $readOnly)
            <a class="btn btn-primary" href="{{ route('classes.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm lớp</a>
        @endif
        <div class="dropdown">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Lọc theo khối" aria-label="Lọc theo khối">
                <i class="bi bi-funnel"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($gradeFilters as $value => $label)
                    <li>
                        <a class="dropdown-item {{ $selectedGrade === $value ? 'active' : '' }}" href="{{ route('classes.index', array_filter(['school_year_id' => $selectedYearId, 'grade_level' => $value], fn ($item) => $item !== null && $item !== '')) }}">
                            @if($selectedGrade === $value)
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
    <div class="table-responsive class-table-wrap">
        <table class="table class-index-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Lớp học</th>
                    <th style="width: 10%;">Khối</th>
                    <th style="width: 18%;">Năm học</th>
                    <th style="width: 22%;">Giáo viên chủ nhiệm</th>
                    <th style="width: 12%;">Sĩ số</th>
                    <th style="width: 14%;">Trạng thái</th>
                    <th style="width: 6%;" class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($classes as $class)
                @php
                    $deleteCheck = $deleteChecks[(string) $class->getKey()] ?? ['allowed' => false, 'message' => null];
                    $statusVisual = $class->isActive()
                        ? ['class' => 'active', 'label' => '🟢 Đang hoạt động']
                        : ($class->isDraft()
                            ? ['class' => 'draft', 'label' => '🟡 Bản nháp']
                            : ['class' => $class->isArchived() ? 'archived' : 'locked', 'label' => $class->statusLabel()]);
                @endphp
                <tr>
                    <td><span class="class-name-main">{{ $class->name }}</span></td>
                    <td>Khối {{ $class->grade_level }}</td>
                    <td>{{ $class->schoolYear->name ?? '-' }}</td>
                    <td>
                        @if($class->homeroomTeacher)
                            {{ $class->homeroomTeacher->name }}
                        @else
                            <span class="class-empty-center">—</span>
                        @endif
                    </td>
                    <td>{{ $class->currentStudentCount() }} / {{ $class->maxCapacity() }}</td>
                    <td><span class="class-status-pill {{ $statusVisual['class'] }}">{{ $statusVisual['label'] }}</span></td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end" data-action-synced="true">
                            @if(! $readOnly && $class->canEdit())
                                <button type="button" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="modal" data-bs-target="#editClass{{ $class->id }}">
                                    <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                </button>
                            @endif
                            <div class="dropdown">
                                <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end content-action-menu class-action-menu">
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#classDetail{{ $class->id }}">
                                            <i class="bi bi-eye"></i>Xem chi tiết & Học sinh
                                        </button>
                                    </li>
                                    @if(! $readOnly)
                                        <li>
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#transferStudents{{ $class->id }}">
                                                <i class="bi bi-arrow-left-right"></i>Chuyển lớp học sinh
                                            </button>
                                        </li>
                                        @if($class->isDraft())
                                            <li>
                                                <form action="{{ route('classes.activate', $class) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-unlock"></i>Kích hoạt hoạt động
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($class->canLock() && ! $class->isLocked())
                                            <li>
                                                <form action="{{ route('classes.lock', $class) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn khóa lớp học này?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-lock"></i>Khóa / Lưu trữ lớp
                                                    </button>
                                                </form>
                                            </li>
                                        @elseif($class->canArchive())
                                            <li>
                                                <form action="{{ route('classes.archive', $class) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn lưu trữ lớp học này?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-archive"></i>Khóa / Lưu trữ lớp
                                                    </button>
                                                </form>
                                            </li>
                                        @else
                                            <li><span class="dropdown-item text-muted"><i class="bi bi-lock"></i>Khóa / Lưu trữ lớp</span></li>
                                        @endif
                                        @if($deleteCheck['allowed'])
                                            <li>
                                                <form action="{{ route('classes.destroy', $class) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa lớp học này? Hành động này không thể hoàn tác.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item danger">
                                                        <i class="bi bi-trash"></i>Xóa lớp
                                                    </button>
                                                </form>
                                            </li>
                                        @else
                                            <li><span class="dropdown-item text-muted"><i class="bi bi-trash"></i>Xóa lớp</span></li>
                                        @endif
                                    @else
                                        <li><span class="dropdown-item text-muted">Chỉ xem</span></li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7"><div class="empty-state"><i class="bi bi-building"></i>Chưa có lớp học.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="class-footer">
        <span>Hiển thị {{ $classes->count() }} trong tổng số {{ $classes->count() }} lớp học</span>
    </div>
</div>

@foreach($classes as $class)
    @php
        $currentStudents = $classStudents[(string) $class->getKey()] ?? collect();
        $isClassFull = $class->currentStudentCount() >= $class->maxCapacity();
        $cohortLabels = $currentStudents
            ->map(fn ($student) => $student->cohortLabel())
            ->filter()
            ->unique()
            ->values();
        $cohortSummary = $class->cohort ?: ($cohortLabels->count() > 1 ? 'Nhiều niên khóa' : ($cohortLabels->first() ?? '-'));
        $availableTransferClasses = $transferClasses->reject(fn ($targetClass) => (string) $targetClass->getKey() === (string) $class->getKey())->values();
        $usedTeacherIdsForClassYear = collect($homeroomTeacherIdsByYear->get($class->school_year_id, collect()))
            ->reject(fn ($row) => (string) $row->id === (string) $class->getKey())
            ->pluck('homeroom_teacher_id')
            ->map(fn ($id) => (string) $id)
            ->values();
    @endphp

    @if(! $readOnly && $class->canEdit())
        <div class="modal fade content-modal class-edit-modal" id="editClass{{ $class->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('classes.update', $class) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title">Sửa lớp {{ $class->name }}</h5>
                                <div class="text-muted small">{{ $class->schoolYear->name ?? '-' }} • {{ $class->statusLabel() }}</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tên lớp</label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $class->name) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Khối</label>
                                    <select name="grade_level" class="form-select" required>
                                        @foreach([10, 11, 12] as $grade)
                                            <option value="{{ $grade }}" @selected(old('grade_level', $class->grade_level) == $grade)>Khối {{ $grade }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Năm học</label>
                                    <select name="school_year_id" class="form-select" required>
                                        @foreach($years as $year)
                                            <option value="{{ $year->id }}" @selected(old('school_year_id', $class->school_year_id) == $year->id)>{{ $year->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Niên khóa</label>
                                    <input type="text" name="cohort" class="form-control" value="{{ old('cohort', $class->cohort) }}" placeholder="2026 - 2029">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Giáo viên chủ nhiệm</label>
                                    <select name="homeroom_teacher_id" class="form-select">
                                        <option value="">—</option>
                                        @foreach($teachers as $teacher)
                                            @continue($usedTeacherIdsForClassYear->contains((string) $teacher->id) && (string) $class->homeroom_teacher_id !== (string) $teacher->id)
                                            <option value="{{ $teacher->id }}" @selected(old('homeroom_teacher_id', $class->homeroom_teacher_id) == $teacher->id)>{{ $teacher->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sức chứa tối đa</label>
                                    <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $class->maxCapacity()) }}" min="{{ $class->currentStudentCount() }}" max="45" required>
                                    <div class="form-text">{{ $class->currentStudentCount() }} / {{ $class->maxCapacity() }} học sinh</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Trạng thái</label>
                                    <div class="form-control bg-light">{{ $class->statusLabel() }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Cập nhật lớp</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade content-modal class-detail-modal" id="classDetail{{ $class->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="class-detail-header">
                        <div class="class-detail-identity">
                            <h5 class="modal-title">LỚP {{ \Illuminate\Support\Str::upper($class->name) }}</h5>
                            <div>Năm học: {{ $class->schoolYear->name ?? '-' }}</div>
                        </div>
                        <span class="badge {{ $class->statusBadgeClass() }}">{{ $class->statusLabel() }}</span>
                    </div>
                    <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <section class="class-detail-section">
                        <h6>Thông tin bộ khung lớp học</h6>
                        <div class="class-detail-info-grid">
                            <article>
                                <span>Giáo viên chủ nhiệm</span>
                                <strong>{{ $class->homeroomTeacher->name ?? '-' }}</strong>
                            </article>
                            <article>
                                <span>Phòng học cố định</span>
                                <strong>{{ $class->fixedRoom?->name ?? '-' }}</strong>
                            </article>
                            <article>
                                <span>Sĩ số hiện tại</span>
                                <strong>{{ $class->currentStudentCount() }} / {{ $class->maxCapacity() }} học sinh</strong>
                            </article>
                            <article>
                                <span>Niên khóa</span>
                                <strong>{{ $cohortSummary }}</strong>
                            </article>
                        </div>
                    </section>

                    <section class="class-detail-section mt-3">
                        <div class="class-student-title-row">
                            <h6>Danh sách học sinh chính thức</h6>
                            @if(! $readOnly && $class->canEdit())
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignStudents{{ $class->id }}">
                                    <i class="bi bi-person-plus me-1"></i>Xếp học sinh vào lớp
                                </button>
                            @endif
                        </div>
                        <div class="table-responsive class-detail-table-wrap">
                            <table class="table class-detail-table">
                                <thead>
                                    <tr>
                                        <th>Mã HS</th>
                                        <th>Họ tên</th>
                                        <th>Ngày sinh</th>
                                        <th>Giới tính</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($currentStudents as $student)
                                    <tr>
                                        <td class="fw-semibold">{{ $student->student_code }}</td>
                                        <td>{{ $student->name }}</td>
                                        <td>{{ $student->dob?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $student->genderLabel() }}</td>
                                        <td><span class="badge {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state"><i class="bi bi-people"></i>Chưa có học sinh trong lớp.</div>
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng hồ sơ lớp học</button>
                </div>
            </div>
        </div>
    </div>

    @if(! $readOnly)
        <div class="modal fade content-modal" id="transferStudents{{ $class->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('classes.student-assignments.update', $class) }}">
                        @csrf
                        <input type="hidden" name="action" value="transfer">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title">Chuyển học sinh sang lớp khác</h5>
                                <div class="text-muted small">{{ $class->name }} - {{ $class->schoolYear->name ?? '' }}</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Lớp đích</label>
                                <select name="target_class_id" class="form-select" required>
                                    <option value="">Chọn lớp đích</option>
                                    @foreach($availableTransferClasses as $targetClass)
                                        <option value="{{ $targetClass->id }}">
                                            {{ $targetClass->name }} ({{ $targetClass->currentStudentCount() }} / {{ $targetClass->maxCapacity() }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="class-assignment-panel">
                                <div class="class-assignment-title">Chọn học sinh cần chuyển</div>
                                <div class="class-assignment-list">
                                    @forelse($currentStudents as $student)
                                        <label class="class-assignment-row">
                                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}">
                                            <span>
                                                <strong>{{ $student->student_code }}</strong>
                                                <em>{{ $student->name }}</em>
                                                <small>{{ $student->genderLabel() }} - {{ $student->dob?->format('d/m/Y') ?? '-' }}</small>
                                            </span>
                                        </label>
                                    @empty
                                        <div class="empty-state"><i class="bi bi-people"></i>Lớp chưa có học sinh để chuyển.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-primary">Chuyển lớp</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if(! $readOnly && $class->canEdit())
        <div class="modal fade content-modal" id="assignStudents{{ $class->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Phân học sinh vào lớp {{ $class->name }}</h5>
                            <div class="text-muted small">Sĩ số: {{ $class->currentStudentCount() }} / {{ $class->maxCapacity() }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        @if($isClassFull)
                            <div class="alert alert-warning">Lớp đã đủ 45 học sinh, không thể phân thêm học sinh.</div>
                        @endif

                        <div class="class-assignment-board">
                            <div class="class-assignment-panel">
                                <div class="class-assignment-title">Học sinh chưa có lớp</div>
                                <form id="assignStudentsForm{{ $class->id }}" method="POST" action="{{ route('classes.student-assignments.update', $class) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="assign">
                                    <div class="class-assignment-list">
                                        @forelse($unassignedStudents as $student)
                                            <label class="class-assignment-row">
                                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" {{ $isClassFull ? 'disabled' : '' }}>
                                                <span>
                                                    <strong>{{ $student->student_code }}</strong>
                                                    <em>{{ $student->name }}</em>
                                                    <small>{{ $student->genderLabel() }} - {{ $student->cohortLabel() }}</small>
                                                </span>
                                            </label>
                                        @empty
                                            <div class="empty-state"><i class="bi bi-person-check"></i>Không có học sinh chưa có lớp.</div>
                                        @endforelse
                                    </div>
                                </form>
                            </div>

                            <div class="class-assignment-actions">
                                <button type="submit" form="assignStudentsForm{{ $class->id }}" class="btn btn-primary" {{ ($isClassFull || $unassignedStudents->isEmpty()) ? 'disabled' : '' }}>
                                    &gt;&gt;
                                </button>
                                <button type="submit" form="unassignStudentsForm{{ $class->id }}" class="btn btn-secondary" {{ $currentStudents->isEmpty() ? 'disabled' : '' }}>
                                    &lt;&lt;
                                </button>
                            </div>

                            <div class="class-assignment-panel">
                                <div class="class-assignment-title">Học sinh của lớp hiện tại</div>
                                <form id="unassignStudentsForm{{ $class->id }}" method="POST" action="{{ route('classes.student-assignments.update', $class) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="unassign">
                                    <div class="class-assignment-list">
                                        @forelse($currentStudents as $student)
                                            <label class="class-assignment-row">
                                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}">
                                                <span>
                                                    <strong>{{ $student->student_code }}</strong>
                                                    <em>{{ $student->name }}</em>
                                                    <small>{{ $student->genderLabel() }} - {{ $student->cohortLabel() }}</small>
                                                </span>
                                            </label>
                                        @empty
                                            <div class="empty-state"><i class="bi bi-people"></i>Lớp chưa có học sinh.</div>
                                        @endforelse
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
