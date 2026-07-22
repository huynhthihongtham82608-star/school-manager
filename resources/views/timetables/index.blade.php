@extends('layouts.app')
@section('title', 'Thời khóa biểu')

@section('content')
<div class="page-heading">
    <div>
        <h5>Xem thời khóa biểu</h5>
        <div class="text-muted">
            @if(auth()->user()->isStudent())
                Thời khóa biểu của lớp đang học.
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
    <form method="GET" class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                @if($selectedYearId)
                    <input type="hidden" name="school_year_id" value="{{ $selectedYearId }}">
                @endif
                <div class="col-md-4">
                    <label class="form-label">Lớp</label>
                    <select class="form-select" name="class_id" required>
                        <option value="">-- Chọn lớp --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected($selectedClass && $selectedClass->id === $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
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

@if($selectedClass && $selectedSemester)
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
                            @php($entry = $entries[$day.'-'.$period] ?? null)
                            <td>
                                @if($entry)
                                    <div class="fw-semibold">{{ $entry->displaySubjectName() }}</div>
                                    <div class="text-muted small">
                                        {{ $entry->displayTeacherName() }}
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
@endsection
