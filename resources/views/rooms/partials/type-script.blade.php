<script>
    (() => {
        const form = document.querySelector('[data-room-form]');
        if (!form) {
            return;
        }

        const select = form.querySelector('[data-room-type]');
        const customWrap = form.querySelector('[data-custom-room-type-wrap]');
        const customInput = customWrap?.querySelector('input');

        const syncCustomType = () => {
            const isOther = select?.value === '{{ \App\Models\Room::TYPE_OTHER }}';
            customWrap?.classList.toggle('d-none', !isOther);
            if (customInput) {
                customInput.required = isOther;
                if (!isOther) {
                    customInput.value = '';
                }
            }
        };

        select?.addEventListener('change', syncCustomType);
        syncCustomType();
    })();
</script>
