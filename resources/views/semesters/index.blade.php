@extends('layouts.app')
@section('title', 'Học kỳ')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Học kỳ</h5>
        <div class="text-muted">Quản lý học kỳ theo năm học</div>
    </div>
    <a class="btn btn-primary" href="{{ route('semesters.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm học kỳ</a>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Thứ tự</th>
                    <th>Năm học</th>
                    <th>Nhập điểm</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($semesters as $semester)
                <tr>
                    <td>{{ $semester->name }}</td>
                    <td>{{ $semester->order }}</td>
                    <td>{{ $semester->schoolYear->name ?? '' }}</td>
                    <td>{!! $semester->is_score_input_open ? '<span class="badge bg-success">Mở</span>' : '<span class="badge bg-secondary">Khóa</span>' !!}</td>
                    <td class="text-end">
                        <a href="{{ route('semesters.edit', $semester) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('semesters.destroy', $semester) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa học kỳ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
