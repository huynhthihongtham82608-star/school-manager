@extends('layouts.app')
@section('title', 'Học kỳ')

@section('content')
<x-page-header
    title="Quản lý học kỳ"
    subtitle="Cấu hình mốc thời gian bắt đầu/kết thúc các học kỳ và thiết lập thời hạn khóa sổ cổng nhập điểm."
>
    @unless($readOnly)
        <a class="btn btn-primary" href="{{ route('semesters.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm học kỳ</a>
    @endunless
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Năm học</th>
                    <th>Trạng thái</th>
                    <th>Nhập điểm</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($semesters as $semester)
                @php
                    $deleteCheck = $deleteChecks[(string) $semester->getKey()] ?? ['allowed' => false, 'message' => null];
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $semester->normalizedName() }}</td>
                    <td>{{ $semester->schoolYear->name ?? '' }}</td>
                    <td><span class="badge {{ $semester->statusBadgeClass() }}">{{ $semester->statusLabel() }}</span></td>
                    <td>
                        @if($semester->is_score_input_open && $semester->isActive())
                            <span class="badge bg-success">Mở</span>
                        @else
                            <span class="badge bg-secondary">Khóa</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            @if(! $readOnly && $semester->canEdit())
                                <a href="{{ route('semesters.edit', $semester) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                </a>
                            @endif

                            <div class="dropdown">
                                <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('semesters.show', $semester) }}">
                                            <i class="bi bi-eye me-2"></i>Xem chi tiết
                                        </a>
                                    </li>
                                    @unless($readOnly)
                                        @if($semester->canMoveToInactive())
                                            <li>
                                                <form action="{{ route('semesters.mark-inactive', $semester) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-arrow-right-circle me-2"></i>Chuyển sang Chưa hoạt động
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($semester->canActivate())
                                            <li>
                                                <form action="{{ route('semesters.activate', $semester) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-check-circle me-2"></i>Đặt làm hiện hành
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($semester->canLock())
                                            <li>
                                                <form action="{{ route('semesters.lock', $semester) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-lock me-2"></i>Khóa học kỳ
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($semester->canArchive())
                                            <li>
                                                <form action="{{ route('semesters.archive', $semester) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-archive me-2"></i>Lưu trữ
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($deleteCheck['allowed'])
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('semesters.destroy', $semester) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash me-2"></i>Xóa
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    @endunless
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"><div class="empty-state"><i class="bi bi-calendar-range"></i>Chưa có học kỳ.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
