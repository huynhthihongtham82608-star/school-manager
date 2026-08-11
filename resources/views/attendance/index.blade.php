@extends('layouts.app')
@section('title', 'Điểm danh')

@section('content')
@php
    $currentUser = auth()->user();
    $canViewAttendanceRoster = $currentUser->isAdmin() || $currentUser->isStaff() || $currentUser->isTeacher();
    $attendanceEditWindowOpen = (bool) ($attendanceEditWindowOpen ?? false);
    $attendanceEditDeadlineLabel = isset($attendanceEditDeadline) && $attendanceEditDeadline
        ? $attendanceEditDeadline->format('H:i d/m/Y')
        : null;
    $editableAttendanceClassIds = $currentUser->isTeacher()
        && ! $currentUser->isAdmin()
        && ! $currentUser->isStaff()
        && $currentUser->teacher
        ? $currentUser->teacher->homeroomClasses()->pluck('id')->map(fn ($id) => (string) $id)
        : collect();
    $isHomeroomForSelectedClass = $selectedClass
        && $currentUser->isHomeroom()
        && $editableAttendanceClassIds->contains((string) $selectedClass->id);
    $attendanceWindowExpiredForHomeroom = $selectedClass
        && $isHomeroomForSelectedClass
        && in_array($selectedSessionType, [\App\Models\AttendanceRecord::SESSION_MORNING, \App\Models\AttendanceRecord::SESSION_AFTERNOON], true)
        && ! $attendanceEditWindowOpen;
    $isAdminAttendanceView = $currentUser->isAdmin() || $currentUser->isStaff();
    $isSubjectTeacherAttendanceView = $currentUser->isTeacher()
        && ! $isAdminAttendanceView
        && ! $isHomeroomForSelectedClass;
    $isPeriodAttendanceMode = $selectedSessionType === \App\Models\AttendanceRecord::SESSION_PERIOD;
    $canEditPeriodAttendanceRoster = $isSubjectTeacherAttendanceView
        && $isPeriodAttendanceMode
        && $selectedClass
        && $selectedTimetableEntry
        && ! $readOnly
        && $attendanceEditWindowOpen;
    $canEditAttendanceRoster = $currentUser->isTeacher()
        && ! $currentUser->isAdmin()
        && ! $currentUser->isStaff()
        && ! $readOnly
        && $selectedClass
        && (
            (
                in_array($selectedSessionType, [\App\Models\AttendanceRecord::SESSION_MORNING, \App\Models\AttendanceRecord::SESSION_AFTERNOON], true)
                && $attendanceEditWindowOpen
                && $isHomeroomForSelectedClass
            )
            || $canEditPeriodAttendanceRoster
        );
    $statusLabels = \App\Models\AttendanceRecord::STATUSES;
    $sessionTypes = \App\Models\AttendanceRecord::SESSION_TYPES;
    $statusBadge = [
        'present' => 'bg-success',
        'late' => 'bg-warning text-dark',
        'excused' => 'bg-info',
        'absent' => 'bg-danger',
    ];
    $inlineAttendanceStatuses = [
        'present' => ['label' => 'CÃ³ máº·t', 'code' => 'V', 'class' => 'present'],
        'late' => ['label' => 'Äi muá»™n', 'code' => 'M', 'class' => 'late'],
        'absent' => ['label' => 'Váº¯ng máº·t', 'code' => 'X', 'class' => 'absent'],
    ];
@endphp

