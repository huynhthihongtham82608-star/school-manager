<script>
document.addEventListener('DOMContentLoaded', () => {
    const yearSelect = document.querySelector('[data-class-year]');
    const semesterSelect = document.querySelector('[data-class-semester]');

    if (!yearSelect || !semesterSelect) {
        return;
    }

    const syncSemesters = () => {
        const selectedYear = yearSelect.value;
        let firstVisible = null;

        [...semesterSelect.options].forEach((option) => {
            const visible = option.dataset.year === selectedYear;
            option.hidden = !visible;
            option.disabled = !visible;

            if (visible && !firstVisible) {
                firstVisible = option;
            }
        });

        if (semesterSelect.selectedOptions[0]?.disabled && firstVisible) {
            semesterSelect.value = firstVisible.value;
        }
    };

    yearSelect.addEventListener('change', syncSemesters);
    syncSemesters();
});
</script>
