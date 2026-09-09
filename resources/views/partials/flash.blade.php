@php
    $flashMessages = collect();

    $flashMap = [
        'success' => ['title' => 'Thành công', 'tone' => 'success'],
        'status' => ['title' => 'Thông báo', 'tone' => 'success'],
        'error' => ['title' => 'Thất bại', 'tone' => 'error'],
        'danger' => ['title' => 'Thất bại', 'tone' => 'error'],
        'warning' => ['title' => 'Cần lưu ý', 'tone' => 'warning'],
        'info' => ['title' => 'Thông báo', 'tone' => 'info'],
    ];

    foreach ($flashMap as $key => $meta) {
        if (session()->has($key)) {
            $flashMessages->push([
                'type' => $meta['tone'],
                'title' => $meta['title'],
                'message' => session($key),
            ]);
        }
    }

    if ($errors->any()) {
        foreach ($errors->all() as $message) {
            $flashMessages->push([
                'type' => 'error',
                'title' => 'Thất bại',
                'message' => $message,
            ]);
        }
    }

    $toastStyles = [
        'success' => ['toast' => '!bg-green-50 border border-green-200 text-green-800', 'icon' => '🟢'],
        'error' => ['toast' => '!bg-red-50 border border-red-200 text-red-800', 'icon' => '🔴'],
        'warning' => ['toast' => '!bg-amber-50 border border-amber-200 text-amber-800', 'icon' => '🟠'],
        'info' => ['toast' => '!bg-orange-50 border border-orange-200 text-orange-800', 'icon' => '🟠'],
    ];
@endphp

@once
    <style>
        .floating-flash-stack {
            position: fixed;
            top: 1.5rem;
            left: 50%;
            z-index: 9999;
            width: min(34rem, calc(100vw - 2rem));
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .625rem;
            pointer-events: none;
            text-align: left;
            transform: translateX(-50%);
            font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .floating-flash-toast {
            width: 100%;
            pointer-events: auto;
            opacity: 1 !important;
            background-image: none !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            isolation: isolate;
            transform: translateY(-10px);
            transition: transform .26s ease;
        }

        .floating-flash-toast.is-visible {
            opacity: 1 !important;
            transform: translateY(0);
        }

        .floating-flash-toast.is-leaving {
            opacity: 1 !important;
            transform: translateY(-12px);
        }

        .floating-flash-toast[data-toast-tone="success"] {
            background: #f0fdf4 !important;
            background-color: #f0fdf4 !important;
            border-color: #bbf7d0 !important;
            color: #166534 !important;
        }

        .floating-flash-toast[data-toast-tone="error"] {
            background: #fef2f2 !important;
            background-color: #fef2f2 !important;
            border-color: #fecaca !important;
            color: #991b1b !important;
        }

        .floating-flash-toast[data-toast-tone="warning"] {
            background: #fffbeb !important;
            background-color: #fffbeb !important;
            border-color: #fde68a !important;
            color: #92400e !important;
        }

        .floating-flash-toast[data-toast-tone="info"] {
            background: #fff7ed !important;
            background-color: #fff7ed !important;
            border-color: #fed7aa !important;
            color: #9a3412 !important;
        }

        @media (max-width: 640px) {
            .floating-flash-stack {
                top: 1rem;
                width: calc(100vw - 1.5rem);
            }
        }
    </style>
@endonce

@if($flashMessages->isNotEmpty())
    <div class="floating-flash-stack fixed top-6 left-1/2 -translate-x-1/2 z-[9999] font-sans font-normal text-base"
         data-floating-flash-stack>
        @foreach($flashMessages as $flash)
            @php($style = $toastStyles[$flash['type']] ?? $toastStyles['info'])
            <div class="floating-flash-toast max-w-lg rounded-xl px-4 py-3 shadow-md flex items-center gap-2.5 duration-300 {{ $style['toast'] }}"
                 style="opacity:1 !important; background-image:none !important; backdrop-filter:none !important; -webkit-backdrop-filter:none !important;"
                 role="alert"
                 data-toast-tone="{{ $flash['type'] }}"
                 data-floating-flash-toast>
                <span class="shrink-0 text-base leading-none">{{ $style['icon'] }}</span>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-base font-normal leading-6 text-left">{{ $flash['title'] }}</div>
                    <div class="mt-0.5 text-base font-normal leading-6 text-left">{{ $flash['message'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@once
    <script>
        (() => {
            const toneMeta = {
                success: { title: 'Thành công', icon: '🟢' },
                status: { title: 'Thông báo', icon: '🟢', tone: 'success' },
                error: { title: 'Thất bại', icon: '🔴' },
                danger: { title: 'Thất bại', icon: '🔴', tone: 'error' },
                warning: { title: 'Cần lưu ý', icon: '🟠' },
                info: { title: 'Thông báo', icon: '🟠' },
            };

            const normalizedTone = (type) => toneMeta[type]?.tone || type || 'info';

            const ensureStack = () => {
                let stack = document.querySelector('[data-floating-flash-stack]');

                if (!stack) {
                    stack = document.createElement('div');
                    stack.className = 'floating-flash-stack fixed top-6 left-1/2 -translate-x-1/2 z-[9999] font-sans font-normal text-base';
                    stack.dataset.floatingFlashStack = '';
                    document.body.appendChild(stack);
                }

                return stack;
            };

            const closeToast = (toast) => {
                if (!toast || !toast.isConnected) {
                    return;
                }

                toast.classList.add('is-leaving');
                window.setTimeout(() => toast.remove(), 260);
            };

            window.SchoolToast = (type = 'info', message = '', title = '') => {
                const tone = normalizedTone(type);
                const meta = toneMeta[type] || toneMeta[tone] || toneMeta.info;
                const toast = document.createElement('div');
                toast.className = 'floating-flash-toast max-w-lg rounded-xl px-4 py-3 shadow-md flex items-center gap-2.5 duration-300';
                toast.dataset.toastTone = tone;
                toast.dataset.floatingFlashToast = '';
                toast.setAttribute('role', 'alert');
                toast.style.opacity = '1';
                toast.style.backgroundImage = 'none';
                toast.style.backdropFilter = 'none';
                toast.style.webkitBackdropFilter = 'none';

                const icon = document.createElement('span');
                icon.className = 'shrink-0 text-base leading-none';
                icon.textContent = meta.icon;

                const text = document.createElement('div');
                text.className = 'min-w-0 flex-1 text-left';

                const heading = document.createElement('div');
                heading.className = 'text-base font-normal leading-6 text-left';
                heading.textContent = title || meta.title;

                const body = document.createElement('div');
                body.className = 'mt-0.5 text-base font-normal leading-6 text-left';
                body.textContent = String(message || '');

                text.append(heading, body);
                toast.append(icon, text);
                ensureStack().appendChild(toast);
                window.setTimeout(() => toast.classList.add('is-visible'), 30);
                window.setTimeout(() => closeToast(toast), 2800);

                return toast;
            };

            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-floating-flash-toast]').forEach((toast, index) => {
                    window.setTimeout(() => toast.classList.add('is-visible'), 30 + (index * 70));
                    window.setTimeout(() => closeToast(toast), 2500 + (index * 300));
                });
            });
        })();
    </script>
@endonce
