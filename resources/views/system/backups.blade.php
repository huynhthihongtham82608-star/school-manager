@extends('layouts.app')
@section('title', 'Sao lÆ°u & KhÃ´i phá»¥c dá»¯ liá»‡u')

@section('content')
<x-page-header
    title="Sao lÆ°u & KhÃ´i phá»¥c dá»¯ liá»‡u"
    subtitle="Táº¡o vÃ  táº£i báº£n sao lÆ°u database. Chá»©c nÄƒng khÃ´i phá»¥c Ä‘ang Ä‘Æ°á»£c khÃ³a Ä‘á»ƒ báº£o vá»‡ dá»¯ liá»‡u."
>
    <form method="POST" action="{{ route('system.backups.store') }}">
        @csrf
        <button class="btn btn-primary" onclick="return confirm('Táº¡o báº£n sao lÆ°u database hiá»‡n táº¡i?')">
            <i class="bi bi-database-add me-2"></i>Táº¡o báº£n sao lÆ°u
        </button>
    </form>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>TÃªn tá»‡p</th>
                    <th>Dung lÆ°á»£ng</th>
                    <th>Thá»i gian táº¡o</th>
                    <th class="text-end">Thao tÃ¡c</th>
                </tr>
            </thead>
            <tbody>
                @forelse($files as $file)
                    <tr>
                        <td class="fw-semibold">{{ $file['name'] }}</td>
                        <td>{{ number_format($file['size'] / 1024, 1) }} KB</td>
                        <td>{{ \Carbon\Carbon::createFromTimestamp($file['updated_at'])->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a class="content-action-btn icon-only view" href="{{ route('system.backups.download', $file['name']) }}" title="Táº£i xuá»‘ng" data-bs-toggle="tooltip">
                                <i class="bi bi-download"></i>
                            </a>
                            <button type="button" class="content-action-btn icon-only" disabled title="KhÃ´i phá»¥c Ä‘ang khÃ³a" data-bs-toggle="tooltip">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state"><i class="bi bi-database"></i>ChÆ°a cÃ³ báº£n sao lÆ°u nÃ o.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
