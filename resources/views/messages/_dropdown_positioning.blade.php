@once('message-dropdown-positioning')
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdowns = document.querySelectorAll('.message-card .dropdown');

            const placeMenu = function (dropdown) {
                const button = dropdown.querySelector('[data-bs-toggle="dropdown"]');
                const menu = dropdown._messageFloatingMenu;
                if (! button || ! menu || ! menu.classList.contains('show')) {
                    return;
                }

                const rect = button.getBoundingClientRect();
                const right = Math.max(8, window.innerWidth - rect.right);

                menu.style.position = 'fixed';
                menu.style.inset = 'auto';
                menu.style.top = (rect.bottom + 6) + 'px';
                menu.style.right = right + 'px';
                menu.style.left = 'auto';
                menu.style.transform = 'none';
                menu.style.zIndex = '9999';
            };

            dropdowns.forEach(function (dropdown, index) {
                const menu = dropdown.querySelector('.dropdown-menu');
                if (! menu) {
                    return;
                }

                dropdown.dataset.messageDropdownId = dropdown.dataset.messageDropdownId || ('message-dropdown-' + index);
                menu.dataset.messageDropdownOwner = dropdown.dataset.messageDropdownId;

                dropdown.addEventListener('shown.bs.dropdown', function () {
                    dropdown._messageFloatingMenu = menu;
                    document.body.appendChild(menu);
                    placeMenu(dropdown);
                });

                dropdown.addEventListener('hide.bs.dropdown', function () {
                    if (menu.parentElement === document.body) {
                        dropdown.appendChild(menu);
                    }

                    menu.removeAttribute('style');
                    dropdown._messageFloatingMenu = null;
                });
            });

            ['scroll', 'resize'].forEach(function (eventName) {
                window.addEventListener(eventName, function () {
                    dropdowns.forEach(placeMenu);
                }, true);
            });
        });
        </script>
    @endpush
@endonce
