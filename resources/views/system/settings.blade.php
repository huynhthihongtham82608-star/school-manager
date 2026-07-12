@extends('layouts.app')
@section('title', 'Cài đặt hệ thống')

@section('content')
<form method="POST" action="{{ route('system.settings.update') }}" enctype="multipart/form-data" class="card shadow-sm p-4">
    @csrf
    @method('PUT')

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Tên trường</label>
            <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $setting->school_name) }}" required>
            @error('school_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Tên viết tắt</label>
            <input type="text" name="short_name" class="form-control" value="{{ old('short_name', $setting->short_name) }}">
            @error('short_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Năm học hiện hành</label>
            <select name="default_school_year_id" class="form-select">
                <option value="">Theo năm học đang hoạt động</option>
                @foreach($schoolYears as $year)
                    <option value="{{ $year->id }}" @selected(old('default_school_year_id', $setting->default_school_year_id) == $year->id)>
                        {{ $year->name }}
                    </option>
                @endforeach
            </select>
            @error('default_school_year_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Logo trường</label>
            <input type="file" name="logo" class="form-control" accept="image/*">
            @error('logo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            @if($setting->logoUrl())
                <div class="mt-2 d-flex align-items-center gap-2">
                    <img src="{{ $setting->logoUrl() }}" alt="Logo trường" style="width:48px;height:48px;object-fit:cover;border-radius:12px">
                    <span class="text-muted small">Logo hiện tại</span>
                </div>
            @endif
        </div>
        <div class="col-md-8">
            <label class="form-label">Địa chỉ</label>
            <input type="text" name="address" class="form-control" value="{{ old('address', $setting->address) }}">
            @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Số điện thoại</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $setting->phone) }}">
            @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Thư điện tử</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $setting->email) }}">
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Trang web</label>
            <input type="text" name="website" class="form-control" value="{{ old('website', $setting->website) }}">
            @error('website')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Hiệu trưởng</label>
            <input type="text" name="principal_name" class="form-control" value="{{ old('principal_name', $setting->principal_name) }}">
            @error('principal_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="mt-4 pt-4 border-top">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
            <div>
                <h5 class="mb-1 fw-bold">Nội dung AI</h5>
                <div class="text-muted small">Thông điệp truyền cảm hứng được hiển thị ở cuối trang AI.</div>
            </div>
            @if($supportsAiContent)
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-ai-encouragement>
                    <i class="bi bi-plus-lg me-1"></i>Thêm thông điệp
                </button>
            @endif
        </div>

        @if($supportsAiContent)
            @php
                $aiEncouragementRows = old('ai_encouragements', $setting->ai_encouragements ?: []);
                if (empty($aiEncouragementRows)) {
                    $aiEncouragementRows = [['content' => '', 'enabled' => true]];
                }
            @endphp

            <div class="table-responsive">
                <table class="table align-middle mb-0" data-ai-encouragement-table>
                    <thead>
                        <tr>
                            <th style="width: 72px;">Bật</th>
                            <th>Thông điệp truyền cảm hứng</th>
                            <th class="text-end" style="width: 88px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($aiEncouragementRows as $index => $row)
                            <tr data-ai-encouragement-row>
                                <td>
                                    <input type="hidden" name="ai_encouragements[{{ $index }}][enabled]" value="0">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" name="ai_encouragements[{{ $index }}][enabled]" value="1" @checked((bool)($row['enabled'] ?? true)) aria-label="Bật thông điệp">
                                    </div>
                                </td>
                                <td>
                                    <textarea name="ai_encouragements[{{ $index }}][content]" class="form-control" rows="2" maxlength="500" placeholder="Nhập thông điệp truyền cảm hứng bằng tiếng Việt">{{ $row['content'] ?? '' }}</textarea>
                                    @error("ai_encouragements.$index.content")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </td>
                                <td class="text-end">
                                    <button type="button" class="content-action-btn icon-only danger" title="Xóa" aria-label="Xóa" data-remove-ai-encouragement>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-warning mb-0">
                Vui lòng chạy migration mới để sử dụng phần Nội dung AI.
            </div>
        @endif
    </div>

    <div class="form-actions mt-4">
        <button class="btn btn-primary"><i class="bi bi-save me-2"></i>Lưu cài đặt</button>
    </div>
</form>

@if($supportsAiContent)
    <template id="aiEncouragementRowTemplate">
        <tr data-ai-encouragement-row>
            <td>
                <input type="hidden" data-name-template="ai_encouragements[__INDEX__][enabled]" value="0">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" data-name-template="ai_encouragements[__INDEX__][enabled]" value="1" checked aria-label="Bật thông điệp">
                </div>
            </td>
            <td>
                <textarea data-name-template="ai_encouragements[__INDEX__][content]" class="form-control" rows="2" maxlength="500" placeholder="Nhập thông điệp truyền cảm hứng bằng tiếng Việt"></textarea>
            </td>
            <td class="text-end">
                <button type="button" class="content-action-btn icon-only danger" title="Xóa" aria-label="Xóa" data-remove-ai-encouragement>
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const table = document.querySelector('[data-ai-encouragement-table] tbody');
            const addButton = document.querySelector('[data-add-ai-encouragement]');
            const template = document.getElementById('aiEncouragementRowTemplate');

            const refreshNames = () => {
                table?.querySelectorAll('[data-ai-encouragement-row]').forEach((row, index) => {
                    row.querySelectorAll('[name], [data-name-template]').forEach((field) => {
                        const templateName = field.dataset.nameTemplate || field.name.replace(/\[\d+\]/, '[__INDEX__]');
                        field.dataset.nameTemplate = templateName;
                        field.name = templateName.replace('__INDEX__', index);
                    });
                });
            };

            addButton?.addEventListener('click', () => {
                const fragment = template.content.cloneNode(true);
                table.appendChild(fragment);
                refreshNames();
            });

            table?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-ai-encouragement]');
                if (! button) {
                    return;
                }

                const row = button.closest('[data-ai-encouragement-row]');
                row?.remove();
                refreshNames();
            });
        });
    </script>
@endif
@endsection