<style>
    .attendance-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: .75rem;
        width: 100%;
        padding: 1rem;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    @media (min-width: 992px) {
        .attendance-toolbar {
            flex-wrap: nowrap;
        }
    }

    .attendance-toolbar-field {
        min-width: 150px;
        flex: 0 0 auto;
    }

    .attendance-toolbar-field.search {
        min-width: 230px;
        flex: 1 1 230px;
    }

    .attendance-toolbar-field.period {
        min-width: 260px;
        flex: 1 1 260px;
    }

    .attendance-leave-ribbon-card {
        border: 1px solid #fed7aa;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .attendance-leave-ribbon-card .card-header {
        background: rgba(255, 247, 237, .75);
        border-bottom: 1px solid #ffedd5;
    }

    .attendance-leave-ribbon-card tbody tr {
        background: linear-gradient(90deg, rgba(255, 247, 237, .7), #fff 34%);
    }

    .attendance-leave-ribbon-card td,
    .attendance-leave-ribbon-card th {
        font-weight: 400;
        font-size: .95rem;
        color: #374151;
        text-align: left;
    }

    .attendance-toolbar label {
        color: #374151;
        font-size: .88rem;
        font-weight: 400;
    }

    .attendance-toolbar .form-control,
    .attendance-toolbar .form-select,
    .attendance-note-input {
        border-color: #e5e7eb;
        border-radius: 8px;
        color: #374151;
        font-size: 1rem;
        font-weight: 400;
    }

    .attendance-toolbar .form-control:focus,
    .attendance-toolbar .form-select:focus,
    .attendance-note-input:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .25rem rgba(255, 237, 213, .65);
    }

    .attendance-search-wrap {
        position: relative;
    }

    .attendance-search-wrap i {
        position: absolute;
        left: .75rem;
        top: 50%;
        color: #9ca3af;
        transform: translateY(-50%);
    }

    .attendance-search-wrap .form-control {
        padding-left: 2.15rem;
        border-color: rgba(254, 215, 170, .5);
        background: rgba(249, 250, 251, .8);
        color: #374151;
    }

    .attendance-search-wrap .form-control::placeholder {
        color: #9ca3af;
        font-size: .88rem;
        font-weight: 400;
    }

    .attendance-search-wrap .form-control:focus {
        border-color: #f97316;
        background: #fff;
        box-shadow: 0 0 0 .25rem rgba(255, 237, 213, .5);
    }

    .attendance-register-table th,
    .attendance-register-table td {
        color: #1f2937;
        font-size: 1rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: nowrap;
    }

    .attendance-register-table th {
        color: #111827;
        font-weight: 500;
        background: #fff;
    }

    .attendance-register-card {
        border: 1px solid #ffedd5;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .attendance-register-card > .card-header {
        border-bottom: 1px solid #ffedd5;
        background: rgba(255, 247, 237, .32);
    }

    .attendance-register-table.subject-period th,
    .attendance-register-table.subject-period td {
        padding: .58rem .75rem;
        font-size: .92rem;
        font-weight: 400;
        text-align: left;
        white-space: nowrap;
    }

    .attendance-register-table.subject-period th:last-child,
    .attendance-register-table.subject-period td:last-child {
        text-align: right;
    }

    .attendance-period-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: .35rem;
    }

    .attendance-period-pill {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .25rem .35rem;
        border: 0;
        border-radius: 0;
        color: #6b7280;
        background: transparent;
        font-size: .78rem;
        font-weight: 400;
        line-height: 1.2;
        cursor: pointer;
        transition: all .16s ease;
    }

    .attendance-period-pill:hover {
        color: #ea580c;
        background: transparent;
    }

    .attendance-period-pill.present {
        color: #15803d;
    }

    .attendance-period-pill.late {
        color: #d97706;
    }

    .attendance-period-pill.absent {
        color: #dc2626;
    }

    .attendance-period-pill.active.present {
        color: #15803d;
        background: transparent;
        font-weight: 500;
    }

    .attendance-period-pill.active.late {
        color: #d97706;
        background: transparent;
        font-weight: 500;
    }

    .attendance-period-pill.active.absent {
        color: #dc2626;
        background: transparent;
        font-weight: 500;
    }

    .attendance-inline-option input:disabled + span,
    .attendance-row-readonly .attendance-note-input {
        cursor: not-allowed;
        opacity: .72;
    }

    .attendance-readonly-status {
        display: inline-flex;
        align-items: center;
        padding: .34rem .65rem;
        border-radius: 999px;
        font-size: .9rem;
        font-weight: 400;
        white-space: nowrap;
    }

    .attendance-readonly-status.present {
        color: #15803d;
        background: #f0fdf4;
    }

    .attendance-readonly-status.late {
        color: #c2410c;
        background: #fff7ed;
    }

    .attendance-readonly-status.absent {
        color: #b91c1c;
        background: #fef2f2;
    }

    .attendance-readonly-status.excused {
        color: #1d4ed8;
        background: #eff6ff;
    }

    .attendance-readonly-status.empty {
        color: #9ca3af;
        background: #f9fafb;
    }

    .attendance-readonly-status.expired {
        color: #6b7280;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
    }

    .attendance-approved-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .25rem .62rem;
        border: 1px solid #bfdbfe;
        border-radius: 6px;
        color: #1d4ed8;
        background: #eff6ff;
        font-size: .92rem;
        font-weight: 400;
        white-space: nowrap;
    }

    .attendance-save-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .attendance-save-count {
        color: #6b7280;
        font-size: .88rem;
        font-weight: 400;
    }

    .attendance-form[aria-disabled="true"] {
        pointer-events: none;
    }

    .attendance-readonly-note {
        color: #6b7280;
        font-size: .88rem;
        font-weight: 400;
    }

    .attendance-note-input:disabled {
        color: #6b7280;
        background: #f3f4f6;
        cursor: not-allowed;
    }

    .attendance-window-note {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .36rem .65rem;
        border: 1px solid #fed7aa;
        border-radius: 6px;
        color: #c2410c;
        background: #fff7ed;
        font-size: .88rem;
        font-weight: 400;
    }

    .attendance-parent-leave-card .form-control,
    .attendance-parent-leave-card .form-select {
        border-color: #e5e7eb;
        border-radius: 8px;
        color: #374151;
        font-size: 1rem;
        font-weight: 400;
    }

    .attendance-parent-leave-card .form-control:focus,
    .attendance-parent-leave-card .form-select:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .25rem rgba(255, 237, 213, .55);
    }

    .attendance-session-table {
        width: 100%;
        table-layout: fixed;
    }

    .attendance-session-table th,
    .attendance-session-table td {
        color: #1f2937;
        font-size: 1rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: normal;
    }

    .attendance-session-table th {
        color: #111827;
        font-weight: 500;
        background: #fff7ed;
    }

    .attendance-session-subtext {
        color: #6b7280;
        font-size: .88rem;
        font-weight: 400;
    }

    .attendance-session-stats-cell {
        white-space: nowrap !important;
    }

    .attendance-session-stats {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        white-space: nowrap;
    }

    .attendance-session-stat-label {
        color: #6b7280;
        font-size: .875rem;
        font-weight: 400;
    }

    .attendance-session-stat-value {
        margin-left: .22rem;
        font-weight: 600;
    }

    .attendance-session-stat-value.total {
        color: #111827;
    }

    .attendance-session-stat-value.present {
        color: #16a34a;
    }

    .attendance-session-stat-value.late {
        color: #ea580c;
    }

    .attendance-session-stat-value.excused {
        color: #2563eb;
    }

    .attendance-session-stat-value.absent {
        color: #dc2626;
    }

    .attendance-session-stat-separator {
        color: #e5e7eb;
        font-weight: 400;
        margin: 0 .38rem;
    }

    .text-gray-200 { color: #e5e7eb !important; }
    .text-gray-500 { color: #6b7280 !important; }
    .text-gray-900 { color: #111827 !important; }
    .text-green-600 { color: #16a34a !important; }
    .text-orange-600 { color: #ea580c !important; }
    .text-blue-600 { color: #2563eb !important; }
    .text-red-600 { color: #dc2626 !important; }
    .mx-1\.5 { margin-left: 0.375rem !important; margin-right: 0.375rem !important; }
    .text-sm { font-size: 0.875rem !important; line-height: 1.25rem !important; }
    .font-normal { font-weight: 400 !important; }
    .font-semibold { font-weight: 600 !important; }
    .whitespace-nowrap { white-space: nowrap !important; }
    .text-left { text-align: left !important; }

    .attendance-session-table tbody tr:hover {
        background-color: #fff7ed !important;
    }
    .attendance-session-table tbody tr:hover .text-gray-900 { color: #111827 !important; }
    .attendance-session-table tbody tr:hover .text-green-600 { color: #16a34a !important; }
    .attendance-session-table tbody tr:hover .text-orange-600 { color: #ea580c !important; }
    .attendance-session-table tbody tr:hover .text-blue-600 { color: #2563eb !important; }
    .attendance-session-table tbody tr:hover .text-red-600 { color: #dc2626 !important; }
    .attendance-session-table tbody tr:hover .text-gray-500 { color: #6b7280 !important; }
    .attendance-session-table tbody tr:hover .text-gray-200 { color: #e5e7eb !important; }

    .attendance-session-badge,
    .attendance-status-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        width: fit-content;
        padding: .34rem .68rem;
        border-radius: 999px;
        font-size: .9rem;
        font-weight: 400;
        white-space: nowrap;
    }

    .attendance-session-badge.morning {
        color: #c2410c;
        background: #fff7ed;
        border: 1px solid #fed7aa;
    }

    .attendance-session-badge.afternoon {
        color: #1d4ed8;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }

    .attendance-session-badge.period {
        color: #ea580c;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 8px;
    }

    .attendance-status-badge.done {
        color: #15803d;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .attendance-status-badge.pending {
        color: #a16207;
        background: #fefce8;
        border: 1px solid #fde68a;
    }

    .attendance-session-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .5rem;
        width: 2.25rem;
        height: 2.25rem;
        border: 1px solid #fed7aa;
        border-radius: 6px;
        color: #ea580c;
        background: rgba(255, 247, 237, .5);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
        cursor: pointer;
        transition: all .18s ease;
    }

    .attendance-session-action:hover,
    .attendance-session-action:focus {
        color: #c2410c;
        background: #ffedd5;
        border-color: #fdba74;
        outline: none;
        box-shadow: 0 0 0 .2rem rgba(255, 237, 213, .7);
    }

    .attendance-session-action:disabled {
        color: #9ca3af;
        background: #f3f4f6;
        border-color: #e5e7eb;
        cursor: not-allowed;
    }

    .attendance-session-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .75rem 1rem;
        border-top: 1px solid #f3f4f6;
    }
</style>

<x-page-header
    title="Điểm danh"
    :subtitle="auth()->user()->isStudent()
        ? 'Theo dÃµi tÃ¬nh tráº¡ng chuyÃªn cáº§n cá»§a báº£n thÃ¢n theo nÄƒm há»c vÃ  há»c ká»³ Ä‘ang chá»n.'
        : (auth()->user()->isParent()
            ? 'Theo dÃµi tÃ¬nh tráº¡ng chuyÃªn cáº§n cá»§a há»c sinh Ä‘ang chá»n theo nÄƒm há»c vÃ  há»c ká»³.'
            : 'Ghi nháº­n vÃ  theo dÃµi tÃ¬nh tráº¡ng chuyÃªn cáº§n cá»§a há»c sinh theo tá»«ng lá»›p.')"
>
    @if($canViewAttendanceRoster && ! $isSubjectTeacherAttendanceView)
        <x-bulk-excel-actions
            module="attendance"
            :context="[
                'school_year_id' => $selectedYearId,
                'class_id' => $selectedClassId,
                'semester_id' => $selectedSemesterId,
                'attendance_date' => $date,
                'attendance_type' => $selectedSessionType,
            ]"
            :allow-import="$canEditAttendanceRoster"
        />
    @endif
</x-page-header>

@if(auth()->user()->isStudent() || auth()->user()->isParent())
    <div class="student-stat-grid mb-3">
        <div class="student-stat-card">
            <span class="student-stat-icon text-primary"><i class="bi bi-calendar-check"></i></span>
            <div>
                <div class="student-stat-label">Tá»•ng ngÃ y Ä‘i há»c</div>
                <div class="student-stat-value">{{ $attendanceSummary['present'] ?? 0 }}</div>
            </div>
        </div>
        <div class="student-stat-card">
            <span class="student-stat-icon text-warning"><i class="bi bi-clock-history"></i></span>
            <div>
                <div class="student-stat-label">Äi muá»™n</div>
                <div class="student-stat-value">{{ $attendanceSummary['late'] ?? 0 }}</div>
            </div>
        </div>
        <div class="student-stat-card">
            <span class="student-stat-icon text-info"><i class="bi bi-patch-check"></i></span>
            <div>
                <div class="student-stat-label">Nghá»‰ cÃ³ phÃ©p</div>
                <div class="student-stat-value">{{ $attendanceSummary['excused'] ?? 0 }}</div>
            </div>
        </div>
        <div class="student-stat-card">
            <span class="student-stat-icon text-danger"><i class="bi bi-exclamation-circle"></i></span>
            <div>
                <div class="student-stat-label">Nghá»‰ khÃ´ng phÃ©p</div>
                <div class="student-stat-value">{{ $attendanceSummary['absent'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    @if(auth()->user()->isParent())
        <div class="card attendance-parent-leave-card mb-3">
            <div class="card-header">
                <div class="fw-semibold">ÄÆ¡n xin nghá»‰ há»c</div>
                <div class="text-muted small">Gá»­i trá»±c tiáº¿p Ä‘áº¿n giÃ¡o viÃªn chá»§ nhiá»‡m cá»§a há»c sinh Ä‘ang chá»n.</div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('parent.leave-requests.store') }}" class="row g-3 align-items-end">
                    @csrf
                    <input type="hidden" name="return_to" value="attendance">
                    <div class="col-md-3">
                        <label class="form-label">Há»c sinh</label>
                        <select name="student_id" class="form-select" required>
                            @foreach(($parentLeaveChildren ?? collect()) as $child)
                                <option value="{{ $child->id }}" @selected(($selectedParentStudent?->id ?? null) === $child->id)>
                                    {{ $child->student_code }} - {{ $child->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">NgÃ y nghá»‰</label>
                        <input type="date" name="leave_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">LÃ½ do</label>
                        <input type="text" name="reason" class="form-control" maxlength="2000" placeholder="Nháº­p lÃ½ do xin nghá»‰ há»c" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-send me-1"></i>
                            Gá»­i Ä‘Æ¡n
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if(auth()->user()->isParent())
    <div class="card mb-3">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2">
            <div>
                <div class="fw-semibold">Chi tiáº¿t chuyÃªn cáº§n</div>
                <div class="text-muted small">Theo dÃµi tá»«ng ngÃ y, tá»«ng tiáº¿t há»c vÃ  lÃ½ do Ä‘Æ°á»£c ghi nháº­n.</div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>NgÃ y</th>
                        <th>Há»c sinh</th>
                        <th>Lá»›p</th>
                        <th>PhiÃªn Ä‘iá»ƒm danh</th>
                        <th>Tráº¡ng thÃ¡i</th>
                        <th>LÃ½ do/Ghi chÃº</th>
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
                            <div>{{ $record->sessionTypeLabel() }}</div>
                            <div class="text-muted small">{{ $record->displaySessionLabel() }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $statusBadge[$record->status] ?? 'bg-secondary' }}">
                                {{ $record->statusLabel() }}
                            </span>
                        </td>
                        <td>{{ $record->note ?: 'KhÃ´ng cÃ³' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-calendar-check"></i>
                                ChÆ°a cÃ³ dá»¯ liá»‡u chuyÃªn cáº§n.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
@endif

@if($canViewAttendanceRoster)
    <form method="GET" action="{{ route('attendance.index') }}" class="attendance-toolbar mb-3">
        <input type="hidden" name="school_year_id" value="{{ $selectedYearId }}">
        @if(! $isAdminAttendanceView && ! $isSubjectTeacherAttendanceView && $selectedClassId)
            <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
        @endif
        @if(! $isAdminAttendanceView && ! $isSubjectTeacherAttendanceView && $selectedSemesterId)
            <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
        @endif
        @if($isSubjectTeacherAttendanceView)
            <input type="hidden" name="attendance_type" value="{{ \App\Models\AttendanceRecord::SESSION_PERIOD }}">
            <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
            <div class="attendance-toolbar-field">
                <label class="form-label text-xs text-gray-600 font-normal">Chọn Lớp Dạy</label>
                <select name="class_id" class="form-select text-sm font-normal bg-orange-50/60 border border-orange-200 text-orange-900 rounded-lg px-3 py-1.5 focus:border-orange-400 focus:ring-0 cursor-pointer" data-attendance-period-class-id required>
                    <option value="">[ Chọn Lớp Dạy ▾ ]</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected($selectedClassId === $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="attendance-toolbar-field">
                <label class="form-label text-xs text-gray-600 font-normal">Chọn Ngày</label>
                <input type="date" name="date" class="form-control text-sm font-normal bg-orange-50/60 border border-orange-200 text-orange-900 rounded-lg px-3 py-1.5 focus:border-orange-400 focus:ring-0" value="{{ $date }}" required>
            </div>
        @endif
        @unless($isSubjectTeacherAttendanceView)
        <div class="attendance-toolbar-field search">
            <label class="form-label">TÃ¬m kiáº¿m</label>
            <div class="attendance-search-wrap">
                <i class="bi bi-search"></i>
                <input type="search" class="form-control" placeholder="TÃ¬m mÃ£ HS hoáº·c há» tÃªn" data-attendance-search>
            </div>
        </div>
        <div class="attendance-toolbar-field">
            <label class="form-label">NgÃ y Ä‘iá»ƒm danh</label>
            <input type="date" name="date" class="form-control" value="{{ $date }}" required>
        </div>
        @if(! $isSubjectTeacherAttendanceView)
        <div class="attendance-toolbar-field">
            <label class="form-label">PhiÃªn Ä‘iá»ƒm danh</label>
            <select name="attendance_type" class="form-select" data-attendance-type-select>
                @foreach($allowedSessionTypes as $typeValue => $typeLabel)
                    <option value="{{ $typeValue }}" @selected($selectedSessionType === $typeValue)>{{ $typeLabel }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @endunless
        @if($isAdminAttendanceView)
            <div class="attendance-toolbar-field">
                <label class="form-label">Há»c ká»³</label>
                <select name="semester_id" class="form-select" required>
                    <option value="">Chá»n há»c ká»³</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected($selectedSemesterId === $semester->id)>
                            {{ $semester->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="attendance-toolbar-field">
                <label class="form-label">Lá»›p</label>
                <select name="class_id" class="form-select" required>
                    <option value="">Chá»n lá»›p</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected($selectedClassId === $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="attendance-toolbar-field period {{ $isSubjectTeacherAttendanceView || $isPeriodAttendanceMode ? '' : 'd-none' }}" data-timetable-entry-filter>
            <label class="form-label text-xs text-gray-600 font-normal">Chọn Tiết Học</label>
            <select name="timetable_entry_id" class="form-select text-sm font-normal bg-orange-50/60 border border-orange-200 text-orange-900 rounded-lg px-3 py-1.5 focus:border-orange-400 focus:ring-0 cursor-pointer">
                <option value="">[ Chọn Tiết Học ▾ ]</option>
                @if($availableTimetableEntries->isNotEmpty())
                    @foreach($availableTimetableEntries as $entry)
                        <option value="{{ $entry->id }}" data-class-id="{{ $entry->timetable?->class_id }}" @selected($selectedTimetableEntryId === $entry->id)>
                            {{ $entry->displayPeriod() }} - {{ $entry->subject?->name ?? 'Môn học' }}{{ $entry->timetable?->classRoom?->name ? ' • Lớp ' . $entry->timetable->classRoom->name : '' }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="attendance-toolbar-field" style="min-width: 52px;">
            <button class="btn btn-primary w-100" title="Táº£i danh sÃ¡ch">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    @if($selectedClassId && $selectedSemesterId && $date)
        <div class="card mb-3 attendance-register-card" id="attendance-register">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2">
                <div>
                    <div class="fw-semibold">{{ $isEditingSession ? 'Cập nhật phiên điểm danh' : 'Bảng điểm danh' }}</div>
                    <div class="text-muted small">
                        {{ $selectedClass?->name ?? 'Không rõ lớp' }} •
                        {{ $selectedSemester?->name ?? 'Không rõ học kỳ' }} •
                        {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }} •
                        {{ $sessionTypes[$selectedSessionType] ?? 'Theo ngày' }}
                        @if($selectedTimetableEntry)
                            • {{ $selectedTimetableEntry->displayPeriod() }} - {{ $selectedTimetableEntry->subject?->name ?? 'Môn học' }}
                        @endif
                    </div>
                </div>
                @if($canEditAttendanceRoster)
                    <button type="button" class="btn btn-secondary align-self-start" data-mark-all-present>
                        <i class="bi bi-check2-circle"></i>
                        Điểm danh tất cả
                    </button>
                @elseif($attendanceWindowExpiredForHomeroom)
                    <span class="attendance-window-note align-self-start">
                        <i class="bi bi-lock"></i>
                        Đã quá 24 giờ, khóa sửa từ {{ $attendanceEditDeadlineLabel }}
                    </span>
                @else
                    <span class="attendance-readonly-note align-self-start">
                        Chế độ chỉ xem, không cho phép sửa trạng thái điểm danh.
                    </span>
                @endif
            </div>

            @if($selectedSessionType === \App\Models\AttendanceRecord::SESSION_PERIOD && ! $selectedTimetableEntry)
                <div class="alert alert-warning m-3 mb-0">
                    Không có tiết học phù hợp để điểm danh. Vui lòng kiểm tra thời khóa biểu hoặc chọn ngày khác.
                </div>
            @endif

            @if($isEditingSession && $canEditAttendanceRoster)
                <div class="alert alert-info m-3 mb-0">
                    Dữ liệu điểm danh của phiên này đã tồn tại. Hệ thống đang mở ở chế độ chỉnh sửa.
                </div>
            @elseif($isEditingSession && $attendanceWindowExpiredForHomeroom)
                <div class="alert alert-warning m-3 mb-0">
                    Dữ liệu điểm danh của ngày này đã quá hạn 24 giờ. GVCN chỉ được xem, không thể chỉnh sửa lịch sử quá khứ.
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
                    <table class="table attendance-register-table {{ $isSubjectTeacherAttendanceView ? 'subject-period' : '' }}" data-admin-table-skip>
                        <thead>
                            <tr>
                                <th>Học sinh</th>
                                <th class="{{ $isSubjectTeacherAttendanceView ? 'text-end' : '' }}">Trạng thái chuyên cần</th>
                                @unless($isSubjectTeacherAttendanceView)
                                    <th>Ghi chú</th>
                                @endunless
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($students as $student)
                            @php
                                $record = $existingRecords->get($student->id);
                                $hasApprovedLeave = ($approvedLeaveStudentIds ?? collect())->contains($student->id);
                                $isLockedByApprovedLeave = ! $isSubjectTeacherAttendanceView && ($hasApprovedLeave || $record?->status === 'excused');
                                $currentStatus = $isLockedByApprovedLeave
                                    ? 'excused'
                                    : old("status.{$student->id}", $record?->status ?? 'present');
                                $leaveRequest = ($approvedLeaveRequests ?? collect())->get($student->id);
                                $searchText = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(trim($student->student_code . ' ' . $student->name)));
                            @endphp
                            <tr
                                @class([
                                    'attendance-row-locked' => $isLockedByApprovedLeave,
                                    'attendance-row-readonly' => ! $canEditAttendanceRoster,
                                    'attendance-row-expired' => $attendanceWindowExpiredForHomeroom,
                                ])
                                data-attendance-roster-row
                                data-attendance-search-text="{{ $searchText }}"
                            >
                                <td class="text-xs md:text-sm font-normal whitespace-nowrap p-3 text-left">
                                    @if($isSubjectTeacherAttendanceView)
                                        <span class="text-gray-500">{{ $student->student_code }}</span>
                                        <span class="text-gray-900 ms-2">{{ $student->name }}</span>
                                    @else
                                        <button
                                            type="button"
                                            class="attendance-student-link"
                                            data-bs-toggle="modal"
                                            data-bs-target="#studentAttendanceHistory{{ $student->id }}"
                                        >
                                            <span>{{ $student->student_code }}</span>
                                            <strong>{{ $student->name }}</strong>
                                        </button>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($isLockedByApprovedLeave)
                                        <input type="hidden" name="status[{{ $student->id }}]" value="excused">
                                        <span class="attendance-approved-badge">
                                            <i class="bi bi-patch-check me-1"></i>
                                            Nghỉ có phép (P)
                                        </span>
                                    @elseif($canEditAttendanceRoster)
                                        <div class="{{ $isSubjectTeacherAttendanceView ? 'attendance-period-actions' : 'flex items-center justify-end gap-1.5' }}">
                                            @foreach([
                                                'present' => ['label' => '🟢 Có mặt', 'badge' => 'bg-green-50 text-green-700 border-green-200', 'period' => 'present', 'flat' => 'text-xs md:text-sm font-normal text-green-600 hover:text-orange-600 bg-transparent border-none cursor-pointer flex items-center'],
                                                'late' => ['label' => '🟡 Muộn', 'badge' => 'bg-amber-50 text-amber-700 border-amber-200', 'period' => 'late', 'flat' => 'text-xs md:text-sm font-normal text-amber-600 hover:text-orange-600 bg-transparent border-none cursor-pointer flex items-center'],
                                                'absent' => ['label' => $isSubjectTeacherAttendanceView ? '❌ Vắng tiết' : '❌ Vắng mặt', 'badge' => 'bg-red-50 text-red-700 border-red-200', 'period' => 'absent', 'flat' => 'text-xs md:text-sm font-normal text-red-600 hover:text-orange-600 bg-transparent border-none cursor-pointer flex items-center'],
                                            ] as $val => $opt)
                                                <label class="{{ $isSubjectTeacherAttendanceView ? $opt['flat'] . ' attendance-period-pill ' . $opt['period'] . ($currentStatus === $val ? ' active' : '') : 'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-normal border cursor-pointer transition-all ' . ($currentStatus === $val ? $opt['badge'] . ' shadow-xs font-medium' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50') }}">
                                                    <input
                                                        type="radio"
                                                        name="status[{{ $student->id }}]"
                                                        value="{{ $val }}"
                                                        @checked($currentStatus === $val)
                                                        @if($val === 'present') data-attendance-present @endif
                                                        class="hidden"
                                                        onchange="const wrap=this.closest('div'); wrap.querySelectorAll('label').forEach(l => { if (l.classList.contains('attendance-period-pill')) { l.classList.remove('active'); } else { l.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-normal border cursor-pointer transition-all bg-white text-gray-600 border-gray-200 hover:bg-gray-50'; } }); if (this.closest('label').classList.contains('attendance-period-pill')) { this.closest('label').classList.add('active'); } else { this.closest('label').className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium border cursor-pointer transition-all {{ $opt['badge'] }} shadow-xs'; }"
                                                    >
                                                    <span>{{ $opt['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <span @class([
                                            'attendance-readonly-status',
                                            $record?->status ?: 'empty',
                                            'expired' => $attendanceWindowExpiredForHomeroom,
                                        ])>
                                            {{ $record?->status ? ($statusLabels[$record->status] ?? $record->status) : 'Chưa ghi nhận' }}
                                        </span>
                                    @endif
                                    @if($isLockedByApprovedLeave)
                                        <div class="text-info small mt-2">
                                            Học sinh đã có đơn nghỉ được GVCN phê duyệt. Phiên điểm danh này được khóa ở trạng thái nghỉ có phép.
                                        </div>
                                    @endif
                                    @if($isSubjectTeacherAttendanceView)
                                        <input type="hidden" name="note[{{ $student->id }}]" value="{{ old("note.{$student->id}", $record?->note) }}">
                                    @endif
                                </td>
                                @unless($isSubjectTeacherAttendanceView)
                                    <td>
                                        <input
                                            name="note[{{ $student->id }}]"
                                            class="form-control attendance-note-input"
                                            value="{{ old("note.{$student->id}", $record?->note ?: ($leaveRequest ? 'ÄÃ£ duyá»‡t Ä‘Æ¡n xin nghá»‰ há»c cá»§a phá»¥ huynh. LÃ½ do: ' . $leaveRequest->reason : null)) }}"
                                            placeholder="Ghi chú nếu có"
                                            @disabled(! $canEditAttendanceRoster || $isLockedByApprovedLeave)
                                        >
                                    </td>
                                @endunless
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isSubjectTeacherAttendanceView ? 2 : 3 }}">
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

                <div class="card-body border-top attendance-save-footer">
                    <span class="attendance-save-count" data-attendance-visible-count>
                        Hiển thị {{ $students->count() }} trong tổng số {{ $students->count() }} học sinh
                    </span>
                    @if($canEditAttendanceRoster)
                        <button class="btn attendance-save-btn" @disabled($students->isEmpty())>
                            <i class="bi bi-save"></i>
                            Lưu kết quả điểm danh ngày {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>

        @if(! $isSubjectTeacherAttendanceView)
        @foreach($students as $student)
            @php
                $studentHistoryRows = ($attendanceDetailRows ?? collect())
                    ->where('student_id', $student->id)
                    ->whereIn('status', ['late', 'excused', 'absent'])
                    ->values();
                $studentHistorySummary = [
                    'excused' => $studentHistoryRows->where('status', 'excused')->count(),
                    'absent' => $studentHistoryRows->where('status', 'absent')->count(),
                    'late' => $studentHistoryRows->where('status', 'late')->count(),
                ];
            @endphp
            <div class="modal fade content-modal attendance-history-modal" id="studentAttendanceHistory{{ $student->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="attendance-history-title">
                                <h5 class="modal-title">Lá»ŠCH Sá»¬ CHUYÃŠN Cáº¦N Há»ŒC SINH</h5>
                                <div>{{ $student->student_code }} Â· {{ $student->name }}</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ÄÃ³ng"></button>
                        </div>
                        <div class="modal-body">
                            <div class="attendance-history-kpi-grid">
                                <article>
                                    <span>Nghá»‰ cÃ³ phÃ©p</span>
                                    <strong>{{ $studentHistorySummary['excused'] }}</strong>
                                    <small>buá»•i</small>
                                </article>
                                <article>
                                    <span>Nghá»‰ khÃ´ng phÃ©p</span>
                                    <strong>{{ $studentHistorySummary['absent'] }}</strong>
                                    <small>buá»•i</small>
                                </article>
                                <article>
                                    <span>Äi muá»™n</span>
                                    <strong>{{ $studentHistorySummary['late'] }}</strong>
                                    <small>láº§n</small>
                                </article>
                            </div>

                            <div class="attendance-history-table-wrap mt-3">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>NgÃ y</th>
                                            <th>Tiáº¿t váº¯ng</th>
                                            <th>MÃ´n há»c</th>
                                            <th>LÃ½ do / Ghi chÃº</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($studentHistoryRows as $historyRecord)
                                        <tr>
                                            <td class="fw-semibold">{{ $historyRecord->attendance_date?->format('d/m/Y') ?: '-' }}</td>
                                            <td>
                                                @if($historyRecord->status === 'late')
                                                    Äi muá»™n
                                                @else
                                                    {{ $historyRecord->session_type === \App\Models\AttendanceRecord::SESSION_PERIOD ? ($historyRecord->timetableEntry?->displayPeriod() ?? $historyRecord->session_label) : $historyRecord->sessionTypeLabel() }}
                                                @endif
                                            </td>
                                            <td>{{ $historyRecord->timetableEntry?->subject?->name ?? '-' }}</td>
                                            <td class="whitespace-normal break-words">{{ $historyRecord->note ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state py-4">
                                                    <i class="bi bi-calendar-check"></i>
                                                    ChÆ°a cÃ³ lá»‹ch sá»­ váº¯ng hoáº·c Ä‘i muá»™n trong pháº¡m vi Ä‘ang chá»n.
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ÄÃ³ng lá»‹ch sá»­ chuyÃªn cáº§n</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        @endif
    @endif

    @if(! $isSubjectTeacherAttendanceView && ($weeklyMatrix['enabled'] ?? false) && $selectedClass)
        <div class="card mb-3">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                <div>
                    <div class="fw-semibold">Ma trận chuyên cần theo tuần</div>
                    <div class="text-muted small">
                        {{ $selectedClass?->name }} • Tuần
                        {{ ($weeklyMatrix['days'] ?? collect())->first()?->format('d/m/Y') }}
                        - {{ ($weeklyMatrix['days'] ?? collect())->last()?->format('d/m/Y') }}
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 small">
                    <span class="badge bg-info">P: Vắng có phép</span>
                    <span class="badge bg-danger">X: Vắng không phép</span>
                    <span class="badge bg-warning text-dark">M: Đi muộn</span>
                    @if($isAdminAttendanceView)
                        <span class="badge bg-light text-danger border">Vắng tiết học bộ môn</span>
                    @endif
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
                            @if($isAdminAttendanceView)
                                <th class="text-center">Vắng tiết học bộ môn</th>
                            @endif
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
                                @php
                                    $cell = $row['cells'][$day->toDateString()] ?? ['excused' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];
                                @endphp
                                <td class="text-center">
                                    @if($cell['total'] <= 0)
                                        <span class="text-muted">-</span>
                                    @else
                                        <div class="d-flex justify-content-center flex-wrap gap-1">
                                            @if($cell['excused'] > 0)<span class="badge bg-info">P {{ $cell['excused'] }}</span>@endif
                                            @if($cell['absent'] > 0)<span class="badge bg-danger">X {{ $cell['absent'] }}</span>@endif
                                            @if($cell['late'] > 0)<span class="badge bg-warning text-dark">M {{ $cell['late'] }}</span>@endif
                                            @if($cell['excused'] + $cell['absent'] + $cell['late'] === 0)<span class="badge bg-success">Äá»§</span>@endif
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center fw-semibold">{{ $row['total_absent_periods'] }}</td>
                            @if($isAdminAttendanceView)
                                <td class="text-center fw-semibold text-red-600">{{ $row['total_subject_absent_periods'] ?? 0 }}</td>
                            @endif
                            <td class="text-center fw-semibold">{{ $row['total_late'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + (($weeklyMatrix['days'] ?? collect())->count()) + ($isAdminAttendanceView ? 1 : 0) }}">
                                <div class="empty-state"><i class="bi bi-calendar-week"></i>ChÆ°a cÃ³ há»c sinh Ä‘á»ƒ láº­p ma tráº­n chuyÃªn cáº§n.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(! $isSubjectTeacherAttendanceView && ($pendingLeaveRequests ?? collect())->isNotEmpty())
        <div class="attendance-leave-ribbon-card mb-3">
            <div class="card-header">
                <div class="fw-normal text-gray-900">Duyá»‡t Ä‘Æ¡n xin nghá»‰</div>
                <div class="text-muted small">CÃ¡c Ä‘Æ¡n phá»¥ huynh Ä‘ang chá» giÃ¡o viÃªn chá»§ nhiá»‡m xá»­ lÃ½.</div>
            </div>
            <div class="p-3 d-grid gap-2">
                @foreach($pendingLeaveRequests as $requestItem)
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 p-3 bg-orange-50/30 border border-orange-100 rounded-lg text-left">
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div>
                                <div class="text-xs text-gray-500 font-normal">Há»c sinh</div>
                                <div class="text-sm text-gray-900 font-normal">{{ $requestItem->student?->student_code }} - {{ $requestItem->student?->name }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 font-normal">NgÃ y nghá»‰</div>
                                <div class="text-sm text-orange-700 font-normal">{{ $requestItem->leave_date?->format('d/m/Y') }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 font-normal">Phá»¥ huynh</div>
                                <div class="text-sm text-gray-700 font-normal">{{ $requestItem->parent?->name ?? '-' }}{{ $requestItem->parent?->phone ? ' â€¢ ' . $requestItem->parent->phone : '' }}</div>
                            </div>
                        </div>
                        <div class="flex-grow-1 text-sm text-gray-700 font-normal">{{ $requestItem->reason }}</div>
                        <div class="d-flex justify-content-end gap-2">
                            <form method="POST" action="{{ route('teacher.leave-requests.approve', $requestItem) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-success">PhÃª duyá»‡t</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectLeaveFromAttendance{{ $requestItem->id }}">
                                Tá»« chá»‘i
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif

@if($canViewAttendanceRoster && ! $isSubjectTeacherAttendanceView)
<div class="card">
    <div class="card-header">
        <div class="fw-semibold">{{ $isSubjectTeacherAttendanceView ? 'Nháº­t kÃ½ Ä‘iá»ƒm danh theo phiÃªn (Tiáº¿t há»c)' : 'Nháº­t kÃ½ Ä‘iá»ƒm danh theo phiÃªn' }}</div>
        <div class="text-muted small">{{ $isSubjectTeacherAttendanceView ? 'Má»—i tiáº¿t dáº¡y Ä‘Æ°á»£c hiá»ƒn thá»‹ thÃ nh má»™t dÃ²ng nháº­t kÃ½ riÃªng.' : 'Má»—i lá»›p trong má»™t ngÃ y chá»‰ hiá»ƒn thá»‹ tá»‘i Ä‘a hai dÃ²ng: Buá»•i SÃ¡ng vÃ  Buá»•i Chiá»u.' }}</div>
    </div>
    <div class="table-responsive">
        <table class="table attendance-session-table mb-0">
            <thead>
                <tr>
                    <th style="width: 28%;">Lá»›p & Thá»i gian</th>
                    <th style="width: 17%;">PhiÃªn Ä‘iá»ƒm danh</th>
                    <th style="width: 35%;">Thá»‘ng kÃª ná» náº¿p</th>
                    <th style="width: 14%;">Tráº¡ng thÃ¡i</th>
                    <th style="width: 6%;" class="text-end">Thao tÃ¡c</th>
                </tr>
            </thead>
            <tbody>
            @forelse($attendanceSessions as $session)
                @php
                    $isMorningSession = $session->session_type === \App\Models\AttendanceRecord::SESSION_MORNING;
                    $isPeriodSession = $session->session_type === \App\Models\AttendanceRecord::SESSION_PERIOD;
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold text-gray-900">Lá»›p {{ $session->class_name }}</div>
                        <div class="attendance-session-subtext">{{ $session->semester_name }} â€¢ {{ optional($session->date)->format('d/m/Y') }}</div>
                    </td>
                    <td>
                        <span @class(['attendance-session-badge', 'morning' => $isMorningSession, 'afternoon' => ! $isMorningSession && ! $isPeriodSession, 'period' => $isPeriodSession])>
                            {{ $isPeriodSession ? $session->session_label : ($isMorningSession ? 'ðŸŒ… Buá»•i SÃ¡ng' : 'ðŸŒ† Buá»•i Chiá»u') }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap text-left attendance-session-stats-cell">
                        <div class="attendance-session-stats d-inline-flex align-items-center" aria-label="Thá»‘ng kÃª ná» náº¿p phiÃªn Ä‘iá»ƒm danh">
                            <span>
                                <span class="text-sm font-normal text-gray-500">Tá»•ng sá»‘:</span>
                                <span class="text-gray-900 font-semibold">{{ $session->total }}</span>
                            </span>
                            <span class="text-gray-200 mx-1.5">|</span>
                            <span>
                                <span class="text-sm font-normal text-gray-500">CÃ³ máº·t:</span>
                                <span class="text-green-600 font-semibold">{{ $session->present }}</span>
                            </span>
                            <span class="text-gray-200 mx-1.5">|</span>
                            <span>
                                <span class="text-sm font-normal text-gray-500">Muá»™n:</span>
                                <span class="text-orange-600 font-semibold">{{ $session->late }}</span>
                            </span>
                            <span class="text-gray-200 mx-1.5">|</span>
                            <span>
                                <span class="text-sm font-normal text-gray-500">CÃ³ phÃ©p:</span>
                                <span class="text-blue-600 font-semibold">{{ $session->excused }}</span>
                            </span>
                            <span class="text-gray-200 mx-1.5">|</span>
                            <span>
                                <span class="text-sm font-normal text-gray-500">Váº¯ng:</span>
                                <span class="text-red-600 font-semibold">{{ $session->absent }}</span>
                            </span>
                        </div>
                    </td>
                    <td>
                        @if($session->is_completed)
                            <span class="attendance-status-badge done">ðŸŸ¢ ÄÃ£ hoÃ n thÃ nh</span>
                        @else
                            <span class="attendance-status-badge pending">ðŸŸ¡ ChÆ°a Ä‘iá»ƒm danh</span>
                        @endif
                    </td>
                    <td class="text-end whitespace-nowrap">
                        <div class="d-inline-flex align-items-center gap-1 justify-content-end">
                            <button
                                type="button"
                                class="text-gray-500 bg-gray-50 p-2 rounded-md hover:bg-orange-50 hover:text-orange-600 transition-all border-0 shadow-xs inline-flex items-center justify-center cursor-pointer"
                                title="Xem chi tiáº¿t phiÃªn Ä‘iá»ƒm danh"
                                aria-label="Xem chi tiáº¿t phiÃªn Ä‘iá»ƒm danh"
                                data-bs-toggle="modal"
                                data-bs-target="#sessionDetailModal{{ $session->key }}"
                            >
                                ðŸ‘ï¸
                            </button>
                            <button
                                type="button"
                                class="text-orange-600 bg-orange-50/50 p-2 rounded-md hover:bg-orange-100 transition-all border-0 shadow-xs inline-flex items-center justify-center cursor-pointer"
                                title="Chá»‰nh sá»­a phiÃªn Ä‘iá»ƒm danh"
                                aria-label="Chá»‰nh sá»­a phiÃªn Ä‘iá»ƒm danh"
                                onclick='handleNavigateToRegister(@json($session->class_id), @json(optional($session->date)->toDateString()), @json($session->session_type), @json($session->semester_id), @json($session->school_year_id), @json($session->timetable_entry_id))'
                            >
                                âœï¸
                            </button>
                        </div>

                        <div class="modal fade content-modal" id="sessionDetailModal{{ $session->key }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content border-0 shadow-2xl rounded-lg p-3">
                                    <div class="modal-header border-bottom pb-3">
                                        <div class="text-start">
                                            <h5 class="modal-title font-bold text-gray-900 text-lg">NHáº¬T KÃ ÄIá»‚M DANH - Lá»šP {{ $session->class_name }}</h5>
                                            <div class="text-sm text-gray-500 mt-1">{{ $session->session_label }} Â· NgÃ y {{ optional($session->date)->format('d/m/Y') }}</div>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ÄÃ³ng"></button>
                                    </div>
                                    <div class="modal-body py-3">
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0 text-left">
                                                <thead>
                                                    <tr class="bg-gray-50">
                                                        <th class="text-left font-medium text-gray-700">MÃ£ HS & Há» tÃªn</th>
                                                        <th class="text-left font-medium text-gray-700">Tráº¡ng thÃ¡i chuyÃªn cáº§n</th>
                                                        <th class="text-left font-medium text-gray-700">Ghi chÃº</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($session->records as $sessionRecord)
                                                    @php
                                                        $recStudent = $sessionRecord->student;
                                                        $statusKey = $sessionRecord->status ?? 'present';
                                                        $statusBadgeClass = match($statusKey) {
                                                            'present' => 'text-green-600 bg-green-50',
                                                            'late' => 'text-orange-600 bg-orange-50',
                                                            'excused' => 'text-blue-600 bg-blue-50',
                                                            'absent' => 'text-red-600 bg-red-50',
                                                            default => 'text-gray-500 bg-gray-50',
                                                        };
                                                        $statusText = match($statusKey) {
                                                            'present' => 'CÃ³ máº·t',
                                                            'late' => 'Äi muá»™n',
                                                            'excused' => 'Nghá»‰ cÃ³ phÃ©p',
                                                            'absent' => 'Váº¯ng khÃ´ng phÃ©p',
                                                            default => 'ChÆ°a rÃµ',
                                                        };
                                                    @endphp
                                                    <tr>
                                                        <td class="text-left whitespace-nowrap">
                                                            <span class="font-semibold text-gray-900 me-2">{{ $recStudent->student_code ?? '-' }}</span>
                                                            <span class="text-gray-700">{{ $recStudent->name ?? '-' }}</span>
                                                        </td>
                                                        <td class="text-left whitespace-nowrap">
                                                            <span class="px-2 py-1 rounded-md text-sm font-medium {{ $statusBadgeClass }}">
                                                                {{ $statusText }}
                                                            </span>
                                                        </td>
                                                        <td class="text-left text-sm text-gray-500 whitespace-normal">
                                                            {{ $sessionRecord->note ?: 'â€”' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top pt-2">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ÄÃ³ng nháº­t kÃ½</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="bi bi-person-check"></i>
                            ChÆ°a cÃ³ dá»¯ liá»‡u Ä‘iá»ƒm danh buá»•i sÃ¡ng/buá»•i chiá»u.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="attendance-session-footer">
        <span class="attendance-save-count">
            Hiá»ƒn thá»‹ {{ $attendanceSessions->count() }} trong tá»•ng sá»‘ {{ method_exists($attendanceSessions, 'total') ? $attendanceSessions->total() : $attendanceSessions->count() }} phiÃªn Ä‘iá»ƒm danh
        </span>
        @if(method_exists($attendanceSessions, 'links') && $attendanceSessions->hasPages())
            <div>{{ $attendanceSessions->links() }}</div>
        @endif
    </div>
</div>

@foreach(($pendingLeaveRequests ?? collect()) as $requestItem)
    <div class="modal fade content-modal" id="rejectLeaveFromAttendance{{ $requestItem->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('teacher.leave-requests.reject', $requestItem) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <div>
                            <div class="modal-kicker">ÄÆ¡n nghá»‰ há»c</div>
                            <h5 class="modal-title">Tá»« chá»‘i Ä‘Æ¡n xin nghá»‰</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ÄÃ³ng"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Tá»« chá»‘i Ä‘Æ¡n nghá»‰ ngÃ y <strong>{{ $requestItem->leave_date?->format('d/m/Y') }}</strong> cá»§a <strong>{{ $requestItem->student?->name }}</strong>.</p>
                        <label class="form-label">LÃ½ do tá»« chá»‘i</label>
                        <textarea name="homeroom_note" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Há»§y</button>
                        <button class="btn btn-danger">Tá»« chá»‘i</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@endif

<script>
    window.handleNavigateToRegister = (classId, date, session, semesterId, schoolYearId, timetableEntryId = null) => {
        if (! classId || ! date || ! session || ! semesterId || ! schoolYearId) {
            return;
        }

        const params = new URLSearchParams({
            school_year_id: schoolYearId,
            semester_id: semesterId,
            class_id: classId,
            date,
            attendance_type: session,
        });

        if (timetableEntryId) {
            params.set('timetable_entry_id', timetableEntryId);
        }

        window.location.href = `${@json(route('attendance.index'))}?${params.toString()}#attendance-register`;
    };

    document.querySelectorAll('[data-attendance-search]').forEach((input) => {
        const rows = Array.from(document.querySelectorAll('[data-attendance-roster-row]'));
        const countLabel = document.querySelector('[data-attendance-visible-count]');
        let debounceTimer = null;

        const normalizeText = (value) => value
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();

        const applyFilter = () => {
            const keyword = normalizeText(input.value.trim());
            let visibleCount = 0;

            rows.forEach((row) => {
                const matched = ! keyword || (row.dataset.attendanceSearchText || '').includes(keyword);
                row.hidden = ! matched;

                if (matched) {
                    visibleCount += 1;
                }
            });

            if (countLabel) {
                countLabel.textContent = `Hiá»ƒn thá»‹ ${visibleCount} trong tá»•ng sá»‘ ${rows.length} há»c sinh`;
            }
        };

        input.addEventListener('input', () => {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(applyFilter, 300);
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-timetable-entry-filter] select').forEach((select) => {
        const form = select.closest('form');
        const classInput = form?.querySelector('[data-attendance-period-class-id]');

        const syncPeriodClass = () => {
            if (classInput) {
                classInput.value = select.selectedOptions[0]?.dataset.classId || classInput.value || '';
            }
        };

        select.addEventListener('change', syncPeriodClass);
        syncPeriodClass();
    });

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
