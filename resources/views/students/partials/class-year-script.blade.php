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
    };

    document.querySelectorAll('[data-student-form]').forEach(setupStudentForm);
});
</script>
