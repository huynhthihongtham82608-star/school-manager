@extends('layouts.app')
@section('title', 'Hạnh kiểm')

@section('content')
<h5 class="mb-3">Nhập hạnh kiểm</h5>
<form method="GET" class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label">Lớp</label>
        <select name="class_id" class="form-select" required>
            <option value="">--Chọn lớp--</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected($selectedClass && $class->id === $selectedClass->id)>{{ $class->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Học kỳ</label>
        <select name="semester_id" class="form-select" required>
            <option value="">--Chọn học kỳ--</option>
            @foreach($semesters as $semester)
                <option value="{{ $semester->id }}" @selected($selectedSemester && $semester->id === $selectedSemester->id)>{{ $semester->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 align-self-end">
        <button class="btn btn-primary">Mở danh sách</button>
    </div>
</form>

@if($selectedClass && $selectedSemester)
    <form method="POST" action="{{ route('conduct.store') }}">
        @csrf
        <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
        <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Mã HS</th>
                            <th>Họ tên</th>
                            <th>Hạnh kiểm</th>
                            <th>Nhận xét</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($students as $student)
                        @php($record = $records[$student->id] ?? null)
                        <tr>
                            <td>{{ $student->student_code }}</td>
                            <td>{{ $student->name }}</td>
                            <td>
                                <select name="conduct[{{ $student->id }}][conduct_level]" class="form-select form-select-sm">
                                    @foreach(['excellent' => 'Tốt', 'good' => 'Khá', 'average' => 'Trung bình', 'weak' => 'Yếu'] as $k => $label)
                                        <option value="{{ $k }}" @selected($record && $record->conduct_level === $k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="text" name="conduct[{{ $student->id }}][comment]" class="form-control form-control-sm" value="{{ $record?->comment }}"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3 text-end">
            <button class="btn btn-primary">Lưu hạnh kiểm</button>
        </div>
    </form>
@endif
@endsection
