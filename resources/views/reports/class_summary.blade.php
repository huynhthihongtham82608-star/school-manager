@extends('layouts.app')
@section('title', 'Báo cáo lớp')

@section('content')
<div class="page-heading">
    <div>
        <h5>Báo cáo tổng kết lớp</h5>
        <div class="text-muted">Xem tổng quan điểm trung bình, học lực và hạnh kiểm theo học kỳ.</div>
    </div>
</div>

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
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
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Xem báo cáo</button>
            </div>
        </div>
    </div>
</form>

@if($selectedClass && $selectedSemester)
    <div class="row g-3 mb-3">
        @php($total = max($rows->count(), 1))
        <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small fw-semibold">Giỏi</div><div class="stat-value">{{ $stats['excellent'] }}</div><div class="small text-muted">{{ round($stats['excellent']/$total*100,1) }}%</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small fw-semibold">Khá</div><div class="stat-value">{{ $stats['good'] }}</div><div class="small text-muted">{{ round($stats['good']/$total*100,1) }}%</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small fw-semibold">Trung bình</div><div class="stat-value">{{ $stats['average'] }}</div><div class="small text-muted">{{ round($stats['average']/$total*100,1) }}%</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small fw-semibold">Yếu</div><div class="stat-value">{{ $stats['weak'] }}</div><div class="small text-muted">{{ round($stats['weak']/$total*100,1) }}%</div></div></div></div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
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
                @forelse($rows as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['student']->student_code }}</td>
                        <td>{{ $row['student']->name }}</td>
                        <td>{{ $row['avg'] }}</td>
                        <td>{{ $row['study_rank'] }}</td>
                        <td>{{ $row['conduct'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"><div class="empty-state"><i class="bi bi-clipboard-data"></i>Không có dữ liệu báo cáo.</div></td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
