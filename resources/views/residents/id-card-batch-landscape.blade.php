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
        <button type="button" onclick="window.print()">Print {{ $residents->count() }} ID Card(s)</button>
        <a href="{{ route('residents.id-cards.form', array_filter(['barangay' => $barangay ?? null, 'status' => $status ?? null, 'batch_number' => $batchNumber ?? null])) }}">Back to Barangay Selection</a>
        @if ($barangay ?? null)
            <span>{{ $barangay }} · Batch {{ $batchNumber }} · {{ $totalResidents }} matching resident(s)</span>
        @endif
        @if ($hasNextBatch ?? false)
            <form action="{{ route('residents.id-cards.batch') }}" method="POST">
                @csrf
                <input type="hidden" name="barangay" value="{{ $barangay }}">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="batch_number" value="{{ $batchNumber + 1 }}">
                <button type="submit">Open Batch {{ $batchNumber + 1 }}</button>
            </form>
        @endif
    </nav>

    <main class="card-sheet batch-sheet" aria-label="Batch ACCESS identification cards">
        @foreach ($residents as $resident)
            @include('residents.partials.access-id-card', ['resident' => $resident])
        @endforeach
    </main>
</body>

</html>
