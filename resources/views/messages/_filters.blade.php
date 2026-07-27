<form method="GET" action="{{ $action }}" class="admin-table-tools mb-3">
    <div class="admin-table-tools-left">
        <div class="admin-table-search">
            <i class="bi bi-search"></i>
            <input type="search" name="q" class="form-control" placeholder="Tìm kiếm..." value="{{ $filters['q'] ?? '' }}" aria-label="Tìm kiếm tin nhắn">
        </div>
        <div class="admin-table-filters">
            @if($showStatus ?? false)
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="unread" @selected(($filters['status'] ?? '') === 'unread')>Chưa đọc</option>
                    <option value="read" @selected(($filters['status'] ?? '') === 'read')>Đã đọc</option>
                </select>
            @endif
            <select name="attachment" class="form-select">
                <option value="">Tất cả tệp</option>
                <option value="1" @selected(($filters['attachment'] ?? '') === '1')>Có tệp đính kèm</option>
            </select>
        </div>
    </div>
    <div class="admin-table-actions">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel me-1"></i>Lọc</button>
        <a class="btn btn-outline-secondary" href="{{ $action }}">Xóa lọc</a>
    </div>
</form>
