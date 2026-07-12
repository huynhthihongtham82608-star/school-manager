@php
    $messageTabs = [
        ['route' => 'messages.inbox', 'label' => 'Hộp thư đến', 'icon' => 'bi-inbox'],
        ['route' => 'messages.sent', 'label' => 'Đã gửi', 'icon' => 'bi-send'],
        ['route' => 'messages.create', 'label' => 'Soạn tin', 'icon' => 'bi-pencil-square'],
        ['route' => 'messages.trash', 'label' => 'Thùng rác', 'icon' => 'bi-trash3'],
    ];
@endphp

<div class="admin-context-bar">
    <div class="admin-section-tabs" aria-label="Tin nhắn nội bộ">
        @foreach($messageTabs as $tab)
            <a href="{{ route($tab['route']) }}" class="admin-section-tab {{ request()->routeIs($tab['route']) ? 'active' : '' }}">
                <i class="bi {{ $tab['icon'] }}"></i>
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
