@extends('layouts.app')
@section('title', 'Báo cáo lớp')

@section('content')
<h5 class="mb-3">Báo cáo tổng kết lớp</h5>
<form method="GET" class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label">Lớp</label>
        <select name="class_id" class="form-select" required>
            <option value="">--Chọn lớp--</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected($selectedClass && $selectedClass->id === $class->id)>{{ $class->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Học kỳ</label>
        <select name="semester_id" class="form-select" required>
            <option value="">--Chọn học kỳ--</option>
            @foreach($semesters as $semester)
                <option value="{{ $semester->id }}" @selected($selectedSemester && $selectedSemester->id === $semester->id)>{{ $semester->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 align-self-end">
        <button class="btn btn-primary">Xem báo cáo</button>
    </div>
</form>

@if($selectedClass && $selectedSemester)
    <div class="row g-3 mb-3">
        @php($total = max($rows->count(), 1))
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted small">Giỏi</div><div class="h4 mb-0">{{ $stats['excellent'] }} ({{ round($stats['excellent']/$total*100,1) }}%)</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted small">Khá</div><div class="h4 mb-0">{{ $stats['good'] }} ({{ round($stats['good']/$total*100,1) }}%)</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted small">Trung bình</div><div class="h4 mb-0">{{ $stats['average'] }} ({{ round($stats['average']/$total*100,1) }}%)</div></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted small">Yếu</div><div class="h4 mb-0">{{ $stats['weak'] }} ({{ round($stats['weak']/$total*100,1) }}%)</div></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Mã HS</th>
                        <th>Họ tên</th>
                        <th>TB</th>
                        <th>Học lực</th>
                        <th>Hạnh kiểm</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row['student']->student_code }}</td>
                        <td>{{ $row['student']->name }}</td>
                        <td>{{ $row['avg'] }}</td>
                        <td>{{ $row['study_rank'] }}</td>
                        <td>{{ $row['conduct'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
