@php
    $moduleSettings = app(\App\Services\ModuleSettings::class);
    $bosesmotoComplaintsEnabled = $moduleSettings->enabled('complaints');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-theme-script />
    <title>Municipal Public Feedback System</title>
    <link rel="icon" type="image/png" href="{{ asset('logos/bosesmoto.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased">
    <header x-data="{ menuOpen: false }" class="navbar relative border-b border-base-300 bg-base-100 shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex min-h-16 items-center justify-between py-2 sm:py-0">
                <a href="{{ $bosesmotoComplaintsEnabled ? route('complaints.public.index') : route('login') }}" class="btn btn-ghost max-w-[13rem] justify-start px-0 text-xs font-semibold leading-tight text-base-content sm:max-w-none sm:text-sm">
                    Municipal Public Feedback System
                </a>

                <div class="flex items-center gap-2 md:hidden">
                    <x-theme-toggle />
                    <button
                    type="button"
                    class="btn btn-square btn-ghost btn-sm md:hidden"
                    @click="menuOpen = !menuOpen"
                    :aria-expanded="menuOpen.toString()"
                    aria-controls="public-mobile-nav"
                    aria-label="Toggle navigation menu"
                >
                    <svg x-show="!menuOpen" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="menuOpen" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                    </button>
                </div>

                <nav class="hidden items-center gap-3 text-sm md:flex">
                    <x-theme-toggle />
                    @if ($bosesmotoComplaintsEnabled)
                        <a href="{{ route('complaints.public.index') }}" class="btn btn-ghost btn-sm">Public Complaints</a>
                    @endif
                    @auth
                        @if ($bosesmotoComplaintsEnabled && !auth()->user()->isInternalUser())
                            <a href="{{ route('complaints.anonymous.create') }}" class="btn btn-ghost btn-sm">Submit Anonymous</a>
                        @endif
                        <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm">Dashboard</a>
                    @else
                        @if ($bosesmotoComplaintsEnabled)
                            <a href="{{ route('complaints.anonymous.create') }}" class="btn btn-ghost btn-sm">Submit Anonymous</a>
                        @endif
                        <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Login</a>
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
                    @endauth
                </nav>
            </div>
        </div>

        <nav id="public-mobile-nav" x-cloak x-show="menuOpen" x-transition.opacity class="absolute left-0 top-full z-50 w-full border-t border-base-300 bg-base-100 shadow-lg md:hidden">
            <div class="menu mx-auto max-w-7xl px-4 py-3 sm:px-6">
                @if ($bosesmotoComplaintsEnabled)
                    <a href="{{ route('complaints.public.index') }}">Public Complaints</a>
                @endif
                @auth
                    @if ($bosesmotoComplaintsEnabled && !auth()->user()->isInternalUser())
                        <a href="{{ route('complaints.anonymous.create') }}">Submit Anonymous</a>
                    @endif
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                @else
                    @if ($bosesmotoComplaintsEnabled)
                        <a href="{{ route('complaints.anonymous.create') }}">Submit Anonymous</a>
                    @endif
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="alert alert-success mb-4 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</body>
</html>
