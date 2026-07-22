@extends('layouts.app')
@section('title', 'Lớp học')

@section('content')
@php
    $gradeFilters = ['all' => 'Tất cả', '10' => 'Khối 10', '11' => 'Khối 11', '12' => 'Khối 12'];
@endphp

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
        <table class="table">
            <thead>
                <tr>
                    <th>Mã lớp</th>
                    <th>Tên lớp</th>
                    <th>Khối</th>
                    <th>Năm học</th>
                    <th>GVCN</th>
                    <th>Sĩ số</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($classes as $class)
                @php
                    $deleteCheck = $deleteChecks[(string) $class->getKey()] ?? ['allowed' => false, 'message' => null];
                @endphp
                <tr>
                    <td class="fw-semibold">{{ strtoupper(str_replace(' ', '', $class->name)) }}</td>
                    <td>{{ $class->name }}</td>
                    <td>{{ $class->grade_level }}</td>
                    <td>{{ $class->schoolYear->name ?? '-' }}</td>
                    <td>{{ $class->homeroomTeacher->name ?? '-' }}</td>
                    <td>{{ $class->currentStudentCount() }} / {{ $class->maxCapacity() }}</td>
                    <td><span class="badge {{ $class->statusBadgeClass() }}">{{ $class->statusLabel() }}</span></td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            @if(! $readOnly && $class->canEdit())
                                <a href="{{ route('classes.edit', $class) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                </a>
                            @endif
                            <div class="dropdown">
                                <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#classStudents{{ $class->id }}">
                                            <i class="bi bi-people me-2"></i>Xem danh sách học sinh
                                        </button>
                                    </li>
                                    @if(! $readOnly)
                                        @if($class->canEdit())
                                            <li>
                                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#assignStudents{{ $class->id }}">
                                                    <i class="bi bi-person-plus me-2"></i>Phân học sinh vào lớp
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                        @endif
                                        <li>
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#transferStudents{{ $class->id }}">
                                                <i class="bi bi-arrow-left-right me-2"></i>Chuyển học sinh sang lớp khác
                                            </button>
                                        </li>
                                        @if(! $class->isActive() && ! $class->isArchived())
                                            <li>
                                                <form action="{{ route('classes.activate', $class) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-check-circle me-2"></i>Kích hoạt
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($class->canLock() && ! $class->isLocked())
                                            <li>
                                                <form action="{{ route('classes.lock', $class) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-lock me-2"></i>Khóa
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($class->canArchive())
                                            <li>
                                                <form action="{{ route('classes.archive', $class) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-archive me-2"></i>Lưu trữ
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($deleteCheck['allowed'])
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('classes.destroy', $class) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash me-2"></i>Xóa
                                                    </button>
                                                </form>
                                            </li>
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
                    <td colspan="8"><div class="empty-state"><i class="bi bi-building"></i>Chưa có lớp học.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
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
        $cohortSummary = $cohortLabels->count() > 1 ? 'Nhiều niên khóa' : ($cohortLabels->first() ?? '-');
        $availableTransferClasses = $transferClasses->reject(fn ($targetClass) => (string) $targetClass->getKey() === (string) $class->getKey())->values();
    @endphp

    <div class="modal fade content-modal" id="classStudents{{ $class->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Danh sách học sinh - {{ $class->name }}</h5>
                        <div class="text-muted small">
                            {{ $class->schoolYear->name ?? '' }}
                        </div>
                        <div class="text-muted small">Niên khóa: {{ $cohortSummary }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mã học sinh</th>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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
