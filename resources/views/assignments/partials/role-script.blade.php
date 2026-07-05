<script>
document.addEventListener('DOMContentLoaded', () => {
    const roleSelect = document.querySelector('[data-assignment-role]');
    const customRoleWrap = document.querySelector('[data-assignment-custom-role-wrap]');
    const customRoleInput = customRoleWrap?.querySelector('input[name="custom_role"]');

    if (!roleSelect || !customRoleWrap || !customRoleInput) {
        return;
    }

    const syncCustomRole = () => {
        const isOther = roleSelect.value === '{{ \App\Models\TeachingAssignment::ROLE_OTHER }}';
        customRoleWrap.classList.toggle('d-none', !isOther);
        customRoleInput.toggleAttribute('required', isOther);

        if (!isOther) {
            customRoleInput.value = '';
        }
    };

    roleSelect.addEventListener('change', syncCustomRole);
    syncCustomRole();
});
</script>
