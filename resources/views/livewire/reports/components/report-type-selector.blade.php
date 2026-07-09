<div>
    <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-slate-100">Report Type</h3>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($reportTypes as $key => $type)
            <div wire:click="selectReportType('{{ $key }}')"
                class="flex cursor-pointer flex-col rounded-lg border p-4 transition-all duration-150
                {{ $reportType == $key ? 'border-blue-500 bg-blue-50 shadow-sm dark:border-cyan-400 dark:bg-cyan-950/55 dark:shadow-cyan-950/25' : 'border-gray-200 hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-950/20 dark:hover:border-cyan-500/70 dark:hover:bg-cyan-950/35' }}">

                <div class="flex items-center mb-2">
                    <div
                        class="mr-2 flex h-8 w-8 items-center justify-center rounded-full
                        {{ $reportType == $key ? 'bg-blue-500 text-white dark:bg-cyan-500 dark:text-slate-950' : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-slate-300' }}">
                        <x-mary-icon :name="$type['icon']" class="w-5 h-5" />
                    </div>
                    <h4 class="font-medium text-gray-900 dark:text-slate-100">{{ $type['label'] }}</h4>
                </div>

                <p class="text-sm text-gray-500 dark:text-slate-300">{{ $type['hint'] }}</p>
            </div>
        @endforeach
    </div>
</div>
