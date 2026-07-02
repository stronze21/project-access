@props([
    'status',
])

@php
    $steps = [
        ['key' => \App\Models\Complaint::STATUS_RECEIVED, 'label' => 'Received', 'icon' => 'inbox'],
        ['key' => \App\Models\Complaint::STATUS_ASSIGNED, 'label' => 'Assigned', 'icon' => 'user-check'],
        ['key' => \App\Models\Complaint::STATUS_IN_PROGRESS, 'label' => 'In Progress', 'icon' => 'loader'],
        ['key' => \App\Models\Complaint::STATUS_RESOLVED, 'label' => 'Resolved', 'icon' => 'check-circle'],
        ['key' => \App\Models\Complaint::STATUS_CLOSED, 'label' => 'Closed', 'icon' => 'lock'],
    ];

    $statusIndex = 0;
    foreach ($steps as $idx => $step) {
        if ($step['key'] === $status) {
            $statusIndex = $idx;
            break;
        }
    }
@endphp

<div class="space-y-2">
    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Progress</p>
    <div class="overflow-x-auto pb-1">
        <div class="flex min-w-max items-start gap-1.5 sm:gap-2">
            @foreach ($steps as $index => $step)
                @php
                    $isDone = $index <= $statusIndex;
                    $iconClass = $isDone
                        ? 'border-blue-200 bg-blue-50 text-blue-700'
                        : 'border-slate-200 bg-slate-50 text-slate-400';
                    $textClass = $isDone ? 'text-blue-700' : 'text-slate-500';
                    $lineClass = $isDone ? 'bg-blue-300' : 'bg-slate-200';
                @endphp

                <div class="flex items-start">
                    <div class="flex min-w-[72px] flex-col items-center">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border {{ $iconClass }}">
                            @if ($step['icon'] === 'inbox')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M22 12h-4l-3 4h-6l-3-4H2"></path>
                                    <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"></path>
                                </svg>
                            @elseif ($step['icon'] === 'user-check')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m16 11 2 2 4-4"></path>
                                    <path d="M12 20H3a1 1 0 0 1-1-1 6 6 0 0 1 12 0"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                </svg>
                            @elseif ($step['icon'] === 'loader')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 2v4"></path>
                                    <path d="m16.2 7.8 2.9-2.9"></path>
                                    <path d="M18 12h4"></path>
                                    <path d="m16.2 16.2 2.9 2.9"></path>
                                    <path d="M12 18v4"></path>
                                    <path d="m4.9 19.1 2.9-2.9"></path>
                                    <path d="M2 12h4"></path>
                                    <path d="m4.9 4.9 2.9 2.9"></path>
                                </svg>
                            @elseif ($step['icon'] === 'check-circle')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 12 11 14 15 10"></path>
                                    <circle cx="12" cy="12" r="10"></circle>
                                </svg>
                            @else
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            @endif
                        </span>
                        <span class="mt-1.5 text-center text-[10px] font-semibold leading-tight {{ $textClass }}">
                            {{ $step['label'] }}
                        </span>
                    </div>

                    @if (!$loop->last)
                        <span class="mt-[18px] h-0.5 w-7 rounded {{ $lineClass }} sm:w-10"></span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
