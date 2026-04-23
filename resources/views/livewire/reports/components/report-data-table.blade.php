<div>
    <x-mary-card>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2">
                <x-mary-input wire:model.live.debounce.300ms="searchTerm" placeholder="Search..." label="Search" />
                <x-mary-select label="Items per page" :options="[
                    ['key' => '10', 'id' => '10 per page'],
                    ['key' => '25', 'id' => '25 per page'],
                    ['key' => '50', 'id' => '50 per page'],
                    ['key' => '100', 'id' => '100 per page'],
                ]" option-value="key" option-label="id"
                    placeholder="Select items per page" placeholder-value="" wire:model.live="perPage" />
            </div>

            <div class="text-sm text-gray-500">
                Showing {{ count($reportData) }} of {{ $totalItems }} items
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        @foreach ($columns as $column)
                            <th scope="col" class="px-4 py-3">
                                @if ($column['sortable'])
                                    <button wire:click="sortBy('{{ $column['key'] }}')"
                                        class="flex items-center hover:text-gray-900">
                                        {{ $column['label'] }}

                                        @if ($sortField === $column['key'])
                                            @if ($sortDirection === 'asc')
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            @endif
                                        @endif
                                    </button>
                                @else
                                    {{ $column['label'] }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @if ($reportType === 'distributions')
                        @foreach ($reportData as $distribution)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ is_object($distribution) ? $distribution->reference_number : $distribution['reference_number'] ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_object($distribution) && isset($distribution->resident))
                                        {{ $distribution->resident->full_name }}
                                    @elseif(isset($distribution['resident']['first_name']))
                                        @php
                                            $fullName = [
                                                $distribution['resident']['last_name'] . ',',
                                                $distribution['resident']['first_name'],
                                                $distribution['resident']['suffix'],
                                                $distribution['resident']['middle_name']
                                                    ? substr($distribution['resident']['middle_name'], 0, 1) . '.'
                                                    : null,
                                            ];
                                        @endphp
                                        {{ implode(' ', array_filter($fullName)) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_object($distribution) && isset($distribution->household))
                                        {{ $distribution->household ? $distribution->household->household_id : 'N/A' }}
                                    @elseif(isset($distribution['household']['household_id']))
                                        {{ $distribution['household']['household_id'] }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_object($distribution) && isset($distribution->household))
                                        {{ $distribution->household ? $distribution->household->barangay : 'N/A' }}
                                    @elseif(isset($distribution['household']['barangay']))
                                        {{ $distribution['household']['barangay'] }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_object($distribution) && isset($distribution->ayudaProgram))
                                        {{ $distribution->ayudaProgram->name }}
                                    @elseif(isset($distribution['ayuda_program']['name']))
                                        {{ $distribution['ayuda_program']['name'] }}
                                    @elseif(isset($distribution['program_name']))
                                        {{ $distribution['program_name'] }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_object($distribution) && $distribution->created_at)
                                        {{ $distribution->created_at->timezone('Asia/Manila')->format('M d, Y g:i A') }}
                                    @elseif(isset($distribution['created_at']))
                                        {{ \Carbon\Carbon::parse($distribution['created_at'])->timezone('Asia/Manila')->format('M d, Y g:i A') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    ₱{{ number_format(is_object($distribution) ? $distribution->amount : $distribution['amount'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ is_object($distribution) ? $distribution->ayudaProgram->goods_description : $distribution['ayuda_program']['goods_description'] ?? '' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ is_object($distribution) ? $distribution->ayudaProgram->services_description : $distribution['ayuda_program']['services_description'] ?? '' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ ucfirst(is_object($distribution) ? $distribution->status : $distribution['status'] ?? 'Unknown') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @elseif($reportType === 'programs')
                        @foreach ($reportData as $program)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ is_object($program) ? $program->name : $program['name'] ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ number_format(is_object($program) ? $program->distributions_count : $program['distributions_count'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3">
                                    ₱{{ number_format(is_object($program) ? $program->total_distributed : $program['total_distributed'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ number_format(is_object($program) ? $program->unique_beneficiaries : $program['unique_beneficiaries'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ number_format(is_object($program) ? $program->unique_households : $program['unique_households'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $utilization = is_object($program)
                                            ? $program->utilization
                                            : $program['utilization'] ?? null;
                                    @endphp
                                    @if ($utilization !== null)
                                        <div class="flex items-center">
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-blue-600 h-2.5 rounded-full"
                                                    style="width: {{ min($utilization, 100) }}%"></div>
                                            </div>
                                            <span class="ml-2">{{ $utilization }}%</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @elseif($reportType === 'residents')
                        @foreach ($reportData as $resident)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ is_object($resident) ? $resident->full_name : $resident['full_name'] ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_object($resident) && isset($resident->household))
                                        {{ $resident->household ? $resident->household->household_id : 'N/A' }}
                                    @elseif(isset($resident['household']['household_id']))
                                        {{ $resident['household']['household_id'] }}
                                    @elseif(isset($resident['household_id']))
                                        {{ $resident['household_id'] }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    {{ number_format(is_object($resident) ? $resident->distributions_count : $resident['distributions_count'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3">
                                    ₱{{ number_format(is_object($resident) ? $resident->total_received : $resident['total_received'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="max-w-xs truncate"
                                        title="{{ is_object($resident) ? $resident->programs_list : $resident['programs_list'] ?? 'N/A' }}">
                                        {{ is_object($resident) ? $resident->programs_list : $resident['programs_list'] ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @php
                                            $isSenior = is_object($resident)
                                                ? $resident->is_senior_citizen ?? false
                                                : $resident['is_senior_citizen'] ?? false;
                                            $isIndigenous = is_object($resident)
                                                ? $resident->is_indigenous ?? false
                                                : $resident['is_indigenous'] ?? false;
                                        @endphp

                                        @if ($isSenior)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">Senior</span>
                                        @endif
                                        @if ($isIndigenous)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Indigenous</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @elseif($reportType === 'barangays')
                        @foreach ($reportData as $barangayData)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ is_object($barangayData) ? $barangayData->barangay : $barangayData['barangay'] ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ number_format(is_object($barangayData) ? $barangayData->distributions_count : $barangayData['distributions_count'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3">
                                    ₱{{ number_format(is_object($barangayData) ? $barangayData->total_amount : $barangayData['total_amount'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ number_format(is_object($barangayData) ? $barangayData->unique_beneficiaries : $barangayData['unique_beneficiaries'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ number_format(is_object($barangayData) ? $barangayData->unique_households : $barangayData['unique_households'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ number_format(is_object($barangayData) ? $barangayData->total_households : $barangayData['total_households'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $coverage = is_object($barangayData)
                                            ? $barangayData->coverage_percentage
                                            : $barangayData['coverage_percentage'] ?? 0;
                                    @endphp
                                    <div class="flex items-center">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-blue-600 h-2.5 rounded-full"
                                                style="width: {{ min($coverage, 100) }}%">
                                            </div>
                                        </div>
                                        <span class="ml-2">{{ $coverage }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @elseif($reportType === 'residents-with-id')
                        @foreach ($reportData as $resident)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    @if (is_object($resident))
                                        {{ $resident->full_name ?? 'N/A' }}
                                    @elseif(isset($resident['last_name']))
                                        @php
                                            $fullName = [
                                                $resident['last_name'] . ',',
                                                $resident['first_name'],
                                                $resident['suffix'] ?? '',
                                                isset($resident['middle_name']) && $resident['middle_name']
                                                    ? substr($resident['middle_name'], 0, 1) . '.'
                                                    : null,
                                            ];
                                        @endphp
                                        {{ implode(' ', array_filter($fullName)) }}
                                    @else
                                        {{ $resident['full_name'] ?? 'N/A' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_object($resident) && isset($resident->household))
                                        {{ $resident->household->address ?? 'N/A' }}
                                    @elseif(isset($resident['household']['address']))
                                        {{ $resident['household']['address'] }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_object($resident) && isset($resident->household))
                                        {{ $resident->household->city_municipality ?? 'N/A' }}
                                    @elseif(isset($resident['household']['city_municipality']))
                                        {{ $resident['household']['city_municipality'] }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_object($resident) && isset($resident->household))
                                        {{ $resident->household->province ?? 'N/A' }}
                                    @elseif(isset($resident['household']['province']))
                                        {{ $resident['household']['province'] }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_object($resident) && $resident->updated_at)
                                        {{ $resident->updated_at->timezone('Asia/Manila')->format('M d, Y g:i A') }}
                                    @elseif(isset($resident['updated_at']))
                                        {{ \Carbon\Carbon::parse($resident['updated_at'])->timezone('Asia/Manila')->format('M d, Y g:i A') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    @if (count($reportData) === 0)
                        <tr class="bg-white border-b">
                            <td colspan="{{ count($columns) }}" class="px-4 py-4 text-center text-gray-500">
                                No data found for the selected filters
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($totalPages > 1)
            <div class="flex items-center justify-between mt-4">
                <div class="text-sm text-gray-700">
                    Showing {{ ($currentPage - 1) * $perPage + 1 }} to
                    {{ min($currentPage * $perPage, $totalItems) }} of {{ $totalItems }} entries
                </div>

                <div class="flex space-x-1">

                    <button wire:click="setPage({{ max(1, $currentPage - 1) }})"
                        class="px-3 py-1 rounded {{ $currentPage == 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50' }}"
                        {{ $currentPage == 1 ? 'disabled' : '' }}>
                        Previous
                    </button>

                    @php
                        $startPage = max(1, min($currentPage - 2, $totalPages - 4));
                        $endPage = min($totalPages, max(5, $currentPage + 2));
                    @endphp

                    @if ($startPage > 1)
                        <button wire:click="setPage(1)"
                            class="px-3 py-1 text-gray-700 bg-white rounded hover:bg-gray-50">
                            1
                        </button>

                        @if ($startPage > 2)
                            <span class="px-3 py-1">...</span>
                        @endif
                    @endif

                    @for ($i = $startPage; $i <= $endPage; $i++)
                        <button wire:click="setPage({{ $i }})"
                            class="px-3 py-1 rounded {{ $currentPage == $i ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                            {{ $i }}
                        </button>
                    @endfor

                    @if ($endPage < $totalPages)
                        @if ($endPage < $totalPages - 1)
                            <span class="px-3 py-1">...</span>
                        @endif

                        <button wire:click="setPage({{ $totalPages }})"
                            class="px-3 py-1 text-gray-700 bg-white rounded hover:bg-gray-50">
                            {{ $totalPages }}
                        </button>
                    @endif

                    <button wire:click="setPage({{ min($totalPages, $currentPage + 1) }})"
                        class="px-3 py-1 rounded {{ $currentPage == $totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50' }}"
                        {{ $currentPage == $totalPages ? 'disabled' : '' }}>
                        Next
                    </button>
                </div>
            </div>
        @endif
    </x-mary-card>
</div>
