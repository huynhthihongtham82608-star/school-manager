@extends('layouts.app')
@section('title', 'Giáo viên')

@section('content')
<x-page-header
    title="Quản lý giáo viên"
    subtitle="Quản lý hồ sơ nhân sự, thông tin liên lạc, chức vụ công tác và trạng thái giảng dạy của cán bộ giáo viên."
>
    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
        <x-bulk-excel-actions module="teachers" :context="['school_year_id' => $selectedYearId]" />
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#teacherCreateModal">
            <i class="bi bi-plus-lg me-1"></i>Thêm giáo viên mới
        </button>
        <div class="dropdown">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Lọc giáo viên" aria-label="Lọc giáo viên">
                <i class="bi bi-funnel"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 320px;">
                <form method="GET" action="{{ route('teachers.index') }}" class="d-grid gap-3">
                    <div>
                        <label class="form-label small">Tìm kiếm</label>
                        <input type="search" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Mã, họ tên, môn, tổ chuyên môn">
                    </div>
                    <div>
                        <label class="form-label small">Tổ chuyên môn</label>
                        <select name="department_id" class="form-select">
                            <option value="all">Tất cả tổ</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected($filters['department_id'] === $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('teachers.index') }}" class="btn btn-secondary">Xóa lọc</a>
                        <button class="btn btn-primary">Áp dụng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Họ tên</th>
                    <th>Môn chính</th>
                    <th>Tổ chuyên môn</th>
                    <th>Lớp chủ nhiệm</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($teachers as $teacher)
                @php
                    $loginLocked = (int) ($teacher->user?->login_status ?? 1) !== 1;
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $teacher->teacher_code }}</td>
                    <td>
                        <div class="fw-semibold d-flex align-items-center gap-1.5 text-left">
                            <span class="{{ $loginLocked ? 'text-red-600' : 'text-green-600' }} text-xs leading-none">{{ $loginLocked ? '🔴' : '🟢' }}</span>
                            <span>{{ $teacher->name }}</span>
                        </div>
                        <div class="text-muted small">{{ $teacher->phone ?: '-' }}</div>
                    </td>
                    <td>{{ $teacher->primarySubjectName() }}</td>
                    <td>{{ $teacher->department?->name ?? 'Chưa phân tổ' }}</td>
                    <td>
                        @php
                            $teacherHomeroomClasses = $selectedYearId ? $teacher->homeroomClasses->where('school_year_id', $selectedYearId) : $teacher->homeroomClasses;
                        @endphp
                        @forelse($teacherHomeroomClasses as $class)
                            <span class="badge bg-info">{{ $class->name }}</span>
                        @empty
                            <span class="text-muted">Chưa phân công</span>
                        @endforelse
                    </td>
                    <td><span class="badge {{ $teacher->workStatusBadgeClass() }}">{{ $teacher->workStatusLabel() }}</span></td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <button type="button" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="modal" data-bs-target="#teacherEdit{{ $teacher->id }}">
                                <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                            </button>
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
                                    <form action="{{ route('teachers.toggle-login', $teacher) }}" method="POST" onsubmit="return confirm('{{ $loginLocked ? 'Bạn có chắc muốn mở khóa tài khoản đăng nhập giáo viên này?' : 'Bạn có chắc muốn khóa tài khoản đăng nhập giáo viên này?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="dropdown-item text-left {{ $loginLocked ? 'text-success' : 'text-orange-700' }}">
                                            <i class="bi {{ $loginLocked ? 'bi-unlock' : 'bi-lock' }} me-2"></i>{{ $loginLocked ? 'Mở khóa tài khoản' : 'Khóa tài khoản' }}
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

