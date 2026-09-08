@extends('layouts.app')
@section('title', 'Thời khóa biểu')

@section('content')
@php
    $isManagementUser = auth()->user()->isAdmin() || auth()->user()->isStaff();
    $viewMode = $viewMode ?? 'class';
@endphp

<div class="page-heading">
    <div>
        <h5>Xem thời khóa biểu</h5>
        <div class="text-muted">
            @if(auth()->user()->isStudent())
                Thời khóa biểu của lớp đang học.
            @elseif($isManagementUser)
                Chọn lớp hoặc giáo viên và học kỳ để xem lịch học.
            @else
                Chọn lớp và học kỳ để xem lịch học.
            @endif
        </div>
    </div>
    @if(auth()->user()->isAdmin())
        <a class="btn btn-outline-primary" href="{{ route('timetable.manage') }}"><i class="bi bi-pencil-square me-1"></i>Quản lý thời khóa biểu</a>
    @endif
</div>

@unless(auth()->user()->isStudent())
    <form method="GET" class="card mb-3" data-timetable-view-form>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                @if($selectedYearId)
                    <input type="hidden" name="school_year_id" value="{{ $selectedYearId }}">
                @endif
                @if($isManagementUser)
                    <div class="col-md-3">
                        <label class="form-label">Chế độ xem</label>
                        <select class="form-select" name="view_mode" data-timetable-view-mode>
                            <option value="class" @selected($viewMode === 'class')>Theo lớp</option>
                            <option value="teacher" @selected($viewMode === 'teacher')>Theo giáo viên</option>
                        </select>
                    </div>
                @endif
                <div class="col-md-3" data-class-view-field>
                    <label class="form-label">Lớp</label>
                    <select class="form-select" name="class_id">
                        <option value="">-- Chọn lớp --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected($selectedClass && $selectedClass->id === $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($isManagementUser)
                    <div class="col-md-3" data-teacher-view-field>
                        <label class="form-label">Giáo viên</label>
                        <select class="form-select" name="teacher_id">
                            <option value="">-- Chọn giáo viên --</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected($selectedTeacher && $selectedTeacher->id === $teacher->id)>
                                    {{ $teacher->teacher_code }} - {{ $teacher->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label">Học kỳ</label>
                    <select class="form-select" name="semester_id" required>
                        <option value="">-- Chọn học kỳ --</option>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected(($selectedSemester?->id ?? $selectedSemesterId) === $semester->id)>{{ $semester->normalizedName() }} ({{ $semester->schoolYear->name ?? '' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Xem</button>
                </div>
            </div>
        </div>
    </form>
@endunless

@if($viewMode === 'teacher' && $selectedTeacher && $selectedSemester)
    <div class="card timetable-grid">
        <div class="card-header">Thời khóa biểu giáo viên {{ $selectedTeacher->name }} - {{ $selectedSemester->normalizedName() }}</div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:120px;">Buổi / Tiết</th>
                        @foreach($days as $dayLabel)
                            <th>{{ $dayLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach($periodGroups as $periodGroup)
                    <tr class="table-light">
                        <td colspan="{{ count($days) + 1 }}" class="fw-bold">{{ $periodGroup['label'] }}</td>
                    </tr>
                    @foreach($periodGroup['periods'] as $period => $periodLabel)
                    <tr>
                        <td class="fw-semibold">{{ $periodLabel }}</td>
                        @foreach($days as $day => $dayLabel)
                            @php
                                $slotEntries = ($teacherSchedule ?? collect())->get($day.'-'.$period, collect());
                            @endphp
                            <td>
                                @forelse($slotEntries as $entry)
                                    <div class="mb-2 p-2 rounded border border-orange-100 bg-orange-50/40">
                                        <div class="fw-semibold">{{ $entry->displaySubjectName() }}</div>
                                        <div class="text-muted small">
                                            Lớp {{ $entry->timetable?->classRoom?->name ?? '-' }}
                                            @if($entry->displaySubstituteMarker())
                                                <span class="text-orange-500 font-normal">{{ $entry->displaySubstituteMarker() }}</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small">Phòng: {{ $entry->displayRoomLabel() ?? '-' }}</div>
                                    </div>
                                @empty
                                    <span class="text-muted">-</span>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif($viewMode === 'teacher' && $isManagementUser)
    <div class="card">
        <div class="empty-state">
            <i class="bi bi-calendar3-week"></i>
            Chọn giáo viên và học kỳ để xem thời khóa biểu.
        </div>
    </div>
@elseif($selectedClass && $selectedSemester)
    <div class="card timetable-grid">
        <div class="card-header">Thời khóa biểu lớp {{ $selectedClass->name }} - {{ $selectedSemester->normalizedName() }}</div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:120px;">Buổi / Tiết</th>
                        @foreach($days as $dayLabel)
                            <th>{{ $dayLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach($periodGroups as $periodGroup)
                    <tr class="table-light">
                        <td colspan="{{ count($days) + 1 }}" class="fw-bold">{{ $periodGroup['label'] }}</td>
                    </tr>
                    @foreach($periodGroup['periods'] as $period => $periodLabel)
                    <tr>
                        <td class="fw-semibold">{{ $periodLabel }}</td>
                        @foreach($days as $day => $dayLabel)
                            @php
                                $entry = $entries[$day.'-'.$period] ?? null;
                            @endphp
                            <td>
                                @if($entry)
                                    <div class="fw-semibold">{{ $entry->displaySubjectName() }}</div>
                                    <div class="text-muted small">
                                        {{ $entry->displayTeacherName() }}
                                        @if($entry->displaySubstituteMarker())
                                            <span class="text-orange-500 font-normal">{{ $entry->displaySubstituteMarker() }}</span>
                                        @endif
                                        @if($entry->displayRoomLabel()) · {{ $entry->displayRoomLabel() }} @endif
                                    </div>
                                    @if($entry->status !== \App\Models\TimetableEntry::STATUS_ACTIVE)
                                        <span class="badge {{ $entry->statusBadgeClass() }}">{{ $entry->statusLabel() }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif(auth()->user()->isStudent())
    <div class="card">
        <div class="empty-state">
            <i class="bi bi-calendar3-week"></i>
            Chưa có thời khóa biểu cho lớp của bạn trong học kỳ hiện hành.
        </div>
    </div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-timetable-view-form]');
        const modeSelect = form?.querySelector('[data-timetable-view-mode]');
        const classField = form?.querySelector('[data-class-view-field]');
        const teacherField = form?.querySelector('[data-teacher-view-field]');
        const classSelect = classField?.querySelector('select');
        const teacherSelect = teacherField?.querySelector('select');

        const syncMode = () => {
            const isTeacher = modeSelect?.value === 'teacher';
            classField?.classList.toggle('d-none', isTeacher);
            teacherField?.classList.toggle('d-none', !isTeacher);
            if (classSelect) classSelect.required = !isTeacher;
            if (teacherSelect) teacherSelect.required = isTeacher;
        };

        modeSelect?.addEventListener('change', syncMode);
        syncMode();
    });
</script>
@endsection
