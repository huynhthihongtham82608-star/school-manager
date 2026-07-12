<script>
(() => {
    if (window.parentStudentPickerInitialized) {
        return;
    }

    window.parentStudentPickerInitialized = true;

    const normalizeText = (value) => (value || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();

    const escapeHtml = (value) => (value || '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    const closeAllPickers = (except = null) => {
        document.querySelectorAll('[data-parent-student-picker].open').forEach((picker) => {
            if (picker !== except) {
                picker.classList.remove('open');
            }
        });
    };

    const initPicker = (select) => {
        const picker = select.nextElementSibling;
        if (!picker || !picker.matches('[data-parent-student-picker]')) {
            return;
        }

        const tags = picker.querySelector('[data-parent-student-tags]');
        const search = picker.querySelector('[data-parent-student-search]');
        const dropdown = picker.querySelector('[data-parent-student-dropdown]');
        const options = Array.from(select.options).map((option) => ({
            option,
            value: option.value,
            label: option.textContent.replace(/\s+/g, ' ').trim(),
            searchText: normalizeText(option.textContent),
        }));

        const selectedOptions = () => options.filter((item) => item.option.selected);

        const renderTags = () => {
            tags.innerHTML = '';
            const selected = selectedOptions();

            if (!selected.length) {
                tags.innerHTML = '<span class="parent-student-placeholder">Chưa chọn học sinh</span>';
                return;
            }

            selected.forEach((item) => {
                const tag = document.createElement('span');
                tag.className = 'parent-student-tag';
                tag.innerHTML = `
                    <span>${escapeHtml(item.label)}</span>
                    <button type="button" aria-label="Bỏ chọn ${escapeHtml(item.label)}">×</button>
                `;

                tag.querySelector('button').addEventListener('click', () => {
                    item.option.selected = false;
                    render();
                    search.focus();
                });

                tags.appendChild(tag);
            });
        };

        const renderDropdown = () => {
            const keyword = normalizeText(search.value);
            const matched = options
                .filter((item) => !keyword || item.searchText.includes(keyword))
                .slice(0, 80);

            dropdown.innerHTML = '';

            if (!matched.length) {
                dropdown.innerHTML = '<div class="parent-student-empty">Không tìm thấy học sinh phù hợp.</div>';
                return;
            }

            matched.forEach((item) => {
                const row = document.createElement('button');
                row.type = 'button';
                row.className = `parent-student-option${item.option.selected ? ' selected' : ''}`;
                row.innerHTML = `
                    <span>${escapeHtml(item.label)}</span>
                    ${item.option.selected ? '<strong>Đã chọn</strong>' : ''}
                `;

                row.addEventListener('click', () => {
                    item.option.selected = !item.option.selected;
                    search.value = '';
                    render();
                    picker.classList.add('open');
                    search.focus();
                });

                dropdown.appendChild(row);
            });
        };

        const render = () => {
            renderTags();
            renderDropdown();
        };

        picker.addEventListener('click', (event) => {
            event.stopPropagation();
            closeAllPickers(picker);
            picker.classList.add('open');
            search.focus();
        });

        search.addEventListener('focus', () => {
            closeAllPickers(picker);
            picker.classList.add('open');
            renderDropdown();
        });

        search.addEventListener('input', () => {
            picker.classList.add('open');
            renderDropdown();
        });

        search.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
            }

            if (event.key === 'Escape') {
                picker.classList.remove('open');
            }
        });

        render();
    };

    document.addEventListener('click', () => closeAllPickers());
    document.querySelectorAll('[data-parent-student-select]').forEach(initPicker);
})();
</script>
