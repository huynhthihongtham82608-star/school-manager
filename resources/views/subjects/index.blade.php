@extends('layouts.app')
@section('title', 'Môn học')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Môn học</h5>
        <div class="text-muted">Danh sách môn</div>
    </div>
    <a class="btn btn-primary" href="{{ route('subjects.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm môn</a>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Tên môn</th>
                    <th>Tín chỉ</th>
                    <th>Hệ số 2</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($subjects as $subject)
                <tr>
                    <td>{{ $subject->name }}</td>
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
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
