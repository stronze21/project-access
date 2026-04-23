<div>
    <div class="flex gap-2">
        @if ($showResidentFullExport)
            <x-mary-select :options="$exportTypes" option-label="1" option-value="0" wire:model.live="selectedExportType"
                class="w-48" />
        @endif

        <x-mary-button wire:click="export" class="tagged-color btn-secondary btn-outline btn-secline"
            icon="o-document-arrow-down" wire:loading.attr="disabled" wire:loading.class="opacity-75"
            class="whitespace-nowrap">
            <span wire:loading.remove wire:target="export">{{ $exportLabel }}</span>
            <span wire:loading wire:target="export">Exporting...</span>
        </x-mary-button>
    </div>

    <!-- Export Progress Modal -->
    @if ($isExporting)
        <div class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">
            <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-xl">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Exporting Report</h3>

                <div class="mb-4">
                    <div class="bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300 ease-in-out"
                            style="width: {{ $exportProgress }}%"></div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        @if ($exportProgress < 100)
                            Preparing your report for download...
                        @else
                            Export complete! Your download will begin shortly.
                        @endif
                    </p>
                </div>

                @if ($exportProgress < 100)
                    <button
                        class="w-full px-4 py-2 mt-4 font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        wire:click="closeModal">
                        Cancel Export
                    </button>
                @else
                    <a href="{{ $exportedFilePath ?? '#' }}"
                        class="block w-full px-4 py-2 mt-4 font-medium text-center text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        onclick="document.getElementById('closeModalBtn').click();">
                        Download & Close
                    </a>
                    <button id="closeModalBtn" wire:click="closeModal" class="hidden">Close</button>
                @endif
            </div>
        </div>
    @endif

    @script
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('showExportProgress', () => {
                    // Simulate progress updates
                    let progress = 0;
                    const interval = setInterval(() => {
                        progress += 5;
                        if (progress <= 90) {
                            @this.exportProgress = progress;
                        } else {
                            clearInterval(interval);
                        }
                    }, 150);
                });

                // Listen for export completed event from the server
                Livewire.on('exportCompleted', (data) => {
                    // Set progress to 100% to indicate completion
                    @this.exportProgress = 100;

                    // Set the exported file path
                    @this.exportedFilePath = data.filePath;
                });

                // Listen for export failed event from the server
                Livewire.on('exportFailed', () => {
                    // Reset the export modal state
                    @this.isExporting = false;
                });
            });
        </script>
    @endscript
</div>
