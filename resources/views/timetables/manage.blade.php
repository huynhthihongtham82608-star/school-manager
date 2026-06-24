@extends('layouts.app')
@section('title', 'Quản lý thời khóa biểu')

@section('content')
<div class="page-heading">
    <div>
        <h5>Quản lý thời khóa biểu</h5>
        <div class="text-muted">Tạo/cập nhật thời khóa biểu theo lớp và học kỳ.</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('timetable.index') }}">Xem thời khóa biểu</a>
</div>

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
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
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Mở bảng</button>
            </div>
        </div>
    </div>
</form>

@if($timetable)
    @php
        $days = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7'];
        $periods = [1,2,3,4,5];
    @endphp

    <form method="POST" action="{{ route('timetable.entries.save') }}">
        @csrf
        <input type="hidden" name="timetable_id" value="{{ $timetable->id }}">
        <div class="card timetable-grid">
            <div class="card-header">Sửa thời khóa biểu: {{ $selectedClass->name }} - {{ $selectedSemester->name }}</div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:100px;">Tiết</th>
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
                                <td style="min-width: 240px;">
                                    <div class="mb-2">
                                        <select class="form-select form-select-sm" name="entries[{{ $d }}][{{ $p }}][subject_id]">
                                            <option value="">-- Môn --</option>
                                            @foreach($subjects as $sub)
                                                <option value="{{ $sub->id }}" @selected($e && $e->subject_id === $sub->id)>{{ $sub->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <select class="form-select form-select-sm" name="entries[{{ $d }}][{{ $p }}][teacher_id]">
                                            <option value="">-- GV --</option>
                                            @foreach($teachers as $t)
                                                <option value="{{ $t->id }}" @selected($e && $e->teacher_id === $t->id)>{{ $t->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <input class="form-control form-control-sm" name="entries[{{ $d }}][{{ $p }}][room]" placeholder="Phòng" value="{{ $e->room ?? '' }}">
                                        <input class="form-control form-control-sm" name="entries[{{ $d }}][{{ $p }}][note]" placeholder="Ghi chú" value="{{ $e->note ?? '' }}">
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3 text-end">
            <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu thời khóa biểu</button>
        </div>
    </form>
@endif
@endsection
