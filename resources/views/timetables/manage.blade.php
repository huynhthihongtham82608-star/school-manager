@extends('layouts.app')
@section('title', 'Quản lý thời khóa biểu')

@php
    $defaultRoom = $selectedClass?->fixedRoom;
    $fallbackRoom = $defaultRoom ?: $rooms->first();
    $activeEntryRows = $entries->filter(fn ($entry) => ($entry->status ?? \App\Models\TimetableEntry::STATUS_ACTIVE) !== \App\Models\TimetableEntry::STATUS_ARCHIVED);
    $assignmentCounts = $activeEntryRows
        ->filter(fn ($entry) => filled($entry->assignment_id))
        ->groupBy('assignment_id')
        ->map(fn ($items) => $items->count());
    $specialCounts = $activeEntryRows
        ->filter(fn ($entry) => blank($entry->assignment_id) && filled($entry->subject_id))
        ->groupBy('subject_id')
        ->map(fn ($items) => $items->count());

    $resourceRows = collect();

    foreach ($assignments as $assignment) {
        $expected = (int) ($assignment->effectiveWeeklyPeriods() ?: 0);
        $scheduled = (int) ($assignmentCounts[(string) $assignment->getKey()] ?? 0);
        $resourceRows->push([
            'key' => 'assignment:' . $assignment->getKey(),
            'entryValue' => 'assignment:' . $assignment->getKey(),
            'assignmentId' => (string) $assignment->getKey(),
            'subjectId' => (string) $assignment->subject_id,
            'subjectName' => (string) ($assignment->subject?->name ?? 'Môn học'),
            'teacherId' => (string) $assignment->teacher_id,
            'teacherName' => (string) ($assignment->teacher?->name ?? 'Chưa có giáo viên'),
            'roomId' => $fallbackRoom?->id ? (string) $fallbackRoom->id : '',
            'roomName' => (string) ($fallbackRoom?->name ?? ''),
            'scheduled' => $scheduled,
            'expected' => $expected,
            'expectedLabel' => $expected > 0 ? (string) $expected : 'chưa cấu hình',
            'requiresAssignment' => true,
            'isOfficial' => true,
            'isSpecial' => false,
            'lockedReason' => '',
        ]);
    }

    foreach ($unassignedOfficialSubjects as $subject) {
        $expected = (int) ($subject->periodNormForGrade((int) ($selectedClass?->grade_level ?: 0))?->periods_per_week ?: 0);
        $resourceRows->push([
            'key' => 'subject:' . $subject->getKey(),
            'entryValue' => 'subject:' . $subject->getKey(),
            'assignmentId' => '',
            'subjectId' => (string) $subject->getKey(),
            'subjectName' => (string) $subject->name,
            'teacherId' => '',
            'teacherName' => 'Chưa phân công',
            'roomId' => $fallbackRoom?->id ? (string) $fallbackRoom->id : '',
            'roomName' => (string) ($fallbackRoom?->name ?? ''),
            'scheduled' => 0,
            'expected' => $expected,
            'expectedLabel' => $expected > 0 ? (string) $expected : 'chưa cấu hình',
            'requiresAssignment' => true,
            'isOfficial' => true,
            'isSpecial' => false,
            'lockedReason' => 'unassigned',
        ]);
    }

    foreach ($specialSubjects as $subject) {
        $teacher = $subject->isHomeroomSubject() ? $selectedClass?->homeroomTeacher : null;
        $scheduled = (int) ($specialCounts[(string) $subject->getKey()] ?? 0);
        $roomName = $subject->isActivitySubject() ? 'Sân trường' : (string) ($fallbackRoom?->name ?? '');
        $resourceRows->push([
            'key' => 'subject:' . $subject->getKey(),
            'entryValue' => 'subject:' . $subject->getKey(),
            'assignmentId' => '',
            'subjectId' => (string) $subject->getKey(),
            'subjectName' => (string) $subject->name,
            'teacherId' => $teacher?->id ? (string) $teacher->id : '',
            'teacherName' => $teacher?->name ?: ($subject->isActivitySubject() ? 'Toàn trường' : 'Giáo viên chủ nhiệm'),
            'roomId' => $subject->isActivitySubject() ? '' : ($fallbackRoom?->id ? (string) $fallbackRoom->id : ''),
            'roomName' => $roomName,
            'scheduled' => $scheduled,
            'expected' => null,
            'expectedLabel' => 'linh hoạt',
            'requiresAssignment' => false,
            'isOfficial' => false,
            'isSpecial' => true,
            'lockedReason' => '',
        ]);
    }

    $existingSlots = [];
    foreach ($days as $day => $dayLabel) {
        foreach ($periods as $period) {
            $entry = $entries[$day . '-' . $period] ?? null;
            $entryValue = $entry ? ($entry->assignment_id ? 'assignment:' . $entry->assignment_id : ($entry->subject_id ? 'subject:' . $entry->subject_id : '')) : '';
            $existingSlots[$day . '-' . $period] = [
                'dayOfWeek' => (int) $day,
                'periodId' => (int) $period,
                'entryValue' => $entryValue,
                'assignmentId' => $entry?->assignment_id ? (string) $entry->assignment_id : '',
                'subjectId' => $entry?->subject_id ? (string) $entry->subject_id : '',
                'subjectName' => $entry ? $entry->displaySubjectName() : '',
                'teacherId' => $entry?->teacher_id ? (string) $entry->teacher_id : ($entry?->assignment?->teacher_id ? (string) $entry->assignment->teacher_id : ''),
                'teacherName' => $entry ? $entry->displayTeacherName() : '',
                'roomId' => $entry?->room_id ? (string) $entry->room_id : '',
                'roomName' => (string) ($entry?->displayRoomLabel() ?? ''),
                'status' => $entry?->status ?: \App\Models\TimetableEntry::STATUS_ACTIVE,
                'requiresAssignment' => false,
                'isOfficial' => (bool) ($entry?->assignment_id),
            ];
        }
    }

    $validationRows = $validationEntries->map(function ($entry) {
        return [
            'entryId' => (string) $entry->getKey(),
            'timetableId' => (string) $entry->timetable_id,
            'classId' => (string) ($entry->timetable?->class_id ?? ''),
            'className' => (string) ($entry->timetable?->classRoom?->name ?? ''),
            'dayOfWeek' => (int) $entry->day_of_week,
            'periodId' => (int) $entry->period,
            'teacherId' => $entry->teacher_id ? (string) $entry->teacher_id : ($entry->assignment?->teacher_id ? (string) $entry->assignment->teacher_id : ''),
            'roomId' => $entry->room_id ? (string) $entry->room_id : '',
            'subjectName' => $entry->displaySubjectName(),
            'teacherName' => $entry->displayTeacherName(),
            'roomName' => (string) ($entry->displayRoomLabel() ?? ''),
        ];
    })->values();
