<script>
document.addEventListener('DOMContentLoaded', () => {
    const normalizeText = (value) => (value || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
    const teacherSelect = document.querySelector('[data-assignment-teacher]');
    const subjectSelect = document.querySelector('[data-assignment-subject-select]');
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
        const subject = subjectSelect?.selectedOptions?.[0];
        const subjectId = subjectSelect?.value || '';
        const subjectName = normalizeText(subject?.dataset?.subjectName);
        const teacherSubjectName = normalizeText(teacher?.dataset?.primarySubjectName);
        const shouldWarn = Boolean(teacher?.value)
            && Boolean(subjectId)
            && teacher?.dataset?.primarySubjectId !== subjectId
            && (! subjectName || teacherSubjectName !== subjectName);

        departmentWarning?.classList.toggle('d-none', ! shouldWarn);
    };

    const filterTeachers = () => {
        const subject = subjectSelect?.selectedOptions?.[0];
        const subjectId = subjectSelect?.value || '';
        const subjectName = normalizeText(subject?.dataset?.subjectName);
        [...teacherSelect.options].forEach((option) => {
            const matchesSubjectId = option.dataset.primarySubjectId === subjectId;
            const matchesSubjectName = subjectName && normalizeText(option.dataset.primarySubjectName) === subjectName;
            option.hidden = Boolean(subjectId) && option.value && ! matchesSubjectId && ! matchesSubjectName;
        });

        if (teacherSelect.selectedOptions[0]?.hidden) {
            teacherSelect.value = '';
        }

        warnIfTeacherOutsideDepartment();
    };

    const syncDepartmentFromSubject = () => {
        updateSubjectDepartmentText();
        filterTeachers();
    };

    subjectSelect?.addEventListener('change', syncDepartmentFromSubject);
    teacherSelect?.addEventListener('change', warnIfTeacherOutsideDepartment);

    syncDepartmentFromSubject();
});
</script>
