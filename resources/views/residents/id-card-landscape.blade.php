<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACCESS ID - {{ $resident->full_name }}</title>
    @include('residents.partials.access-id-card-styles')
    @if (request('preview') === 'front')
        <style>
            html,
            body {
                width: 100%;
                height: 100%;
                min-height: 0;
                overflow: hidden;
                background: #fff;
            }

            body {
                display: flex;
                align-items: flex-start;
                justify-content: flex-start;
                padding: 0;
            }

            .card-sheet,
            .print-page {
                width: 100%;
                height: 100%;
            }

            .print-page {
                box-shadow: none;
                transform: scale(var(--preview-scale, 1));
                transform-origin: top left;
            }
        </style>
        <script>
            const fitFrontIdCard = () => {
                document.documentElement.style.setProperty('--preview-scale', window.innerWidth / 324);
            };

            fitFrontIdCard();
            window.addEventListener('resize', fitFrontIdCard);
        </script>
    @endif
</head>

<body>
    @if (request('preview') !== 'front')
    <nav class="print-controls" aria-label="ID card actions">
        <button type="button" onclick="window.print()">Print ID Card</button>
        <a href="{{ route('residents.show', $resident->id) }}">Back to Resident</a>
    </nav>

    <aside class="editor-panel" data-resident-id="{{ $resident->resident_id }}" aria-label="ID Card Editor">
        <div class="editor-heading">
            <div>
                <strong>ID Card Editor</strong>
                <span>Changes are saved in this browser.</span>
            </div>
            <button type="button" class="editor-collapse" aria-label="Collapse editor" aria-expanded="true">−</button>
        </div>

        <div class="editor-body">
            <label class="editor-field">
                <span>Element</span>
                <select id="editor-target" aria-label="Element to edit"></select>
            </label>

            <div class="editor-grid editor-metrics">
                <label class="editor-field"><span>X (in)</span><input id="editor-x" type="number" step="0.01"></label>
                <label class="editor-field"><span>Y (in)</span><input id="editor-y" type="number" step="0.01"></label>
                <label class="editor-field"><span>Width (in)</span><input id="editor-width" type="number" min="0.05" step="0.01"></label>
                <label class="editor-field"><span>Height (in)</span><input id="editor-height" type="number" min="0.03" step="0.01"></label>
            </div>

            <label class="editor-field editor-font-row">
                <span>Font size (px)</span>
                <input id="editor-font-size" type="number" min="5" max="72" step="1">
            </label>

            <div class="editor-section">
                <span class="editor-section-title">Move</span>
                <div class="editor-nudge-grid">
                    <button type="button" data-move-x="0" data-move-y="-0.01" aria-label="Move up">↑</button>
                    <button type="button" data-move-x="-0.01" data-move-y="0" aria-label="Move left">←</button>
                    <button type="button" data-move-x="0.01" data-move-y="0" aria-label="Move right">→</button>
                    <button type="button" data-move-x="0" data-move-y="0.01" aria-label="Move down">↓</button>
                </div>
            </div>

            <div class="editor-section">
                <span class="editor-section-title">Transform</span>
                <div class="editor-actions">
                    <button type="button" data-action="smaller">− Smaller</button>
                    <button type="button" data-action="larger">+ Larger</button>
                    <button type="button" data-action="rotate-left">↶ Rotate</button>
                    <button type="button" data-action="rotate-right">↷ Rotate</button>
                    <button type="button" data-action="flip-horizontal">Flip H</button>
                    <button type="button" data-action="flip-vertical">Flip V</button>
                    <button type="button" data-action="toggle-fit">Contain / Cover</button>
                </div>
            </div>

            <p class="editor-hint">Select an element, then drag it directly on the card or use the controls.</p>

            <div class="editor-actions editor-reset-actions">
                <button type="button" data-action="reset-selected">Reset Selected</button>
                <button type="button" data-action="reset-all" class="danger">Reset All</button>
            </div>
        </div>
    </aside>
    @endif

    <main class="card-sheet" aria-label="ACCESS identification card for {{ $resident->full_name }}">
        @include('residents.partials.access-id-card', [
            'resident' => $resident,
            'side' => request('preview') === 'front' ? 'front' : 'both',
        ])
    </main>

    @if (request('preview') !== 'front')
    @include('residents.partials.access-id-card-editor')
    @endif
</body>

</html>
