@extends('layouts.app')
@section('title', 'Học kỳ')

@section('content')
<div class="page-heading">
    <div>
        <h5>Học kỳ</h5>
        <div class="text-muted">Quản lý học kỳ theo năm học.</div>
    </div>
    <a class="btn btn-primary" href="{{ route('semesters.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm học kỳ</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
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
            @forelse($semesters as $semester)
                <tr>
                    <td class="fw-semibold">{{ $semester->name }}</td>
                    <td>{{ $semester->order }}</td>
                    <td>{{ $semester->schoolYear->name ?? '' }}</td>
                    <td>{!! $semester->is_score_input_open ? '<span class="badge bg-success">Mở</span>' : '<span class="badge bg-secondary">Khóa</span>' !!}</td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <a href="{{ route('semesters.edit', $semester) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                            </a>
                            <form action="{{ route('semesters.destroy', $semester) }}" method="POST" class="d-inline">
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
                    <td colspan="5"><div class="empty-state"><i class="bi bi-calendar-range"></i>Chưa có học kỳ.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
