<style>
    @page {
        size: 3.375in 2.125in;
        margin: 0;
    }

    :root {
        --card-width: 3.375in;
        --card-height: 2.125in;
        --artwork-height: 2.175in;
        --bleed-offset-y: -0.025in;
        --access-blue: #23699d;
    }

    * {
        box-sizing: border-box;
    }

    html,
    body {
        margin: 0;
        min-height: 100%;
        font-family: Arial, Helvetica, sans-serif;
    }

    body {
        background: #e8edf3;
        color: #111827;
        padding: 4.75rem 1.25rem 2rem;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .print-controls {
        position: fixed;
        top: 1rem;
        left: 50%;
        z-index: 100;
        display: flex;
        gap: .65rem;
        transform: translateX(-50%);
    }

    .print-controls button,
    .print-controls a {
        border: 0;
        border-radius: .45rem;
        background: #23699d;
        color: #fff;
        cursor: pointer;
        font: 700 .875rem/1 Arial, Helvetica, sans-serif;
        padding: .8rem 1rem;
        text-decoration: none;
        white-space: nowrap;
    }

    .print-controls a {
        background: #475569;
    }

    .card-sheet {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.5rem;
    }

    .print-page {
        position: relative;
        width: var(--card-width);
        height: var(--card-height);
        flex: 0 0 auto;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 .35rem 1.1rem rgb(15 23 42 / 18%);
        isolation: isolate;
    }

    .front-artwork,
    .back-artwork {
        position: absolute;
        z-index: 3;
        top: var(--bleed-offset-y);
        left: 0;
        width: var(--card-width);
        height: var(--artwork-height);
        pointer-events: none;
        user-select: none;
    }

    .front-artwork {
        object-fit: fill;
    }

    .back-artwork {
        z-index: 1;
        object-fit: fill;
    }

    .resident-photo {
        position: absolute;
        z-index: 2;
        top: .49in;
        left: 1.73in;
        width: 1.47in;
        height: 1.33in;
        object-fit: contain;
        object-position: center bottom;
    }

    .photo-placeholder {
        position: absolute;
        z-index: 2;
        top: .72in;
        left: 2.1in;
        color: #94a3b8;
        font-size: .065in;
        font-weight: 700;
        text-transform: uppercase;
    }

    .field-value {
        position: absolute;
        z-index: 5;
        left: .435in;
        width: 1.3in;
        height: .14in;
        overflow: hidden;
        color: #050505;
        font-size: .112in;
        font-weight: 800;
        letter-spacing: .001in;
        line-height: 1.05;
        text-overflow: clip;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .field-value.compact {
        font-size: .098in;
    }

    .field-value.very-compact {
        font-size: .084in;
    }

    .field-last-name { top: .5in; }
    .field-given-name { top: .7in; width: 1.42in; }
    .field-middle-name { top: .91in; }
    .field-birthdate { top: 1.125in; width: 1.38in; font-size: .09in; }
    .field-gender { top: 1.275in; }
    .field-resident-id { top: 1.45in; width: 1.45in; font-size: .09in; }

    .gender-label-mask {
        position: absolute;
        z-index: 4;
        top: 1.205in;
        left: .425in;
        width: .5in;
        height: .13in;
        background: #fff;
    }

    .gender-label {
        position: absolute;
        z-index: 5;
        top: 1.215in;
        left: .435in;
        color: var(--access-blue);
        font-size: .068in;
        font-weight: 800;
        line-height: 1;
    }

    .resident-signature {
        position: absolute;
        z-index: 5;
        top: 1.635in;
        left: .45in;
        width: .78in;
        height: .36in;
        object-fit: contain;
        object-position: center;
        filter: grayscale(1) contrast(1.35);
    }

    .back-qr-mask {
        position: absolute;
        z-index: 2;
        top: .07in;
        left: .98in;
        width: 1.42in;
        height: 1.37in;
        background: #fff;
    }

    .resident-qr {
        position: absolute;
        z-index: 3;
        top: .125in;
        left: 1.06in;
        width: 1.25in;
        height: 1.25in;
        object-fit: contain;
        image-rendering: pixelated;
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .editor-panel {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 110;
        width: 17rem;
        max-height: calc(100vh - 2rem);
        overflow-y: auto;
        border: 1px solid #cbd5e1;
        border-radius: .65rem;
        background: #fff;
        box-shadow: 0 .75rem 2rem rgb(15 23 42 / 20%);
        color: #0f172a;
    }

    .editor-heading {
        position: sticky;
        top: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .8rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .editor-heading strong,
    .editor-heading span {
        display: block;
    }

    .editor-heading strong {
        font-size: .9rem;
    }

    .editor-heading span,
    .editor-hint {
        margin-top: .15rem;
        color: #64748b;
        font-size: .68rem;
        line-height: 1.35;
    }

    .editor-collapse {
        width: 1.8rem;
        height: 1.8rem;
        border: 0;
        border-radius: .35rem;
        background: #e2e8f0;
        cursor: pointer;
        font-size: 1.2rem;
        line-height: 1;
    }

    .editor-panel.collapsed {
        width: 12rem;
        overflow: hidden;
    }

    .editor-panel.collapsed .editor-body {
        display: none;
    }

    .editor-body {
        display: grid;
        gap: .8rem;
        padding: .8rem;
    }

    .editor-field {
        display: grid;
        gap: .3rem;
        color: #475569;
        font-size: .68rem;
        font-weight: 700;
    }

    .editor-field input,
    .editor-field select {
        width: 100%;
        min-width: 0;
        height: 2rem;
        border: 1px solid #cbd5e1;
        border-radius: .35rem;
        background: #fff;
        color: #0f172a;
        font: 500 .75rem/1 Arial, Helvetica, sans-serif;
        padding: .35rem .45rem;
    }

    .editor-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .55rem;
    }

    .editor-section {
        display: grid;
        gap: .4rem;
    }

    .editor-section-title {
        color: #475569;
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .editor-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .4rem;
    }

    .editor-actions button,
    .editor-nudge-grid button {
        min-height: 2rem;
        border: 0;
        border-radius: .35rem;
        background: #e2e8f0;
        color: #1e293b;
        cursor: pointer;
        font: 700 .7rem/1.1 Arial, Helvetica, sans-serif;
        padding: .45rem;
    }

    .editor-actions button:hover,
    .editor-nudge-grid button:hover {
        background: #cbd5e1;
    }

    .editor-nudge-grid {
        display: grid;
        grid-template-columns: repeat(3, 2rem);
        grid-template-areas:
            ". up ."
            "left down right";
        justify-content: center;
        gap: .3rem;
    }

    .editor-nudge-grid [data-move-y="-0.01"] { grid-area: up; }
    .editor-nudge-grid [data-move-x="-0.01"] { grid-area: left; }
    .editor-nudge-grid [data-move-y="0.01"] { grid-area: down; }
    .editor-nudge-grid [data-move-x="0.01"] { grid-area: right; }

    .editor-reset-actions {
        padding-top: .7rem;
        border-top: 1px solid #e2e8f0;
    }

    .editor-actions .danger {
        background: #fee2e2;
        color: #b91c1c;
    }

    .editor-mode .editable-element {
        pointer-events: auto;
    }

    .editor-mode .editor-selected {
        z-index: 20 !important;
        outline: 2px dashed #f97316;
        outline-offset: 2px;
        cursor: move;
        touch-action: none;
    }

    @media (max-width: 920px) {
        body {
            padding-top: 4.75rem;
        }

        .editor-panel {
            position: relative;
            top: auto;
            right: auto;
            width: min(100%, 24rem);
            max-height: none;
            margin: 0 auto 1rem;
        }

        .editor-panel.collapsed {
            width: min(100%, 24rem);
        }
    }

    @media print {
        html,
        body {
            width: var(--card-width);
            min-height: 0;
            background: #fff;
        }

        body {
            padding: 0;
        }

        .print-controls,
        .editor-panel {
            display: none !important;
        }

        .editor-selected {
            outline: none !important;
        }

        .card-sheet {
            display: block;
        }

        .print-page {
            margin: 0;
            break-after: page;
            page-break-after: always;
            box-shadow: none;
        }

        .print-page:last-child {
            break-after: auto;
            page-break-after: auto;
        }

        .print-page.card-back {
            transform: rotate(180deg);
        }
    }
</style>
