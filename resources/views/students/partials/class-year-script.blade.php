<script>
document.addEventListener('DOMContentLoaded', () => {
    const setupStudentForm = (scope) => {
        const classSelect = scope.querySelector('[data-student-class]');
        const yearInput = scope.querySelector('[data-student-year]');

        if (classSelect && yearInput) {
            const syncYear = () => {
                const selected = classSelect.options[classSelect.selectedIndex];
                yearInput.value = selected ? selected.dataset.year || '' : '';
            };

            classSelect.addEventListener('change', syncYear);
            syncYear();
        }

        const admissionType = scope.querySelector('[data-admission-type]');
        const transferFields = scope.querySelectorAll('[data-transfer-field]');

        const syncAdmissionFields = () => {
            const isTransfer = admissionType && admissionType.value === '{{ \App\Models\Student::ADMISSION_TRANSFER }}';

            transferFields.forEach((field) => {
                field.classList.toggle('d-none', !isTransfer);
            });
        };

        admissionType?.addEventListener('change', syncAdmissionFields);
        syncAdmissionFields();

        scope.querySelectorAll('[data-custom-toggle]').forEach((toggle) => {
            const target = toggle.dataset.customToggle;
            const fields = scope.querySelectorAll(`[data-custom-field="${target}"]`);
            const syncCustomField = () => {
                const isOther = toggle.value === 'Khác';

                fields.forEach((field) => {
                    field.classList.toggle('d-none', !isOther);
                });
            };

            toggle.addEventListener('change', syncCustomField);
            syncCustomField();
        });

        const parentPhone = scope.querySelector('[data-parent-phone]');
        const parentName = scope.querySelector('[data-parent-name]');
        const parentId = scope.querySelector('[data-parent-id]');
        const parentStatus = scope.querySelector('[data-parent-lookup-status]');
        const lookupUrl = scope.dataset.parentLookupUrl;
        const originalParentId = parentId?.dataset.originalParentId || parentId?.value || '';
        let parentLookupTimer = null;
        let parentLookupController = null;

        const setParentStatus = (message = '', type = 'neutral') => {
            if (!parentStatus) {
                return;
            }

            parentStatus.textContent = message;
            parentStatus.classList.toggle('d-none', message === '');
            parentStatus.classList.toggle('text-green-700', type === 'success');
            parentStatus.classList.toggle('text-orange-500', type !== 'success' && type !== 'error');
            parentStatus.classList.toggle('text-red-700', type === 'error');
        };

        const unlockParentName = () => {
            if (!parentName) {
                return;
            }

            parentName.readOnly = false;
            parentName.classList.remove('bg-orange-50', 'border-orange-200');
            parentName.classList.add('bg-white');
        };

        const lockParentName = (name) => {
            if (!parentName) {
                return;
            }

            parentName.value = name || '';
            parentName.readOnly = true;
            parentName.classList.add('bg-orange-50', 'border-orange-200');
            parentName.classList.remove('bg-white');
        };

        const lookupParent = async () => {
            const phone = (parentPhone?.value || '').trim();

            if (!parentPhone || !parentName || !lookupUrl) {
                return;
            }

            if (phone.length < 8) {
                unlockParentName();
                setParentStatus('');
                return;
            }

            parentLookupController?.abort();
            parentLookupController = new AbortController();

            try {
                const url = new URL(lookupUrl, window.location.origin);
                url.searchParams.set('phone', phone);
                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: parentLookupController.signal,
                });
                const payload = await response.json();

                if (payload.exists) {
                    if (parentId) {
                        parentId.value = payload.id || '';
                    }
                    lockParentName(payload.name || '');
                    const isReplacement = originalParentId && payload.id && String(payload.id) !== String(originalParentId);
                    setParentStatus('🟢 Phụ huynh đã có sẵn', 'success');
                    setParentStatus(isReplacement ? 'Phụ huynh đã có sẵn; lưu form sẽ thay phụ huynh cho học sinh này.' : 'Phụ huynh đã có sẵn; lưu form sẽ dùng hồ sơ phụ huynh này.', 'success');
                    return;
                }

                if (parentId) {
                    parentId.value = originalParentId;
                }
                unlockParentName();
                setParentStatus('Số điện thoại mới, nhập họ tên phụ huynh để tạo liên kết.', 'neutral');
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                unlockParentName();
                setParentStatus('Không tra cứu được phụ huynh, vui lòng kiểm tra lại.', 'error');
            }
        };

        parentPhone?.addEventListener('input', () => {
            window.clearTimeout(parentLookupTimer);
            parentLookupTimer = window.setTimeout(lookupParent, 350);
        });

        if ((parentPhone?.value || '').trim().length >= 8) {
            lookupParent();
        }
    };

    document.querySelectorAll('[data-student-form]').forEach(setupStudentForm);
});
</script>
