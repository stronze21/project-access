<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2b67a2">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SmartCity ACCESS">
    <title>@yield('title', 'SmartCity ACCESS')</title>
    <link rel="icon" type="image/png" href="{{ asset('resident-portal/images/appicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('resident-portal/images/appicon.png') }}">
    <link rel="manifest" href="{{ asset('resident-portal/manifest.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:FILL@0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('resident-portal/app.css') }}?v={{ filemtime(public_path('resident-portal/app.css')) }}">
</head>
<body class="resident-portal-body {{ $guest ?? false ? 'is-guest' : '' }}">
    <div class="native-app-shell">
        @unless($guest ?? false)
            <header class="top-app-bar">
                <a class="top-app-brand" href="{{ route('resident-portal.home') }}">
                    <img class="top-app-seal" src="{{ asset('resident-portal/images/alaminos-seal.jpg') }}" alt="City of Alaminos seal">
                    <span class="top-app-title"><strong>City of Alaminos</strong><small>Pangasinan, Philippines</small></span>
                </a>
                <img class="top-app-logo" src="{{ asset('resident-portal/images/access-logo.png') }}" alt="Alaminos City ACCESS">
            </header>
        @endunless

        <main class="app-content {{ $guest ?? false ? 'auth-content' : '' }}">
            @if (session('status'))
                <div class="portal-alert success" role="status"><span class="material-symbols-rounded filled">check_circle</span>{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="portal-alert danger" role="alert"><span class="material-symbols-rounded filled">error</span><span>{{ $errors->first() }}</span></div>
            @endif
            @yield('content')
        </main>

        @unless($guest ?? false)
            @php($path = request()->path())
            <nav class="bottom-nav" aria-label="Resident portal navigation">
                <a href="{{ route('resident-portal.home') }}" class="bottom-nav-item {{ $path === 'resident-portal' ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>Home</span>
                </a>
                <a href="{{ url('/resident-portal/citizen-services') }}" class="bottom-nav-item {{ str_contains($path, 'citizen-services') || str_contains($path, 'bosesmoto') || str_contains($path, 'ayuda') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h16"/></svg>
                    <span>Services</span>
                </a>
                <a href="{{ url('/resident-portal/digital-id') }}" class="bottom-nav-center {{ str_contains($path, 'digital-id') ? 'active' : '' }}">
                    <span class="bottom-nav-center-btn"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="9" cy="12" r="2.5"/><path d="M15 9h2M15 13h2"/></svg></span>
                    <span>Digital ID</span>
                </a>
                <a href="{{ url('/resident-portal/announcements') }}" class="bottom-nav-item {{ str_contains($path, 'announcements') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span>Updates</span>
                </a>
                <a href="{{ url('/resident-portal/profile') }}" class="bottom-nav-item {{ str_contains($path, 'profile') || str_contains($path, 'settings') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Profile</span>
                </a>
            </nav>
        @endunless
    </div>
    <script src="{{ asset('resident-portal/app.js') }}?v={{ filemtime(public_path('resident-portal/app.js')) }}" defer></script>
</body>
</html>
