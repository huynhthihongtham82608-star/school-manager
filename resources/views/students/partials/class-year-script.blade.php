<script>
document.addEventListener('DOMContentLoaded', () => {
    const classSelect = document.querySelector('[data-student-class]');
    const yearInput = document.querySelector('[data-student-year]');

    if (!classSelect || !yearInput) {
        return;
    }

    const syncYear = () => {
        const selected = classSelect.options[classSelect.selectedIndex];
        yearInput.value = selected ? selected.dataset.year || '' : '';
    };

    classSelect.addEventListener('change', syncYear);
    syncYear();

    const admissionType = document.querySelector('[data-admission-type]');
    const transferFields = document.querySelectorAll('[data-transfer-field]');

    const syncAdmissionFields = () => {
        const isTransfer = admissionType && admissionType.value === '{{ \App\Models\Student::ADMISSION_TRANSFER }}';

        transferFields.forEach((field) => {
            field.classList.toggle('d-none', !isTransfer);
        });
    };

    admissionType?.addEventListener('change', syncAdmissionFields);
    syncAdmissionFields();

    document.querySelectorAll('[data-custom-toggle]').forEach((toggle) => {
        const target = toggle.dataset.customToggle;
        const fields = document.querySelectorAll(`[data-custom-field="${target}"]`);
        const syncCustomField = () => {
            const isOther = toggle.value === 'Khác';

            fields.forEach((field) => {
                field.classList.toggle('d-none', !isOther);
            });
        };

        toggle.addEventListener('change', syncCustomField);
        syncCustomField();
    });
});
</script>
