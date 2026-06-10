@extends('layouts.app')
@section('title', 'Thời khóa biểu của tôi')

@section('content')
<h5 class="mb-3">Thời khóa biểu của tôi</h5>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
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
                    <td>{{ $dayMap[$e->day_of_week] ?? $e->day_of_week }}</td>
                    <td>{{ $e->period }}</td>
                    <td>{{ $e->timetable->classRoom->name ?? '' }}</td>
                    <td>{{ $e->subject->name ?? '' }}</td>
                    <td>{{ $e->room }}</td>
                    <td>{{ $e->note }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted p-4">Chưa có dữ liệu thời khóa biểu.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
