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
<body class="bg-gray-100 text-gray-900 antialiased">
    <header x-data="{ menuOpen: false }" class="bg-white border-b border-gray-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex min-h-16 items-center justify-between py-2 sm:py-0">
                <a href="{{ $bosesmotoComplaintsEnabled ? route('complaints.public.index') : route('login') }}" class="max-w-[13rem] text-xs font-semibold leading-tight text-gray-800 sm:max-w-none sm:text-sm">
                    Municipal Public Feedback System
                </a>

                <div class="flex items-center gap-2 md:hidden">
                    <x-theme-toggle />
                    <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white p-2 text-gray-700 hover:bg-gray-50 md:hidden"
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
                    <a href="{{ route('mobile-app.index') }}" class="text-gray-700 hover:text-gray-900">Mobile App</a>
                    @if ($bosesmotoComplaintsEnabled)
                        <a href="{{ route('complaints.public.index') }}" class="text-gray-700 hover:text-gray-900">Public Complaints</a>
                    @endif
                    @auth
                        @if ($bosesmotoComplaintsEnabled && !auth()->user()->isInternalUser())
                            <a href="{{ route('complaints.anonymous.create') }}" class="text-gray-700 hover:text-gray-900">Submit Anonymous</a>
                        @endif
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-gray-900">Dashboard</a>
                    @else
                        @if ($bosesmotoComplaintsEnabled)
                            <a href="{{ route('complaints.anonymous.create') }}" class="text-gray-700 hover:text-gray-900">Submit Anonymous</a>
                        @endif
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900">Login</a>
                        <a href="{{ route('register') }}" class="inline-flex rounded-md bg-blue-600 px-3 py-1.5 font-semibold text-white hover:bg-blue-700">Register</a>
                    @endauth
                </nav>
            </div>
        </div>

        <nav id="public-mobile-nav" x-cloak x-show="menuOpen" x-transition.opacity class="border-t border-gray-200 bg-white md:hidden">
            <div class="mx-auto max-w-7xl space-y-1 px-4 py-3 sm:px-6">
                <a href="{{ route('mobile-app.index') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900">Mobile App</a>
                @if ($bosesmotoComplaintsEnabled)
                    <a href="{{ route('complaints.public.index') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900">Public Complaints</a>
                @endif
                @auth
                    @if ($bosesmotoComplaintsEnabled && !auth()->user()->isInternalUser())
                        <a href="{{ route('complaints.anonymous.create') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900">Submit Anonymous</a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900">Dashboard</a>
                @else
                    @if ($bosesmotoComplaintsEnabled)
                        <a href="{{ route('complaints.anonymous.create') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900">Submit Anonymous</a>
                    @endif
                    <a href="{{ route('login') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900">Login</a>
                    <a href="{{ route('register') }}" class="block rounded-md bg-blue-600 px-3 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700">Register</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</body>
</html>
