@extends('layouts.app')
@section('title', 'Phân công giảng dạy')

@section('content')
<div class="page-heading">
    <div>
        <h5>Phân công giảng dạy</h5>
        <div class="text-muted">Gán giáo viên - lớp - môn - năm học.</div>
    </div>
    <a class="btn btn-primary" href="{{ route('assignments.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm phân công</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
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
            @forelse($assignments as $assignment)
                <tr>
                    <td class="fw-semibold">{{ $assignment->teacher->name }}</td>
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
            @empty
                <tr>
                    <td colspan="5"><div class="empty-state"><i class="bi bi-diagram-3"></i>Chưa có phân công.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
