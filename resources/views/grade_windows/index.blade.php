@extends('layouts.app')
@section('title', 'Khóa nhập điểm')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Cấu hình cửa sổ nhập điểm</h5>
        <div class="text-muted">Mở/khóa theo lớp - môn - học kỳ</div>
    </div>
</div>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('grade-windows.store') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Lớp</label>
                <select name="class_id" class="form-select" required>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Môn</label>
                <select name="subject_id" class="form-select" required>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Học kỳ</label>
                <select name="semester_id" class="form-select" required>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Năm học</label>
                <select name="school_year_id" class="form-select" required>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="is_open" id="is_open" checked>
                    <label class="form-check-label" for="is_open">Mở</label>
                </div>
            </div>
            <div class="col-md-1 text-end">
                <button class="btn btn-primary w-100">Lưu</button>
            </div>
        </form>
    </div>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Lớp</th>
                    <th>Môn</th>
                    <th>Học kỳ</th>
                    <th>Năm học</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($windows as $window)
                <tr>
                    <td>{{ $window->classRoom->name ?? '' }}</td>
                    <td>{{ $window->subject->name ?? '' }}</td>
                    <td>{{ $window->semester->name ?? '' }}</td>
                    <td>{{ $window->schoolYear->name ?? '' }}</td>
                    <td>{!! $window->is_open ? '<span class="badge bg-success">Mở</span>' : '<span class="badge bg-secondary">Khóa</span>' !!}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('grade-windows.update', $window) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="is_open" value="{{ $window->is_open ? 0 : 1 }}">
                            <button class="btn btn-sm btn-outline-primary">Chuyển {{ $window->is_open ? 'Khóa' : 'Mở' }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
