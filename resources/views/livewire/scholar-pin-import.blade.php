<div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Scholar PIN Import</h1>
            <p class="mt-1 text-sm text-base-content/60">Mark existing residents as scholars using an Excel workbook containing a PIN column.</p>
        </div>
        <a href="{{ route('residents.index') }}" class="btn btn-outline btn-sm">Back to Residents</a>
    </div>

    @if ($notice)
        <div role="alert" class="alert {{ $noticeType === 'error' ? 'alert-error' : 'alert-success' }}">
            <span>{{ $notice }}</span>
        </div>
    @endif

    <section class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body">
            <h2 class="card-title">Upload scholar workbook</h2>
            <p class="text-sm text-base-content/60">The first worksheet must contain a column named <code>PIN</code>. This import only enables scholar flags; it never removes existing scholar flags or creates residents.</p>

            <form wire:submit="preview" class="space-y-4">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Excel workbook</legend>
                    <input type="file" wire:model="workbook" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="file-input file-input-bordered w-full">
                    <p class="label text-xs text-base-content/50">Maximum 10 MB, .xlsx only.</p>
                    @error('workbook')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </fieldset>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="workbook,preview">
                    Validate and Preview
                </button>
            </form>
        </div>
    </section>

    @if ($report !== [])
        <section class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body">
                <h2 class="card-title">Import preview</h2>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                    @foreach ([
                        ['Source rows', $report['source_rows'] ?? 0],
                        ['Unique valid PINs', $report['unique_valid'] ?? 0],
                        ['Matched residents', $report['matched'] ?? 0],
                        ['Already scholars', $report['already_scholars'] ?? 0],
                        ['Will update', isset($report['updated']) ? $report['updated'] : (($report['matched'] ?? 0) - ($report['already_scholars'] ?? 0))],
                    ] as [$label, $value])
                        <div class="stat rounded-box border border-base-300 bg-base-200/40 p-4">
                            <div class="stat-title text-xs uppercase">{{ $label }}</div>
                            <div class="stat-value text-2xl">{{ number_format($value) }}</div>
                        </div>
                    @endforeach
                </div>

                @if (($report['unmatched'] ?? []) !== [] || ($report['invalid'] ?? []) !== [])
                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-box border border-warning/30 bg-warning/10 p-4">
                            <h3 class="font-semibold">Unmatched PINs ({{ count($report['unmatched'] ?? []) }})</h3>
                            <p class="mt-2 break-words font-mono text-xs">{{ implode(', ', $report['unmatched'] ?? []) ?: 'None' }}</p>
                        </div>
                        <div class="rounded-box border border-error/30 bg-error/10 p-4">
                            <h3 class="font-semibold">Invalid PINs ({{ count($report['invalid'] ?? []) }})</h3>
                            <p class="mt-2 break-words font-mono text-xs">{{ implode(', ', $report['invalid'] ?? []) ?: 'None' }}</p>
                        </div>
                    </div>
                @endif

                <label class="label cursor-pointer justify-start gap-3">
                    <input type="checkbox" wire:model="confirmImport" class="checkbox checkbox-primary">
                    <span class="label-text">I reviewed the matches and want to mark these residents as scholars.</span>
                </label>
                @error('confirmImport')<p class="text-sm text-error">{{ $message }}</p>@enderror

                <button type="button" wire:click="import" class="btn btn-primary" wire:loading.attr="disabled">
                    Apply Scholar Flags
                </button>
            </div>
        </section>
    @endif
</div>
