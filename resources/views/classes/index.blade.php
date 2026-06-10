@extends('layouts.app')
@section('title', 'Lớp học')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Lớp học</h5>
        <div class="text-muted">Quản lý lớp 10/11/12</div>
    </div>
    <a class="btn btn-primary" href="{{ route('classes.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm lớp</a>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Tên lớp</th>
                    <th>Khối</th>
                    <th>Năm học</th>
                    <th>GVCN</th>
                    <th>Sĩ số</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($classes as $class)
                <tr>
                    <td>{{ $class->name }}</td>
                    <td>{{ $class->grade_level }}</td>
                    <td>{{ $class->schoolYear->name ?? '' }}</td>
                    <td>{{ $class->homeroomTeacher->name ?? '-' }}</td>
                    <td>{{ $class->students->count() }} / {{ $class->capacity }}</td>
                    <td class="text-end">
                        <a href="{{ route('classes.edit', $class) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('classes.destroy', $class) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa lớp này?')">
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
