@extends('layouts.app')
@section('title', 'Điểm danh tiết dạy')

@section('content')
@php
    $dateLabel = $date ? \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') : now()->format('d/m/Y');
    $sessionLabel = $selectedTimetableEntry
        ? ((int) $selectedTimetableEntry->period <= 5 ? 'Buổi Sáng' : 'Buổi Chiều')
        : 'Buổi Sáng';
    $periodLabel = $selectedTimetableEntry ? ' • Tiết ' . $selectedTimetableEntry->periodInSession() : '';
    $subjectLabel = $selectedTimetableEntry?->subject?->name ? ' • ' . $selectedTimetableEntry->subject->name : '';
    $subtitle = ($selectedClass?->name ?? 'Chưa chọn lớp') . ' • ' . ($selectedSemester?->normalizedName() ?? $selectedSemester?->name ?? 'Chưa chọn học kỳ') . ' • ' . $dateLabel . ' • ' . $sessionLabel . $periodLabel . $subjectLabel;
    $canInteract = $selectedClass
        && $selectedSemester
        && ! $readOnly
        && $date
        && \Illuminate\Support\Carbon::parse($date)->isToday();
    $canEdit = $canInteract && ! $readOnly;
    $selectedTeachingSession = (string) ($selectedTeachingSession ?? ($selectedTimetableEntry ? ((int) $selectedTimetableEntry->period <= 5 ? 'morning' : 'afternoon') : 'morning'));
    $selectedPeriod = (string) ($selectedPeriod ?? ($selectedTimetableEntry ? $selectedTimetableEntry->periodInSession() : ''));
    $teachingSessionOptions = [
        'morning' => ['value' => 'morning', 'label' => 'Buổi Sáng'],
        'afternoon' => ['value' => 'afternoon', 'label' => 'Buổi Chiều'],
    ];
    $periodEntryMap = $availableTimetableEntries
        ->mapWithKeys(fn ($entry) => [(((int) $entry->period <= 5 ? 'morning' : 'afternoon') . '_' . (string) $entry->periodInSession()) => [
            'id' => (string) $entry->id,
            'label' => $entry->displayPeriod() . ' - ' . ($entry->subject?->name ?? 'Môn học'),
            'class_id' => (string) ($entry->timetable?->class_id ?? ''),
            'session' => (int) $entry->period <= 5 ? 'morning' : 'afternoon',
            'period' => (string) $entry->periodInSession(),
        ]])
        ->all();
    $availablePeriodsBySession = $availableTimetableEntries
        ->groupBy(fn ($entry) => (int) $entry->period <= 5 ? 'morning' : 'afternoon')
        ->map(fn ($entries) => $entries
            ->map(fn ($entry) => (string) $entry->periodInSession())
            ->unique()
            ->values()
            ->all())
        ->all();
@endphp

