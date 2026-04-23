<div>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold text-gray-900">Data Visualization</h2>
        <div class="flex items-center space-x-2">
            <button class="p-1 text-gray-400 hover:text-gray-600" wire:click="toggleCharts"
                title="{{ $showCharts ? 'Hide charts' : 'Show charts' }}">
                @if ($showCharts)
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
    </div>

    @if ($showCharts && !empty($chartConfigs))
        <div class="mb-4">
            <div class="flex pb-2 space-x-2 overflow-x-auto">
                @foreach ($chartConfigs as $index => $chart)
                    <button type="button"
                        class="px-4 py-2 text-sm font-medium whitespace-nowrap {{ $activeChartIndex === $index ? 'bg-blue-100 text-blue-700 border-b-2 border-blue-700' : 'bg-white text-gray-700 hover:bg-gray-50' }} rounded-t-lg"
                        wire:click="setActiveChart({{ $index }})">
                        {{ $chart['title'] }}
                    </button>
                @endforeach
            </div>
        </div>

        @if ($activeChartData)
            <x-mary-card title="{{ $activeChartData['config']['title'] }}">
                <div class="flex justify-end mb-2">
                    <button class="p-1 text-gray-400 hover:text-gray-600"
                        onclick="downloadChartAsPNG('{{ $activeChartData['config']['id'] }}')"
                        title="Download chart as PNG">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                    </button>
                </div>
                <p class="mb-4 text-sm text-gray-500">{{ $activeChartData['config']['description'] }}</p>
                <div class="h-80" wire:ignore>
                    <canvas id="{{ $activeChartData['config']['id'] }}"></canvas>
                </div>
            </x-mary-card>
        @endif
    @endif

    @script
        <script>
            let chartInstances = {};

            // Function to safely destroy a chart
            function safeDestroyChart(chartId) {
                if (chartInstances[chartId] && typeof chartInstances[chartId].destroy === 'function') {
                    chartInstances[chartId].destroy();
                    chartInstances[chartId] = null;
                }
            }

            // Function to render a chart
            function renderChart(chartId, chartType, chartData) {
                if (!chartData) return;

                const canvas = document.getElementById(chartId);
                if (!canvas) return;

                const ctx = canvas.getContext('2d');

                // Destroy existing chart
                safeDestroyChart(chartId);

                // Create new chart
                chartInstances[chartId] = new Chart(ctx, {
                    type: chartType,
                    data: chartData,
                    options: chartData.options || {}
                });
            }

            // Function to download chart as PNG
            function downloadChartAsPNG(chartId) {
                const canvas = document.getElementById(chartId);
                if (!canvas) return;

                const dataURL = canvas.toDataURL('image/png');
                const downloadLink = document.createElement('a');
                downloadLink.href = dataURL;
                downloadLink.download = `${chartId}.png`;
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            }

            document.addEventListener('livewire:initialized', () => {
                // Listen for chart data updates
                Livewire.on('chartDataUpdated', (chartData) => {
                    if (!chartData) return;

                    // Get active chart config
                    const activeChartIndex = @this.activeChartIndex;
                    if (chartData[activeChartIndex]) {
                        const chartConfig = chartData[activeChartIndex];
                        const chartId = chartConfig.id;
                        const chartType = chartConfig.type;
                        const dataKey = chartConfig.dataKey;

                        // Render the chart if we have data
                        if (@this.chartData && @this.chartData[dataKey]) {
                            renderChart(chartId, chartType, @this.chartData[dataKey]);
                        }
                    }
                });

                // Handle active chart changes
                @this.watch('activeChartIndex', (value) => {
                    const chartConfigs = @json($chartConfigs);
                    if (chartConfigs && chartConfigs[value]) {
                        const chartConfig = chartConfigs[value];
                        const chartId = chartConfig.id;
                        const chartType = chartConfig.type;
                        const dataKey = chartConfig.dataKey;

                        // Render the chart if we have data
                        if (@this.chartData && @this.chartData[dataKey]) {
                            renderChart(chartId, chartType, @this.chartData[dataKey]);
                        }
                    }
                });

                // Initial chart rendering
                const activeChartData = @json($activeChartData);
                if (activeChartData && activeChartData.config) {
                    const chartId = activeChartData.config.id;
                    const chartType = activeChartData.config.type;

                    // Render the chart if we have data
                    if (activeChartData.data) {
                        renderChart(chartId, chartType, activeChartData.data);
                    }
                }
            });
        </script>
    @endscript
</div>
