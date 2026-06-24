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
                    <th>Môn</th>
                    <th>Phòng</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
            @php($dayMap = [1=>'Thứ 2',2=>'Thứ 3',3=>'Thứ 4',4=>'Thứ 5',5=>'Thứ 6',6=>'Thứ 7',7=>'CN'])
            @forelse($entries as $e)
                <tr>
                    <td class="fw-semibold">{{ $dayMap[$e->day_of_week] ?? $e->day_of_week }}</td>
                    <td>{{ $e->period }}</td>
                    <td>{{ $e->timetable->classRoom->name ?? '' }}</td>
                    <td>{{ $e->subject->name ?? '' }}</td>
                    <td>{{ $e->room }}</td>
                    <td>{{ $e->note }}</td>
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
