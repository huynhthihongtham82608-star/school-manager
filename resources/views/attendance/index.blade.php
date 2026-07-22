@extends('layouts.app')
@section('title', 'Điểm danh')

@section('content')
@php
    $canManageAttendance = auth()->user()->isAdmin() || (auth()->user()->isTeacher() && ! $readOnly);
    $statusLabels = \App\Models\AttendanceRecord::STATUSES;
    $sessionTypes = \App\Models\AttendanceRecord::SESSION_TYPES;
    $statusBadge = [
        'present' => 'bg-success',
        'late' => 'bg-warning text-dark',
        'excused' => 'bg-info',
        'absent' => 'bg-danger',
    ];
@endphp

<x-page-header
    title="Điểm danh"
    :subtitle="auth()->user()->isStudent()
        ? 'Theo dõi tình trạng chuyên cần của bản thân theo năm học và học kỳ đang chọn.'
        : (auth()->user()->isParent()
            ? 'Theo dõi tình trạng chuyên cần của học sinh đang chọn theo năm học và học kỳ.'
            : 'Ghi nhận và theo dõi tình trạng chuyên cần của học sinh theo từng lớp.')"
/>

@if(auth()->user()->isStudent() || auth()->user()->isParent())
    <div class="student-stat-grid mb-3">
        <div class="student-stat-card">
            <span class="student-stat-icon text-primary"><i class="bi bi-calendar-check"></i></span>
            <div>
                <div class="student-stat-label">Tổng số phiên</div>
                <div class="student-stat-value">{{ $attendanceSummary['total'] ?? 0 }}</div>
            </div>
        </div>
        <div class="student-stat-card">
            <span class="student-stat-icon text-warning"><i class="bi bi-clock-history"></i></span>
            <div>
                <div class="student-stat-label">Đi muộn</div>
                <div class="student-stat-value">{{ $attendanceSummary['late'] ?? 0 }}</div>
            </div>
        </div>
        <div class="student-stat-card">
            <span class="student-stat-icon text-info"><i class="bi bi-patch-check"></i></span>
            <div>
                <div class="student-stat-label">Nghỉ có phép</div>
                <div class="student-stat-value">{{ $attendanceSummary['excused'] ?? 0 }}</div>
            </div>
        </div>
        <div class="student-stat-card">
            <span class="student-stat-icon text-danger"><i class="bi bi-exclamation-circle"></i></span>
            <div>
                <div class="student-stat-label">Nghỉ không phép</div>
                <div class="student-stat-value">{{ $attendanceSummary['absent'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2">
            <div>
                <div class="fw-semibold">Chi tiết chuyên cần</div>
                <div class="text-muted small">Theo dõi từng ngày, từng tiết học và lý do được ghi nhận.</div>
            </div>
            @if(auth()->user()->isParent())
                <a href="{{ route('parent.leave-requests.index') }}" class="btn btn-primary btn-sm align-self-start">
                    <i class="bi bi-envelope-paper me-1"></i>Xin nghỉ học
                </a>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Học sinh</th>
                        <th>Lớp</th>
                        <th>Phiên điểm danh</th>
                        <th>Trạng thái</th>
                        <th>Lý do/Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($attendanceDetailRows as $record)
                    <tr>
                        <td class="fw-semibold">{{ $record->attendance_date?->format('d/m/Y') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $record->student?->name ?? '-' }}</div>
                            <div class="text-muted small">{{ $record->student?->student_code ?? '-' }}</div>
                        </td>
                        <td>{{ $record->classRoom?->name ?? '-' }}</td>
                        <td>
                            <div>{{ $record->session_type === \App\Models\AttendanceRecord::SESSION_PERIOD ? 'Theo tiết học' : 'Theo ngày' }}</div>
                            <div class="text-muted small">{{ $record->displaySessionLabel() }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $statusBadge[$record->status] ?? 'bg-secondary' }}">
                                {{ $record->statusLabel() }}
                            </span>
                        </td>
                        <td>{{ $record->note ?: 'Không có' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-calendar-check"></i>
                                Chưa có dữ liệu chuyên cần.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($canManageAttendance)
    <div class="card mb-3">
        <div class="card-header">Chọn thông tin điểm danh</div>
        <div class="card-body">
            <form method="GET" action="{{ route('attendance.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="school_year_id" value="{{ $selectedYearId }}">
                <div class="col-md-2">
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
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label class="form-label">Kiểu điểm danh</label>
                    <select name="attendance_type" class="form-select" data-attendance-type-select>
                        @foreach($allowedSessionTypes as $typeValue => $typeLabel)
                            <option value="{{ $typeValue }}" @selected($selectedSessionType === $typeValue)>{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3" data-timetable-entry-filter @class(['d-none' => $selectedSessionType !== \App\Models\AttendanceRecord::SESSION_PERIOD])>
                    <label class="form-label">Tiết học</label>
                    <select name="timetable_entry_id" class="form-select" @disabled($selectedSessionType !== \App\Models\AttendanceRecord::SESSION_PERIOD)>
                        @forelse($availableTimetableEntries as $entry)
                            <option value="{{ $entry->id }}" @selected($selectedTimetableEntryId === $entry->id)>
                                {{ $entry->displayPeriod() }} - {{ $entry->subject?->name ?? 'Môn học' }} - {{ $entry->teacher?->name ?? 'Giáo viên' }}
                            </option>
                        @empty
                            <option value="">Không có tiết học phù hợp</option>
                        @endforelse
                    </select>
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
                        {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }} ·
                        {{ $sessionTypes[$selectedSessionType] ?? 'Theo ngày' }}
                        @if($selectedTimetableEntry)
                            · {{ $selectedTimetableEntry->displayPeriod() }} - {{ $selectedTimetableEntry->subject?->name ?? 'Môn học' }}
                        @endif
                    </div>
                </div>
                <button type="button" class="btn btn-secondary align-self-start" data-mark-all-present>
                    <i class="bi bi-check2-circle"></i>
                    Điểm danh tất cả
                </button>
            </div>

            @if($selectedSessionType === \App\Models\AttendanceRecord::SESSION_PERIOD && ! $selectedTimetableEntry)
                <div class="alert alert-warning m-3 mb-0">
                    Không có tiết học phù hợp để điểm danh. Vui lòng kiểm tra thời khóa biểu hoặc chọn ngày khác.
                </div>
            @endif

            @if($isEditingSession)
                <div class="alert alert-info m-3 mb-0">
                    Dữ liệu điểm danh của phiên này đã tồn tại. Hệ thống đang mở ở chế độ chỉnh sửa.
                </div>
            @endif

            <form method="POST" action="{{ route('attendance.store') }}">
                @csrf
                <input type="hidden" name="school_year_id" value="{{ $selectedYearId }}">
                <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                <input type="hidden" name="attendance_date" value="{{ $date }}">
                <input type="hidden" name="attendance_type" value="{{ $selectedSessionType }}">
                <input type="hidden" name="timetable_entry_id" value="{{ $selectedTimetableEntryId }}">

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
                                $isLockedByApprovedLeave = ! auth()->user()->isAdmin()
                                    && $selectedSessionType === \App\Models\AttendanceRecord::SESSION_PERIOD
                                    && ($approvedLeaveStudentIds ?? collect())->contains($student->id);
                                $currentStatus = $isLockedByApprovedLeave
                                    ? 'excused'
                                    : old("status.{$student->id}", $record?->status ?? 'present');
                                $leaveRequest = ($approvedLeaveRequests ?? collect())->get($student->id);
                            @endphp
                            <tr>
                                <td class="fw-semibold">
                                    <div>{{ $student->student_code }}</div>
                                    <div class="text-muted small">{{ $student->name }}</div>
                                </td>
                                <td>
                                    @if($isLockedByApprovedLeave)
                                        <input type="hidden" name="status[{{ $student->id }}]" value="excused">
                                    @endif
                                    <div class="attendance-status-group">
                                        @foreach($statusLabels as $value => $label)
                                            <label class="attendance-status-option">
                                                <input
                                                    type="radio"
                                                    name="status[{{ $student->id }}]"
                                                    value="{{ $value }}"
                                                    @checked($currentStatus === $value)
                                                    @if($value === 'present') data-attendance-present @endif
                                                    @disabled($isLockedByApprovedLeave)
                                                >
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if($isLockedByApprovedLeave)
                                        <div class="text-info small mt-2">
                                            Học sinh đã có đơn nghỉ được GVCN phê duyệt. Giáo viên bộ môn không thể đổi sang vắng không phép.
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <input
                                        name="note[{{ $student->id }}]"
                                        class="form-control"
                                        value="{{ old("note.{$student->id}", $record?->note ?: ($leaveRequest ? 'Đã duyệt đơn xin nghỉ học của phụ huynh. Lý do: ' . $leaveRequest->reason : null)) }}"
                                        placeholder="Ghi chú nếu có"
                                        @readonly($isLockedByApprovedLeave)
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
                    <button class="btn btn-primary" @disabled($students->isEmpty() || ($selectedSessionType === \App\Models\AttendanceRecord::SESSION_PERIOD && ! $selectedTimetableEntry))>
                        <i class="bi bi-save"></i>
                        Lưu điểm danh
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if(($weeklyMatrix['enabled'] ?? false) && $selectedClass)
        <div class="card mb-3">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                <div>
                    <div class="fw-semibold">Ma trận chuyên cần theo tuần</div>
                    <div class="text-muted small">
                        {{ $selectedClass?->name }} · Tuần
                        {{ ($weeklyMatrix['days'] ?? collect())->first()?->format('d/m/Y') }}
                        - {{ ($weeklyMatrix['days'] ?? collect())->last()?->format('d/m/Y') }}
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 small">
                    <span class="badge bg-info">P: Vắng có phép</span>
                    <span class="badge bg-danger">X: Vắng không phép</span>
                    <span class="badge bg-warning text-dark">M: Đi muộn</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Học sinh</th>
                            @foreach(($weeklyMatrix['days'] ?? collect()) as $day)
                                <th class="text-center">
                                    <div>{{ ['T2','T3','T4','T5','T6','T7'][$loop->index] ?? 'Ngày' }}</div>
                                    <div class="text-muted small">{{ $day->format('d/m') }}</div>
                                </th>
                            @endforeach
                            <th class="text-center">Tổng vắng</th>
                            <th class="text-center">Đi muộn</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse(($weeklyMatrix['rows'] ?? collect()) as $row)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $row['student']->student_code }}</div>
                                <div class="text-muted small">{{ $row['student']->name }}</div>
                            </td>
                            @foreach(($weeklyMatrix['days'] ?? collect()) as $day)
                                @php($cell = $row['cells'][$day->toDateString()] ?? ['excused' => 0, 'absent' => 0, 'late' => 0, 'total' => 0])
                                <td class="text-center">
                                    @if($cell['total'] <= 0)
                                        <span class="text-muted">-</span>
                                    @else
                                        <div class="d-flex justify-content-center flex-wrap gap-1">
                                            @if($cell['excused'] > 0)<span class="badge bg-info">P {{ $cell['excused'] }}</span>@endif
                                            @if($cell['absent'] > 0)<span class="badge bg-danger">X {{ $cell['absent'] }}</span>@endif
                                            @if($cell['late'] > 0)<span class="badge bg-warning text-dark">M {{ $cell['late'] }}</span>@endif
                                            @if($cell['excused'] + $cell['absent'] + $cell['late'] === 0)<span class="badge bg-success">Đủ</span>@endif
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center fw-semibold">{{ $row['total_absent_periods'] }}</td>
                            <td class="text-center fw-semibold">{{ $row['total_late'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + (($weeklyMatrix['days'] ?? collect())->count()) }}">
                                <div class="empty-state"><i class="bi bi-calendar-week"></i>Chưa có học sinh để lập ma trận chuyên cần.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(($pendingLeaveRequests ?? collect())->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <div class="fw-semibold">Duyệt đơn xin nghỉ</div>
                <div class="text-muted small">Các đơn đang chờ giáo viên chủ nhiệm xử lý.</div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Học sinh</th>
                            <th>Ngày nghỉ</th>
                            <th>Phụ huynh</th>
                            <th>Lý do</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($pendingLeaveRequests as $requestItem)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $requestItem->student?->student_code }}</div>
                                <div class="text-muted small">{{ $requestItem->student?->name }}</div>
                            </td>
                            <td class="fw-semibold">{{ $requestItem->leave_date?->format('d/m/Y') }}</td>
                            <td>
                                <div>{{ $requestItem->parent?->name ?? '-' }}</div>
                                <div class="text-muted small">{{ $requestItem->parent?->phone }}</div>
                            </td>
                            <td style="max-width: 360px;">{{ $requestItem->reason }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('teacher.leave-requests.approve', $requestItem) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-success">Phê duyệt</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectLeaveFromAttendance{{ $requestItem->id }}">
                                    Từ chối
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
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
                    <th>Phiên điểm danh</th>
                    <th>Số học sinh</th>
                    <th>Có mặt</th>
                    <th>Đi muộn</th>
                    <th>Vắng có phép</th>
                    <th>Vắng không phép</th>
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
                    <td>
                        <div class="fw-semibold">{{ $session->session_type === \App\Models\AttendanceRecord::SESSION_PERIOD ? 'Theo tiết học' : 'Theo ngày' }}</div>
                        <div class="text-muted small">{{ $session->session_label }}</div>
                    </td>
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
                                    'attendance_type' => $session->session_type,
                                    'timetable_entry_id' => $session->timetable_entry_id,
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
                    <td colspan="9">
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
                        <div class="col-md-12">
                            <div class="text-muted small">Phiên điểm danh</div>
                            <div class="fw-semibold">{{ $session->session_label }}</div>
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

@foreach(($pendingLeaveRequests ?? collect()) as $requestItem)
    <div class="modal fade content-modal" id="rejectLeaveFromAttendance{{ $requestItem->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('teacher.leave-requests.reject', $requestItem) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <div>
                            <div class="modal-kicker">Đơn nghỉ học</div>
                            <h5 class="modal-title">Từ chối đơn xin nghỉ</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Từ chối đơn nghỉ ngày <strong>{{ $requestItem->leave_date?->format('d/m/Y') }}</strong> của <strong>{{ $requestItem->student?->name }}</strong>.</p>
                        <label class="form-label">Lý do từ chối</label>
                        <textarea name="homeroom_note" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button class="btn btn-danger">Từ chối</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.querySelectorAll('[data-attendance-type-select]').forEach((select) => {
        const form = select.closest('form');
        const wrapper = form?.querySelector('[data-timetable-entry-filter]');
        const timetableSelect = wrapper?.querySelector('select');

        const syncTimetableField = () => {
            const isPeriod = select.value === @json(\App\Models\AttendanceRecord::SESSION_PERIOD);
            wrapper?.classList.toggle('d-none', ! isPeriod);

            if (timetableSelect) {
                timetableSelect.disabled = ! isPeriod;
            }
        };

        select.addEventListener('change', syncTimetableField);
        syncTimetableField();
    });

    document.querySelectorAll('[data-mark-all-present]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-attendance-present]:not(:disabled)').forEach((input) => {
                input.checked = true;
            });
        });
    });
</script>
@endsection
