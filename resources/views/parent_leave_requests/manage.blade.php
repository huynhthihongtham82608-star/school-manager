@extends('layouts.app')
@section('title', 'Duyệt đơn nghỉ học')

@section('content')
@php
    $statusLabels = \App\Models\ParentLeaveRequest::statusLabels();
@endphp

<div class="page-heading">
    <div>
        <h5>Duyệt đơn nghỉ học</h5>
        <div class="text-muted">Giáo viên chủ nhiệm duyệt đơn nghỉ học do phụ huynh gửi.</div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    @foreach(['pending', 'approved', 'rejected'] as $value)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $statusLabels[$value] ?? $value }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary">
                <i class="bi bi-search"></i>
                Lọc
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Học sinh</th>
                    <th>Lớp</th>
                    <th>Ngày nghỉ</th>
                    <th>Phụ huynh</th>
                    <th>Lý do</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($leaveRequests as $requestItem)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $requestItem->student?->student_code }}</div>
                        <div class="text-muted small">{{ $requestItem->student?->name }}</div>
                    </td>
                    <td>{{ $requestItem->classRoom?->name ?? '-' }}</td>
                    <td class="fw-semibold">{{ $requestItem->leave_date?->format('d/m/Y') }}</td>
                    <td>
                        <div>{{ $requestItem->parent?->name ?? '-' }}</div>
                        <div class="text-muted small">{{ $requestItem->parent?->phone }}</div>
                    </td>
                    <td style="max-width: 260px;">{{ $requestItem->reason }}</td>
                    <td><span class="badge {{ $requestItem->statusBadgeClass() }}">{{ $requestItem->statusLabel() }}</span></td>
                    <td class="text-end">
                        @if($requestItem->status === \App\Models\ParentLeaveRequest::STATUS_PENDING)
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveLeave{{ $requestItem->id }}">
                                Phê duyệt
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectLeave{{ $requestItem->id }}">
                                Không duyệt
                            </button>
                        @else
                            <span class="text-muted small">{{ $requestItem->reviewed_at?->format('d/m/Y H:i') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state"><i class="bi bi-envelope-paper"></i>Không có đơn xin nghỉ học phù hợp.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $leaveRequests->links() }}</div>

@foreach($leaveRequests as $requestItem)
    @if($requestItem->status === \App\Models\ParentLeaveRequest::STATUS_PENDING)
        <div class="modal fade content-modal" id="approveLeave{{ $requestItem->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('teacher.leave-requests.approve', $requestItem) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <div>
                                <div class="modal-kicker">Đơn nghỉ học</div>
                                <h5 class="modal-title">Phê duyệt đơn</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2">Phê duyệt đơn nghỉ ngày <strong>{{ $requestItem->leave_date?->format('d/m/Y') }}</strong> của <strong>{{ $requestItem->student?->name }}</strong>.</p>
                            <div class="alert alert-info small">Sau khi phê duyệt, hệ thống sẽ tự cập nhật điểm danh các tiết trong ngày thành “Có phép”.</div>
                            <label class="form-label">Ghi chú</label>
                            <textarea name="homeroom_note" class="form-control" rows="3" placeholder="Ghi chú nếu có"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button class="btn btn-success">Phê duyệt</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade content-modal" id="rejectLeave{{ $requestItem->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('teacher.leave-requests.reject', $requestItem) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <div>
                                <div class="modal-kicker">Đơn nghỉ học</div>
                                <h5 class="modal-title">Không duyệt đơn</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Lý do không duyệt</label>
                            <textarea name="homeroom_note" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button class="btn btn-danger">Không duyệt</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
