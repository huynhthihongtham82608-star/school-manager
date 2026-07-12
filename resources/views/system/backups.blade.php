@extends('layouts.app')
@section('title', 'Sao lưu & Khôi phục dữ liệu')

@section('content')
<div class="page-heading">
    <div>
        <h5>Sao lưu & Khôi phục dữ liệu</h5>
        <div class="text-muted">Tạo và tải bản sao lưu database. Chức năng khôi phục đang được khóa để bảo vệ dữ liệu.</div>
    </div>
    <form method="POST" action="{{ route('system.backups.store') }}">
        @csrf
        <button class="btn btn-primary" onclick="return confirm('Tạo bản sao lưu database hiện tại?')">
            <i class="bi bi-database-add me-2"></i>Tạo bản sao lưu
        </button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tên tệp</th>
                    <th>Dung lượng</th>
                    <th>Thời gian tạo</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($files as $file)
                    <tr>
                        <td class="fw-semibold">{{ $file['name'] }}</td>
                        <td>{{ number_format($file['size'] / 1024, 1) }} KB</td>
                        <td>{{ \Carbon\Carbon::createFromTimestamp($file['updated_at'])->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a class="content-action-btn icon-only view" href="{{ route('system.backups.download', $file['name']) }}" title="Tải xuống" data-bs-toggle="tooltip">
                                <i class="bi bi-download"></i>
                            </a>
                            <button type="button" class="content-action-btn icon-only" disabled title="Khôi phục đang khóa" data-bs-toggle="tooltip">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state"><i class="bi bi-database"></i>Chưa có bản sao lưu nào.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
