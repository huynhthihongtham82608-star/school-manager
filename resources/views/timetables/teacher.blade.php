@extends('layouts.app')
@section('title', 'Thời khóa biểu giảng dạy')

@section('content')
<style>
    .teacher-timetable-page {
        width: 100%;
        text-align: left;
    }

    .teacher-timetable-shell {
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
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
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .teacher-timetable-grid th,
    .teacher-timetable-grid td {
        border-right: 1px solid rgba(255, 237, 213, .76);
        border-bottom: 1px solid rgba(255, 237, 213, .76);
        padding: .65rem;
        color: #374151;
        font-size: .75rem;
        font-weight: 400;
        text-align: left;
        vertical-align: top;
    }

    @media (min-width: 768px) {
        .teacher-timetable-grid th,
        .teacher-timetable-grid td {
            font-size: .875rem;
        }
    }

    .teacher-timetable-grid th {
        background: rgba(255, 247, 237, .58);
        color: #111827;
    }

    .teacher-timetable-grid th:first-child,
    .teacher-timetable-grid td:first-child {
        width: 86px;
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
        border-radius: 8px;
        background: rgba(249, 250, 251, .62);
    }

    .teacher-timetable-slot {
        min-width: 0;
        max-width: 100%;
        line-height: 1.35;
    }

    .teacher-timetable-slot + .teacher-timetable-slot {
        margin-top: .4rem;
    }

    .teacher-timetable-slot-title,
    .teacher-timetable-slot-room {
        display: block;
        min-width: 0;
        max-width: 100%;
        font-weight: 400;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .teacher-timetable-slot-title {
        color: #374151;
        font-size: .78rem;
    }

    .teacher-timetable-slot-room {
        color: #9ca3af;
        font-size: .7rem;
    }

    @media (min-width: 768px) {
        .teacher-timetable-slot-title {
            font-size: .875rem;
        }

        .teacher-timetable-slot-room {
            font-size: .78rem;
        }
    }
</style>

<div class="teacher-timetable-page">
    <div class="page-heading mb-4">
        <div>
            <h5 class="text-xl font-normal text-gray-900 mb-1">Thời khóa biểu giảng dạy</h5>
        </div>
    </div>

    <div class="d-grid gap-3">
        @foreach(($teachingPeriodGroups ?? ['morning' => ['periods' => ($teachingPeriods ?? [])]]) as $periodGroupKey => $periodGroup)
            <div class="teacher-timetable-shell">
                <div class="teacher-timetable-section-title">{{ $periodGroupKey === 'afternoon' ? 'Buổi Chiều' : 'Buổi Sáng' }}</div>
                <div class="table-responsive">
                    <table class="teacher-timetable-grid" data-admin-table-skip>
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
                                                        $title = $subjectName . ' • Lớp ' . $className;
                                                        $roomLabel = 'Phòng: ' . $roomName;
                                                    @endphp
                                                    <div class="teacher-timetable-slot bg-orange-50/40 border border-orange-100/60 rounded-lg p-2 flex flex-col gap-1">
                                                        <span class="teacher-timetable-slot-title w-full h-full min-w-0 font-normal text-xs md:text-sm text-gray-700 block text-left" title="{{ $title }}">{{ $title }}</span>
                                                        <span class="teacher-timetable-slot-room w-full min-w-0 font-normal text-xs text-gray-500 block text-left" title="{{ $roomLabel }}">{{ $roomLabel }}</span>
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
