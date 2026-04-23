<div>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold text-gray-900">Report Summary</h2>
        <button class="text-gray-400 hover:text-gray-600" wire:click="toggleSummary">
            @if ($showSummary)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            @endif
        </button>
    </div>

    @if ($showSummary)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($summaryCards as $card)
                <x-mary-stat title="{{ $card['title'] }}"
                    value="{{ $this->formatValue($summaryData[$card['key']] ?? null, $card['formatter']) }}"
                    icon="{{ $card['icon'] }}" class="{{ 'bg-' . $card['color'] }}" color="text-base-100" />
            @endforeach
        </div>

        @if (count($additionalMetrics) > 0)
            <div class="mt-6">
                @foreach ($additionalMetrics as $section)
                    <div class="mb-4">
                        <h3 class="mb-2 text-lg font-medium text-gray-700">{{ $section['title'] }}</h3>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            @foreach ($section['metrics'] as $metric)
                                <div class="p-4 bg-white rounded-lg shadow">
                                    <div class="flex items-center">
                                        <div
                                            class="flex items-center justify-center w-10 h-10 mr-4 text-{{ $metric['color'] }}-500 bg-{{ $metric['color'] }}-100 rounded-full">
                                            <x-mary-icon name="{{ $metric['icon'] }}" class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">{{ $metric['label'] }}</p>
                                            <p class="text-lg font-semibold">
                                                @if (isset($metric['key']))
                                                    {{ $this->formatValue($summaryData[$metric['key']] ?? null, 'number') }}
                                                @else
                                                    {{ $metric['value'] }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
