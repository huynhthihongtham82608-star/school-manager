@extends('layouts.app')
@section('title', 'AI nhận xét')

@section('content')
<h5 class="mb-3">AI nhận xét</h5>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Học sinh</th>
                    <th>Học kỳ</th>
                    <th>Xu hướng</th>
                    <th>Nhận xét</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
            @forelse($reports as $r)
                <tr>
                    <td>{{ $r->student?->name }}</td>
                    <td>{{ $r->semester?->name }}</td>
                    <td>{{ $r->trend }}</td>
                    <td style="white-space: pre-wrap;">{{ $r->summary }}</td>
                    <td class="text-muted">{{ $r->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted p-4">Chưa có nhận xét.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
