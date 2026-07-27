@extends('layouts.app')
@section('title', 'Duyá»‡t Ä‘Æ¡n nghá»‰ há»c')

@section('content')
@php
    $statusLabels = \App\Models\ParentLeaveRequest::statusLabels();
@endphp

<div class="page-heading">
    <div>
        <h5>Duyá»‡t Ä‘Æ¡n nghá»‰ há»c</h5>
        <div class="text-muted">GiÃ¡o viÃªn chá»§ nhiá»‡m duyá»‡t Ä‘Æ¡n nghá»‰ há»c do phá»¥ huynh gá»­i.</div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label">Tráº¡ng thÃ¡i</label>
                <select name="status" class="form-select">
                    @foreach(['pending', 'approved', 'rejected'] as $value)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $statusLabels[$value] ?? $value }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary">
                <i class="bi bi-search"></i>
                Lá»c
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Há»c sinh</th>
                    <th>Lá»›p</th>
                    <th>NgÃ y nghá»‰</th>
                    <th>Phá»¥ huynh</th>
                    <th>LÃ½ do</th>
                    <th>Tráº¡ng thÃ¡i</th>
                    <th class="text-end">Thao tÃ¡c</th>
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
                                PhÃª duyá»‡t
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectLeave{{ $requestItem->id }}">
                                KhÃ´ng duyá»‡t
                            </button>
                        @else
                            <span class="text-muted small">{{ $requestItem->reviewed_at?->format('d/m/Y H:i') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state"><i class="bi bi-envelope-paper"></i>KhÃ´ng cÃ³ Ä‘Æ¡n xin nghá»‰ há»c phÃ¹ há»£p.</div>
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
                                <div class="modal-kicker">ÄÆ¡n nghá»‰ há»c</div>
                                <h5 class="modal-title">PhÃª duyá»‡t Ä‘Æ¡n</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ÄÃ³ng"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2">PhÃª duyá»‡t Ä‘Æ¡n nghá»‰ ngÃ y <strong>{{ $requestItem->leave_date?->format('d/m/Y') }}</strong> cá»§a <strong>{{ $requestItem->student?->name }}</strong>.</p>
                            <div class="alert alert-info small">Sau khi phÃª duyá»‡t, há»‡ thá»‘ng sáº½ tá»± cáº­p nháº­t Ä‘iá»ƒm danh cÃ¡c tiáº¿t trong ngÃ y thÃ nh â€œCÃ³ phÃ©pâ€.</div>
                            <label class="form-label">Ghi chÃº</label>
                            <textarea name="homeroom_note" class="form-control" rows="3" placeholder="Ghi chÃº náº¿u cÃ³"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Há»§y</button>
                            <button class="btn btn-success">PhÃª duyá»‡t</button>
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
                                <div class="modal-kicker">ÄÆ¡n nghá»‰ há»c</div>
                                <h5 class="modal-title">KhÃ´ng duyá»‡t Ä‘Æ¡n</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ÄÃ³ng"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">LÃ½ do khÃ´ng duyá»‡t</label>
                            <textarea name="homeroom_note" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Há»§y</button>
                            <button class="btn btn-danger">KhÃ´ng duyá»‡t</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
