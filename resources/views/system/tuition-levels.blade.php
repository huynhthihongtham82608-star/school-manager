@extends('layouts.app')
@section('title', 'Cấu hình mức thu')

@section('content')
<style>
    .tuition-config-page,
    .tuition-config-page * {
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        text-align: left;
    }

    .tuition-config-card {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        box-shadow: 0 1px 0 rgba(15, 23, 42, .03);
    }

    .tuition-config-table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        margin: 0;
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
    }

    .tuition-config-table th {
        padding: .85rem .75rem;
        color: #111827;
        font-size: .875rem;
        font-weight: 500;
        background: rgba(255, 247, 237, .7);
        border-bottom: 1px solid rgba(254, 215, 170, .7);
    }

    .tuition-config-table td {
        padding: .8rem .75rem;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
        font-size: .875rem;
        font-weight: 400;
    }

    .tuition-config-table tbody tr:nth-child(even) {
        background: rgba(255, 247, 237, .18);
    }

    .tuition-config-table tbody tr:hover {
        background: rgba(255, 247, 237, .45);
    }

    .tuition-config-input {
        width: 100%;
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        background: #f9fafb;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        padding: .5rem .7rem;
        outline: none;
    }

    .tuition-config-input:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .18rem rgba(255, 237, 213, .82);
    }

    .tuition-config-add {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        color: #c2410c;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        padding: .48rem .8rem;
        font-size: .8rem;
        font-weight: 400;
        cursor: pointer;
        transition: all .15s ease;
    }

    .tuition-config-add:hover {
        color: #9a3412;
        background: #ffedd5;
    }

    .tuition-qr-preview {
        width: 156px;
        height: 156px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: #9a3412;
        font-size: .78rem;
        font-weight: 400;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 14px;
    }

    .tuition-qr-preview img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #fff;
    }
</style>

<div class="tuition-config-page w-full text-left font-sans">
    <x-page-header
        title="Cấu hình mức thu"
        subtitle="Thiết lập các khoản thu, số tiền nền và ảnh QR nhận tiền toàn trường."
    >
        <button type="submit" form="tuition-config-form" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Lưu mức thu
        </button>
    </x-page-header>

    @if(! ($settingsTableReady ?? false))
        <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm font-normal rounded-xl px-4 py-3 mb-6 text-left">
            Chưa có bảng settings. Vui lòng chạy migration trước khi lưu cấu hình.
        </div>
    @endif

    <form id="tuition-config-form" method="POST" action="{{ route('system.tuition-levels.update') }}" enctype="multipart/form-data" class="w-full text-left">
        @csrf
        @method('PUT')

        <div class="tuition-config-card p-0 mb-5">
            <table class="tuition-config-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">STT</th>
                        <th style="width: 48%;">Khoản thu</th>
                        <th style="width: 28%;">Số tiền nền</th>
                        <th style="width: 16%;">Đơn vị</th>
                    </tr>
                </thead>
                <tbody id="tuition-fee-items-body">
                    @foreach($feeItems as $index => $item)
                        <tr>
                            <td class="text-gray-500" data-row-index>{{ $index + 1 }}</td>
                            <td>
                                <input type="hidden" name="fee_items[{{ $index }}][key]" value="{{ old('fee_items.' . $index . '.key', $item['key']) }}">
                                <input type="text" name="fee_items[{{ $index }}][label]" value="{{ old('fee_items.' . $index . '.label', $item['label']) }}" class="tuition-config-input" required>
                            </td>
                            <td>
                                <input type="number" name="fee_items[{{ $index }}][amount]" value="{{ old('fee_items.' . $index . '.amount', $item['amount']) }}" min="0" step="1000" class="tuition-config-input" required>
                            </td>
                            <td class="text-sm font-normal text-gray-500">VNĐ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button type="button" id="add-tuition-fee-item" class="tuition-config-add mb-5">
            <i class="bi bi-plus-lg"></i><span>Thêm khoản thu khác</span>
        </button>

        <div class="bg-white border border-orange-100 p-5 rounded-xl shadow-2xs text-left w-full">
            <div class="flex flex-col md:flex-row items-start gap-5 w-full">
                <div class="flex-1 text-left">
                    <h2 class="text-base font-semibold text-gray-900 mb-1 text-left">Ảnh mã QR nhận tiền toàn trường</h2>
                    <p class="text-sm font-normal text-orange-700/70 mb-3 text-left">Tải lên ảnh mã QR để Phụ huynh quét khi chọn chuyển khoản.</p>
                    <label for="tuition_qr_image" class="form-label text-sm font-normal text-gray-700 text-left">Tải lên ảnh mã QR nhận tiền toàn trường</label>
                    <input id="tuition_qr_image" type="file" name="tuition_qr_image" accept="image/png,image/jpeg,image/webp" class="tuition-config-input">
                    @error('tuition_qr_image')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="tuition-qr-preview" id="tuition-qr-preview">
                    @if($qrImageUrl)
                        <img src="{{ $qrImageUrl }}" alt="QR nhận tiền hiện tại">
                    @else
                        <span>Chưa có QR</span>
                    @endif
                </div>
            </div>
        </div>

        @if($errors->has('fee_items') || collect($errors->getMessages())->keys()->contains(fn ($key) => str_starts_with($key, 'fee_items.')))
            <div class="text-danger small text-left mt-3">Vui lòng kiểm tra lại cấu hình mức thu.</div>
        @endif
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('tuition-fee-items-body');
    const addButton = document.getElementById('add-tuition-fee-item');
    const qrInput = document.getElementById('tuition_qr_image');
    const qrPreview = document.getElementById('tuition-qr-preview');

    const refreshIndexes = () => {
        [...body.querySelectorAll('tr')].forEach((row, index) => {
            row.querySelector('[data-row-index]').textContent = index + 1;
            row.querySelectorAll('input').forEach((input) => {
                input.name = input.name.replace(/fee_items\[\d+]/, `fee_items[${index}]`);
            });
        });
    };

    addButton?.addEventListener('click', () => {
        const index = body.querySelectorAll('tr').length;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="text-gray-500" data-row-index>${index + 1}</td>
            <td>
                <input type="hidden" name="fee_items[${index}][key]" value="fee_custom_${Date.now()}_${index}">
                <input type="text" name="fee_items[${index}][label]" value="" placeholder="Tên khoản thu tự nghĩa" class="tuition-config-input" required>
            </td>
            <td>
                <input type="number" name="fee_items[${index}][amount]" value="" min="0" step="1000" placeholder="Số tiền" class="tuition-config-input" required>
            </td>
            <td class="text-sm font-normal text-gray-500">VNĐ</td>
        `;
        body.appendChild(row);
        refreshIndexes();
        row.querySelector('input[type="text"]')?.focus();
    });

    qrInput?.addEventListener('change', () => {
        const file = qrInput.files?.[0];
        if (!file || !qrPreview) {
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            qrPreview.innerHTML = `<img src="${event.target.result}" alt="Preview QR nhận tiền">`;
        };
        reader.readAsDataURL(file);
    });
});
</script>
@endpush
