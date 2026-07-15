<div
    class="space-y-6 px-4 py-8 sm:px-6 lg:px-8"
    x-on:legacy-promotion-next.window="setTimeout(() => $wire.processPromotionChunk(), 75)"
>
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Legacy Resident Import</h1>
            <p class="mt-1 text-sm text-base-content/60">Validate, stage, review, and safely promote on-prem CSV exports.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('manage-legacy-reference-data')
                <a href="{{ route('legacy-data.references.index', 'source-income-types') }}" class="btn btn-outline btn-sm">Manage Legacy Data</a>
            @endcan
            <a href="{{ route('residents.import') }}" class="btn btn-outline btn-sm">Standard Import</a>
            <a href="{{ route('residents.index') }}" class="btn btn-outline btn-sm">Back to Residents</a>
        </div>
    </div>

    @if ($notice)
        <div role="alert" class="alert {{ $noticeType === 'error' ? 'alert-error' : ($noticeType === 'warning' ? 'alert-warning' : 'alert-success') }}" wire:transition>
            <span>{{ $notice }}</span>
            <button type="button" wire:click="$set('notice', null)" class="btn btn-ghost btn-xs">Dismiss</button>
        </div>
    @endif
    @if ($errors->any())
        <div role="alert" class="alert alert-error"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card border border-base-300 bg-base-100 shadow-sm xl:col-span-2">
            <div class="card-body">
                <h2 class="card-title">Upload legacy CSV files</h2>
                <p class="text-sm text-base-content/60">Upload <code>tblPersonalInfo.csv</code> alone or select up to seven related exports. Uploading only stages an immutable copy.</p>
                <form wire:submit="stage" class="space-y-5">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Legacy CSV files</legend>
                        <input type="file" wire:model="csvFiles" accept=".csv,.txt,text/csv" multiple class="file-input file-input-bordered w-full">
                        <p class="label text-xs text-base-content/50">Maximum 100 MB per file. Current PHP upload limit: {{ $serverUploadLimit }}.</p>
                    </fieldset>
                    <div class="alert alert-info items-start"><div>
                        <p class="font-semibold">Stage-first safety</p>
                        <ol class="mt-2 list-inside list-decimal space-y-1 text-sm">
                            <li>Headers, row structure, PINs, duplicates, and cross-file references are validated.</li>
                            <li>Every source row is retained with its checksum and validation state.</li>
                            <li>Canonical changes require a separate preview and typed confirmation.</li>
                        </ol>
                    </div></div>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="csvFiles,stage">
                        <span wire:loading.remove wire:target="csvFiles,stage">Validate and Stage</span>
                        <span wire:loading wire:target="csvFiles,stage" class="loading loading-spinner loading-sm"></span>
                    </button>
                </form>
            </div>
        </section>
        <section class="card border border-base-300 bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="card-title">Supported exports</h2>
            <ul class="space-y-2 text-sm text-base-content/70">
                @foreach (['tblPersonalInfo.csv', 'tblFamilyMembers.csv', 'tblBHWMaster.csv', 'tblBarangay.csv', 'tblCivilStatus.csv', 'tblSourceIncomeType.csv', 'tblEduc_Attainment.csv'] as $filename)<li><code>{{ $filename }}</code></li>@endforeach
            </ul>
            <div class="alert alert-warning mt-3 text-sm">Conflicting or incomplete records are held for review rather than promoted.</div>
        </div></section>
    </div>

    <section class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body p-0">
            <div class="p-6 pb-2"><h2 class="card-title">Import batches</h2><p class="text-sm text-base-content/60">Recent staged file manifests and their current status.</p></div>
            <div class="overflow-x-auto"><table class="table table-zebra">
                <thead><tr><th>Batch</th><th>Imported</th><th>Files</th><th>Rows</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                <tbody>
                    @forelse ($batches as $item)
                        @php
                            $totalRows = collect($item->stats ?? [])->sum('rows');
                        @endphp
                        <tr wire:key="legacy-batch-{{ $item->id }}" class="{{ $selectedBatch?->id === $item->id ? 'bg-primary/10' : '' }}">
                            <td class="font-semibold">#{{ $item->id }}</td><td>{{ $item->imported_at?->format('M d, Y H:i') ?? '—' }}</td>
                            <td>{{ count($item->file_manifest ?? []) }}</td><td>{{ number_format($totalRows) }}</td>
                            <td><span class="badge {{ $item->status === 'promoted' ? 'badge-success' : ($item->status === 'promoted_with_conflicts' ? 'badge-warning' : 'badge-info') }}">{{ str($item->status)->replace('_', ' ')->title() }}</span></td>
                            <td class="text-right"><button type="button" wire:click="selectBatch({{ $item->id }})" class="btn btn-ghost btn-sm">Review</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-base-content/60">No legacy import batches yet.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    </section>
    @if ($batches->hasPages())
        <div class="flex items-center justify-between"><span class="text-sm text-base-content/60">Page {{ $batches->currentPage() }} of {{ $batches->lastPage() }}</span><div class="join">
            <button type="button" wire:click="previousPage" class="btn join-item btn-sm" @disabled($batches->onFirstPage())>Previous</button>
            <button type="button" wire:click="nextPage" class="btn join-item btn-sm" @disabled(! $batches->hasMorePages())>Next</button>
        </div></div>
    @endif

    @if ($selectedBatch)
        @php
            $stats = collect($selectedBatch->stats ?? []);
            $totals = ['rows' => $stats->sum('rows'), 'valid' => $stats->sum('valid_rows'), 'invalid' => $stats->sum('invalid_rows'), 'conflicts' => $stats->sum('conflict_rows')];
        @endphp
        <section class="card border border-base-300 bg-base-100 shadow-sm"><div class="card-body">
            <div class="flex flex-col justify-between gap-3 md:flex-row md:items-start">
                <div><h2 class="card-title">Batch #{{ $selectedBatch->id }}</h2><p class="mt-1 break-all font-mono text-xs text-base-content/50">SHA-256 manifest: {{ $selectedBatch->manifest_checksum }}</p></div>
                <span class="badge badge-info">{{ str($selectedBatch->status)->replace('_', ' ')->title() }}</span>
            </div>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ([['Source rows', $totals['rows'], ''], ['Valid', $totals['valid'], 'text-success'], ['Invalid', $totals['invalid'], 'text-error'], ['Conflicts', $totals['conflicts'], 'text-warning']] as [$label, $value, $color])
                    <div class="stat rounded-box border border-base-300 bg-base-200/40 p-4"><div class="stat-title text-xs uppercase">{{ $label }}</div><div class="stat-value text-2xl {{ $color }}">{{ number_format($value) }}</div></div>
                @endforeach
            </div>
            <div class="grid gap-6 lg:grid-cols-2">
                <div><h3 class="font-semibold">Files</h3><div class="mt-2 space-y-2">
                    @foreach ($selectedBatch->file_manifest ?? [] as $file)<div class="flex justify-between rounded-box border border-base-300 p-3 text-sm"><span><b>{{ $file['filename'] }}</b> <small>{{ $file['source_table'] }}</small></span><span>{{ number_format($file['size_bytes'] / 1024, 1) }} KB</span></div>@endforeach
                </div></div>
                <div><h3 class="font-semibold">Validation summary</h3><div class="mt-2 overflow-x-auto rounded-box border border-base-300"><table class="table table-sm">
                    <thead><tr><th>Source</th><th>State</th><th class="text-right">Rows</th></tr></thead><tbody>
                        @foreach ($rowSummary as $summary)<tr><td>{{ $summary->source_table }}</td><td>{{ str($summary->validation_status)->title() }}</td><td class="text-right">{{ number_format($summary->aggregate) }}</td></tr>@endforeach
                    </tbody>
                </table></div></div>
            </div>

            @if (($promotionReport['batch_id'] ?? null) === $selectedBatch->id)
                <div class="rounded-box border border-info/30 bg-info/10 p-5"><h3 class="font-semibold">{{ $promotionReport['dry_run'] ? 'Promotion preview' : 'Promotion result' }}</h3><div class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach (['residents', 'households', 'bhw'] as $section)
                        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4"><h4 class="font-medium">{{ str($section)->title() }}</h4><dl class="space-y-1 text-sm">
                            @foreach ($promotionReport[$section] as $metric => $count)<div class="flex justify-between gap-4"><dt class="text-base-content/60">{{ str($metric)->replace('_', ' ')->title() }}</dt><dd>{{ number_format($count) }}</dd></div>@endforeach
                        </dl></div></div>
                    @endforeach
                </div></div>
            @endif

            @if ($promotionRunning)
                @php
                    $promotionPercent = $promotionTotal > 0
                        ? min(100, (int) round(($promotionProcessed / $promotionTotal) * 100))
                        : ($promotionPhase === 'related' ? 100 : 0);
                @endphp
                <div class="rounded-box border border-primary/30 bg-primary/10 p-5" wire:loading.class="opacity-75" wire:target="processPromotionChunk">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="font-semibold">{{ $promotionMode === 'commit' ? 'Promoting' : 'Previewing' }} batch #{{ $selectedBatch->id }}</h3>
                            <p class="text-sm text-base-content/60">
                                {{ match ($promotionPhase) {
                                    'personal' => 'Processing personal information in 1,000-row requests.',
                                    'family' => 'Processing family members in 500-row requests while preserving family boundaries.',
                                    default => 'Finishing related BHW data.',
                                } }}
                            </p>
                        </div>
                        <span class="font-mono text-sm">{{ $promotionPercent }}%</span>
                    </div>
                    <progress class="progress progress-primary mt-3 w-full" value="{{ $promotionPercent }}" max="100"></progress>
                    <p class="mt-2 text-xs text-base-content/50">Processed {{ number_format($promotionProcessed) }} of {{ number_format($promotionTotal) }} personal and family-member rows. Keep this page open.</p>
                </div>
            @endif

            <div class="grid gap-6 border-t border-base-300 pt-6 lg:grid-cols-2">
                <div class="card border border-info/30 bg-info/10"><div class="card-body"><h3 class="card-title text-base">1. Preview canonical changes</h3><p class="text-sm">Calculates changes and conflicts without writing canonical data. Large batches may take several minutes; keep this page open while it runs.</p><div class="card-actions"><button type="button" wire:click="preview" class="btn btn-info" wire:loading.attr="disabled" @disabled($promotionRunning)>Run Promotion Preview</button></div></div></div>
                <div class="card border border-error/30 bg-error/10"><div class="card-body">
                    <h3 class="card-title text-base">2. Promote safe records</h3><p class="text-sm">Only complete records and null-field backfills are applied. Large batches run synchronously, so keep this page open until promotion finishes.</p>
                    <fieldset class="fieldset"><legend class="fieldset-legend">Type <code>PROMOTE-{{ $selectedBatch->id }}</code></legend><input type="text" wire:model="confirmation" class="input input-bordered w-full" @disabled(! $previewIsCurrent)>@error('confirmation')<p class="text-error text-sm">{{ $message }}</p>@enderror</fieldset>
                    <label class="label cursor-pointer justify-start gap-3"><input type="checkbox" wire:model="confirmSafePromotion" class="checkbox checkbox-error" @disabled(! $previewIsCurrent)><span class="label-text">I reviewed the latest preview and understand that safe records will be committed.</span></label>
                    @error('confirmSafePromotion')<p class="text-error text-sm">{{ $message }}</p>@enderror
                    <div class="card-actions"><button type="button" wire:click="promote" class="btn btn-error" @disabled(! $previewIsCurrent || $promotionRunning) wire:loading.attr="disabled">Promote Safe Records</button></div>
                    @unless ($previewIsCurrent)<p class="text-xs text-error">Run a fresh preview to enable promotion.</p>@endunless
                </div></div>
            </div>
        </div></section>
    @endif
</div>
