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
                        <div class="content-action-group justify-content-end">
                            <a href="{{ route('assignments.edit', $assignment) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                            </a>
                            <form action="{{ route('assignments.destroy', $assignment) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="content-action-btn icon-only delete" title="Xóa" aria-label="Xóa" data-bs-toggle="tooltip">
                                    <i class="bi bi-trash"></i><span class="visually-hidden">Xóa</span>
                                </button>
                            </form>
                        </div>
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
