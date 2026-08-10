<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch ACCESS ID Cards</title>
    @include('residents.partials.access-id-card-styles')
</head>

<body>
    <nav class="print-controls" aria-label="Batch ID card actions">
        <form action="{{ route('residents.id-cards.batches.printed', $printBatch) }}" method="POST">
            @csrf
            <button type="submit">Print {{ $residents->count() }} ID Card(s)</button>
        </form>
        <a href="{{ route('residents.id-cards.form', array_filter(['barangay' => $barangay ?? null, 'status' => $status ?? null])) }}">Back to Barangay Selection</a>
        @if ($barangay ?? null)
            <span>{{ $barangay === 'all' ? 'All Barangays' : $barangay }} · Batch {{ $batchNumber }} · {{ $residents->count() }} assigned ID(s)</span>
        @endif
        <span>Tracking reference: {{ $printBatch->reference_number }}</span>
        @unless ($printBatch->exclude_printed)
            <span>Reprint mode: previously printed residents included</span>
        @endunless
        @if ($hasNextBatch ?? false)
            <form action="{{ route('residents.id-cards.batch') }}" method="POST">
                @csrf
                <input type="hidden" name="barangay" value="{{ $barangay }}">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="exclude_printed" value="{{ $printBatch->exclude_printed ? 1 : 0 }}">
                <button type="submit">Generate Next Unassigned Batch</button>
            </form>
        @endif
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
