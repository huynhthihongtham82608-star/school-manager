@extends('layouts.app')
@section('title', html_entity_decode('H&#7891; s&#417; l&#7899;p ch&#7911; nhi&#7879;m'))

@section('content')
<style>
    .homeroom-profile-modal {
        max-width: min(1024px, calc(100vw - 2rem));
    }

    .homeroom-profile-table th,
    .homeroom-profile-table td {
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #374151;
        font-size: .75rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (min-width: 768px) {
        .homeroom-profile-table th,
        .homeroom-profile-table td {
            font-size: .875rem;
        }
    }

    .homeroom-profile-table th {
        background: rgba(255, 247, 237, .42);
        color: #111827;
    }

    .homeroom-profile-card {
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .homeroom-detail-box {
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: rgba(255, 247, 237, .18);
        padding: .85rem;
        text-align: left;
    }

    .homeroom-detail-title {
        display: flex;
        align-items: center;
        gap: .4rem;
        margin-bottom: .65rem;
        color: #111827;
        font-size: .78rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .homeroom-detail-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .45rem .85rem;
        color: #374151;
        font-size: .78rem;
        font-weight: 400;
        text-align: left;
    }

    .homeroom-detail-list .full {
        grid-column: 1 / -1;
    }

    .homeroom-detail-list span {
        display: block;
        color: #6b7280;
        font-weight: 400;
    }

    .homeroom-detail-list strong {
        display: block;
        min-width: 0;
        color: #111827;
        font-weight: 400;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>

<div class="page-heading w-full !text-left !items-start flex flex-col justify-start text-left items-start gap-1 mb-4 px-1" style="width: 100% !important; text-align: left !important; align-items: flex-start !important; justify-content: flex-start !important; margin-left: 0 !important; margin-right: auto !important;">
    <div class="w-full !text-left !items-start flex flex-col justify-start text-left items-start gap-1 mb-4 px-1" style="width: 100% !important; text-align: left !important; align-items: flex-start !important; justify-content: flex-start !important; margin-left: 0 !important; margin-right: auto !important;">
        <h5 class="text-xl font-semibold text-gray-900 !text-left" style="text-align: left !important;">H&#7891; s&#417; s&#417; y&#7871;u l&#253; l&#7883;ch L&#7899;p {{ $homeroomClass?->name ?? 'ch&#7911; nhi&#7879;m' }}</h5>
        <div class="text-sm font-normal text-gray-400 mt-1 !text-left" style="text-align: left !important;">
            {{ $homeroomClass?->schoolYear?->name ?? '-' }} &bull; {{ $homeroomClass?->semester?->name ?? '-' }} &bull; S&#297; s&#7889;: {{ $homeroomClass?->students->count() ?? 0 }} h&#7885;c sinh
        </div>
    </div>
</div>

@if(! $homeroomClass)
    <div class="bg-orange-50 border border-orange-200 text-orange-800 p-4 rounded-lg text-sm text-left font-normal">
        B&#7841;n ch&#432;a &#273;&#432;&#7907;c ph&#226;n c&#244;ng l&#224;m Gi&#225;o vi&#234;n ch&#7911; nhi&#7879;m cho l&#7899;p h&#7885;c n&#224;o trong h&#7879; th&#7889;ng.
    </div>
@else
    <div class="homeroom-profile-card overflow-hidden">
        <div class="p-3 bg-orange-50/20 border-b border-orange-100 flex items-center justify-between">
            <h6 class="text-sm font-normal text-gray-900 mb-0">Danh s&#225;ch h&#7885;c sinh L&#7899;p {{ $homeroomClass->name }}</h6>
            <div class="text-xs text-gray-500 font-normal">GVCN: {{ auth()->user()->display_name }}</div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle w-full table-fixed max-w-full overflow-hidden mb-0 homeroom-profile-table" data-admin-table-skip>
                <thead>
                    <tr>
                        <th class="w-32">M&#227; HS</th>
                        <th>H&#7885; v&#224; T&#234;n</th>
                        <th class="w-28">Gi&#7899;i t&#237;nh</th>
                        <th class="w-32">Ng&#224;y sinh</th>
                        <th class="w-40">S&#272;T Ph&#7909; huynh</th>
                        <th class="text-end w-20">H&#7891; s&#417;</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-orange-100/60">
                @forelse($homeroomClass->students as $student)
                    <tr class="hover:bg-orange-50/20 transition-colors">
                        <td class="text-gray-900" title="{{ $student->student_code }}">{{ $student->student_code }}</td>
                        <td title="{{ $student->name }}">{{ $student->name }}</td>
                        <td>
                            @if($student->gender === \App\Models\Student::GENDER_NU)
                                N&#7919;
                            @elseif($student->gender === \App\Models\Student::GENDER_NAM)
                                Nam
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $student->dob?->format('d/m/Y') ?? '-' }}</td>
                        <td title="{{ $student->parent_phone ?: '-' }}">{{ $student->parent_phone ?: '-' }}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center p-1.5 text-gray-500 hover:text-orange-600 bg-white border border-gray-200 hover:border-orange-300 rounded-md shadow-xs transition-colors cursor-pointer"
                                data-bs-toggle="modal"
                                data-bs-target="#studentProfile{{ $student->id }}"
                                title="Xem h&#7891; s&#417; h&#7885;c sinh"
                                aria-label="Xem h&#7891; s&#417; h&#7885;c sinh"
                            >
                                <i class="bi bi-eye text-base"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-4 text-left text-gray-500 font-normal">L&#7899;p ch&#7911; nhi&#7879;m ch&#432;a c&#243; h&#7885;c sinh n&#224;o.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach($homeroomClass->students as $student)
        <div class="modal fade content-modal" id="studentProfile{{ $student->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered homeroom-profile-modal">
                <div class="modal-content max-w-4xl bg-white shadow-2xl rounded-xl z-50 animate-fade-in-up border-0 overflow-hidden">
                    <div class="modal-header bg-orange-50/60 border-b border-orange-100 px-4 py-3">
                        <div>
                            <div class="text-xs text-orange-700 font-normal">H&#7891; s&#417; h&#7885;c sinh</div>
                            <h5 class="modal-title text-base font-normal text-gray-900">Xem chi ti&#7871;t - {{ $student->name }}</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="&#272;&#243;ng"></button>
                    </div>
                    <div class="modal-body p-4 text-left font-normal text-sm">
                        <div class="student-v2-shell">
                            <div class="student-v2-card-header">
                                <div class="student-v2-avatar">
                                    @if($student->avatar)
                                        <img src="{{ asset('storage/'.$student->avatar) }}" alt="{{ $student->name }}">
                                    @else
                                        <i class="bi bi-person-fill"></i>
                                    @endif
                                </div>
                                <div class="student-v2-identity">
                                    <div class="student-v2-kicker">Th&#7867; h&#7885;c sinh</div>
                                    <h5>{{ $student->name }}</h5>
                                    <div class="student-v2-code">{{ $student->student_code }}</div>
                                </div>
                                <span class="student-v2-status badge {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span>
                            </div>

                            <div class="student-compact-detail-grid">
                                <section class="student-compact-box">
                                    <div class="student-compact-title">
                                        <i class="bi bi-mortarboard"></i>
                                        <h6>Th&#244;ng tin h&#7885;c t&#7853;p</h6>
                                    </div>
                                    <div class="student-compact-list">
                                        <div><span>Ni&#234;n kh&#243;a</span><strong>{{ $student->cohortLabel() }}</strong></div>
                                        <div><span>L&#7899;p</span><strong>{{ $student->classRoom->name ?? $homeroomClass->name }}</strong></div>
                                        <div><span>Lo&#7841;i nh&#7853;p h&#7885;c</span><strong>{{ $student->admissionTypeLabel() }}</strong></div>
                                        <div><span>Ng&#224;y nh&#7853;p h&#7885;c</span><strong>{{ $student->enrollment_date?->format('d/m/Y') ?? '-' }}</strong></div>
                                    </div>
                                </section>

                                <section class="student-compact-box wide">
                                    <div class="student-compact-title">
                                        <i class="bi bi-person-lines-fill"></i>
                                        <h6>Th&#244;ng tin c&#225; nh&#226;n &amp; li&#234;n h&#7879;</h6>
                                    </div>
                                    <div class="student-compact-list two">
                                        <div><span>S&#272;T ph&#7909; huynh</span><strong>{{ $student->parent_phone ?: '-' }}</strong></div>
                                        <div><span>Ng&#224;y sinh</span><strong>{{ $student->dob?->format('d/m/Y') ?? '-' }}</strong></div>
                                        <div><span>Gi&#7899;i t&#237;nh</span><strong>{{ $student->genderLabel() }}</strong></div>
                                        <div><span>N&#417;i sinh</span><strong>{{ $student->place_of_birth ?: '-' }}</strong></div>
                                        <div><span>D&#226;n t&#7897;c</span><strong>{{ $student->ethnicity ?: '-' }}</strong></div>
                                        <div><span>T&#244;n gi&#225;o</span><strong>{{ $student->religion ?: '-' }}</strong></div>
                                        <div class="full"><span>&#272;&#7883;a ch&#7881;</span><strong>{{ $student->address ?: '-' }}</strong></div>
                                        <div class="full"><span>Ghi ch&#250;</span><strong>{{ $student->note ?: '-' }}</strong></div>
                                    </div>
                                </section>

                                <section class="student-compact-box full">
                                    <div class="student-compact-title">
                                        <i class="bi bi-people"></i>
                                        <h6>Ph&#7909; huynh / ng&#432;&#7901;i gi&#225;m h&#7897;</h6>
                                    </div>
                                    <div class="student-compact-list two">
                                        @forelse($student->parents as $parent)
                                            <div>
                                                <span>{{ $parent->pivot?->relation ?: 'Ph&#7909; huynh' }}</span>
                                                <strong>{{ $parent->name }}{{ $parent->phone ? ' &bull; ' . $parent->phone : '' }}</strong>
                                            </div>
                                            <div>
                                                <span>Email</span>
                                                <strong>{{ $parent->email ?: '-' }}</strong>
                                            </div>
                                        @empty
                                            <div class="full"><span>Li&#234;n k&#7871;t</span><strong>Ch&#432;a li&#234;n k&#7871;t h&#7891; s&#417; ph&#7909; huynh.</strong></div>
                                        @endforelse
                                    </div>
                                </section>

                                @if($student->admission_type === \App\Models\Student::ADMISSION_TRANSFER)
                                    <section class="student-compact-box full">
                                        <div class="student-compact-title">
                                            <i class="bi bi-arrow-left-right"></i>
                                            <h6>Chuy&#7875;n tr&#432;&#7901;ng</h6>
                                        </div>
                                        <div class="student-compact-list two">
                                            <div><span>Tr&#432;&#7901;ng c&#361;</span><strong>{{ $student->previous_school ?: '-' }}</strong></div>
                                            <div><span>Kh&#7889;i hi&#7879;n t&#7841;i</span><strong>{{ $student->transfer_grade_level ? 'Kh&#7889;i '.$student->transfer_grade_level : '-' }}</strong></div>
                                        </div>
                                    </section>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-gray-50 border-t border-gray-200 px-4 py-2 flex justify-end">
                        <button type="button" class="btn btn-secondary text-xs px-3 py-1.5" data-bs-dismiss="modal">&#272;&#243;ng h&#7891; s&#417;</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