<style>
    .teacher-attendance-page,
    .teacher-attendance-page * {
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .teacher-attendance-page {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        color: #374151;
        text-align: left;
    }

    .teacher-attendance-heading {
        width: 100% !important;
        text-align: left !important;
        align-items: flex-start !important;
        justify-content: flex-start !important;
    }

    .teacher-attendance-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 1rem;
        width: 100%;
        max-width: 100%;
        padding: 1rem;
        border: 1px solid #ffedd5;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 1px 1px rgba(15, 23, 42, .035);
        overflow: hidden;
    }

    .teacher-attendance-toolbar-field {
        min-width: 220px;
        flex: 1 1 220px;
    }

    .teacher-attendance-toolbar label {
        display: block;
        margin-bottom: .35rem;
        color: #6b7280;
        font-size: .75rem;
        font-weight: 400;
        text-align: left;
    }

    .teacher-attendance-toolbar select {
        width: 100%;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        background: #fff7ed;
        color: #7c2d12;
        padding: .45rem .7rem;
        font-size: .875rem;
        font-weight: 400;
        outline: none;
    }

    .teacher-attendance-toolbar input[type="date"] {
        width: 100%;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        background: #fff7ed;
        color: #7c2d12;
        padding: .45rem .7rem;
        font-size: .875rem;
        font-weight: 400;
        outline: none;
    }

    .teacher-attendance-note {
        width: 100%;
        border: 1px solid rgba(229, 231, 235, .6);
        border-radius: 6px;
        background: #f9fafb;
        color: #374151;
        padding: .25rem .5rem;
        font-size: .875rem;
        font-weight: 400;
        text-align: left;
        outline: none;
    }

    .teacher-attendance-note:focus {
        border-color: #f97316;
        background: #fff;
    }

    .teacher-attendance-load {
        border: 1px solid #fb923c;
        border-radius: 8px;
        background: #ea580c;
        color: #fff;
        padding: .48rem .9rem;
        font-size: .875rem;
        font-weight: 400;
        white-space: nowrap;
    }

    .teacher-attendance-card {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .teacher-attendance-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem;
        border-bottom: 1px solid #ffedd5;
        background: rgba(255, 247, 237, .45);
        text-align: left;
    }

    .teacher-attendance-table-wrap {
        width: 100%;
        max-width: 100%;
        max-height: calc(100vh - 260px);
        overflow-y: auto;
        overflow-x: hidden;
    }

    .teacher-attendance-table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        margin: 0;
    }

    .teacher-attendance-table th,
    .teacher-attendance-table td {
        padding: .7rem .85rem;
        border-bottom: 1px solid rgba(254, 215, 170, .58);
        color: #374151;
        font-size: 1rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .teacher-attendance-table th {
        background: #fff7ed;
        color: #7c2d12;
        font-weight: 500;
    }

    .teacher-attendance-actions {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: .8rem;
        min-width: 0;
        white-space: nowrap;
    }

    .teacher-attendance-pill {
        padding: 0 !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0;
        color: #9ca3af !important;
        line-height: 1.2;
        transition: color .15s ease, background-color .15s ease;
    }

    .teacher-attendance-pill input {
        display: none !important;
    }

    .teacher-attendance-pill:hover {
        color: #ea580c !important;
        background: transparent !important;
    }

    .teacher-attendance-pill.active.present {
        color: #fff !important;
        background: #16a34a !important;
        border-radius: 8px;
        padding: .375rem .75rem !important;
        font-weight: 500 !important;
        box-shadow: 0 1px 1px rgba(15, 23, 42, .035) !important;
    }

    .teacher-attendance-pill.active.late {
        color: #fff !important;
        background: #f59e0b !important;
        border-radius: 8px;
        padding: .375rem .75rem !important;
        font-weight: 500 !important;
        box-shadow: 0 1px 1px rgba(15, 23, 42, .035) !important;
    }

    .teacher-attendance-pill.active.absent {
        color: #fff !important;
        background: #dc2626 !important;
        border-radius: 8px;
        padding: .375rem .75rem !important;
        font-weight: 500 !important;
        box-shadow: 0 1px 1px rgba(15, 23, 42, .035) !important;
    }

    .teacher-attendance-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .9rem 1rem;
        border-top: 1px solid #ffedd5;
        background: #fff;
    }

    .teacher-attendance-save {
        border: 1px solid #ea580c;
        border-radius: 8px;
        background: #ea580c;
        color: #fff;
        padding: .55rem 1rem;
        font-size: .875rem;
        font-weight: 400;
    }

    .teacher-attendance-save.is-create {
        border-color: #ea580c;
        background: #ea580c;
        color: #fff;
    }

    .teacher-attendance-save.is-create:hover {
        background: #c2410c;
    }

    .teacher-attendance-save.is-update {
        border-color: #d97706;
        background: #d97706;
        color: #fff;
        padding: .5rem 1rem;
        border-radius: 8px;
        font-weight: 400;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .12);
        transition: all .15s ease;
    }

    .teacher-attendance-save.is-update:hover {
        background: #b45309;
    }

    .teacher-attendance-save:disabled {
        border-color: #e5e7eb;
        background: #f3f4f6;
        color: #9ca3af;
        cursor: not-allowed;
    }

    .teacher-attendance-history-card {
        width: 100%;
        max-width: 100%;
        margin-top: 1.5rem;
        padding: 1.25rem;
        border: 1px solid #ffedd5;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 1px 1px rgba(15, 23, 42, .035);
        text-align: left;
    }

    .teacher-attendance-history-table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        overflow: hidden;
    }

    .teacher-attendance-history-table th,
    .teacher-attendance-history-table td {
        padding: .65rem .75rem;
        border-bottom: 1px solid rgba(254, 215, 170, .58);
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .teacher-attendance-history-table th {
        background: rgba(255, 247, 237, .4);
        color: #9a3412;
        border-bottom: 1px solid rgba(255, 237, 213, .7);
        font-family: Inter, Roboto, ui-sans-serif, system-ui, sans-serif;
    }

    .teacher-attendance-history-badges {
        display: flex;
        align-items: center;
        gap: .35rem;
        min-width: 0;
        white-space: nowrap;
    }

    .teacher-attendance-history-badge {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .22rem .55rem;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #f9fafb;
        color: #4b5563;
        font-size: .75rem;
        font-weight: 400;
    }

    .teacher-attendance-history-badge.present {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .teacher-attendance-history-badge.late {
        border-color: #fde68a;
        background: #fffbeb;
        color: #b45309;
    }

    .teacher-attendance-history-badge.absent {
        border-color: #fecaca;
        background: #fef2f2;
        color: #b91c1c;
    }

    .teacher-attendance-modal {
        position: fixed;
        inset: 0;
        z-index: 50;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(0, 0, 0, .4);
    }

    .teacher-attendance-modal.is-open {
        display: flex;
    }

    .teacher-attendance-modal-card {
        width: 100%;
        max-width: 28rem;
        border: 1px solid #ffedd5;
        border-radius: 12px;
        background: #fff;
        padding: 1.25rem;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .18);
        display: flex;
        flex-direction: column;
        gap: 1rem;
        text-align: left;
    }

    .teacher-attendance-modal-header {
        display: flex;
        flex-direction: column;
        gap: .25rem;
        padding: 1rem;
        border-bottom: 1px solid #fed7aa;
        border-radius: 10px 10px 0 0;
        background: rgba(255, 237, 213, .5);
        text-align: left;
    }

    .teacher-attendance-modal-table {
        width: 100%;
        max-width: 100%;
        font-size: .8rem;
        table-layout: fixed;
        border-collapse: collapse;
        overflow: hidden;
    }

    .teacher-attendance-modal-table th,
    .teacher-attendance-modal-table td {
        padding: .625rem;
        border-bottom: 1px solid #f3f4f6;
        color: #374151;
        font-size: .8rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
        word-break: break-word;
    }

    .teacher-attendance-modal-table th {
        padding: .625rem;
        border-bottom: 1px solid #ffedd5;
        background: #fff7ed;
        color: #7c2d12;
        font-weight: 500;
        white-space: nowrap;
    }

    .teacher-attendance-modal-table tr:nth-child(odd) {
        background: #fff;
    }

    .teacher-attendance-modal-table tr:nth-child(even) {
        background: rgba(255, 247, 237, .2);
    }

    .teacher-attendance-modal-table tr:hover {
        background: rgba(255, 247, 237, .4);
    }

    @media (max-width: 900px) {
        .teacher-attendance-toolbar,
        .teacher-attendance-card-header,
        .teacher-attendance-footer {
            flex-direction: column;
            align-items: stretch;
        }

        .teacher-attendance-table th,
        .teacher-attendance-table td {
            font-size: .875rem;
            padding: .65rem;
        }
    }
</style>

<div class="teacher-attendance-page font-sans text-left">
    <div class="teacher-attendance-heading w-full !text-left !items-start flex flex-col justify-start text-left mb-3 px-1">
        <h5 class="text-xl font-semibold text-gray-900 !text-left mb-0">Điểm danh tiết dạy</h5>
        <div class="w-full !text-left !items-start flex flex-col justify-start text-left text-sm font-normal text-orange-700/80 mt-1 mb-4 px-1">
            {{ $subtitle }}
        </div>
    </div>

    <form method="GET" action="{{ route('teacher.attendance.session') }}" class="teacher-attendance-toolbar w-full flex flex-wrap items-center gap-4 bg-white p-4 rounded-xl border border-orange-100 shadow-2xs text-left mb-3" data-teacher-attendance-filter>
        <input type="hidden" name="school_year_id" value="{{ $selectedYearId }}">
        <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
        <input type="hidden" name="attendance_type" value="{{ \App\Models\AttendanceRecord::SESSION_PERIOD }}">
        <input type="hidden" name="timetable_entry_id" value="{{ $selectedTimetableEntryId }}" data-filter-timetable-entry-id>

        <div class="teacher-attendance-toolbar-field">
            <select name="class_id" required data-filter-class onchange="clearTeacherAttendanceSelection(); this.form.submit()" class="text-sm font-normal text-gray-700 bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-md focus:border-orange-500 focus:outline-none cursor-pointer">
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) $selectedClassId === (string) $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="teacher-attendance-toolbar-field">
            <input type="date" name="date" value="{{ $date }}" required data-filter-date onchange="clearTeacherAttendanceSelection(); this.form.submit()" class="text-sm font-normal text-gray-700 bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-md focus:border-orange-500 focus:outline-none cursor-pointer">
        </div>

        <div class="teacher-attendance-toolbar-field">
            <select name="session_type" required data-filter-session onchange="syncTeacherAttendancePeriods(); this.form.submit()" class="text-sm font-normal text-gray-700 bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-md focus:border-orange-500 focus:outline-none cursor-pointer">
                @foreach($teachingSessionOptions as $sessionKey => $sessionOption)
                    <option value="{{ $sessionOption['value'] }}" @selected($selectedTeachingSession === $sessionKey)>{{ $sessionOption['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="teacher-attendance-toolbar-field">
            <select name="slot" required data-filter-period onchange="syncTeacherAttendanceEntry(); this.form.submit()" class="text-sm font-normal text-gray-700 bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-md focus:border-orange-500 focus:outline-none cursor-pointer">
                @foreach(($availablePeriodsBySession[$selectedTeachingSession] ?? []) as $periodOption)
                    <option value="{{ $periodOption }}" @selected($selectedPeriod === (string) $periodOption)>Tiết {{ $periodOption }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="teacher-attendance-load">Tải danh sách</button>
    </form>

    <div class="teacher-attendance-card" id="attendance-register">
        <div class="teacher-attendance-card-header">
            <div class="text-left">
                <div class="text-base font-semibold text-gray-900">Bảng điểm danh</div>
                <div class="w-full !text-left !items-start flex flex-col justify-start text-left text-sm font-normal text-orange-700/80 mt-1 mb-4 px-1">
                    {{ $subtitle }}
                </div>
            </div>
        </div>

        @php
            $attendanceActionMode = request('action_mode') === 'update' || $isEditingSession ? 'update' : 'create';
            $attendanceActionSessionId = request('attendance_session_id', $existingRecords->isNotEmpty()
                ? md5((string) ($existingRecords->first()?->session_key ?: $selectedTimetableEntryId))
                : '');
        @endphp

        <form method="POST" action="{{ route('attendance.store') }}">
            @csrf
            <input type="hidden" name="school_year_id" value="{{ $selectedYearId }}">
            <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
            <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
            <input type="hidden" name="attendance_date" value="{{ $date }}">
            <input type="hidden" name="attendance_type" value="{{ \App\Models\AttendanceRecord::SESSION_PERIOD }}">
            <input type="hidden" name="timetable_entry_id" value="{{ $selectedTimetableEntryId }}" data-store-timetable-entry-id>
            <input type="hidden" id="attendance-action-mode" name="action_mode" value="{{ $attendanceActionMode }}">
            <input type="hidden" id="attendance-session-id" name="attendance_session_id" value="{{ $attendanceActionSessionId }}">

            <div class="teacher-attendance-table-wrap">
                <table class="teacher-attendance-table w-full table-fixed max-w-full overflow-hidden">
                    <thead>
                        <tr>
                            <th class="bg-orange-50/40 text-orange-850 border-b border-orange-100/60 font-sans text-sm font-normal text-left" style="width: 8%;">STT</th>
                            <th class="bg-orange-50/40 text-orange-850 border-b border-orange-100/60 font-sans text-sm font-normal text-left" style="width: 14%;">Mã HS</th>
                            <th class="bg-orange-50/40 text-orange-850 border-b border-orange-100/60 font-sans text-sm font-normal text-left" style="width: 28%;">Họ và Tên</th>
                            <th class="bg-orange-50/40 text-orange-850 border-b border-orange-100/60 font-sans text-sm font-normal text-left" style="width: 30%;">Trạng thái điểm danh tiết dạy</th>
                            <th class="bg-orange-50/40 text-orange-850 border-b border-orange-100/60 font-sans text-sm font-normal text-left" style="width: 20%;">Ghi chú tiết học</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($students as $student)
                        @php
                            $record = $existingRecords->get($student->id);
                            $leaveRequest = ($approvedLeaveRequests ?? collect())->get($student->id);
                            $isLockedByApprovedLeave = (bool) $leaveRequest || $record?->status === 'excused';
                            $approvedLeaveNote = $leaveRequest
                                ? 'Đã duyệt đơn xin nghỉ học của phụ huynh. Lý do: ' . $leaveRequest->reason
                                : ($record?->note ?: 'Đã duyệt đơn xin nghỉ học của phụ huynh.');
                            $currentStatus = $isLockedByApprovedLeave
                                ? 'excused'
                                : old("status.{$student->id}", $record?->status);
                        @endphp
                        <tr>
                            <td class="text-sm font-normal text-gray-500 text-left">{{ $loop->iteration }}</td>
                            <td class="text-sm font-normal text-gray-600 text-left" title="{{ $student->student_code }}">{{ $student->student_code }}</td>
                            <td class="text-sm font-normal text-gray-900 text-left" title="{{ $student->name }}">{{ $student->name }}</td>
                            <td>
                                @if($isLockedByApprovedLeave)
                                    <input type="hidden" name="status[{{ $student->id }}]" value="excused">
                                    <div class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 border border-blue-200 text-xs md:text-sm font-normal px-3 py-1.5 rounded-full">
                                        🟦 Nghỉ có phép
                                    </div>
                                    <div class="text-xs font-normal text-gray-500 mt-1 text-left">
                                        Đã có đơn nghỉ cả ngày được GVCN duyệt, không cho phép tích vắng thêm.
                                    </div>
                                @else
                                    <div class="teacher-attendance-actions">
                                    @foreach([
                                        'present' => ['label' => '🟢 Có mặt', 'class' => 'present'],
                                        'late' => ['label' => '🟡 Muộn', 'class' => 'late'],
                                        'absent' => ['label' => '❌ Vắng mặt', 'class' => 'absent'],
                                    ] as $value => $option)
                                        @php
                                            $inactiveClass = 'teacher-attendance-pill ' . $option['class'] . ' text-xs md:text-sm font-normal text-gray-400 bg-transparent border-none cursor-pointer flex items-center gap-1';
                                            $activeClass = match ($value) {
                                                'present' => 'active bg-green-600 text-white font-medium shadow-2xs rounded-md px-3 py-1.5',
                                                'late' => 'active bg-amber-500 text-white font-medium shadow-2xs rounded-md px-3 py-1.5',
                                                'absent' => 'active bg-red-600 text-white font-medium shadow-2xs rounded-md px-3 py-1.5',
                                                default => 'active',
                                            };
                                        @endphp
                                        <label class="{{ $currentStatus === $value ? $inactiveClass . ' ' . $activeClass : $inactiveClass }}" onclick="setTeacherAttendanceStatus(this)">
                                            <input
                                                type="radio"
                                                name="status[{{ $student->id }}]"
                                                value="{{ $value }}"
                                                @checked($currentStatus === $value)
                                                @disabled(! $canInteract)
                                                @if($value === 'present') data-attendance-present @endif
                                                data-attendance-status-input
                                                data-student-id="{{ $student->id }}"
                                            >
                                            <span>{{ $option['label'] }}</span>
                                        </label>
                                    @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($isLockedByApprovedLeave)
                                    <input type="hidden" name="note[{{ $student->id }}]" value="{{ old("note.{$student->id}", $approvedLeaveNote) }}">
                                @endif
                                <input
                                    type="text"
                                    name="note[{{ $student->id }}]"
                                    value="{{ old("note.{$student->id}", $isLockedByApprovedLeave ? $approvedLeaveNote : $record?->note) }}"
                                    class="teacher-attendance-note text-xs md:text-sm font-normal text-gray-700 bg-gray-50 border border-gray-200/60 rounded-md px-2 py-1 focus:border-orange-500 focus:outline-none w-full text-left"
                                    placeholder="Ghi chú nếu có"
                                    @disabled(! $canInteract || $isLockedByApprovedLeave)
                                >
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-left text-sm font-normal text-gray-500">
                                Chưa có học sinh để điểm danh trong lớp này.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="teacher-attendance-footer">
                <span class="text-sm font-normal text-gray-500">Hiển thị {{ $students->count() }} học sinh</span>
                <div class="flex items-center gap-2">
                    @if($canEdit && $selectedTimetableEntryId)
                        <button type="button" class="teacher-attendance-load" data-mark-all-present>Điểm danh tất cả</button>
                    @endif
                    <button
                        id="teacher-attendance-submit"
                        class="teacher-attendance-save {{ $attendanceActionMode === 'update' ? 'is-update bg-amber-600 hover:bg-amber-700 text-white font-normal px-4 py-2 rounded-lg shadow-sm transition-all' : 'is-create' }}"
                        data-create-label="🚀 Lưu nhật ký điểm danh"
                        data-update-label="💾 Cập nhật thay đổi nhật ký"
                        @disabled(! $canInteract || ! $selectedTimetableEntryId || $students->isEmpty())
                    >
                        {{ $isEditingSession ? '💾 Cập nhật nhật ký điểm danh' : '💾 Lưu nhật ký điểm danh' }}
                    </button>
                </div>
            </div>
        </form>

        <div class="teacher-attendance-history-card w-full bg-white border border-orange-100 p-5 rounded-xl shadow-2xs text-left mt-6">
            <div class="text-base font-semibold text-gray-900 text-left mb-3">
                📜 Lịch sử các phiên điểm danh tiết dạy trong ngày hôm nay
            </div>
            <div class="w-full max-w-full overflow-hidden">
                <table class="teacher-attendance-history-table w-full table-fixed max-w-full overflow-hidden">
                    <thead>
                        <tr>
                            <th style="width: 7%;">STT</th>
                            <th style="width: 13%;">Thời gian</th>
                            <th style="width: 15%;">Lớp học</th>
                            <th style="width: 12%;">Buổi</th>
                            <th style="width: 12%;">Tiết dạy</th>
                            <th style="width: 27%;">Thống kê sĩ số</th>
                            <th style="width: 14%;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse(($attendanceHistory ?? collect()) as $history)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $history->time ? \Illuminate\Support\Carbon::parse($history->time)->format('H:i') : '-' }}</td>
                            <td title="{{ $history->class_name }}">{{ $history->class_name }}</td>
                            <td>{{ $history->session_label }}</td>
                            <td title="{{ $history->subject_name }}">Tiết {{ $history->slot }} • {{ $history->subject_name }}</td>
                            <td>
                                <div class="teacher-attendance-history-badges">
                                    <span class="teacher-attendance-history-badge present">🟢 {{ $history->present }} Có mặt</span>
                                    <span class="teacher-attendance-history-badge late">🟡 {{ $history->late }} Muộn</span>
                                    <span class="teacher-attendance-history-badge absent">❌ {{ $history->absent }} Vắng</span>
                                </div>
                            </td>
                            <td class="text-left">
                                <div class="flex items-center gap-1.5 text-left">
                                    <button
                                        type="button"
                                        class="text-xs font-normal text-gray-700 bg-gray-50 border border-gray-200 px-2 py-1 rounded-md hover:bg-gray-100 transition-colors cursor-pointer flex items-center gap-1"
                                        onclick="openTeacherAttendanceHistoryModal('teacherAttendanceHistory{{ $history->id }}')"
                                    >
                                        👁️ Xem chi tiết
                                    </button>
                                    @if($history->can_edit ?? false)
                                        <a
                                            href="{{ route('teacher.attendance.session', [
                                                'school_year_id' => $selectedYearId,
                                                'semester_id' => $history->semester_id,
                                                'class_id' => $history->class_id,
                                                'date' => $history->attendance_date,
                                                'session_type' => $history->session_type,
                                                'slot' => $history->slot,
                                                'timetable_entry_id' => $history->timetable_entry_id,
                                                'action_mode' => 'update',
                                                'attendance_session_id' => $history->id,
                                            ]) }}#attendance-register"
                                            class="text-xs font-normal text-orange-700 bg-orange-50 border border-orange-200 px-2 py-1 rounded-md hover:bg-orange-100 transition-colors cursor-pointer flex items-center gap-1"
                                            onclick="prepareTeacherAttendanceEdit('{{ $history->id }}')"
                                        >
                                            ✏️ Sửa nhật ký
                                        </a>
                                    @endif
                                </div>

                                <div class="teacher-attendance-modal fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 animate-fade-in" id="teacherAttendanceHistory{{ $history->id }}" aria-hidden="true">
                                    <div class="teacher-attendance-modal-card w-full max-w-md bg-white border border-orange-100 p-5 rounded-xl shadow-2xl flex flex-col gap-4 text-left">
                                        <div class="teacher-attendance-modal-header bg-orange-100/50 p-4 rounded-t-xl border-b border-orange-200 flex flex-col gap-1">
                                            <div class="text-base font-semibold text-gray-900">👁️ Chi tiết phiên điểm danh</div>
                                            <div class="text-xs font-normal text-orange-700 mt-1 text-left font-sans">
                                                {{ $history->class_name }} • {{ $history->session_label }} • Tiết {{ $history->slot }} • {{ $history->subject_name }}
                                            </div>
                                        </div>
                                        <div class="w-full max-w-full overflow-hidden">
                                            <table class="teacher-attendance-modal-table w-full table-fixed max-w-full overflow-hidden text-xs md:text-sm">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 46%;">Học sinh</th>
                                                        <th style="width: 22%;">Trạng thái</th>
                                                        <th style="width: 32%;">Ghi chú</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @forelse($history->detail_records as $detailRecord)
                                                    @php
                                                        $detailStatusLabel = $detailRecord->status === 'late' ? '🟡 Muộn' : '❌ Vắng mặt';
                                                        $detailStatusClass = $detailRecord->status === 'late'
                                                            ? 'bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded-full inline-block font-normal text-xs whitespace-nowrap'
                                                            : 'bg-red-50 text-red-700 border border-red-200 px-2 py-0.5 rounded-full inline-block font-normal text-xs whitespace-nowrap';
                                                    @endphp
                                                    <tr class="odd:bg-white even:bg-orange-50/20 border-b border-gray-100 hover:bg-orange-50/40 transition-colors">
                                                        <td class="text-xs md:text-sm font-normal text-gray-800 text-left p-2.5" title="{{ $detailRecord->student?->student_code }} {{ $detailRecord->student?->name }}">
                                                            {{ $detailRecord->student?->student_code }} - {{ $detailRecord->student?->name }}
                                                        </td>
                                                        <td class="text-left p-2.5"><span class="{{ $detailStatusClass }}">{{ $detailStatusLabel }}</span></td>
                                                        <td class="text-xs md:text-sm font-normal text-gray-700 text-left p-2.5" title="{{ $detailRecord->note }}">{{ $detailRecord->note ?: '-' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-sm font-normal text-gray-500 text-left">
                                                            Phiên này chưa có học sinh muộn hoặc vắng mặt.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        <button
                                            type="button"
                                            class="text-xs font-normal text-orange-700 bg-orange-50 border border-orange-200 px-3 py-1.5 rounded-md hover:bg-orange-100 transition-colors cursor-pointer ml-auto"
                                            onclick="closeTeacherAttendanceHistoryModal('teacherAttendanceHistory{{ $history->id }}')"
                                        >
                                            Đóng
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-sm font-normal text-gray-500 text-left">
                                Chưa có phiên điểm danh tiết dạy nào được lưu trong ngày đang chọn.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const teacherAttendancePeriodEntries = @json($periodEntryMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    const teacherAttendancePeriodsBySession = @json($availablePeriodsBySession, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    const teacherAttendanceSavedStatusMap = @json($existingRecords->mapWithKeys(fn ($record) => [(string) $record->student_id => [
        'status' => $record->status,
        'note' => $record->note,
    ]])->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    const teacherAttendancePeriodLabel = (period) => `Tiết ${period}`;

    function normalizeTeacherAttendanceSession(value) {
        if (value === 'Sáng' || value === 'sang' || value === 'morning') {
            return 'morning';
        }

        if (value === 'Chiều' || value === 'chieu' || value === 'afternoon') {
            return 'afternoon';
        }

        return value || 'morning';
    }

    function syncTeacherAttendanceEntry() {
        const sessionSelect = document.querySelector('[data-filter-session]');
        const periodSelect = document.querySelector('[data-filter-period]');
        const selectedSession = normalizeTeacherAttendanceSession(sessionSelect?.value || 'morning');
        const selectedPeriod = periodSelect?.value || '';
        const selectedEntry = teacherAttendancePeriodEntries[`${selectedSession}_${selectedPeriod}`] || null;
        document.querySelectorAll('[data-filter-timetable-entry-id], [data-store-timetable-entry-id]').forEach((input) => {
            input.value = selectedEntry?.id || '';
        });
    }

    function clearTeacherAttendanceEntry() {
        document.querySelectorAll('[data-filter-timetable-entry-id], [data-store-timetable-entry-id]').forEach((input) => {
            input.value = '';
        });
    }

    function clearTeacherAttendanceSelection() {
        clearTeacherAttendanceEntry();
        const periodSelect = document.querySelector('[data-filter-period]');
        if (periodSelect) {
            periodSelect.value = '';
        }
    }

    function syncTeacherAttendancePeriods() {
        const sessionSelect = document.querySelector('[data-filter-session]');
        const periodSelect = document.querySelector('[data-filter-period]');
        if (!periodSelect) return;

        const selectedSession = normalizeTeacherAttendanceSession(sessionSelect?.value || 'morning');
        const currentPeriod = periodSelect.value;
        const periods = teacherAttendancePeriodsBySession[selectedSession] || [];

        periodSelect.innerHTML = '';
        periods.forEach((period) => {
            const option = document.createElement('option');
            option.value = String(period);
            option.textContent = teacherAttendancePeriodLabel(period);
            periodSelect.appendChild(option);
        });

        if (periods.map(String).includes(String(currentPeriod))) {
            periodSelect.value = currentPeriod;
        } else if (periods.length > 0) {
            periodSelect.value = String(periods[0]);
        }

        syncTeacherAttendanceEntry();
    }

    const inactiveStatusClass = 'teacher-attendance-pill text-xs md:text-sm font-normal text-gray-400 bg-transparent border-none cursor-pointer flex items-center gap-1';
    const statusClassMap = {
        present: `${inactiveStatusClass} present`,
        late: `${inactiveStatusClass} late`,
        absent: `${inactiveStatusClass} absent`,
    };
    const activeStatusClassMap = {
        present: `${statusClassMap.present} active bg-green-600 text-white font-medium shadow-2xs rounded-md px-3 py-1.5`,
        late: `${statusClassMap.late} active bg-amber-500 text-white font-medium shadow-2xs rounded-md px-3 py-1.5`,
        absent: `${statusClassMap.absent} active bg-red-600 text-white font-medium shadow-2xs rounded-md px-3 py-1.5`,
    };

    function paintTeacherAttendanceStatus(input) {
        const wrap = input.closest('.teacher-attendance-actions');
        if (!wrap) return;

        wrap.querySelectorAll('.teacher-attendance-pill').forEach((pill) => {
            const statusInput = pill.querySelector('[data-attendance-status-input]');
            const status = statusInput?.value || '';
            pill.className = statusClassMap[status] || inactiveStatusClass;
        });

        const activePill = input.closest('.teacher-attendance-pill');
        if (activePill) {
            activePill.className = activeStatusClassMap[input.value] || `${statusClassMap[input.value] || inactiveStatusClass} active`;
        }
    }

    function setTeacherAttendanceStatus(label) {
        const input = label.querySelector('[data-attendance-status-input]');
        if (!input || input.disabled) return;

        input.checked = true;
        paintTeacherAttendanceStatus(input);
    }

    function hydrateTeacherAttendanceSavedStatuses() {
        document.querySelectorAll('.teacher-attendance-actions').forEach((wrap) => {
            const inputs = Array.from(wrap.querySelectorAll('[data-attendance-status-input]'));
            const firstInput = inputs[0] || null;
            const studentId = firstInput?.dataset.studentId || '';
            const savedStatus = teacherAttendanceSavedStatusMap?.[studentId]?.status || '';
            const checkedInput = inputs.find((input) => input.checked);
            const savedInput = savedStatus ? inputs.find((input) => input.value === savedStatus) : null;
            const targetInput = savedInput || checkedInput;

            inputs.forEach((input) => {
                const pill = input.closest('.teacher-attendance-pill');
                if (pill) {
                    pill.className = statusClassMap[input.value] || inactiveStatusClass;
                }
            });

            if (targetInput) {
                targetInput.checked = true;
                paintTeacherAttendanceStatus(targetInput);
            }
        });
    }

    function openTeacherAttendanceHistoryModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeTeacherAttendanceHistoryModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function applyTeacherAttendanceActionMode(mode, sessionId = '') {
        const actionModeInput = document.getElementById('attendance-action-mode');
        const attendanceSessionInput = document.getElementById('attendance-session-id');
        const submitButton = document.getElementById('teacher-attendance-submit');
        const normalizedMode = mode === 'update' ? 'update' : 'create';

        if (actionModeInput) {
            actionModeInput.value = normalizedMode;
        }

        if (attendanceSessionInput && sessionId) {
            attendanceSessionInput.value = sessionId;
        }

        if (!submitButton) return;

        const updateClasses = ['is-update', 'bg-amber-600', 'hover:bg-amber-700', 'text-white', 'font-normal', 'px-4', 'py-2', 'rounded-lg', 'shadow-sm', 'transition-all'];
        submitButton.classList.remove('is-create', ...updateClasses);

        if (normalizedMode === 'update') {
            submitButton.classList.add(...updateClasses);
            submitButton.textContent = submitButton.dataset.updateLabel || '💾 Cập nhật thay đổi nhật ký';
            return;
        }

        submitButton.classList.add('is-create');
        submitButton.textContent = submitButton.dataset.createLabel || '🚀 Lưu nhật ký điểm danh';
    }

    function prepareTeacherAttendanceEdit(sessionId) {
        applyTeacherAttendanceActionMode('update', sessionId);
    }

    function initializeTeacherAttendanceInteractions() {
        syncTeacherAttendancePeriods();
        applyTeacherAttendanceActionMode(document.getElementById('attendance-action-mode')?.value || 'create', document.getElementById('attendance-session-id')?.value || '');

        document.querySelectorAll('[data-attendance-status-input]').forEach((input) => {
            input.addEventListener('change', () => {
                paintTeacherAttendanceStatus(input);
            });
        });

        hydrateTeacherAttendanceSavedStatuses();

        document.querySelectorAll('.teacher-attendance-modal').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                }
            });
        });

        document.querySelectorAll('[data-mark-all-present]').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-attendance-present]:not(:disabled)').forEach((input) => {
                    input.checked = true;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTeacherAttendanceInteractions);
    } else {
        initializeTeacherAttendanceInteractions();
    }
</script>
@endsection
