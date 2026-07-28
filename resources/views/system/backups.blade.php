@extends('layouts.app')
@section('title', 'Sao lưu & Khôi phục dữ liệu')

@section('content')
<x-page-header
    class="backup-page-header"
    title="Sao lưu & Khôi phục dữ liệu"
    subtitle="Tạo và tải bản sao lưu database. Chức năng khôi phục đang được khóa để bảo vệ dữ liệu."
>
    <div class="backup-header-actions">
        <form method="POST" action="{{ route('system.backups.store') }}">
            @csrf
            <button class="btn btn-primary backup-create-btn" onclick="return confirm('Tạo bản sao lưu database hiện tại?')">
                <i class="bi bi-rocket-takeoff me-2"></i>Tạo bản sao lưu mới
            </button>
        </form>
    </div>
</x-page-header>

<div class="backup-list-card card">
    <div class="backup-table-toolbar">
        <div class="backup-search">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" placeholder="Tìm kiếm..." data-backup-search>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table backup-table" data-no-auto-toolbar>
            <thead>
                <tr>
                    <th>Tên tệp</th>
                    <th>Dung lượng</th>
                    <th>Thời gian tạo</th>
                    <th class="text-end action-column-header"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($files as $file)
                    <tr data-backup-row data-search-text="{{ \Illuminate\Support\Str::lower($file['name']) }}">
                        <td class="fw-semibold content-break-cell">{{ $file['name'] }}</td>
                        <td>{{ number_format($file['size'] / 1024, 1) }} KB</td>
                        <td class="text-muted">{{ \Carbon\Carbon::createFromTimestamp($file['updated_at'])->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <div class="content-action-group justify-content-center" data-action-synced="true">
                                <a class="content-action-btn icon-only backup-download-action" href="{{ route('system.backups.download', $file['name']) }}" title="Tải xuống tệp" data-bs-toggle="tooltip">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button type="button" class="backup-restore-action" title="Khôi phục" aria-label="Khôi phục" data-bs-toggle="tooltip" data-restore-action data-restore-url="{{ route('system.backups.restore', $file['name']) }}" data-backup-name="{{ $file['name'] }}">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr data-backup-empty>
                        <td colspan="4">
                            <div class="empty-state"><i class="bi bi-database"></i>Chưa có bản sao lưu nào.</div>
                        </td>
                    </tr>
                @endforelse
                <tr class="d-none" data-backup-no-results>
                    <td colspan="4">
                        <div class="empty-state"><i class="bi bi-search"></i>Không tìm thấy tệp sao lưu phù hợp.</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade content-modal" id="backupRestoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered backup-unlock-dialog">
        <form class="modal-content backup-unlock-modal" data-restore-form>
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body pt-0">
                <div data-restore-step="1">
                    <h2 class="backup-unlock-title">Xác thực đặc quyền Khôi phục</h2>
                    <p class="backup-unlock-warning">
                        Hành động khôi phục dữ liệu sẽ ghi đè và xóa hoàn toàn các số liệu hiện tại của nhà trường. Vui lòng nhập mật khẩu tài khoản 'admin' để xác nhận.
                    </p>
                    <div class="backup-selected-file" data-restore-selected-file></div>
                    <label class="backup-unlock-label" for="backupRestorePassword">Mật khẩu admin</label>
                    <input type="password" id="backupRestorePassword" class="form-control backup-unlock-input" placeholder="Nhập mật khẩu tài khoản admin..." autocomplete="current-password" data-restore-password required>
                </div>

                <div class="d-none" data-restore-step="2">
                    <h2 class="backup-unlock-title">Lớp bảo vệ trước khi khôi phục</h2>
                    <p class="backup-unlock-warning">
                        Bạn có muốn hệ thống tự động tạo một bản sao lưu dữ liệu của ngày hôm nay trước khi tiến hành ghi đè dữ liệu cũ vào không?
                    </p>
                    <div class="backup-selected-file" data-restore-confirm-file></div>
                    <div class="backup-safety-note">
                        Nếu chọn sao lưu trước, hệ thống sẽ tạo thêm một tệp .sql mới từ dữ liệu hiện tại, lưu vào kho sao lưu, rồi mới khôi phục từ tệp đã chọn.
                    </div>
                </div>

                <div class="backup-unlock-message d-none" data-restore-message></div>
            </div>
            <div class="modal-footer border-0 pt-0" data-restore-footer-step="1">
                <button type="button" class="btn backup-unlock-cancel" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="submit" class="btn backup-unlock-submit">
                    <i class="bi bi-shield-lock me-2"></i>Tiếp tục
                </button>
            </div>
            <div class="modal-footer border-0 pt-0 d-none backup-restore-decision-footer" data-restore-footer-step="2">
                <button type="button" class="btn backup-unlock-cancel" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn backup-restore-now" data-restore-submit="0">
                    <i class="bi bi-x-circle me-2"></i>Không, khôi phục ngay
                </button>
                <button type="button" class="btn backup-restore-with-backup" data-restore-submit="1">
                    <i class="bi bi-database-add me-2"></i>Có, tự động sao lưu trước
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.querySelector('[data-backup-search]');
    const rows = Array.from(document.querySelectorAll('[data-backup-row]'));
    const noResults = document.querySelector('[data-backup-no-results]');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const restoreForm = document.querySelector('[data-restore-form]');
    const restorePassword = document.querySelector('[data-restore-password]');
    const restoreMessage = document.querySelector('[data-restore-message]');
    const restoreModalElement = document.getElementById('backupRestoreModal');
    const restoreModal = restoreModalElement && window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(restoreModalElement) : null;
    const selectedFileLabel = document.querySelector('[data-restore-selected-file]');
    const confirmFileLabel = document.querySelector('[data-restore-confirm-file]');
    const stepOne = document.querySelector('[data-restore-step="1"]');
    const stepTwo = document.querySelector('[data-restore-step="2"]');
    const stepOneFooter = document.querySelector('[data-restore-footer-step="1"]');
    const stepTwoFooter = document.querySelector('[data-restore-footer-step="2"]');
    const decisionButtons = Array.from(document.querySelectorAll('[data-restore-submit]'));
    let selectedRestoreUrl = null;
    let selectedBackupName = null;
    let restoreToken = null;

    const setStep = (step) => {
        stepOne?.classList.toggle('d-none', step !== 1);
        stepTwo?.classList.toggle('d-none', step !== 2);
        stepOneFooter?.classList.toggle('d-none', step !== 1);
        stepTwoFooter?.classList.toggle('d-none', step !== 2);
    };

    const showRestoreMessage = (message, isError = true) => {
        if (!restoreMessage) {
            return;
        }

        restoreMessage.textContent = message;
        restoreMessage.classList.remove('d-none', 'is-error', 'is-success');
        restoreMessage.classList.add(isError ? 'is-error' : 'is-success');
    };

    const resetRestoreModal = (button) => {
        selectedRestoreUrl = button.dataset.restoreUrl;
        selectedBackupName = button.dataset.backupName || 'tệp sao lưu đã chọn';
        restoreToken = null;
        restorePassword.value = '';
        restoreMessage?.classList.add('d-none');
        if (selectedFileLabel) {
            selectedFileLabel.textContent = `Tệp sẽ khôi phục: ${selectedBackupName}`;
        }
        if (confirmFileLabel) {
            confirmFileLabel.textContent = `Tệp đã xác thực: ${selectedBackupName}`;
        }
        setStep(1);
    };

    if (searchInput && rows.length) {
        searchInput.addEventListener('input', () => {
            const keyword = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach((row) => {
                const matched = !keyword || row.dataset.searchText.includes(keyword);
                row.classList.toggle('d-none', !matched);
                visibleCount += matched ? 1 : 0;
            });

            noResults?.classList.toggle('d-none', visibleCount > 0);
        });
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-restore-action]');

        if (!button) {
            return;
        }

        event.preventDefault();
        resetRestoreModal(button);
        restoreModal?.show();
        window.setTimeout(() => restorePassword?.focus(), 180);
    });

    restoreForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitButton = restoreForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        restoreMessage?.classList.add('d-none');

        try {
            const response = await fetch('{{ route('system.backups.restore.verify') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    password: restorePassword?.value || '',
                    filename: selectedBackupName,
                }),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Không thể mở cổng khôi phục dữ liệu.');
            }

            restoreToken = payload.restore_token;
            showRestoreMessage(payload.message || 'Mật khẩu chính xác.', false);
            setStep(2);
        } catch (error) {
            restoreToken = null;
            showRestoreMessage(error.message);
        } finally {
            submitButton.disabled = false;
        }
    });

    decisionButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const backupCurrent = button.dataset.restoreSubmit === '1';

            if (!restoreToken || !selectedRestoreUrl) {
                showRestoreMessage('Phiên xác thực khôi phục không hợp lệ. Vui lòng nhập lại mật khẩu.');
                setStep(1);
                return;
            }

            const confirmed = confirm(`Bạn có chắc chắn muốn khôi phục dữ liệu từ ${selectedBackupName}? Hành động này sẽ ghi đè dữ liệu hiện tại và không thể hoàn tác.`);

            if (!confirmed) {
                return;
            }

            decisionButtons.forEach((item) => item.disabled = true);

            try {
                const response = await fetch(selectedRestoreUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        restore_token: restoreToken,
                        backup_current: backupCurrent,
                    }),
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message || 'Không thể khôi phục dữ liệu.');
                }

                restoreToken = null;
                alert(payload.message || 'Đã khôi phục dữ liệu thành công.');
                window.location.reload();
            } catch (error) {
                alert(error.message);
                if (error.message.includes('đã khóa') || error.message.includes('hết hạn') || error.message.includes('không hợp lệ')) {
                    restoreToken = null;
                    setStep(1);
                }
            } finally {
                decisionButtons.forEach((item) => item.disabled = false);
            }
        });
    });

    restoreModalElement?.addEventListener('hidden.bs.modal', () => {
        selectedRestoreUrl = null;
        selectedBackupName = null;
        restoreToken = null;
        restorePassword.value = '';
        restoreMessage?.classList.add('d-none');
        setStep(1);
    });
});
</script>
@endpush
@endsection
