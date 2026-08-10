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
                <form action="{{ route('residents.id-cards.batches.printed', $printBatch) }}" method="POST">
                    @csrf
                    <button type="submit">Print {{ $residents->count() }} ID Card(s)</button>
                </form>
                <a href="{{ route('residents.id-cards.form', array_filter(['barangay' => $barangay ?? null, 'status' => $status ?? null])) }}">Back to Selection</a>
            </div>
            @if ($hasNextBatch ?? false)
                <form action="{{ route('residents.id-cards.batch') }}" method="POST">
                    @csrf
                    <input type="hidden" name="barangay" value="{{ $barangay }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="exclude_printed" value="{{ $printBatch->exclude_printed ? 1 : 0 }}">
                    <button type="submit">Generate Next Unassigned Batch</button>
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
    </nav>

    <main class="card-sheet batch-sheet" aria-label="Batch ACCESS identification cards">
        @foreach ($residents as $resident)
            @include('residents.partials.access-id-card', ['resident' => $resident])
        @endforeach
    </main>
    @if (request()->boolean('print'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
