@extends('layouts.app')
@section('title', 'Môn học')

@section('content')
<div class="page-heading">
    <div>
        <h5>Môn học</h5>
        <div class="text-muted">Danh sách môn và hệ số tính điểm.</div>
    </div>
    <a class="btn btn-primary" href="{{ route('subjects.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm môn</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tên môn</th>
                    <th>Tín chỉ</th>
                    <th>Hệ số 2</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($subjects as $subject)
                <tr>
                    <td class="fw-semibold">{{ $subject->name }}</td>
                    <td>{{ $subject->credit }}</td>
                    <td>{!! $subject->is_weighted ? '<span class="badge bg-info">Có</span>' : '-' !!}</td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <a href="{{ route('subjects.edit', $subject) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                            </a>
                            <form action="{{ route('subjects.destroy', $subject) }}" method="POST" class="d-inline">
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
                    <td colspan="4"><div class="empty-state"><i class="bi bi-book"></i>Chưa có môn học.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
