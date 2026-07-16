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
        <a href="{{ route('residents.id-cards.form') }}">Back to Selection</a>
    </nav>

    <main class="card-sheet batch-sheet" aria-label="Batch ACCESS identification cards">
        @foreach ($residents as $resident)
            @include('residents.partials.access-id-card', ['resident' => $resident])
        @endforeach
    </main>
</body>

</html>
