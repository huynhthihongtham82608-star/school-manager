@extends('layouts.app')
@section('title', 'Năm học')

@section('content')
<div class="page-heading">
    <div>
        <h5>Năm học</h5>
        <div class="text-muted">Quản lý năm học và trạng thái sử dụng.</div>
    </div>
    <div class="d-flex flex-wrap justify-content-end gap-2">
        <a class="btn btn-secondary" href="{{ route('school-years.initialize.form') }}">
            <i class="bi bi-magic me-1"></i>Khởi tạo năm học mới
        </a>
        <a class="btn btn-primary" href="{{ route('school-years.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Thêm năm học
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Bắt đầu</th>
                    <th>Kết thúc</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($years as $year)
                    @php($deleteCheck = $deleteChecks[(string) $year->getKey()] ?? ['allowed' => false, 'message' => null])
                    <tr>
                        <td class="fw-semibold">{{ $year->name }}</td>
                        <td>{{ optional($year->start_date)->format('d/m/Y') }}</td>
                        <td>{{ optional($year->end_date)->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge {{ $year->statusBadgeClass() }}">
                                @if($year->isArchived())
                                    ⚪
                                @elseif($year->is_active)
                                    🟢
                                @else
                                    🟡
                                @endif
                                {{ $year->statusLabel() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="content-action-group justify-content-end">
                                @if(! $year->isArchived())
                                    <a href="{{ route('school-years.edit', $year) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                        <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                    </a>
                                @endif

                                <div class="dropdown" data-school-year-dropdown>
                                    <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('school-years.detail', $year) }}">
                                                <i class="bi bi-eye me-2"></i>Xem chi tiết
                                            </a>
                                        </li>
                                        @if(! $year->is_active && ! $year->isArchived())
                                            <li>
                                                <button type="button" class="dropdown-item" data-activate-school-year data-action="{{ route('school-years.activate', $year) }}" data-year-name="{{ $year->name }}">
                                                    <i class="bi bi-check-circle me-2"></i>Kích hoạt
                                                </button>
                                            </li>
                                            <li>
                                                <form action="{{ route('school-years.archive', $year) }}" method="POST">
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
                                                <button type="button" class="dropdown-item text-danger" data-delete-school-year data-action="{{ route('school-years.destroy', $year) }}" data-year-name="{{ $year->name }}">
                                                    <i class="bi bi-trash me-2"></i>Xóa
                                                </button>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"><div class="empty-state"><i class="bi bi-calendar-x"></i>Chưa có năm học.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade content-modal" id="activateSchoolYearListModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" data-activate-school-year-form>
            @csrf
            @method('PATCH')
            <input type="hidden" name="confirm_activation" value="1">
            <div class="modal-header">
                <div>
                    <div class="modal-kicker">Xác nhận kích hoạt</div>
                    <h5 class="modal-title">Chuyển năm học hoạt động</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Hiện đang có một năm học hoạt động. Bạn có muốn chuyển sang năm học <strong data-activate-year-name></strong> không?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade content-modal" id="deleteSchoolYearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" data-delete-school-year-form>
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <div>
                    <div class="modal-kicker">Xác nhận xóa</div>
                    <h5 class="modal-title">Bạn có chắc muốn xóa năm học này?</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Năm học: <strong data-delete-year-name></strong></p>
                <p class="mb-0 text-muted">Toàn bộ dữ liệu khởi tạo ban đầu sẽ bị xóa. Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" data-delete-school-year-submit>
                    <i class="bi bi-trash me-1"></i>Xác nhận xóa
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalElement = document.getElementById('activateSchoolYearListModal');
    const form = modalElement?.querySelector('[data-activate-school-year-form]');
    const yearName = modalElement?.querySelector('[data-activate-year-name]');
    const modal = modalElement && window.bootstrap ? new bootstrap.Modal(modalElement) : null;
    const deleteModalElement = document.getElementById('deleteSchoolYearModal');
    const deleteForm = deleteModalElement?.querySelector('[data-delete-school-year-form]');
    const deleteYearName = deleteModalElement?.querySelector('[data-delete-year-name]');
    const deleteSubmit = deleteModalElement?.querySelector('[data-delete-school-year-submit]');
    const deleteModal = deleteModalElement && window.bootstrap ? new bootstrap.Modal(deleteModalElement) : null;

    const closeSchoolYearDropdowns = (except = null) => {
        document.querySelectorAll('[data-school-year-dropdown] [data-bs-toggle="dropdown"]').forEach((toggle) => {
            if (except && toggle === except) {
                return;
            }

            const instance = bootstrap.Dropdown.getInstance(toggle);
            if (instance) {
                instance.hide();
            }
        });
    };

    document.querySelectorAll('[data-school-year-dropdown] [data-bs-toggle="dropdown"]').forEach((toggle) => {
        toggle.addEventListener('show.bs.dropdown', () => closeSchoolYearDropdowns(toggle));
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-school-year-dropdown]')) {
            closeSchoolYearDropdowns();
        }
    });

    window.addEventListener('pagehide', () => closeSchoolYearDropdowns());
    window.addEventListener('beforeunload', () => closeSchoolYearDropdowns());

    document.querySelectorAll('[data-activate-school-year]').forEach((button) => {
        button.addEventListener('click', () => {
            closeSchoolYearDropdowns();
            if (!form || !modal) {
                return;
            }

            form.action = button.dataset.action || '';
            if (yearName) {
                yearName.textContent = button.dataset.yearName || 'năm học này';
            }
            modal.show();
        });
    });

    document.querySelectorAll('[data-delete-school-year]').forEach((button) => {
        button.addEventListener('click', () => {
            closeSchoolYearDropdowns();
            if (!deleteForm || !deleteModal) {
                return;
            }

            deleteForm.action = button.dataset.action || '';
            if (deleteYearName) {
                deleteYearName.textContent = button.dataset.yearName || 'năm học này';
            }
            deleteModal.show();
        });
    });

    deleteSubmit?.addEventListener('click', () => {
        if (!deleteForm) {
            return;
        }

        HTMLFormElement.prototype.submit.call(deleteForm);
    });
});
</script>
@endsection
