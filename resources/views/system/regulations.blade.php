@extends('layouts.app')
@section('title', 'Quy định & Định mức')

@section('content')
<x-page-header
    title="Quy định & Định mức"
    subtitle="Trung tâm cấu hình các ngưỡng chuyên cần, định mức học lực và thời hạn xử lý dữ liệu học vụ."
/>

<section class="w-full bg-white border border-orange-100 rounded-xl p-5 shadow-2xs text-left font-sans">
    <div class="w-full text-left items-start flex flex-col gap-1 mb-4 px-1">
        <h2 class="text-xl font-semibold text-gray-900 !text-left">Cấu hình nghiệp vụ trọng yếu</h2>
        <p class="text-sm font-normal text-gray-400 mt-1 !text-left">
            Các mục bên dưới trỏ trực tiếp đến những màn hình cấu hình đang vận hành trong hệ thống.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
        @forelse($cards as $card)
            <a
                href="{{ $card['href'] }}"
                class="block bg-orange-50/30 border border-orange-100 rounded-lg p-4 text-left hover:bg-orange-50/70 hover:border-orange-200 transition-all"
            >
                <div class="flex items-start gap-3 text-left">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white border border-orange-100 text-orange-600">
                        <i class="bi {{ $card['icon'] }}"></i>
                    </span>
                    <div class="min-w-0 text-left">
                        <h3 class="text-sm font-semibold text-gray-900 text-left truncate">{{ $card['title'] }}</h3>
                        <p class="text-xs md:text-sm font-normal text-gray-500 text-left mt-1 leading-5">
                            {{ $card['description'] }}
                        </p>
                        <span class="inline-flex items-center gap-1 text-xs font-normal text-orange-700 bg-orange-50 border border-orange-200 px-2.5 py-1 rounded-md mt-3">
                            {{ $card['action'] }}
                            <i class="bi bi-arrow-right-short"></i>
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full bg-gray-50 border border-gray-200 rounded-lg p-4 text-left">
                <p class="text-sm font-normal text-gray-500 mb-0">Chưa có cấu hình nào khả dụng với quyền hiện tại.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
