@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-school-year-form]').forEach((form) => {
        const startYearInput = form.querySelector('[data-start-year]');
        const endYearInput = form.querySelector('[data-end-year]');
        const startDateInput = form.querySelector('[data-start-date]');
        const endDateInput = form.querySelector('[data-end-date]');
        const activeInput = form.querySelector('input[name="is_active"]');
        const confirmInput = form.querySelector('[data-confirm-activation]');
        const activeYear = form.dataset.activeYear || '';
        const modalElement = document.getElementById('activateSchoolYearModal');
        const targetYearLabel = modalElement?.querySelector('[data-target-year-label]');
        const confirmButton = modalElement?.querySelector('[data-confirm-activation-submit]');
        const modal = modalElement && window.bootstrap ? new bootstrap.Modal(modalElement) : null;
        let startDateTouched = Boolean(startDateInput?.value);
        let endDateTouched = Boolean(endDateInput?.value);

        const padYear = (value) => String(value).padStart(4, '0');

        const selectedYearName = () => {
            const startYear = Number.parseInt(startYearInput?.value || '', 10);
            const endYear = Number.parseInt(endYearInput?.value || '', 10);

            if (Number.isNaN(startYear) || Number.isNaN(endYear)) {
                return '';
            }

            return `${startYear} - ${endYear}`;
        };

        const syncDates = () => {
            const startYear = Number.parseInt(startYearInput?.value || '', 10);
            const endYear = Number.parseInt(endYearInput?.value || '', 10);

            if (Number.isNaN(startYear) || Number.isNaN(endYear)) {
                return;
            }

            if (!startDateTouched && startDateInput) {
                startDateInput.value = `${padYear(startYear)}-08-01`;
            }

            if (!endDateTouched && endDateInput) {
                endDateInput.value = `${padYear(endYear)}-05-31`;
            }
        };

        startYearInput?.addEventListener('input', syncDates);
        endYearInput?.addEventListener('input', syncDates);
        startDateInput?.addEventListener('input', () => startDateTouched = true);
        endDateInput?.addEventListener('input', () => endDateTouched = true);
        syncDates();

        form.addEventListener('submit', (event) => {
            if (!activeInput?.checked || !activeYear || confirmInput?.value === '1') {
                return;
            }

            event.preventDefault();
            if (targetYearLabel) {
                targetYearLabel.textContent = selectedYearName() || 'năm học này';
            }
            modal?.show();
        });

        confirmButton?.addEventListener('click', () => {
            if (confirmInput) {
                confirmInput.value = '1';
            }
            modal?.hide();
            HTMLFormElement.prototype.submit.call(form);
        });
    });
});
</script>
@endonce
