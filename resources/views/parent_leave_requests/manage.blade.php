@extends('layouts.app')
@section('title', 'Duyệt đơn nghỉ học')

@section('content')
@php
    $statusLabels = \App\Models\ParentLeaveRequest::statusLabels();
@endphp

<style>
    .leave-review-filter,
    .leave-review-list {
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        text-align: left;
    }

    .leave-review-filter form {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: .6rem;
    }

    .leave-review-filter .form-select {
        min-width: 180px;
        border-color: #fed7aa;
        border-radius: 8px;
        color: #9a3412;
        background: #fff7ed;
        font-size: .875rem;
        font-weight: 400;
    }

    .leave-review-ribbon {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: .75rem;
        align-items: center;
        padding: .72rem .85rem;
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: linear-gradient(90deg, rgba(255, 247, 237, .62), #fff 45%);
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        text-align: left;
    }

    .leave-review-info {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem 1rem;
        min-width: 0;
    }

    .leave-review-item {
        min-width: 96px;
        max-width: 240px;
    }

    .leave-review-label {
        color: #9ca3af;
        font-size: .72rem;
        font-weight: 400;
        line-height: 1.1;
    }

    .leave-review-value {
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .leave-review-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .4rem;
        white-space: nowrap;
    }

    @media (max-width: 991.98px) {
        .leave-review-ribbon {
            grid-template-columns: 1fr;
        }

        .leave-review-actions {
            justify-content: flex-start;
        }
    }
</style>

<div class="page-heading mb-3">
    <div>
        <h5 class="text-xl font-normal text-gray-900 mb-1">Duyệt đơn nghỉ học</h5>
        <div class="text-sm text-gray-500 font-normal">Giáo viên chủ nhiệm duyệt đơn nghỉ học do phụ huynh gửi.</div>
    </div>
</div>

<div class="leave-review-filter mb-3">
    <div class="p-3">
        <form method="GET">
            <div>
                <label class="form-label text-xs text-gray-500 font-normal">Trạng thái</label>
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

<div class="leave-review-list">
    <div class="p-3 d-grid gap-2">
        @forelse($leaveRequests as $requestItem)
            <div class="leave-review-ribbon">
                <div class="leave-review-info">
                    <div class="leave-review-item">
                        <div class="leave-review-label">Học sinh</div>
                        <div class="leave-review-value" title="{{ $requestItem->student?->student_code }} - {{ $requestItem->student?->name }}">
                            {{ $requestItem->student?->student_code }} - {{ $requestItem->student?->name }}
                        </div>
                    </div>
                    <div class="leave-review-item">
                        <div class="leave-review-label">Lớp</div>
                        <div class="leave-review-value">{{ $requestItem->classRoom?->name ?? '-' }}</div>
                    </div>
                    <div class="leave-review-item">
                        <div class="leave-review-label">Ngày nghỉ</div>
                        <div class="leave-review-value text-orange-700">{{ $requestItem->leave_date?->format('d/m/Y') }}</div>
                    </div>
                    <div class="leave-review-item">
                        <div class="leave-review-label">Phụ huynh</div>
                        <div class="leave-review-value" title="{{ $requestItem->parent?->name ?? '-' }}{{ $requestItem->parent?->phone ? ' • ' . $requestItem->parent->phone : '' }}">
                            {{ $requestItem->parent?->name ?? '-' }}{{ $requestItem->parent?->phone ? ' • ' . $requestItem->parent->phone : '' }}
                        </div>
                    </div>
                    <div class="leave-review-item flex-grow-1">
                        <div class="leave-review-label">Lý do</div>
                        <div class="leave-review-value" title="{{ $requestItem->reason }}">{{ $requestItem->reason }}</div>
                    </div>
                </div>
                <div class="leave-review-actions">
                    <span class="badge {{ $requestItem->statusBadgeClass() }}">{{ $requestItem->statusLabel() }}</span>
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
                </div>
            </div>
        @empty
            <div class="empty-state"><i class="bi bi-envelope-paper"></i>Không có đơn xin nghỉ học phù hợp.</div>
        @endforelse
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
                            <div class="alert alert-info small">Sau khi phê duyệt, hệ thống tự cập nhật điểm danh ngày thành nghỉ có phép.</div>
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
