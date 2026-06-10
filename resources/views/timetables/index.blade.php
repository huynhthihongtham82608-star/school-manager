@extends('layouts.app')
@section('title', 'Thời khóa biểu')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Xem thời khóa biểu</h5>
        <div class="text-muted">Chọn lớp và học kỳ để xem</div>
    </div>
    @if(auth()->user()->isAdmin())
        <a class="btn btn-outline-primary" href="{{ route('timetable.manage') }}"><i class="bi bi-pencil-square me-1"></i>Quản lý thời khóa biểu</a>
    @endif
</div>

<form method="GET" class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label">Lớp</label>
        <select class="form-select" name="class_id" required>
            <option value="">-- Chọn lớp --</option>
            @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected($selectedClass && $selectedClass->id === $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Học kỳ</label>
        <select class="form-select" name="semester_id" required>
            <option value="">-- Chọn học kỳ --</option>
            @foreach($semesters as $s)
                <option value="{{ $s->id }}" @selected($selectedSemester && $selectedSemester->id === $s->id)>{{ $s->name }} ({{ $s->schoolYear->name ?? '' }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 align-self-end">
        <button class="btn btn-primary w-100">Xem</button>
    </div>
</form>

@if($selectedClass && $selectedSemester)
    @php
        $days = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7'];
        $periods = [1,2,3,4,5];
    @endphp
    <div class="card shadow-sm">
        <div class="card-header fw-semibold">Thời khóa biểu lớp {{ $selectedClass->name }} - {{ $selectedSemester->name }}</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width:110px;">Tiết</th>
                        @foreach($days as $dLabel)
                            <th>{{ $dLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach($periods as $p)
                    <tr>
                        <td class="fw-semibold">Tiết {{ $p }}</td>
                        @foreach($days as $d => $dLabel)
                            @php($e = $entries[$d.'-'.$p] ?? null)
                            <td>
                                @if($e)
                                    <div class="fw-semibold">{{ $e->subject->name ?? '' }}</div>
                                    <div class="text-muted small">{{ $e->teacher->name ?? '' }} @if($e->room) · Phòng {{ $e->room }} @endif</div>
                                    @if($e->note)<div class="text-muted small">{{ $e->note }}</div>@endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
