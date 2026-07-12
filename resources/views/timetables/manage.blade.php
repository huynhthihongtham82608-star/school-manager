@extends('layouts.app')
@section('title', 'Quản lý thời khóa biểu')

@section('content')
<div class="page-heading">
    <div>
        <h5>Quản lý thời khóa biểu</h5>
        <div class="text-muted">Tạo và cập nhật lịch học từ phân công giảng dạy.</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('timetable.index') }}">Xem thời khóa biểu</a>
</div>

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

    @if($assignments->isEmpty())
        <div class="alert alert-warning">
            Lớp này chưa có phân công giảng dạy đang hoạt động trong học kỳ đã chọn.
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
                            <th style="width:100px;">Tiết</th>
                            @foreach($days as $dayLabel)
                                <th>{{ $dayLabel }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($periods as $period)
                        <tr>
                            <td class="fw-semibold">Tiết {{ $period }}</td>
                            @foreach($days as $day => $dayLabel)
                                @php($entry = $entries[$day.'-'.$period] ?? null)
                                <td style="min-width: 260px;">
                                    <div class="mb-2">
                                        <select class="form-select form-select-sm" name="entries[{{ $day }}][{{ $period }}][assignment_id]" @disabled($readOnly)>
                                            <option value="">-- Trống --</option>
                                            @foreach($assignments as $assignment)
                                                @php($norm = $assignment->subject?->periodNormForGrade((int) $selectedClass->grade_level))
                                                <option value="{{ $assignment->id }}" @selected($entry && $entry->assignment_id === $assignment->id)>
                                                    {{ $assignment->subject->name ?? '' }} - {{ $assignment->teacher->name ?? '' }} ({{ $assignment->roleLabel() }}){{ $norm ? ' - ' . $norm->periods_per_week . ' tiết/tuần' : '' }}
                                                </option>
                                            @endforeach
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
                                            {{ $entry->assignment?->teacher?->name ?? $entry->teacher?->name }}
                                            @if($entry->status)
                                                · {{ $entry->statusLabel() }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
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
