@extends('layouts.app')
@section('title', 'Tổ chuyên môn')

@section('content')
<div class="page-heading">
    <div>
        <h5>{{ $department->name }}</h5>
        <div class="text-muted">Màn hình tổng quan dành cho tổ trưởng, chỉ hỗ trợ xem và theo dõi.</div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="school-year-data-card">
            <i class="bi bi-book"></i>
            <span><span>Môn phụ trách</span><strong>{{ $department->subjectNames() }}</strong></span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="school-year-data-card">
            <i class="bi bi-people"></i>
            <span><span>Giáo viên trong tổ</span><strong>{{ $department->teachers->count() }}</strong></span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="school-year-data-card">
            <i class="bi bi-building"></i>
            <span><span>Lớp đang dạy</span><strong>{{ $assignments->pluck('class_id')->unique()->count() }}</strong></span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="school-year-data-card">
            <i class="bi bi-clock-history"></i>
            <span><span>Tổng số tiết</span><strong>{{ $assignments->sum(fn ($assignment) => (int) ($assignment->effectiveWeeklyPeriods() ?: 0)) }}</strong></span>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-accent-title mb-3">Tiến độ nhập điểm</h6>
        <div class="table-responsive">
            <table class="table content-table align-middle">
                <thead>
                    <tr>
                        <th>Giáo viên</th>
                        <th>Môn chính</th>
                        <th>Lớp đang dạy</th>
                        <th>Tiến độ</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($department->teachers as $teacher)
                    @php($teacherAssignments = $assignments->where('teacher_id', $teacher->id))
                    @php($progress = $scoreProgress[$teacher->id] ?? ['total' => $teacherAssignments->count(), 'completed' => 0, 'missing' => $teacherAssignments->count()])
                    <tr>
                        <td class="fw-semibold">{{ $teacher->teacher_code }} - {{ $teacher->name }}</td>
                        <td>{{ $teacher->primarySubjectName() }}</td>
                        <td>{{ $teacherAssignments->pluck('class_id')->unique()->count() }}</td>
                        <td>
                            @if($progress['total'] > 0)
                                <span class="badge {{ $progress['missing'] === 0 ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $progress['completed'] }}/{{ $progress['total'] }} phân công đã có điểm
                                </span>
                            @else
                                <span class="text-muted">Chưa có phân công</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state"><i class="bi bi-people"></i>Chưa có giáo viên trong tổ.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="card-accent-title mb-3">Lịch dạy và phân công của giáo viên trong tổ</h6>
        <div class="table-responsive">
            <table class="table content-table align-middle">
                <thead>
                    <tr>
                        <th>Giáo viên</th>
                        <th>Lớp</th>
                        <th>Môn</th>
                        <th>Vai trò</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td class="fw-semibold">{{ $assignment->teacher?->name ?? '-' }}</td>
                        <td>{{ $assignment->classRoom?->name ?? '-' }}</td>
                        <td>{{ $assignment->subject?->name ?? '-' }}</td>
                        <td>{{ $assignment->roleLabel() }}</td>
                        <td>{{ $assignment->note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><i class="bi bi-diagram-3"></i>Chưa có phân công trong tổ.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
