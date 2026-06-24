@extends('layouts.app')
@section('title', 'Điểm danh')

@section('content')
<div class="page-heading">
    <div>
        <h5>Điểm danh</h5>
        <div class="text-muted">Ghi nhận và theo dõi tình trạng chuyên cần của học sinh.</div>
    </div>
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isHomeroom())
    <div class="card mb-3">
        <div class="card-header">Chọn lớp điểm danh</div>
        <div class="card-body">
            <form method="GET" action="{{ route('attendance.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Lớp</label>
                    <select name="class_id" class="form-select">
                        <option value="">Chọn lớp</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected($selectedClassId === $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ngày điểm danh</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Tải danh sách</button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedClassId)
        <div class="card mb-3">
            <div class="card-header">Bảng điểm danh</div>
            <form method="POST" action="{{ route('attendance.store') }}">
                @csrf
                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                <input type="hidden" name="attendance_date" value="{{ $date }}">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Học sinh</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td class="fw-semibold">{{ $student->student_code }} - {{ $student->name }}</td>
                                <td>
                                    <select name="status[{{ $student->id }}]" class="form-select">
                                        <option value="present">Có mặt</option>
                                        <option value="absent">Vắng</option>
                                        <option value="late">Đi muộn</option>
                                        <option value="excused">Nghỉ có phép</option>
                                    </select>
                                </td>
                                <td><input name="note[{{ $student->id }}]" class="form-control" placeholder="Ghi chú nếu có"></td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><div class="empty-state"><i class="bi bi-person-dash"></i>Lớp chưa có học sinh.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body border-top">
                    <button class="btn btn-primary"><i class="bi bi-save me-2"></i>Lưu điểm danh</button>
                </div>
            </form>
        </div>
    @endif
@endif

<div class="card">
    <div class="card-header">Lịch sử điểm danh</div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Học sinh</th>
                    <th>Lớp</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
            @forelse($records as $record)
                <tr>
                    <td>{{ optional($record->attendance_date)->format('d/m/Y') }}</td>
                    <td class="fw-semibold">{{ $record->student->name ?? 'Không rõ' }}</td>
                    <td>{{ $record->classRoom->name ?? 'Không rõ' }}</td>
                    <td><span class="badge bg-info">{{ ['present' => 'Có mặt', 'absent' => 'Vắng', 'late' => 'Đi muộn', 'excused' => 'Nghỉ có phép'][$record->status] ?? $record->status }}</span></td>
                    <td>{{ $record->note }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty-state"><i class="bi bi-person-check"></i>Chưa có dữ liệu điểm danh.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($records, 'links'))
    <div class="mt-3">{{ $records->links() }}</div>
@endif
@endsection
