<script>
document.addEventListener('DOMContentLoaded', () => {
    const teacherSelect = document.querySelector('[data-assignment-teacher]');
    const subjectSelect = document.querySelector('[data-assignment-subject-select]');
    const departmentFilter = document.querySelector('[data-assignment-department-filter]');
    const departmentWarning = document.querySelector('[data-assignment-department-warning]');
    const subjectDepartmentsText = document.querySelector('[data-assignment-subject-departments]');

    const selectedSubjectDepartmentIds = () => {
        const selected = subjectSelect?.selectedOptions?.[0];
        return (selected?.dataset?.departments || '').split(',').filter(Boolean);
    };

    const updateSubjectDepartmentText = () => {
        const selected = subjectSelect?.selectedOptions?.[0];
        const names = selected?.dataset?.departmentNames || '';
        subjectDepartmentsText.textContent = names
            ? `Tổ phụ trách: ${names}`
            : 'Môn học này chưa được gán tổ phụ trách.';
    };

    const warnIfTeacherOutsideDepartment = () => {
        const teacher = teacherSelect?.selectedOptions?.[0];
        const teacherDepartment = teacher?.dataset?.department || '';
        const subjectDepartments = selectedSubjectDepartmentIds();
        const shouldWarn = subjectDepartments.length > 0
            && Boolean(teacher?.value)
            && (! teacherDepartment || ! subjectDepartments.includes(teacherDepartment));

        departmentWarning?.classList.toggle('d-none', ! shouldWarn);
    };

    const filterTeachers = () => {
        const departmentId = departmentFilter?.value || '';
        [...teacherSelect.options].forEach((option) => {
            option.hidden = Boolean(departmentId) && option.value && option.dataset.department !== departmentId;
        });

        if (teacherSelect.selectedOptions[0]?.hidden) {
            teacherSelect.value = '';
        }

        warnIfTeacherOutsideDepartment();
    };

    const syncDepartmentFromSubject = () => {
        updateSubjectDepartmentText();

        const subjectDepartments = selectedSubjectDepartmentIds();
        if (subjectDepartments.length === 1 && departmentFilter) {
            departmentFilter.value = subjectDepartments[0];
        }

        filterTeachers();
    };

    subjectSelect?.addEventListener('change', syncDepartmentFromSubject);
    teacherSelect?.addEventListener('change', warnIfTeacherOutsideDepartment);
    departmentFilter?.addEventListener('change', filterTeachers);

    syncDepartmentFromSubject();
});
</script>
