<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch ACCESS ID Cards</title>
    @include('residents.partials.access-id-card-styles')
</head>
<body class="batch-print-preview">
    <nav class="print-controls batch-toolbar" aria-label="Batch ID card actions">
        <div class="batch-toolbar-row">
            <div class="batch-toolbar-actions">
                <form id="batch-print-form" action="{{ route('residents.id-cards.batches.printed', $printBatch) }}" method="POST">
                    @csrf
                    @unless ($printBatch->exclude_printed)
                        <input type="hidden" name="reprint_start" value="{{ $reprintStart }}">
                    @endunless
                    <button id="print-selected" type="submit">Print {{ count($selectedResidentIds) }} Selected ID(s)</button>
                </form>
                <a href="{{ route('residents.id-cards.batches.print', ['printBatch' => $printBatch, 'remaining' => 1, 'reprint_start' => $printBatch->exclude_printed ? null : $reprintStart]) }}">Print Remaining</a>
                <a href="{{ route('residents.id-cards.batches.index') }}">Print History / Pending IDs</a>
                <button type="button" data-select-all="true">Select All</button>
                <button type="button" data-select-all="false">Deselect All</button>
                <a href="{{ route('residents.id-cards.form', array_filter(['barangay' => $barangay ?? null, 'status' => $status ?? null])) }}">Back to Selection</a>
            </div>
            @if ($hasNextBatch ?? false)
                <form action="{{ route('residents.id-cards.batch') }}" method="POST">
                    @csrf
                    <input type="hidden" name="barangay" value="{{ $barangay }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="exclude_printed" value="{{ $printBatch->exclude_printed ? 1 : 0 }}">
                    @unless ($printBatch->exclude_printed)
                        <input type="hidden" name="reprint_start" value="{{ $reprintStart }}">
                    @endunless
                    <button type="submit">{{ $printBatch->exclude_printed ? 'Generate Next Unassigned Batch' : 'Generate Next Reprint Batch' }}</button>
                </form>
            @endif
        </div>
        <div class="batch-toolbar-meta">
            @if ($barangay ?? null)
                <span class="batch-toolbar-chip">{{ $barangay === 'all' ? 'All Barangays' : $barangay }}</span>
                <span class="batch-toolbar-chip">Batch {{ $batchNumber }}</span>
                <span class="batch-toolbar-chip">{{ $residents->count() }} assigned ID(s)</span>
            @endif
            <span class="batch-toolbar-chip batch-toolbar-reference">Reference: {{ $printBatch->reference_number }}</span>
            @unless ($printBatch->exclude_printed)
                <span class="batch-toolbar-chip batch-toolbar-warning">Reprint mode enabled</span>
            @endunless
        </div>
        <p class="selection-summary">{{ $printBatch->items->whereNotNull('printed_at')->count() }} print initiated; {{ $printBatch->items->whereNull('printed_at')->whereNotNull('resident')->count() }} remaining available in this batch. Print initiation does not confirm physical printing. Pending IDs stay in their original batches; open Print History to resume them.</p>
        <p class="selection-summary" id="selection-summary" aria-live="polite">Uncheck any resident to exclude both sides from printing.</p>
        @if (isset($errors) && $errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif
    </nav>

    <main class="card-sheet batch-sheet" aria-label="Batch ACCESS identification cards">
        @foreach ($residents as $resident)
            <div class="resident-print-group {{ in_array($resident->id, $selectedResidentIds) ? '' : 'print-excluded' }}">
                <label class="resident-print-selection">
                    <input type="checkbox" name="selected_residents[]" value="{{ $resident->id }}"
                        form="batch-print-form" @checked(in_array($resident->id, $selectedResidentIds))>
                    <span><strong>Include in printing</strong><br>{{ $resident->full_name }} &middot; {{ $resident->resident_id }}
                        <small>{{ $printBatch->items->firstWhere('resident_id', $resident->id)?->printed_at ? 'Print previously initiated' : 'Not yet initiated' }}</small>
                    </span>
                </label>
                @include('residents.partials.access-id-card', ['resident' => $resident])
            </div>
        @endforeach
    </main>
    <script>
        const choices = [...document.querySelectorAll('[name="selected_residents[]"]')];
        const printButton = document.getElementById('print-selected');
        function updateSelection() {
            const selected = choices.filter(choice => choice.checked);
            choices.forEach(choice => {
                const group = choice.closest('.resident-print-group');
                group.classList.toggle('print-excluded', !choice.checked);
                group.classList.remove('last-print-group');
            });
            selected.at(-1)?.closest('.resident-print-group').classList.add('last-print-group');
            printButton.disabled = selected.length === 0;
            printButton.textContent = `Print ${selected.length} Selected ID(s)`;
            document.getElementById('selection-summary').textContent =
                `${selected.length} of ${choices.length} selected. Unchecked residents will not print (front and back).`;
        }
        choices.forEach(choice => choice.addEventListener('change', updateSelection));
        document.querySelectorAll('[data-select-all]').forEach(button => {
            button.addEventListener('click', () => {
                choices.forEach(choice => { choice.checked = button.dataset.selectAll === 'true'; });
                updateSelection();
            });
        });
        document.getElementById('batch-print-form').addEventListener('submit', event => {
            if (!choices.some(choice => choice.checked)) event.preventDefault();
        });
        window.addEventListener('beforeprint', updateSelection);
        window.addEventListener('pageshow', updateSelection);
        updateSelection();
        @if (request()->boolean('print'))
            window.addEventListener('load', () => window.print());
        @endif
    </script>
</body>
</html>