<div class="modal fade student-form-modal teacher-form-modal" id="teacherCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Thêm giáo viên mới</h5>
                    <div class="text-muted small">Nhập thông tin công tác, cá nhân và liên hệ của giáo viên.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <?php echo $__env->make('teachers.partials.form', [
                    'action' => route('teachers.store'),
                    'teacher' => null,
                    'subjects' => $subjects,
                    'departments' => $departments,
                    'nextTeacherCode' => $nextTeacherCode,
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </div>
</div>

@foreach($teachers as $teacher)
    @php
        $teacherTeachingAssignments = $selectedYearId ? $teacher->assignments->where('school_year_id', $selectedYearId) : $teacher->assignments;
    @endphp
    @php
        $teacherHomeroomDetailClasses = $selectedYearId ? $teacher->homeroomClasses->where('school_year_id', $selectedYearId) : $teacher->homeroomClasses;
    @endphp
    @php
        $teacherInitial = \Illuminate\Support\Str::of($teacher->name ?: 'G')->substr(0, 1)->upper();
    @endphp

    <div class="modal fade student-form-modal teacher-form-modal" id="teacherEdit{{ $teacher->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Chỉnh sửa hồ sơ giáo viên</h5>
                        <div class="text-muted small">{{ $teacher->teacher_code }} - {{ $teacher->name }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <?php echo $__env->make('teachers.partials.form', [
                        'action' => route('teachers.update', $teacher),
                        'teacher' => $teacher,
                        'subjects' => $subjects,
                        'departments' => $departments,
                        'nextTeacherCode' => $nextTeacherCode,
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade content-modal profile-detail-modal teacher-detail-modal" id="teacherDetail{{ $teacher->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header teacher-detail-clean-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="teacher-detail-profile-card">
                        <div class="teacher-drawer-left">
                            <div class="teacher-drawer-avatar">{{ $teacherInitial }}</div>
                            <div class="teacher-drawer-identity">
                                <h5>{{ $teacher->name }}</h5>
                                <div>Mã số: {{ $teacher->teacher_code }}</div>
                            </div>
                        </div>
                        <span class="teacher-drawer-status badge {{ $teacher->workStatusBadgeClass() }}">{{ $teacher->workStatusLabel() }}</span>
                    </div>

                    <div class="teacher-detail-two-col">
                        <section class="teacher-detail-info-card">
                            <div class="profile-detail-section-title">
                                <h6>Thông tin công tác</h6>
                            </div>
                            <div class="teacher-detail-lines">
                                <div>
                                    <span>Môn chính</span>
                                    <strong>{{ $teacher->primarySubjectName() }}</strong>
                                </div>
                                <div>
                                    <span>Tổ chuyên môn</span>
                                    <strong class="{{ $teacher->department ? '' : 'text-muted fw-normal' }}">{{ $teacher->department?->name ?? '-' }}</strong>
                                </div>
                                <div>
                                    <span>Ngày vào trường</span>
                                    <strong class="{{ $teacher->joined_at ? '' : 'text-muted fw-normal' }}">{{ $teacher->joined_at?->format('d/m/Y') ?? '-' }}</strong>
                                </div>
                            </div>
                        </section>

                        <section class="teacher-detail-info-card">
                            <div class="profile-detail-section-title">
                                <h6>Thông tin cá nhân & liên hệ</h6>
                            </div>
                            <div class="teacher-detail-lines">
                                <div>
                                    <span>Giới tính</span>
                                    <strong class="{{ $teacher->gender ? '' : 'text-muted fw-normal' }}">{{ $teacher->genderLabel() }}</strong>
                                </div>
                                <div>
                                    <span>Ngày sinh</span>
                                    <strong class="{{ $teacher->dob ? '' : 'text-muted fw-normal' }}">{{ $teacher->dob?->format('d/m/Y') ?? '-' }}</strong>
                                </div>
                                <div>
                                    <span>Thư điện tử</span>
                                    <strong class="teacher-detail-email {{ $teacher->email ? '' : 'text-muted fw-normal' }}">{{ $teacher->email ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>Số điện thoại</span>
                                    <strong class="{{ $teacher->phone ? '' : 'text-muted fw-normal' }}">{{ $teacher->phone ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>Trình độ</span>
                                    <strong class="{{ $teacher->qualification ? '' : 'text-muted fw-normal' }}">{{ $teacher->qualification ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>Địa chỉ</span>
                                    <strong class="{{ $teacher->address ? '' : 'text-muted fw-normal' }}">{{ $teacher->address ?: '-' }}</strong>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="teacher-assignment-box">
                        <div class="teacher-assignment-title">🎓 Khối lượng công tác phân công</div>
                        <div class="teacher-assignment-row">
                            <span>Danh sách lớp đang dạy</span>
                            <div class="teacher-assignment-tags">
                                @if($teacherTeachingAssignments->isNotEmpty())
                                    @foreach($teacherTeachingAssignments as $assignment)
                                        <span class="teacher-assignment-tag soft">{{ $assignment->classRoom->name ?? '-' }} - {{ $assignment->subject->name ?? '-' }}</span>
                                    @endforeach
                                @else
                                    <span class="teacher-assignment-empty">Chưa có phân công giảng dạy.</span>
                                @endif
                            </div>
                        </div>
                        <div class="teacher-assignment-row">
                            <span>Lớp đang chủ nhiệm</span>
                            <div class="teacher-assignment-tags">
                                @if($teacherHomeroomDetailClasses->isNotEmpty())
                                    @foreach($teacherHomeroomDetailClasses as $class)
                                        <span class="teacher-assignment-tag strong">Lớp {{ $class->name }}</span>
                                    @endforeach
                                @else
                                    <span class="teacher-assignment-empty">Chưa được phân công chủ nhiệm.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng hồ sơ giáo viên</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@if($errors->any() && old('_teacher_form_modal'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById(@json(old('_teacher_form_modal')));
            if (modalElement && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        });
    </script>
@endif
@endsection
