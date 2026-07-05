@extends('layouts.app')
@section('title', 'Thời khóa biểu của tôi')

@section('content')
<div class="page-heading">
    <div>
        <h5>Thời khóa biểu của tôi</h5>
        <div class="text-muted">Danh sách tiết dạy theo thời khóa biểu hiện có.</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Thứ</th>
                    <th>Tiết</th>
                    <th>Lớp</th>
                    <th>Môn học</th>
                    <th>Phòng</th>
                    <th>Vai trò</th>
                </tr>
            </thead>
            <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td class="fw-semibold">{{ $dayMap[$entry->day_of_week] ?? $entry->day_of_week }}</td>
                    <td>{{ $entry->period }}</td>
                    <td>{{ $entry->timetable->classRoom->name ?? '' }}</td>
                    <td>{{ $entry->assignment?->subject?->name ?? $entry->subject?->name ?? '' }}</td>
                    <td>{{ $entry->displayRoom() ?? '-' }}</td>
                    <td>{{ $entry->assignment?->roleLabel() ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="empty-state"><i class="bi bi-calendar3-week"></i>Chưa có dữ liệu thời khóa biểu.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
