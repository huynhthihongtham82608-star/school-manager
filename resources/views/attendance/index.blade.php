@extends('layouts.app')
@section('title', 'Điểm danh')

@section('content')
@php
    $canManageAttendance = auth()->user()->isAdmin() || auth()->user()->isHomeroom();
    $statusLabels = \App\Models\AttendanceRecord::STATUSES;
    $statusBadge = [
        'present' => 'bg-success',
        'late' => 'bg-warning text-dark',
        'excused' => 'bg-info',
        'absent' => 'bg-danger',
    ];
@endphp

<div class="page-heading">
    <div>
        <h5>Điểm danh</h5>
        <div class="text-muted">Ghi nhận và theo dõi tình trạng chuyên cần của học sinh theo từng lớp.</div>
    </div>
</div>

@if($canManageAttendance)
    <div class="card mb-3">
        <div class="card-header">Chọn thông tin điểm danh</div>
        <div class="card-body">
            <form method="GET" action="{{ route('attendance.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Năm học</label>
                    <select name="school_year_id" class="form-select" required>
                        <option value="">Chọn năm học</option>
                        @foreach($schoolYears as $year)
                            <option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Học kỳ</label>
                    <select name="semester_id" class="form-select" required>
                        <option value="">Chọn học kỳ</option>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected($selectedSemesterId === $semester->id)>
                                {{ $semester->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lớp</label>
                    <select name="class_id" class="form-select" required>
                        <option value="">Chọn lớp</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected($selectedClassId === $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ngày điểm danh</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}" required>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100" title="Tải danh sách">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedClassId && $selectedSemesterId && $date)
        <div class="card mb-3">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2">
                <div>
                    <div class="fw-semibold">{{ $isEditingSession ? 'Chỉnh sửa điểm danh' : 'Bảng điểm danh' }}</div>
                    <div class="text-muted small">
                        {{ $selectedClass?->name ?? 'Không rõ lớp' }} ·
                        {{ $selectedSemester?->name ?? 'Không rõ học kỳ' }} ·
                        {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}
                    </div>
                </div>
                <button type="button" class="btn btn-secondary align-self-start" data-mark-all-present>
                    <i class="bi bi-check2-circle"></i>
                    Điểm danh tất cả
                </button>
            </div>

            @if($isEditingSession)
                <div class="alert alert-info m-3 mb-0">
                    Dữ liệu điểm danh của lớp trong ngày này đã tồn tại. Hệ thống đang mở ở chế độ chỉnh sửa.
                </div>
            @endif

            <form method="POST" action="{{ route('attendance.store') }}">
                @csrf
                <input type="hidden" name="school_year_id" value="{{ $selectedYearId }}">
                <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                <input type="hidden" name="attendance_date" value="{{ $date }}">

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Học sinh</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($students as $student)
                            @php
                                $record = $existingRecords->get($student->id);
                                $currentStatus = old("status.{$student->id}", $record?->status ?? 'present');
                            @endphp
                            <tr>
                                <td class="fw-semibold">
                                    <div>{{ $student->student_code }}</div>
                                    <div class="text-muted small">{{ $student->name }}</div>
                                </td>
                                <td>
                                    <div class="attendance-status-group">
                                        @foreach($statusLabels as $value => $label)
                                            <label class="attendance-status-option">
                                                <input
                                                    type="radio"
                                                    name="status[{{ $student->id }}]"
                                                    value="{{ $value }}"
                                                    @checked($currentStatus === $value)
                                                    @if($value === 'present') data-attendance-present @endif
                                                >
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <input
                                        name="note[{{ $student->id }}]"
                                        class="form-control"
                                        value="{{ old("note.{$student->id}", $record?->note) }}"
                                        placeholder="Ghi chú nếu có"
                                    >
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state">
                                        <i class="bi bi-person-dash"></i>
                                        Lớp chưa có học sinh.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-body border-top text-end">
                    <button class="btn btn-primary">
                        <i class="bi bi-save"></i>
                        Lưu điểm danh
                    </button>
                </div>
            </form>
        </div>
    @endif
@endif

<div class="card">
    <div class="card-header">Danh sách điểm danh</div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Lớp</th>
                    <th>Ngày</th>
                    <th>Số học sinh</th>
                    <th>Có mặt</th>
                    <th>Đi muộn</th>
                    <th>Có phép</th>
                    <th>Không phép</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($attendanceSessions as $session)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $session->class_name }}</div>
                        <div class="text-muted small">{{ $session->school_year_name }} · {{ $session->semester_name }}</div>
                    </td>
                    <td>{{ optional($session->date)->format('d/m/Y') }}</td>
                    <td>{{ $session->total }}</td>
                    <td><span class="badge bg-success">{{ $session->present }}</span></td>
                    <td><span class="badge bg-warning text-dark">{{ $session->late }}</span></td>
                    <td><span class="badge bg-info">{{ $session->excused }}</span></td>
                    <td><span class="badge bg-danger">{{ $session->absent }}</span></td>
                    <td class="text-end">
                        <button
                            type="button"
                            class="content-action-btn icon-only detail"
                            data-bs-toggle="modal"
                            data-bs-target="#attendanceDetail{{ $session->key }}"
                            title="Xem chi tiết"
                            aria-label="Xem chi tiết"
                        >
                            <i class="bi bi-eye"></i>
                        </button>
                        @if($canManageAttendance && $session->school_year_id && $session->semester_id && $session->class_id)
                            <a
                                href="{{ route('attendance.index', [
                                    'school_year_id' => $session->school_year_id,
                                    'semester_id' => $session->semester_id,
                                    'class_id' => $session->class_id,
                                    'date' => optional($session->date)->toDateString(),
                                ]) }}"
                                class="content-action-btn icon-only edit"
                                title="Chỉnh sửa"
                                aria-label="Chỉnh sửa"
                            >
                                <i class="bi bi-pencil"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-person-check"></i>
                            Chưa có dữ liệu điểm danh.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($attendanceSessions, 'links'))
    <div class="mt-3">{{ $attendanceSessions->links() }}</div>
@endif

@foreach($attendanceSessions as $session)
    <div class="modal fade content-modal" id="attendanceDetail{{ $session->key }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="modal-kicker">Chi tiết điểm danh</div>
                        <h5 class="modal-title">{{ $session->class_name }} · {{ optional($session->date)->format('d/m/Y') }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="text-muted small">Năm học</div>
                            <div class="fw-semibold">{{ $session->school_year_name }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Học kỳ</div>
                            <div class="fw-semibold">{{ $session->semester_name }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Số học sinh</div>
                            <div class="fw-semibold">{{ $session->total }}</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mã học sinh</th>
                                    <th>Họ tên</th>
                                    <th>Trạng thái</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($session->records as $record)
                                <tr>
                                    <td class="fw-semibold">{{ $record->student->student_code ?? 'Không rõ' }}</td>
                                    <td>{{ $record->student->name ?? 'Không rõ' }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadge[$record->status] ?? 'bg-secondary' }}">
                                            {{ $record->statusLabel() }}
                                        </span>
                                    </td>
                                    <td>{{ $record->note ?: 'Không có' }}</td>
                                </tr>
                            @endforeach
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
@endforeach

<script>
    document.querySelectorAll('[data-mark-all-present]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-attendance-present]').forEach((input) => {
                input.checked = true;
            });
        });
    });
</script>
@endsection
