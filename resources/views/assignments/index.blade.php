@extends('layouts.app')
@section('title', 'Phân công giảng dạy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Phân công giảng dạy</h5>
        <div class="text-muted">Gán giáo viên - lớp - môn - năm học</div>
    </div>
    <a class="btn btn-primary" href="{{ route('assignments.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm phân công</a>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Giáo viên</th>
                    <th>Lớp</th>
                    <th>Môn</th>
                    <th>Năm học</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($assignments as $assignment)
                <tr>
                    <td>{{ $assignment->teacher->name }}</td>
                    <td>{{ $assignment->classRoom->name }}</td>
                    <td>{{ $assignment->subject->name }}</td>
                    <td>{{ $assignment->schoolYear->name }}</td>
                    <td class="text-end">
                        <a href="{{ route('assignments.edit', $assignment) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('assignments.destroy', $assignment) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa phân công?')">
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
