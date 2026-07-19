<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#174f7a">
    <title>{{ $code }} — {{ $title }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo1.png') }}">
    <style>
        :root { color-scheme: light; --blue:#23689b; --blue-dark:#123e61; --teal:#0f9f84; --ink:#122033; --muted:#5e6c7d; --line:#dbe4ec; }
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; overflow-x: hidden; color: var(--ink); background: #edf4f8; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body::before, body::after { content: ""; position: fixed; border-radius: 999px; filter: blur(2px); pointer-events: none; }
        body::before { width: 34rem; height: 34rem; top: -18rem; right: -12rem; background: rgba(35,104,155,.15); }
        body::after { width: 28rem; height: 28rem; bottom: -17rem; left: -10rem; background: rgba(15,159,132,.13); }
        .shell { position: relative; z-index: 1; width: min(100%, 68rem); overflow: hidden; border: 1px solid rgba(255,255,255,.9); border-radius: 28px; background: rgba(255,255,255,.94); box-shadow: 0 28px 75px rgba(18,62,97,.16); }
        .bar { height: 7px; background: linear-gradient(90deg, var(--blue), #2780b8 55%, var(--teal)); }
        .content { display: grid; grid-template-columns: minmax(0,1fr) minmax(19rem,.72fr); align-items: center; gap: 54px; padding: clamp(34px,7vw,76px); }
        .brand { display: inline-flex; align-items: center; gap: 12px; margin-bottom: 42px; color: var(--blue-dark); font-size: 13px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .brand img { width: 42px; height: 42px; border-radius: 12px; object-fit: contain; box-shadow: 0 5px 14px rgba(18,62,97,.14); }
        .eyebrow { margin: 0 0 10px; color: var(--teal); font-size: 13px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 0; max-width: 38rem; font-size: clamp(2.25rem,5vw,4.2rem); line-height: 1.03; letter-spacing: -.045em; }
        .message { max-width: 39rem; margin: 22px 0 0; color: var(--muted); font-size: clamp(1rem,2vw,1.12rem); line-height: 1.75; }
        .guidance { display: grid; gap: 10px; max-width: 39rem; margin: 24px 0 0; padding: 0; list-style: none; }
        .guidance li { position: relative; padding-left: 25px; color: var(--muted); font-size: 14px; line-height: 1.55; }
        .guidance li::before { content: "✓"; position: absolute; left: 0; top: 0; color: var(--teal); font-weight: 900; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 34px; }
        .button { display: inline-flex; min-height: 46px; align-items: center; justify-content: center; gap: 8px; border: 1px solid var(--line); border-radius: 12px; padding: 0 18px; color: var(--blue-dark); background: white; font-size: 14px; font-weight: 750; text-decoration: none; cursor: pointer; }
        .button:hover { border-color: #a9bdcc; background: #f7fafc; }
        .button.primary { border-color: var(--blue); color: white; background: var(--blue); box-shadow: 0 8px 22px rgba(35,104,155,.22); }
        .button.primary:hover { background: #1a557f; }
        .visual { position: relative; display: grid; min-height: 290px; place-items: center; isolation: isolate; }
        .visual::before { content: ""; position: absolute; z-index: -1; width: min(25vw,250px); aspect-ratio: 1; border-radius: 50%; background: linear-gradient(145deg, #e7f3fa, #e4f7f2); box-shadow: 0 0 0 24px rgba(35,104,155,.035), 0 0 0 48px rgba(15,159,132,.025); }
        .code { color: var(--blue-dark); font-size: clamp(6rem,15vw,10rem); font-weight: 900; line-height: 1; letter-spacing: -.1em; text-shadow: 5px 7px 0 white; transform: translateX(-.04em); }
        .status { position: absolute; right: 5%; bottom: 10%; display: flex; align-items: center; gap: 8px; border: 1px solid var(--line); border-radius: 999px; padding: 9px 13px; color: #375166; background: white; box-shadow: 0 10px 24px rgba(18,62,97,.12); font-size: 12px; font-weight: 800; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--teal); box-shadow: 0 0 0 4px rgba(15,159,132,.13); }
        .footer { padding: 18px 28px; border-top: 1px solid var(--line); color: #718094; background: #f8fafc; font-size: 12px; text-align: center; }
        @media (max-width: 760px) { .content { grid-template-columns: 1fr; gap: 24px; padding: 32px 26px 38px; } .brand { margin-bottom: 28px; } .visual { grid-row: 1; min-height: 180px; } .visual::before { width: 155px; } .code { font-size: 6.5rem; } .status { right: 12%; bottom: 4%; } .actions { flex-direction: column; } .button { width: 100%; } }
        @media (prefers-color-scheme: dark) { :root { color-scheme: dark; --ink:#edf5fb; --muted:#b4c1cf; --line:#32465a; } body { background:#0d1722; } .shell { border-color:#293d50; background:rgba(18,31,44,.96); } .visual::before { background:linear-gradient(145deg,#18384e,#153d3a); } .code { color:#8dc8ed; text-shadow:5px 7px 0 #12202d; } .button { color:#dcecf6; background:#17293a; } .button:hover { background:#1d3346; } .button.primary { background:#2878ad; } .status { color:#d0deea; background:#17293a; } .footer { background:#101d29; } }
    </style>
</head>
<body>
    <main class="shell">
        <div class="bar"></div>
        <div class="content">
            <section>
                <div class="brand"><img src="{{ asset('logo1.png') }}" alt=""> Alaminos City ACCESS</div>
                <p class="eyebrow">{{ $label ?? 'Request interrupted' }}</p>
                <h1>{{ $title }}</h1>
                <p class="message">{{ $message }}</p>
                @if (!empty($guidance))
                    <ul class="guidance">
                        @foreach ($guidance as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif
                <div class="actions">
                    @if (!empty($actionUrl))
                        <a class="button primary" href="{{ $actionUrl }}">{{ $actionLabel ?? 'Try again' }}</a>
                    @else
                        <button class="button primary" type="button" onclick="window.location.reload()">Try again</button>
                    @endif
                    <button class="button" type="button" onclick="history.length > 1 ? history.back() : window.location.assign('{{ url('/') }}')">Go back</button>
                    <a class="button" href="{{ url('/') }}">Return home</a>
                </div>
            </section>
            <div class="visual" aria-hidden="true">
                <div class="code">{{ $code }}</div>
                <div class="status"><span class="dot"></span> {{ $statusText ?? 'HTTP '.$code }}</div>
            </div>
        </div>
        <footer class="footer">{{ $footer ?? 'If the problem continues, contact your ACCESS system administrator and mention error '.$code.'.' }}</footer>
    </main>
</body>
</html>
