@extends('layouts.app')
@section('title', 'Giáo viên')

@section('content')
<div class="page-heading">
    <div>
        <h5>Giáo viên</h5>
        <div class="text-muted">Quản lý thông tin giáo viên và tài khoản liên kết.</div>
    </div>
    <a class="btn btn-primary" href="{{ route('teachers.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm giáo viên</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Họ tên</th>
                    <th>Môn chính</th>
                    <th>GVCN</th>
                    <th>Trạng thái</th>
                    <th>Tài khoản</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($teachers as $teacher)
                <tr>
                    <td class="fw-semibold">{{ $teacher->teacher_code }}</td>
                    <td>
                        <div class="fw-semibold">{{ $teacher->name }}</div>
                        <div class="text-muted small">{{ $teacher->phone ?: '-' }}</div>
                    </td>
                    <td>{{ $teacher->main_subject ?: '-' }}</td>
                    <td>{!! $teacher->is_homeroom ? '<span class="badge bg-info">Có</span>' : '-' !!}</td>
                    <td><span class="badge {{ $teacher->workStatusBadgeClass() }}">{{ $teacher->workStatusLabel() }}</span></td>
                    <td>{{ $teacher->user?->username ?: '-' }}</td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <a href="{{ route('teachers.edit', $teacher) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                            </a>
                            <div class="dropdown">
                                <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#teacherDetail{{ $teacher->id }}">
                                        <i class="bi bi-eye me-2"></i>Xem chi tiết
                                    </button>
                                    <form action="{{ route('teachers.reset-password', $teacher) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn đặt lại mật khẩu cho giáo viên này?');">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="bi bi-key me-2"></i>Đặt lại mật khẩu
                                        </button>
                                    </form>
                                    <div class="dropdown-divider"></div>
                                    <form action="{{ route('teachers.destroy', $teacher) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa giáo viên này? Hành động này không thể hoàn tác.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-trash me-2"></i>Xóa
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7"><div class="empty-state"><i class="bi bi-person-badge"></i>Chưa có giáo viên.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($teachers as $teacher)
    @php
        $teachingAssignments = $selectedYearId
            ? $teacher->assignments->where('school_year_id', $selectedYearId)
            : $teacher->assignments;
        $homeroomClasses = $selectedYearId
            ? $teacher->homeroomClasses->where('school_year_id', $selectedYearId)
            : $teacher->homeroomClasses;
    @endphp
    <div class="modal fade content-modal teacher-profile-modal" id="teacherDetail{{ $teacher->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="teacher-profile-card">
                        <div class="teacher-profile-avatar">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                        <div class="teacher-profile-main">
                            <div class="teacher-profile-kicker">Hồ sơ giáo viên</div>
                            <h5>{{ $teacher->name }}</h5>
                            <div class="teacher-profile-code">{{ $teacher->teacher_code }}</div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <span class="badge bg-light text-dark border">{{ $teacher->main_subject ?: 'Chưa cập nhật bộ môn' }}</span>
                                <span class="badge {{ $teacher->workStatusBadgeClass() }}">{{ $teacher->workStatusLabel() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="teacher-detail-grid mt-3">
                        <article>
                            <span>Giới tính</span>
                            <strong>{{ $teacher->genderLabel() }}</strong>
                        </article>
                        <article>
                            <span>Ngày sinh</span>
                            <strong>{{ $teacher->dob?->format('d/m/Y') ?? '-' }}</strong>
                        </article>
                        <article>
                            <span>Email</span>
                            <strong>{{ $teacher->email ?: '-' }}</strong>
                        </article>
                        <article>
                            <span>SĐT</span>
                            <strong>{{ $teacher->phone ?: '-' }}</strong>
                        </article>
                        <article>
                            <span>Ngày vào trường</span>
                            <strong>{{ $teacher->joined_at?->format('d/m/Y') ?? '-' }}</strong>
                        </article>
                        <article>
                            <span>Trình độ</span>
                            <strong>{{ $teacher->qualification ?: '-' }}</strong>
                        </article>
                        <article class="wide">
                            <span>Địa chỉ</span>
                            <strong>{{ $teacher->address ?: '-' }}</strong>
                        </article>
                    </div>

                    <div class="teacher-detail-section mt-3">
                        <div class="teacher-detail-title"><i class="bi bi-journal-bookmark"></i><h6>Danh sách lớp đang dạy</h6></div>
                        <div class="teacher-pill-list">
                            @forelse($teachingAssignments as $assignment)
                                <span class="teacher-pill">
                                    {{ $assignment->subject->name ?? '-' }} - {{ $assignment->classRoom->name ?? '-' }}
                                </span>
                            @empty
                                <div class="text-muted">Chưa có phân công giảng dạy.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="teacher-detail-section mt-3">
                        <div class="teacher-detail-title"><i class="bi bi-people"></i><h6>Lớp đang chủ nhiệm</h6></div>
                        @if($teacher->is_homeroom)
                            <div class="teacher-pill-list">
                                @forelse($homeroomClasses as $class)
                                    <span class="teacher-pill">Chủ nhiệm: {{ $class->name }}</span>
                                @empty
                                    <div class="text-muted">Chưa được phân công chủ nhiệm</div>
                                @endforelse
                            </div>
                        @else
                            <div class="text-muted">Chưa được phân công chủ nhiệm</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
