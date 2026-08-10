<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch ACCESS ID Cards</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('residents.partials.access-id-card-styles')
</head>

<body>
    <nav class="print-controls" aria-label="Batch ID card actions">
        <button id="print-batch" type="button">Print {{ $residents->count() }} ID Card(s)</button>
        <a href="{{ route('residents.id-cards.form', array_filter(['barangay' => $barangay ?? null, 'status' => $status ?? null, 'batch_number' => $batchNumber ?? null])) }}">Back to Barangay Selection</a>
        @if ($barangay ?? null)
            <span>{{ $barangay === 'all' ? 'All Barangays' : $barangay }} · Batch {{ $batchNumber }} · {{ $totalResidents }} matching resident(s)</span>
        @endif
        <span>Tracking reference: {{ $printBatch->reference_number }}</span>
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
    <script>
        document.getElementById('print-batch').addEventListener('click', async (event) => {
            const button = event.currentTarget;
            button.disabled = true;
            try {
                const response = await fetch(@json(route('residents.id-cards.batches.printed', $printBatch)), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                if (!response.ok) throw new Error('The print could not be recorded.');
                window.print();
            } catch (error) {
                window.alert(`${error.message} Please retry so these printed IDs remain traceable.`);
            } finally {
                button.disabled = false;
            }
        });
    </script>
</body>

</html>
