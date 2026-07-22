<script>
(() => {
    if (window.systemMultiSelectPickerInitialized) {
        return;
    }

    window.systemMultiSelectPickerInitialized = true;

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

    const pickerSelector = '[data-parent-student-picker], [data-multi-select-picker]';

    const closeAllPickers = (except = null) => {
        document.querySelectorAll(`${pickerSelector}.open`).forEach((picker) => {
            if (picker !== except) {
                picker.classList.remove('open');
            }
        });
    };

    const findPicker = (select) => {
        const picker = select.nextElementSibling;
        return picker && picker.matches(pickerSelector) ? picker : null;
    };

    const findElement = (picker, genericSelector, legacySelector) => {
        return picker.querySelector(genericSelector) || picker.querySelector(legacySelector);
    };

    const initPicker = (select) => {
        const picker = findPicker(select);
        if (!picker || picker.dataset.initialized === 'true') {
            return;
        }

        picker.dataset.initialized = 'true';

        const tags = findElement(picker, '[data-multi-select-tags]', '[data-parent-student-tags]');
        const search = findElement(picker, '[data-multi-select-search]', '[data-parent-student-search]');
        const dropdown = findElement(picker, '[data-multi-select-dropdown]', '[data-parent-student-dropdown]');

        if (!tags || !search || !dropdown) {
            return;
        }

        const isParentStudentPicker = picker.matches('[data-parent-student-picker]');
        const placeholder = picker.dataset.placeholder || (isParentStudentPicker ? 'Chưa chọn học sinh' : 'Chưa chọn dữ liệu');
        const emptyText = picker.dataset.emptyText || (isParentStudentPicker ? 'Không tìm thấy học sinh phù hợp.' : 'Không tìm thấy dữ liệu phù hợp.');
        const selectedText = picker.dataset.selectedText || 'Đã chọn';
        const maxVisibleTags = Number.parseInt(picker.dataset.maxVisibleTags || '0', 10);
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
                tags.innerHTML = `<span class="parent-student-placeholder">${escapeHtml(placeholder)}</span>`;
                return;
            }

            const visible = maxVisibleTags > 0 ? selected.slice(0, maxVisibleTags) : selected;
            visible.forEach((item) => {
                const tag = document.createElement('span');
                tag.className = 'parent-student-tag';
                tag.innerHTML = `
                    <span>${escapeHtml(item.label)}</span>
                    <button type="button" aria-label="Bỏ chọn ${escapeHtml(item.label)}">×</button>
                `;

                tag.querySelector('button').addEventListener('click', (event) => {
                    event.stopPropagation();
                    item.option.selected = false;
                    render();
                    search.focus();
                });

                tags.appendChild(tag);
            });

            const hiddenCount = selected.length - visible.length;
            if (hiddenCount > 0) {
                const more = document.createElement('span');
                more.className = 'parent-student-tag';
                more.title = selected.slice(visible.length).map((item) => item.label).join('\n');
                more.textContent = `+${hiddenCount} mục khác`;
                tags.appendChild(more);
            }
        };

        const renderDropdown = () => {
            const keyword = normalizeText(search.value);
            const matched = options
                .filter((item) => !keyword || item.searchText.includes(keyword))
                .slice(0, 80);

            dropdown.innerHTML = '';

            if (!matched.length) {
                dropdown.innerHTML = `<div class="parent-student-empty">${escapeHtml(emptyText)}</div>`;
                return;
            }

            matched.forEach((item) => {
                const row = document.createElement('button');
                row.type = 'button';
                row.className = `parent-student-option${item.option.selected ? ' selected' : ''}`;
                row.innerHTML = `
                    <span>${escapeHtml(item.label)}</span>
                    ${item.option.selected ? `<strong>${escapeHtml(selectedText)}</strong>` : ''}
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
    document.querySelectorAll('[data-parent-student-select], [data-multi-select-picker-select]').forEach(initPicker);
})();
</script>
