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
                        <a href="{{ route('subjects.edit', $subject) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('subjects.destroy', $subject) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa môn học?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
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
