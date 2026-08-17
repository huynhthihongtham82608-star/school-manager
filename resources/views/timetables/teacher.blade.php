@extends('layouts.app')
@section('title', 'Thời khóa biểu giảng dạy')

@section('content')
<style>
    .teacher-timetable-page {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        text-align: left;
    }

    .teacher-timetable-shell {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .teacher-timetable-section-title {
        padding: .7rem .85rem;
        border-bottom: 1px solid #ffedd5;
        background: rgba(255, 247, 237, .45);
        color: #9a3412;
        font-size: .9rem;
        font-weight: 400;
        text-align: left;
    }

    .teacher-timetable-grid {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        overflow: hidden;
    }

    .teacher-timetable-grid th,
    .teacher-timetable-grid td {
        border-right: 1px solid rgba(255, 237, 213, .76);
        border-bottom: 1px solid rgba(255, 237, 213, .76);
        padding: .55rem;
        color: #374151;
        font-size: .75rem;
        font-weight: 400;
        text-align: left;
        vertical-align: top;
        overflow: visible;
    }

    .teacher-timetable-grid th {
        background: rgba(255, 247, 237, .58);
        color: #111827;
    }

    .teacher-timetable-grid th:first-child,
    .teacher-timetable-grid td:first-child {
        width: 72px;
        background: rgba(255, 247, 237, .36);
        color: #9a3412;
    }

    .teacher-timetable-grid th:last-child,
    .teacher-timetable-grid td:last-child {
        border-right: 0;
    }

    .teacher-timetable-grid tbody tr:last-child td {
        border-bottom: 0;
    }

    .teacher-timetable-cell {
        min-height: 76px;
        width: 100%;
        max-width: 100%;
        overflow: visible;
        border-radius: 8px;
        background: rgba(249, 250, 251, .62);
    }

    .teacher-timetable-slot {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow: visible;
        line-height: 1.35;
    }

    .teacher-timetable-slot + .teacher-timetable-slot {
        margin-top: .35rem;
    }

    .teacher-timetable-slot-title,
    .teacher-timetable-slot-room {
        display: block;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        overflow: visible;
        text-overflow: clip;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: normal;
        font-weight: 400;
    }
</style>

<div class="teacher-timetable-page w-full max-w-full overflow-hidden">
    <div class="page-heading w-full !text-left !items-start flex flex-col justify-start text-left items-start gap-1 mb-4 px-1" style="width: 100% !important; text-align: left !important; align-items: flex-start !important; justify-content: flex-start !important; margin-left: 0 !important; margin-right: auto !important;">
        <h5 class="text-xl font-semibold text-gray-900 !text-left" style="text-align: left !important;">Thời khóa biểu giảng dạy</h5>
    </div>

    <div class="d-grid gap-3 w-full max-w-full overflow-hidden">
        @foreach(($teachingPeriodGroups ?? ['morning' => ['periods' => ($teachingPeriods ?? [])]]) as $periodGroupKey => $periodGroup)
            <div class="teacher-timetable-shell">
                <div class="teacher-timetable-section-title">{{ $periodGroupKey === 'afternoon' ? 'Buổi Chiều' : 'Buổi Sáng' }}</div>
                <div class="w-full max-w-full overflow-hidden">
                    <table class="teacher-timetable-grid w-full table-fixed max-w-full overflow-hidden" data-admin-table-skip>
                        <thead>
                            <tr>
                                <th>Tiết</th>
                                @foreach($teachingDays ?? [] as $dayNumber => $dayLabel)
                                    <th>Thứ {{ ((int) $dayNumber) + 1 }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(($periodGroup['periods'] ?? []) as $periodNumber => $periodLabel)
                                <tr>
                                    <td>{{ $periodLabel }}</td>
                                    @foreach($teachingDays ?? [] as $dayNumber => $dayLabel)
                                        @php
                                            $slotEntries = ($teachingSchedule ?? collect())->get($dayNumber . '-' . $periodNumber, collect());
                                        @endphp
                                        <td>
                                            <div class="teacher-timetable-cell">
                                                @foreach($slotEntries as $entry)
                                                    @php
                                                        $subjectName = $entry->subject?->name ?? $entry->assignment?->subject?->name ?? 'Môn học';
                                                        $className = $entry->timetable?->classRoom?->name ?? '-';
                                                        $roomName = $entry->roomInfo?->name ?? $entry->room ?? '-';
                                                        $title = $subjectName . ' • ' . $className;
                                                        $roomLabel = 'Phòng: ' . $roomName;
                                                        $marker = $entry->displaySubstituteMarker();
                                                        $title .= $marker ? ' ' . $marker : '';
                                                    @endphp
                                                    <div class="teacher-timetable-slot bg-orange-50/40 border border-orange-100/60 rounded-lg p-2 flex flex-col gap-1">
                                                        <span class="teacher-timetable-slot-title w-full font-normal text-xs md:text-sm text-gray-700 block text-left" title="{{ $title }}">{{ $title }}</span>
                                                        <span class="teacher-timetable-slot-room w-full font-normal text-xs text-gray-500 block text-left" title="{{ $roomLabel }}">{{ $roomLabel }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
