@extends('layouts.app')
@section('title', 'Quản lý thời khóa biểu')

@section('content')
<x-page-header
    title="Quản lý thời khóa biểu"
    subtitle="Tạo và cập nhật lịch học theo lớp, buổi, tiết, giáo viên, môn học và phòng học."
>
    <a class="btn btn-outline-secondary" href="{{ route('timetable.index') }}">Xem thời khóa biểu</a>
</x-page-header>

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Năm học</label>
                <select class="form-select" name="school_year_id" required>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Học kỳ</label>
                <select class="form-select" name="semester_id" required>
                    <option value="">-- Chọn học kỳ --</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected(($selectedSemester?->id ?? $selectedSemesterId) === $semester->id)>{{ $semester->normalizedName() }} ({{ $semester->schoolYear->name ?? '' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Lớp</label>
                <select class="form-select" name="class_id" required>
                    <option value="">-- Chọn lớp --</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected($selectedClass && $selectedClass->id === $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Mở bảng</button>
            </div>
        </div>
    </div>
</form>

@if($timetable)
    @if(! $readOnly && $cloneTargetSemesters->isNotEmpty())
        <div class="card mb-3">
            <div class="card-body">
                <form method="POST" action="{{ route('timetable.clone') }}" class="row g-3 align-items-end">
                    @csrf
                    <input type="hidden" name="source_class_id" value="{{ $selectedClass->id }}">
                    <input type="hidden" name="source_semester_id" value="{{ $selectedSemester->id }}">
                    <div class="col-md-4">
                        <label class="form-label">Clone HK1 sang HK2 cùng lớp</label>
                        <select name="target_semester_id" class="form-select" required>
                            @foreach($cloneTargetSemesters as $semester)
                                <option value="{{ $semester->id }}">{{ $semester->normalizedName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-files me-1"></i>Clone học kỳ</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($assignments->isEmpty() && $specialSubjects->isEmpty())
        <div class="alert alert-warning">
            Lớp này chưa có phân công giảng dạy đang hoạt động trong học kỳ đã chọn.
        </div>
    @elseif($assignments->isEmpty())
        <div class="alert alert-info">
            Lớp này chưa có phân công môn chính khóa. Bạn vẫn có thể xếp các môn Chủ nhiệm hoặc Hoạt động nếu phù hợp.
        </div>
    @endif

    <form method="POST" action="{{ route('timetable.entries.save') }}">
        @csrf
        <input type="hidden" name="timetable_id" value="{{ $timetable->id }}">
        <div class="card timetable-grid">
            <div class="card-header">
                Thời khóa biểu: {{ $selectedClass->name }} - {{ $selectedSemester->normalizedName() }}
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
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
                                @php($entry = $entries[$day.'-'.$period] ?? null)
                                @php($selectedEntryValue = $entry ? ($entry->assignment_id ? 'assignment:'.$entry->assignment_id : ($entry->subject_id ? 'subject:'.$entry->subject_id : '')) : '')
                                <td style="min-width: 260px;">
                                    <div class="mb-2">
                                        <select class="form-select form-select-sm" name="entries[{{ $day }}][{{ $period }}][entry_value]" @disabled($readOnly)>
                                            <option value="">-- Trống --</option>
                                            @if($assignments->isNotEmpty())
                                                <optgroup label="Môn chính khóa đã phân công">
                                                    @foreach($assignments as $assignment)
                                                        @php($effectivePeriods = $assignment->effectiveWeeklyPeriods())
                                                        <option value="assignment:{{ $assignment->id }}" @selected($selectedEntryValue === 'assignment:'.$assignment->id)>
                                                            {{ $assignment->subject->name ?? '' }} - {{ $assignment->teacher->name ?? '' }} ({{ $assignment->roleLabel() }}){{ $effectivePeriods ? ' - ' . $effectivePeriods . ' tiết/tuần, ' . mb_strtolower($assignment->weeklyPeriodSourceLabel()) : ' - chưa có định mức' }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                            @if($specialSubjects->isNotEmpty())
                                                <optgroup label="Môn chủ nhiệm / hoạt động">
                                                    @foreach($specialSubjects as $subject)
                                                        <option value="subject:{{ $subject->id }}" @selected($selectedEntryValue === 'subject:'.$subject->id)>
                                                            {{ $subject->name }} - {{ $subject->typeLabel() }}{{ $subject->isHomeroomSubject() ? ' - tự lấy GVCN' : ' - không cần phân công' }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2 mb-2">
                                        <select class="form-select form-select-sm" name="entries[{{ $day }}][{{ $period }}][room_id]" @disabled($readOnly)>
                                            <option value="">-- Phòng --</option>
                                            @foreach($rooms as $room)
                                                <option value="{{ $room->id }}" @selected($entry && $entry->room_id === $room->id)>{{ $room->name }}</option>
                                            @endforeach
                                        </select>
                                        <select class="form-select form-select-sm" name="entries[{{ $day }}][{{ $period }}][status]" @disabled($readOnly)>
                                            @foreach($statuses as $value => $label)
                                                <option value="{{ $value }}" @selected(($entry->status ?? \App\Models\TimetableEntry::STATUS_ACTIVE) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if($entry)
                                        <div class="small text-muted">
                                            {{ $entry->displayTeacherName() ?: 'Không có giáo viên cụ thể' }}
                                            @if($entry->displayRoomLabel())
                                                · {{ $entry->displayRoomLabel() }}
                                            @endif
                                            @if($entry->status)
                                                · {{ $entry->statusLabel() }}
                                            @endif
                                        </div>
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
        @unless($readOnly)
            <div class="form-actions mt-3">
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu thời khóa biểu</button>
            </div>
        @endunless
    </form>
@endif
@endsection
