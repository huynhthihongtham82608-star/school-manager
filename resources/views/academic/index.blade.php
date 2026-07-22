@extends('layouts.app')
@section('title', 'Quản lý học vụ')

@section('content')
<div class="academic-launchpad">
    <div class="academic-launchpad-head">
        <div>
            <h5>Quản lý học vụ</h5>
            <p>Truy cập nhanh các chức năng cấu hình, tổ chức giảng dạy, kết quả học tập và nề nếp học sinh.</p>
        </div>
        <span class="badge bg-light text-muted border">{{ $totalCards }} chức năng học vụ</span>
    </div>

    @foreach($groups as $group)
        <section class="academic-launchpad-section">
            <div class="academic-launchpad-section-head">
                <div>
                    <h6>{{ $group['title'] }}</h6>
                    <p>{{ $group['subtitle'] }}</p>
                </div>
            </div>

            <div class="academic-launchpad-grid">
                @foreach($group['items'] as $item)
                    <a href="{{ $item['url'] }}" class="academic-launchpad-card tone-{{ $item['tone'] }}">
                        <span class="academic-launchpad-icon">
                            <i class="bi {{ $item['icon'] }}"></i>
                        </span>
                        <span class="academic-launchpad-content">
                            <span class="academic-launchpad-title">{{ $item['title'] }}</span>
                            <span class="academic-launchpad-desc">{{ $item['description'] }}</span>
                        </span>
                        <i class="bi bi-arrow-right-short academic-launchpad-arrow"></i>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
