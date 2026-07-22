@extends('layouts.app')
@section('title', 'Xin nghỉ học')

@section('content')
<div class="page-heading">
    <div>
        <h5>Xin nghỉ học</h5>
        <div class="text-muted">Phụ huynh gửi đơn xin nghỉ học cho học sinh đang chọn để giáo viên chủ nhiệm phê duyệt.</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">Gửi đơn xin nghỉ</div>
            <div class="card-body">
                @if($selectedStudent)
                    <form method="POST" action="{{ route('parent.leave-requests.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Học sinh</label>
                            <select name="student_id" class="form-select" required>
                                @foreach($children as $child)
                                    <option value="{{ $child->id }}" @selected($selectedStudent->id === $child->id)>
                                        {{ $child->student_code }} - {{ $child->name }}{{ $child->classRoom ? ' - ' . $child->classRoom->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ngày nghỉ</label>
                            <input type="date" name="leave_date" class="form-control" value="{{ old('leave_date', now()->toDateString()) }}" required>
                            @error('leave_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Lý do nghỉ học</label>
                            <textarea name="reason" rows="5" class="form-control" required placeholder="Nhập lý do nghỉ học rõ ràng để GVCN xem xét.">{{ old('reason') }}</textarea>
                            @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="alert alert-light border small mb-0">
                                Sau khi gửi, đơn sẽ ở trạng thái <strong>Chờ GVCN duyệt</strong>. Phụ huynh có thể theo dõi kết quả trong danh sách bên cạnh.
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-primary">
                                <i class="bi bi-send me-1"></i>Gửi đơn
                            </button>
                        </div>
                    </form>
                @else
                    <div class="empty-state">
                        <i class="bi bi-person-dash"></i>
                        Tài khoản phụ huynh chưa liên kết học sinh.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">Lịch sử đơn xin nghỉ</div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Ngày nghỉ</th>
                            <th>Học sinh</th>
                            <th>Lý do</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($leaveRequests as $requestItem)
                        <tr>
                            <td class="fw-semibold">{{ $requestItem->leave_date?->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $requestItem->student?->name ?? '-' }}</div>
                                <div class="text-muted small">{{ $requestItem->classRoom?->name ?? $requestItem->student?->classRoom?->name ?? '-' }}</div>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($requestItem->reason, 90, '...') }}</td>
                            <td>
                                <span class="badge {{ $requestItem->statusBadgeClass() }}">{{ $requestItem->statusLabel() }}</span>
                                @if($requestItem->homeroom_note)
                                    <div class="text-muted small mt-1">{{ $requestItem->homeroom_note }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="bi bi-envelope-paper"></i>
                                    Chưa có đơn xin nghỉ học.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($leaveRequests, 'links'))
                <div class="card-footer bg-white">{{ $leaveRequests->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
