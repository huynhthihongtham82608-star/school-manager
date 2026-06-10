@extends('layouts.app')
@section('title', 'AI cảnh báo')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">AI cảnh báo</h5>
        <div class="text-muted">Danh sách học sinh có dấu hiệu cần theo dõi</div>
    </div>
    @if(auth()->user()->isAdmin() || auth()->user()->isHomeroom())
        <a class="btn btn-outline-primary" href="{{ route('ai.run.form') }}"><i class="bi bi-cpu me-1"></i>Chạy phân tích</a>
    @endif
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Mức</th>
                    <th>Học sinh</th>
                    <th>Lớp</th>
                    <th>Học kỳ</th>
                    <th>Cảnh báo</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
            @forelse($alerts as $a)
                <tr>
                    <td>
                        @php($map = ['low'=>'secondary','medium'=>'warning','high'=>'danger'])
                        <span class="badge bg-{{ $map[$a->risk_level] ?? 'secondary' }}">{{ strtoupper($a->risk_level) }}</span>
                    </td>
                    <td>{{ $a->student?->name }}</td>
                    <td>{{ $a->classRoom?->name }}</td>
                    <td>{{ $a->semester?->name }}</td>
                    <td>{{ $a->message }}</td>
                    <td class="text-muted">{{ $a->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted p-4">Chưa có cảnh báo.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
