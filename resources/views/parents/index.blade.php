@extends('layouts.app')
@section('title', 'Phụ huynh')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Phụ huynh</h5>
        <div class="text-muted">Quản lý phụ huynh và liên kết học sinh</div>
    </div>
    <a class="btn btn-primary" href="{{ route('parents.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm phụ huynh</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Họ tên</th>
                    <th>Liên hệ</th>
                    <th>Học sinh</th>
                    <th>Tài khoản</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($parents as $p)
                <tr>
                    <td class="fw-semibold">{{ $p->name }}</td>
                    <td class="text-muted small">{{ $p->phone }}<br>{{ $p->email }}</td>
                    <td>
                        @forelse($p->students as $st)
                            <span class="badge bg-info text-dark">{{ $st->student_code }}</span>
                        @empty
                            <span class="text-muted">-</span>
                        @endforelse
                    </td>
                    <td>{{ $p->user?->username }}</td>
                    <td>{!! ($p->user && $p->user->is_active) ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' !!}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('parents.edit', $p) }}">Sửa</a>
                        <form method="POST" action="{{ route('parents.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Xóa phụ huynh?')">
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
