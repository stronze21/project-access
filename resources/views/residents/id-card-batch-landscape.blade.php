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
                <a href="{{ route('residents.id-cards.form', array_filter(['barangay' => $barangay ?? null, 'status' => $status ?? null, 'filter_sectors' => (bool) $printBatch->sector_filter, 'sectors' => $printBatch->sector_filter, 'exclude_printed' => (int) $printBatch->exclude_printed, 'alphabetical' => (int) $printBatch->alphabetical], fn ($value) => $value !== null)) }}">Back to Selection</a>
            </div>
            @if ($hasNextBatch ?? false)
                <form action="{{ route('residents.id-cards.batch') }}" method="POST">
                    @csrf
                    <input type="hidden" name="alphabetical" value="{{ (int) $printBatch->alphabetical }}">
                    <input type="hidden" name="barangay" value="{{ $barangay }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="filter_sectors" value="{{ $printBatch->sector_filter ? 1 : 0 }}">
                    @foreach ($printBatch->sector_filter ?? [] as $sector)
                        <input type="hidden" name="sectors[]" value="{{ $sector }}">
                    @endforeach
                    <input type="hidden" name="exclude_printed" value="{{ $printBatch->exclude_printed ? 1 : 0 }}">
                    @unless ($printBatch->exclude_printed)
                        <input type="hidden" name="reprint_start" value="{{ $reprintStart }}">
                    @endunless
                    <button type="submit">{{ $printBatch->exclude_printed ? 'Generate Next Unassigned Batch' : 'Generate Next Reprint Batch' }}</button>
                </form>
            @endif
        </div>
        <form id="batch-order-form" action="{{ route('residents.id-cards.batches.order', $printBatch) }}" method="POST" class="batch-toolbar-actions">
            @csrf
            <input type="hidden" name="alphabetical" value="0">
            @unless ($printBatch->exclude_printed)
                <input type="hidden" name="reprint_start" value="{{ $reprintStart }}">
            @endunless
            <label><input type="checkbox" name="alphabetical" value="1" @checked($printBatch->alphabetical)> Arrange alphabetically (A–Z by surname)</label>
            <button type="submit">Apply order</button>
            <span>Order: {{ $printBatch->alphabetical ? 'Surname A–Z' : 'Resident record ID' }}</span>
            <div id="order-selection-inputs">
                @foreach ($selectedResidentIds as $residentId)
                    <input type="hidden" name="selected_residents[]" value="{{ $residentId }}">
                @endforeach
            </div>
        </form>
        <div class="batch-toolbar-meta">
            @if ($barangay ?? null)
                <span class="batch-toolbar-chip">{{ $barangay === 'all' ? 'All Barangays' : $barangay }}</span>
                <span class="batch-toolbar-chip">Batch {{ $batchNumber }}</span>
                <span class="batch-toolbar-chip">{{ $residents->count() }} assigned ID(s)</span>
            @endif
            <span class="batch-toolbar-chip">{{ str($status)->headline() }} · {{ $printBatch->sector_label }}</span>
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
        const choices = [...document.querySelectorAll('.resident-print-selection [name="selected_residents[]"]')];
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
        document.getElementById('batch-order-form').addEventListener('submit', () => {
            const inputs = document.getElementById('order-selection-inputs');
            inputs.replaceChildren();
            choices.filter(choice => choice.checked).forEach(choice => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_residents[]';
                input.value = choice.value;
                inputs.append(input);
            });
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
