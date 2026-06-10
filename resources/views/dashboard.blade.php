@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="row g-3">
    <div class="col-6 col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Học sinh</div>
                <div class="h4 mb-0">{{ $stats['students'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Giáo viên</div>
                <div class="h4 mb-0">{{ $stats['teachers'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Lớp học</div>
                <div class="h4 mb-0">{{ $stats['classes'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Phân công</div>
                <div class="h4 mb-0">{{ $stats['assignments'] }}</div>
            </div>
        </div>
    </div>
</div>

@if($user->isTeacher() && $teacherAssignments->count())
    <div class="card shadow-sm mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="fw-semibold">Lớp được phân công</div>
            @if($homeroomClass)<span class="badge bg-info">GVCN: {{ $homeroomClass->name }}</span>@endif
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Lớp</th>
                        <th>Môn</th>
                        <th>Năm học</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teacherAssignments as $assign)
                        <tr>
                            <td>{{ $assign->classRoom->name }}</td>
                            <td>{{ $assign->subject->name }}</td>
                            <td>{{ $assign->schoolYear->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($user->isHomeroom() && $homeroomClass)
    <div class="card shadow-sm mt-4">
        <div class="card-header fw-semibold">Lớp chủ nhiệm {{ $homeroomClass->name }}</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Mã HS</th>
                        <th>Họ tên</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($homeroomClass->students as $st)
                    <tr>
                        <td>{{ $st->student_code }}</td>
                        <td>{{ $st->name }}</td>
                        <td><span class="badge bg-success">{{ $st->status }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($user->isStudent())
    <div class="card shadow-sm mt-4">
        <div class="card-header fw-semibold">Điểm của tôi</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Môn</th>
                        <th>Học kỳ</th>
                        <th>TB</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($studentScores as $sc)
                    <tr>
                        <td>{{ $sc->subject->name }}</td>
                        <td>{{ $sc->semester->name }}</td>
                        <td>{{ $sc->average }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header fw-semibold">Hạnh kiểm</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Học kỳ</th>
                        <th>Mức</th>
                        <th>Nhận xét</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($conduct as $c)
                    <tr>
                        <td>{{ $c->semester->name }}</td>
                        <td>{{ $c->conduct_level }}</td>
                        <td>{{ $c->comment }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