@endphp

@section('content')
<style>
    .timetable-page-header h1 {
        display: flex;
        align-items: center;
        color: #111827;
        font-weight: 700;
    }

    .timetable-page-header h1::before {
        width: 4px;
        height: 16px;
        margin-right: 8px;
        display: inline-block;
        border-radius: 999px;
        background: #f97316;
        content: "";
    }

    .timetable-scheduler .form-label,
    .timetable-scheduler .form-select,
    .timetable-scheduler .form-control,
    .timetable-scheduler .btn,
    .timetable-scheduler .dropdown-item,
    .timetable-scheduler td,
    .timetable-scheduler th {
        font-weight: 400;
    }

    .timetable-scheduler .form-select:focus,
    .timetable-scheduler .form-control:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .2rem rgba(255, 237, 213, .95);
    }

    .timetable-scheduler-grid {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-top: 1rem;
    }

    @media (min-width: 992px) {
        .timetable-scheduler-grid {
            grid-template-columns: minmax(250px, 1fr) minmax(0, 3fr);
        }
    }

    .resource-panel,
    .matrix-panel {
        min-width: 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
    }

    .resource-panel {
        align-self: start;
        position: sticky;
        top: 1rem;
    }

    .resource-panel-body {
        padding: 1rem;
    }

    .resource-filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .75rem;
    }

    .resource-list {
        max-height: 65vh;
        overflow-y: auto;
        display: grid;
        gap: .6rem;
        padding-right: .15rem;
    }

    .resource-card {
        cursor: grab;
        user-select: none;
        border: 1px solid transparent;
        border-radius: 8px;
        padding: .72rem .78rem;
        line-height: 1.35;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }

    .resource-card:active {
        cursor: grabbing;
        transform: scale(.99);
    }

    .resource-card.bg-orange-50 {
        color: #c2410c;
        background: #fff7ed;
        border-color: #fed7aa;
    }

    .resource-card.bg-green-50 {
        color: #15803d;
        background: #f0fdf4;
        border-color: #bbf7d0;
    }

    .resource-card.bg-red-50 {
        color: #b91c1c;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .resource-card:hover {
        box-shadow: 0 10px 22px rgba(15, 23, 42, .08);
    }

    .resource-title {
        color: inherit;
        font-size: .94rem;
        font-weight: 500;
    }

    .resource-meta {
        margin-top: .22rem;
        color: inherit;
        opacity: .78;
        font-size: .82rem;
        font-weight: 400;
    }

    .matrix-panel {
        overflow: hidden;
    }

    .matrix-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .9rem 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .matrix-title {
        margin: 0;
        color: #111827;
        font-size: 1rem;
        font-weight: 500;
    }

    .matrix-subtitle {
        margin: .2rem 0 0;
        color: #6b7280;
        font-size: .86rem;
        font-weight: 400;
    }

    .timetable-table {
        width: 100%;
        margin: 0;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .timetable-table th,
    .timetable-table td {
        border-color: #e5e7eb;
        vertical-align: top;
    }

    .timetable-table thead th {
        padding: .72rem .45rem;
        color: #374151;
        background: #f9fafb;
        text-align: center;
        font-size: .9rem;
        font-weight: 500;
    }

    .period-label-cell {
        width: 88px;
        color: #374151;
        background: #fff;
        font-size: .86rem;
        text-align: center;
    }

    .session-row td {
        padding: .45rem .75rem;
        color: #9a3412;
        background: #fff7ed;
        font-size: .86rem;
        font-weight: 500;
    }

    .drop-slot {
        min-height: 112px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: .35rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        padding: .58rem;
        transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
    }

    .drop-slot.is-empty {
        color: #9ca3af;
        background: #f9fafb;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-size: .84rem;
    }

    .drop-slot.is-over {
        border-color: #f97316;
        background: #fff7ed;
        box-shadow: 0 0 0 .18rem rgba(255, 237, 213, .95);
    }

    .drop-slot.has-error {
        background: #fef2f2;
        border-color: #ef4444;
        box-shadow: 0 0 0 .18rem rgba(254, 226, 226, .95);
    }

    .slot-subject {
        color: #111827;
        font-size: .9rem;
        font-weight: 500;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .slot-teacher,
    .slot-room {
        color: #4b5563;
        font-size: .8rem;
        font-weight: 400;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .slot-room-select {
        height: 28px;
        padding: .12rem 1.65rem .12rem .45rem;
        border-radius: 7px;
        font-size: .78rem;
        font-weight: 400;
    }

    .slot-error {
        color: #dc2626;
        font-size: .76rem;
        font-weight: 500;
        line-height: 1.25;
    }

    .slot-clear-btn {
        width: 26px;
        height: 26px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 7px;
        color: #9a3412;
        background: #ffedd5;
    }

    .slot-clear-btn:hover {
        color: #fff;
        background: #ea580c;
    }

    .scheduler-actions {
        display: flex;
        justify-content: flex-end;
        gap: .75rem;
        margin-top: 1rem;
    }

    .btn-orange {
        border-color: #ea580c;
        color: #fff;
        background: #ea580c;
    }

    .btn-orange:hover,
    .btn-orange:focus {
        border-color: #c2410c;
        color: #fff;
        background: #c2410c;
    }

    .btn-outline-orange {
        border-color: #fdba74;
        color: #c2410c;
        background: #fff;
    }

    .btn-outline-orange:hover,
    .btn-outline-orange:focus {
        border-color: #ea580c;
        color: #fff;
        background: #ea580c;
    }

    .scheduler-toast {
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        z-index: 1080;
        min-width: 280px;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        color: #9a3412;
        background: #fff7ed;
        box-shadow: 0 18px 42px rgba(15, 23, 42, .14);
    }

    .scheduler-toast.is-error {
        border-color: #fecaca;
        color: #991b1b;
        background: #fef2f2;
    }
</style>

<x-page-header
    class="timetable-page-header"
    title="Quản lý thời khóa biểu"
    subtitle="Kéo thả môn học vào ma trận tiết học, kiểm tra trùng giáo viên và phòng học theo thời gian thực."
>
    <a class="btn btn-outline-orange" href="{{ route('timetable.index') }}">
        <i class="bi bi-calendar3-week me-1"></i>Xem thời khóa biểu
    </a>
</x-page-header>

<div class="timetable-scheduler">
    @if($timetable && ! $readOnly && $cloneTargetSemesters->isNotEmpty())
        <div class="card mb-3 border-0">
            <div class="card-body">
                <form method="POST" action="{{ route('timetable.clone') }}" class="row g-3 align-items-end">
                    @csrf
                    <input type="hidden" name="source_class_id" value="{{ $selectedClass->id }}">
                    <input type="hidden" name="source_semester_id" value="{{ $selectedSemester->id }}">
                    <div class="col-md-4">
                        <label class="form-label text-sm">Clone HK1 sang HK2 cùng lớp</label>
                        <select name="target_semester_id" class="form-select" required>
                            @foreach($cloneTargetSemesters as $semester)
                                <option value="{{ $semester->id }}">{{ $semester->normalizedName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-orange w-100">
                            <i class="bi bi-files me-1"></i>Clone học kỳ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 w-full mt-4 timetable-scheduler-grid">
        <aside class="resource-panel">
            <div class="resource-panel-body">
                <form method="GET" data-scheduler-filter-form>
                    <input type="hidden" name="school_year_id" value="{{ $selectedYearId }}">
                    <div class="resource-filter-grid">
                        <div>
                            <label class="form-label text-sm">Chọn lớp</label>
                            <select class="form-select" name="class_id" required data-auto-submit-filter>
                                <option value="">Chọn lớp</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected($selectedClass && $selectedClass->id === $class->id)>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-sm">Chọn học kỳ</label>
                            <select class="form-select" name="semester_id" required data-auto-submit-filter>
                                <option value="">Chọn học kỳ</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" @selected(($selectedSemester?->id ?? $selectedSemesterId) === $semester->id)>{{ $semester->normalizedName() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>

                <div class="mt-3 mb-2 d-flex align-items-center justify-content-between">
                    <div class="text-gray-900 text-base fw-medium">Khay môn học</div>
                    <span class="badge rounded-pill text-bg-light border fw-normal">{{ $resourceRows->count() }} mục</span>
                </div>

                @if($resourceRows->isEmpty())
                    <div class="alert alert-warning mb-0 fw-normal">
                        Chọn lớp và học kỳ để tải danh sách môn học có thể xếp lịch.
                    </div>
                @else
                    <div class="resource-list" data-resource-list>
                        @foreach($resourceRows as $resource)
                            @php
                                $isFlexible = $resource['expected'] === null;
                                $progressClass = $isFlexible
                                    ? ($resource['scheduled'] > 0 ? 'bg-green-50 text-green-700' : 'bg-orange-50 text-orange-700')
                                    : ($resource['scheduled'] > $resource['expected']
                                        ? 'bg-red-50 text-red-700'
                                        : ($resource['expected'] > 0 && $resource['scheduled'] === $resource['expected']
                                            ? 'bg-green-50 text-green-700'
                                            : 'bg-orange-50 text-orange-700'));
                            @endphp
                            <div
                                class="resource-card {{ $progressClass }}"
                                draggable="{{ $readOnly ? 'false' : 'true' }}"
                                data-resource-card
                                data-resource-key="{{ $resource['key'] }}"
                            >
                                <div class="resource-title" data-resource-title>
                                    {{ $resource['subjectName'] }} - {{ $resource['teacherName'] }}
                                </div>
                                <div class="resource-meta" data-resource-progress>
                                    {{ $resource['scheduled'] }} / {{ $resource['expectedLabel'] }} Tiết
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </aside>

        <section class="matrix-panel">
            <div class="matrix-toolbar">
                <div>
                    <h2 class="matrix-title">
                        {{ $selectedClass?->name ? 'Thời khóa biểu lớp ' . $selectedClass->name : 'Chưa chọn lớp' }}
                    </h2>
                    <p class="matrix-subtitle">
                        {{ $selectedSemester?->normalizedName() ?? 'Chọn học kỳ' }}
                        @if($selectedClass?->fixedRoom)
                            · Phòng cố định: {{ $selectedClass->fixedRoom->name }}
                        @endif
                    </p>
                </div>
            </div>

            @if(! $timetable)
                <div class="p-4 text-muted fw-normal">
                    Vui lòng chọn lớp và học kỳ ở khay bên trái để mở ma trận thời khóa biểu.
                </div>
            @else
                <form method="POST" action="{{ route('timetable.entries.save') }}" data-timetable-form>
                    @csrf
                    <input type="hidden" name="timetable_id" value="{{ $timetable->id }}">

                    <div class="table-responsive overflow-hidden">
                        <table class="table timetable-table align-middle">
                            <thead>
                                <tr>
                                    <th class="period-label-cell">Buổi / Tiết</th>
                                    @foreach($days as $dayLabel)
                                        <th>{{ $dayLabel }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($periodGroups as $periodGroup)
                                    <tr class="session-row">
                                        <td colspan="{{ count($days) + 1 }}">{{ $periodGroup['label'] }}</td>
                                    </tr>
                                    @foreach($periodGroup['periods'] as $period => $periodLabel)
                                        <tr>
                                            <td class="period-label-cell">{{ $periodLabel }}</td>
                                            @foreach($days as $day => $dayLabel)
                                                @php
                                                    $slotKey = $day . '-' . $period;
                                                    $slot = $existingSlots[$slotKey];
                                                @endphp
                                                <td>
                                                    <input type="hidden" data-slot-entry-input name="entries[{{ $day }}][{{ $period }}][entry_value]" value="{{ $slot['entryValue'] }}">
                                                    <input type="hidden" data-slot-room-input name="entries[{{ $day }}][{{ $period }}][room_id]" value="{{ $slot['roomId'] }}">
                                                    <input type="hidden" data-slot-status-input name="entries[{{ $day }}][{{ $period }}][status]" value="{{ $slot['status'] }}">
                                                    <div
                                                        class="drop-slot"
                                                        data-drop-slot
                                                        data-day="{{ $day }}"
                                                        data-period="{{ $period }}"
                                                        data-read-only="{{ $readOnly ? '1' : '0' }}"
                                                    ></div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @unless($readOnly)
                        <div class="scheduler-actions">
                            <button type="button" class="btn btn-outline-orange" data-reset-scheduler>
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset bảng
                            </button>
                            <button type="submit" class="btn btn-orange" data-save-scheduler>
                                <i class="bi bi-save me-1"></i>Lưu thời khóa biểu
                            </button>
                        </div>
                    @endunless
                </form>
            @endif
        </section>
    </div>
</div>

@if($timetable)
    <script type="application/json" id="timetable-resource-data">@json($resourceRows->values())</script>
    <script type="application/json" id="timetable-existing-slot-data">@json($existingSlots)</script>
    <script type="application/json" id="timetable-validation-data">@json($validationRows)</script>
    <script type="application/json" id="timetable-room-data">@json($rooms->map(fn ($room) => ['id' => (string) $room->id, 'name' => (string) $room->name])->values())</script>
    <script>
        (() => {
            const form = document.querySelector('[data-timetable-form]');
            if (!form) {
                return;
            }

            const currentTimetableId = @json((string) $timetable->id);
            const statusActive = @json(\App\Models\TimetableEntry::STATUS_ACTIVE);
            const resources = JSON.parse(document.getElementById('timetable-resource-data')?.textContent || '[]');
            const existingSlots = JSON.parse(document.getElementById('timetable-existing-slot-data')?.textContent || '{}');
            const validationRows = JSON.parse(document.getElementById('timetable-validation-data')?.textContent || '[]');
            const rooms = JSON.parse(document.getElementById('timetable-room-data')?.textContent || '[]');
            const resourcesByKey = new Map(resources.map((resource) => [resource.key, resource]));
            const slotState = new Map();
            let draggedResourceKey = '';

            const slotKeyOf = (day, period) => `${day}-${period}`;
            const roomNameOf = (roomId, fallback = '') => rooms.find((room) => String(room.id) === String(roomId))?.name || fallback || '';
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));

            const showToast = (message, isError = false) => {
                const oldToast = document.querySelector('.scheduler-toast');
                oldToast?.remove();
                const toast = document.createElement('div');
                toast.className = `scheduler-toast p-3 ${isError ? 'is-error' : ''}`;
                toast.setAttribute('role', 'status');
                toast.textContent = message;
                document.body.appendChild(toast);
                window.setTimeout(() => toast.remove(), 3600);
            };

            const normalizeSlot = (slot) => ({
                entryValue: slot?.entryValue || '',
                assignmentId: slot?.assignmentId || '',
                subjectId: slot?.subjectId || '',
                subjectName: slot?.subjectName || '',
                teacherId: slot?.teacherId || '',
                teacherName: slot?.teacherName || '',
                roomId: slot?.roomId || '',
                roomName: slot?.roomName || '',
                status: slot?.status || statusActive,
                requiresAssignment: Boolean(slot?.requiresAssignment),
                isOfficial: Boolean(slot?.isOfficial),
            });

            const writeHiddenInputs = (slotElement, slot) => {
                const cell = slotElement.closest('td');
                cell.querySelector('[data-slot-entry-input]').value = slot.entryValue || '';
                cell.querySelector('[data-slot-room-input]').value = slot.roomId || '';
                cell.querySelector('[data-slot-status-input]').value = slot.status || statusActive;
            };

            const assignmentScheduledCount = (assignmentId) => {
                if (!assignmentId) {
                    return 0;
                }

                let count = 0;
                slotState.forEach((slot) => {
                    if (String(slot.assignmentId || '') === String(assignmentId) && slot.entryValue && slot.status !== 'archived') {
                        count += 1;
                    }
                });

                return count;
            };

            const resourceScheduledCount = (resource) => {
                if (!resource) {
                    return 0;
                }

                if (resource.assignmentId) {
                    return assignmentScheduledCount(resource.assignmentId);
                }

                let count = 0;
                slotState.forEach((slot) => {
                    if (slot.entryValue === resource.entryValue && slot.status !== 'archived') {
                        count += 1;
                    }
                });

                return count;
            };

            const progressClassFor = (resource, scheduled) => {
                const expected = resource.expected === null ? null : Number(resource.expected || 0);
                if (expected === null) {
                    return scheduled > 0 ? 'bg-green-50 text-green-700' : 'bg-orange-50 text-orange-700';
                }

                if (scheduled > expected) {
                    return 'bg-red-50 text-red-700';
                }

                if (expected > 0 && scheduled === expected) {
                    return 'bg-green-50 text-green-700';
                }

                return 'bg-orange-50 text-orange-700';
            };

            const updateResourceProgress = () => {
                document.querySelectorAll('[data-resource-card]').forEach((card) => {
                    const resource = resourcesByKey.get(card.dataset.resourceKey);
                    if (!resource) {
                        return;
                    }

                    const scheduled = resourceScheduledCount(resource);
                    card.classList.remove('bg-green-50', 'text-green-700', 'bg-orange-50', 'text-orange-700', 'bg-red-50', 'text-red-700');
                    progressClassFor(resource, scheduled).split(' ').forEach((className) => card.classList.add(className));
                    card.querySelector('[data-resource-progress]').textContent = `${scheduled} / ${resource.expectedLabel} Tiết`;
                });
            };

            window.validateTimetableSlot = (teacherId, roomId, dayOfWeek, periodId) => {
                const key = slotKeyOf(dayOfWeek, periodId);
                const slot = slotState.get(key) || {};
                const resource = resourcesByKey.get(slot.entryValue);
                const errors = [];

                if (!slot.entryValue) {
                    return errors;
                }

                if ((resource?.requiresAssignment || slot.requiresAssignment) && !slot.assignmentId) {
                    errors.push('⚠️ Lỗi: Lớp chưa được phân công giáo viên dạy môn này.');
                }

                if (slot.assignmentId && resource && resource.expected !== null) {
                    const expected = Number(resource.expected || 0);
                    if (expected <= 0 || assignmentScheduledCount(slot.assignmentId) > expected) {
                        errors.push('⚠️ Lỗi: Môn học đã xếp đủ số tiết định mức quy định tuần.');
                    }
                }

                if (teacherId) {
                    const teacherConflict = validationRows.find((row) => (
                        String(row.teacherId || '') === String(teacherId)
                        && Number(row.dayOfWeek) === Number(dayOfWeek)
                        && Number(row.periodId) === Number(periodId)
                        && !(String(row.timetableId) === currentTimetableId && Number(row.dayOfWeek) === Number(dayOfWeek) && Number(row.periodId) === Number(periodId))
                    ));

                    if (teacherConflict) {
                        errors.push('⚠️ Lỗi: Giáo viên đang có giờ dạy trùng tại lớp khác vào tiết này.');
                    }
                }

                if (roomId) {
                    const roomConflict = validationRows.find((row) => (
                        String(row.roomId || '') === String(roomId)
                        && Number(row.dayOfWeek) === Number(dayOfWeek)
                        && Number(row.periodId) === Number(periodId)
                        && !(String(row.timetableId) === currentTimetableId && Number(row.dayOfWeek) === Number(dayOfWeek) && Number(row.periodId) === Number(periodId))
                    ));

                    if (roomConflict) {
                        errors.push('⚠️ Lỗi: Phòng học này đã bị trùng lịch sử dụng.');
                    }
                }

                return errors;
            };

            const renderSlot = (slotElement) => {
                const key = slotKeyOf(slotElement.dataset.day, slotElement.dataset.period);
                const slot = slotState.get(key) || normalizeSlot();
                const readOnly = slotElement.dataset.readOnly === '1';
                const errors = window.validateTimetableSlot(slot.teacherId, slot.roomId, Number(slotElement.dataset.day), Number(slotElement.dataset.period));

                slotElement.classList.toggle('is-empty', !slot.entryValue);
                slotElement.classList.toggle('has-error', errors.length > 0);
                writeHiddenInputs(slotElement, slot);

                if (!slot.entryValue) {
                    slotElement.innerHTML = '<span>Thả môn học vào ô này</span>';
                    return;
                }

                const roomOptions = rooms.map((room) => (
                    `<option value="${escapeHtml(room.id)}" ${String(room.id) === String(slot.roomId) ? 'selected' : ''}>${escapeHtml(room.name)}</option>`
                )).join('');
                const roomSelect = readOnly
                    ? ''
                    : `<select class="form-select slot-room-select" data-slot-room-select>
                        <option value="">Chọn phòng</option>
                        ${roomOptions}
                    </select>`;
                const clearButton = readOnly
                    ? ''
                    : '<button type="button" class="slot-clear-btn" data-clear-slot aria-label="Xóa tiết học"><i class="bi bi-x-lg"></i></button>';
                const errorHtml = errors.length
                    ? `<div class="slot-error">${errors.map((error) => `<div>${error}</div>`).join('')}</div>`
                    : '';

                slotElement.innerHTML = `
                    <div>
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="slot-subject">${escapeHtml(slot.subjectName || 'Môn học')}</div>
                            ${clearButton}
                        </div>
                        <div class="slot-teacher">${escapeHtml(slot.teacherName || 'Chưa có giáo viên')}</div>
                        <div class="slot-room">${escapeHtml(roomNameOf(slot.roomId, slot.roomName) || 'Chưa chọn phòng')}</div>
                    </div>
                    ${roomSelect}
                    ${errorHtml}
                `;
            };

            const validateAllSlots = () => {
                let totalErrors = 0;
                document.querySelectorAll('[data-drop-slot]').forEach((slotElement) => {
                    const key = slotKeyOf(slotElement.dataset.day, slotElement.dataset.period);
                    const slot = slotState.get(key) || {};
                    const errors = window.validateTimetableSlot(slot.teacherId, slot.roomId, Number(slotElement.dataset.day), Number(slotElement.dataset.period));
                    totalErrors += errors.length;
                    renderSlot(slotElement);
                });

                updateResourceProgress();
                return totalErrors;
            };

            Object.entries(existingSlots).forEach(([key, slot]) => {
                const resource = resourcesByKey.get(slot.entryValue);
                slotState.set(key, normalizeSlot({
                    ...slot,
                    assignmentId: slot.assignmentId || resource?.assignmentId || '',
                    subjectId: slot.subjectId || resource?.subjectId || '',
                    subjectName: slot.subjectName || resource?.subjectName || '',
                    teacherId: slot.teacherId || resource?.teacherId || '',
                    teacherName: slot.teacherName || resource?.teacherName || '',
                    roomId: slot.roomId || resource?.roomId || '',
                    roomName: slot.roomName || resource?.roomName || '',
                    requiresAssignment: resource?.requiresAssignment || false,
                    isOfficial: resource?.isOfficial || false,
                }));
            });

            document.querySelectorAll('[data-resource-card]').forEach((card) => {
                card.addEventListener('dragstart', (event) => {
                    draggedResourceKey = card.dataset.resourceKey;
                    event.dataTransfer.effectAllowed = 'copy';
                    event.dataTransfer.setData('text/plain', draggedResourceKey);
                });
            });

            document.querySelectorAll('[data-drop-slot]').forEach((slotElement) => {
                renderSlot(slotElement);

                if (slotElement.dataset.readOnly === '1') {
                    return;
                }

                slotElement.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    slotElement.classList.add('is-over');
                });

                slotElement.addEventListener('dragleave', () => {
                    slotElement.classList.remove('is-over');
                });

                slotElement.addEventListener('drop', (event) => {
                    event.preventDefault();
                    slotElement.classList.remove('is-over');
                    const resourceKey = event.dataTransfer.getData('text/plain') || draggedResourceKey;
                    const resource = resourcesByKey.get(resourceKey);
                    if (!resource) {
                        return;
                    }

                    const key = slotKeyOf(slotElement.dataset.day, slotElement.dataset.period);
                    slotState.set(key, normalizeSlot({
                        entryValue: resource.entryValue,
                        assignmentId: resource.assignmentId,
                        subjectId: resource.subjectId,
                        subjectName: resource.subjectName,
                        teacherId: resource.teacherId,
                        teacherName: resource.teacherName,
                        roomId: resource.roomId,
                        roomName: resource.roomName,
                        status: statusActive,
                        requiresAssignment: resource.requiresAssignment,
                        isOfficial: resource.isOfficial,
                    }));

                    validateAllSlots();
                });
            });

            form.addEventListener('change', (event) => {
                if (!event.target.matches('[data-slot-room-select]')) {
                    return;
                }

                const slotElement = event.target.closest('[data-drop-slot]');
                const key = slotKeyOf(slotElement.dataset.day, slotElement.dataset.period);
                const slot = slotState.get(key) || normalizeSlot();
                slot.roomId = event.target.value;
                slot.roomName = roomNameOf(event.target.value);
                slotState.set(key, slot);
                validateAllSlots();
            });

            form.addEventListener('click', (event) => {
                const clearButton = event.target.closest('[data-clear-slot]');
                if (!clearButton) {
                    return;
                }

                const slotElement = clearButton.closest('[data-drop-slot]');
                const key = slotKeyOf(slotElement.dataset.day, slotElement.dataset.period);
                slotState.set(key, normalizeSlot());
                validateAllSlots();
            });

            document.querySelector('[data-reset-scheduler]')?.addEventListener('click', () => {
                if (!window.confirm('Cảnh báo: thao tác này sẽ xóa sạch bảng lịch biểu hiện tại trên màn hình để xếp lại từ đầu. Bạn có chắc chắn muốn tiếp tục?')) {
                    return;
                }

                slotState.forEach((slot, key) => {
                    slotState.set(key, normalizeSlot());
                });
                validateAllSlots();
                showToast('Đã reset bảng trên màn hình. Bấm Lưu thời khóa biểu để ghi thay đổi vào cơ sở dữ liệu.');
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const totalErrors = validateAllSlots();
                if (totalErrors > 0) {
                    showToast('Vui lòng xử lý các ô đang báo lỗi màu đỏ trước khi lưu.', true);
                    return;
                }

                const saveButton = form.querySelector('[data-save-scheduler]');
                saveButton?.setAttribute('disabled', 'disabled');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form),
                    });
                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const firstError = payload?.errors ? Object.values(payload.errors).flat()[0] : null;
                        throw new Error(firstError || payload?.message || 'Không thể lưu thời khóa biểu.');
                    }

                    const keptRows = validationRows.filter((row) => String(row.timetableId) !== currentTimetableId);
                    slotState.forEach((slot, key) => {
                        if (!slot.entryValue || slot.status !== statusActive) {
                            return;
                        }
                        const [dayOfWeek, periodId] = key.split('-').map(Number);
                        keptRows.push({
                            entryId: `${currentTimetableId}:${key}`,
                            timetableId: currentTimetableId,
                            classId: @json((string) ($selectedClass?->id ?? '')),
                            className: @json((string) ($selectedClass?->name ?? '')),
                            dayOfWeek,
                            periodId,
                            teacherId: slot.teacherId || '',
                            roomId: slot.roomId || '',
                            subjectName: slot.subjectName || '',
                            teacherName: slot.teacherName || '',
                            roomName: roomNameOf(slot.roomId, slot.roomName),
                        });
                    });
                    validationRows.splice(0, validationRows.length, ...keptRows);
                    showToast(payload?.message || 'Đã lưu thời khóa biểu.');
                } catch (error) {
                    showToast(error.message || 'Không thể lưu thời khóa biểu.', true);
                } finally {
                    saveButton?.removeAttribute('disabled');
                }
            });

            document.querySelectorAll('[data-auto-submit-filter]').forEach((select) => {
                select.addEventListener('change', () => {
                    const filterForm = select.closest('[data-scheduler-filter-form]');
                    const classValue = filterForm?.querySelector('[name="class_id"]')?.value;
                    const semesterValue = filterForm?.querySelector('[name="semester_id"]')?.value;
                    if (classValue && semesterValue) {
                        filterForm.submit();
                    }
                });
            });

            validateAllSlots();
        })();
    </script>
@else
    <script>
        (() => {
            document.querySelectorAll('[data-auto-submit-filter]').forEach((select) => {
                select.addEventListener('change', () => {
                    const filterForm = select.closest('[data-scheduler-filter-form]');
                    const classValue = filterForm?.querySelector('[name="class_id"]')?.value;
                    const semesterValue = filterForm?.querySelector('[name="semester_id"]')?.value;
                    if (classValue && semesterValue) {
                        filterForm.submit();
                    }
                });
            });
        })();
    </script>
@endif
@endsection
