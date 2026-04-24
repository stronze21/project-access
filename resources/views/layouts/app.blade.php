<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $configuredBrandName = trim((string) config('app.name', 'Alaminos City E-Services Solutions'));
    $settingsBrandName = trim(collect([
        $settings['app_name_1'] ?? null,
        $settings['app_name_2'] ?? null,
    ])->filter(fn ($value) => filled($value))->implode(' '));
    $legacyBrandNames = ['Ayuda Hub', 'Ayuda Hub1', 'Ayuda Portal', 'AyudaPortal', 'AyudaHub'];
    $brandName = $settingsBrandName && !in_array($settingsBrandName, $legacyBrandNames, true)
        ? $settingsBrandName
        : $configuredBrandName;
    $brandLocation = trim(collect([
        $settings['municipality'] ?? null,
        $settings['province'] ?? null,
        $settings['region'] ?? null,
    ])->filter(fn ($value) => filled($value))->implode(', '));
    $legacyLocationLabels = ['Default Municipality', 'Default Province', 'Default Region'];
    $brandLocation = collect(explode(',', $brandLocation))
        ->map(fn ($value) => trim($value))
        ->reject(fn ($value) => in_array($value, $legacyLocationLabels, true))
        ->implode(', ');
    $currentRoleLabel = auth()->check()
        ? collect(auth()->user()->getRoleNames())
            ->map(fn ($role) => str($role)->replace('-', ' ')->title()->toString())
            ->first()
        : null;
    $currentRoleLabel = $currentRoleLabel ?: 'Authorized User';
    $userInitials = auth()->check()
        ? collect(explode(' ', trim(auth()->user()->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('')
        : 'AU';
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $brandName }}</title>
    @if (isset($settings['app_favicon']))
        <link rel="icon" href="{{ Storage::url($settings['app_favicon']) }}" type="image/x-icon">
    @endif

    <style>
        /* Ensure the signature canvas has proper styling */
        .signature-container canvas {
            touch-action: none;
            /* Disable browser's default touch actions */
            cursor: crosshair;
            /* Change cursor to indicate drawing capability */
        }

        /* Prevent text selection during drawing */
        .signature-container {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        /* Style the canvas container for visual clarity */
        #signature-canvas-container {
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            background-color: white;
        }

        /* Prevent text selection during drawing */
        #tablet-signature-container {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        /* Drawing tablet specific styles for better visual feedback */
        #tablet-signature-pad canvas {
            cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2'%3E%3Ccircle cx='12' cy='12' r='5'/%3E%3C/svg%3E") 8 8, crosshair;
            background-color: white;
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 0, 0, 0.05) 1px, transparent 1px);
            background-size: 20px 20px;
            background-position: -1px -1px;
        }
    </style>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- HTML5 QR Code Scanner -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <!-- SigWeb dependencies -->
    {{-- <script type="text/javascript" src="{{ asset('js/topaz/SigWebTablet.js') }}"></script> --}}

    <!-- Livewire Styles -->
    @livewireStyles
</head>

<body class="min-h-screen font-sans antialiased brand-shell bg-base-300">
    <div class="flex min-h-screen flex-col">
        <!-- Navigation -->
        <nav class="sticky top-0 z-40 brand-navbar" x-data="{ open: false }">
            <div class="px-4 pt-3 pb-1 mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
                <div class="brand-navbar-shell">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <!-- Logo -->
                        <div class="flex items-center flex-1 min-w-0">
                            <a href="{{ route('dashboard') }}" class="brand-navbar-logo">
                                <img src="{{ asset('logo.png') }}" alt="{{ $brandName }}"
                                    class="w-auto brand-navbar-mark h-11 sm:h-12">
                                <span class="min-w-0">
                                    <span class="block truncate brand-navbar-title">{{ $brandName }}</span>
                                    <span class="block truncate brand-navbar-subtitle">
                                        {{ $brandLocation ?: 'Alaminos City Digital Platform' }}
                                    </span>
                                </span>
                            </a>
                        </div>
                        <div class="flex-wrap items-center justify-end hidden gap-3 sm:flex xl:flex-nowrap">
                            <div class="brand-navbar-kicker">
                                {{ $currentRoleLabel }}
                            </div>
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false"
                                    class="brand-user-trigger focus:outline-none">
                                    <div class="brand-user-avatar">
                                        {{ $userInitials }}
                                    </div>
                                    <div class="hidden min-w-0 sm:block">
                                        <div class="text-sm font-semibold truncate text-slate-800">{{ Auth::user()->name }}</div>
                                        <div class="mt-0.5 flex items-center gap-2">
                                            <span class="brand-user-role">{{ $currentRoleLabel }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-1 text-slate-500">
                                        <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>

                                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 translate-y-1"
                                    class="absolute right-0 z-10 w-64 mt-3 origin-top-right brand-navbar-menu"
                                    style="display: none;">
                                    <div class="px-2 py-2">
                                        <div class="px-3 pb-3 mb-2 border-b border-slate-100">
                                            <div class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ Auth::user()->email }}</div>
                                            <div class="inline-flex mt-2 brand-user-role">{{ $currentRoleLabel }}</div>
                                        </div>
                                        <div class="block px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                            Manage Account
                                        </div>

                                        <a href="{{ route('profile.show') }}"
                                            class="brand-navbar-menu-link">
                                            Profile
                                        </a>

                                        <form method="POST" action="{{ route('logout') }}" x-data>
                                            @csrf
                                            <a href="{{ route('logout') }}" @click.prevent="$root.submit();"
                                                class="block w-full text-left brand-navbar-menu-link">
                                                Log Out
                                            </a>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Links -->
                    <div class="flex items-center justify-between gap-3 pt-2 mt-3 border-t border-slate-200/70">
                        <div class="brand-navbar-nav hidden min-w-0 flex-1 flex-wrap items-center gap-x-5 gap-y-2 overflow-visible sm:flex">
                            @role('registration-officer')
                                <x-nav-link href="{{ route('registration.dashboard') }}" :active="request()->routeIs('registration.dashboard')">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Registration Dashboard
                                    </span>
                                </x-nav-link>
                            @else
                                <!-- Dashboard - accessible to all authenticated users -->
                                <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Dashboard
                                    </span>
                                </x-nav-link>
                            @endrole

                            <!-- Residents - only show if user has permission -->
                            @can('view-residents')
                                <x-nav-link href="{{ route('residents.index') }}" :active="request()->routeIs('residents.*')">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Residents
                                    </span>
                                </x-nav-link>
                            @endcan

                            <!-- Households - only show if user has permission -->
                            @can('view-households')
                                <x-nav-link href="{{ route('households.index') }}" :active="request()->routeIs('households.*')">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Households
                                    </span>
                                </x-nav-link>
                            @endcan

                            <!-- Programs Dropdown - only show if user has any of the related permissions -->
                            @if (auth()->user()->hasAnyPermission(['view-programs', 'view-distributions', 'create-distributions']))
                                <div class="relative hidden sm:inline-flex sm:items-center" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false"
                                        class="inline-flex items-center h-full px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2 focus:outline-none"
                                        :class="{
                                            'border-[var(--brand-secondary)] text-[var(--brand-ink)]': {{ request()->routeIs('programs.*') || request()->routeIs('distributions.*') ? 'true' : 'false' }},
                                            'border-transparent text-gray-500 hover:text-[var(--brand-primary)] hover:border-[var(--brand-accent)]':
                                                !
                                                {{ request()->routeIs('programs.*') || request()->routeIs('distributions.*') ? 'true' : 'false' }}
                                        }">
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            Programs
                                        </span>
                                        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 translate-y-1"
                                        class="absolute left-0 top-full z-20 mt-2 w-56 origin-top-left rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-200/40"
                                        style="display: none;">
                                        <div class="py-1">
                                            @can('view-programs')
                                                <a href="{{ route('programs.index') }}"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Ayuda Programs
                                                </a>
                                            @endcan

                                            @can('create-distributions')
                                                <a href="{{ route('distributions.create') }}"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Distribute Aid
                                                </a>
                                            @endcan

                                            @can('view-distributions')
                                                <a href="{{ route('distributions.index') }}"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Distribution History
                                                </a>
                                            @endcan
                                            @can('manage-distribution-batches')
                                                <a href="{{ route('distributions.batches') }}"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Batch Management
                                                </a>
                                                <a href="{{ route('distributions.barangay-batch') }}"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Barangay Batch Distribution
                                                </a>
                                                <a href="{{ route('distributions.batch-verification') }}"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Batch Verification
                                                </a>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Reports - only show if user has permission -->
                            @can('view-reports')
                                <x-nav-link href="{{ route('report.controller') }}" :active="request()->routeIs('report.controller')">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Reports
                                    </span>
                                </x-nav-link>
                            @endcan

                            @can('manage-citizen-services')
                                <x-nav-link href="{{ route('citizen-services.index') }}" :active="request()->routeIs('citizen-services.*')">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 8h10M7 12h8m-8 4h6m7-11l-8.586 8.586a2 2 0 01-1.414.586H5a1 1 0 01-1-1v-4.586a2 2 0 01.586-1.414L13.172 2.586A2 2 0 0114.586 2H19a1 1 0 011 1v4.414a2 2 0 01-.586 1.414z" />
                                        </svg>
                                        Citizen Services
                                    </span>
                                </x-nav-link>
                            @endcan

                            <!-- Admin Dropdown - only show for appropriate permissions -->
                            @if (auth()->user()->can('manage-users') || auth()->user()->hasRole('system-administrator'))
                                <div class="relative hidden sm:inline-flex sm:items-center" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false"
                                        class="inline-flex items-center h-full px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2 focus:outline-none"
                                        :class="{
                                            'border-[var(--brand-secondary)] text-[var(--brand-ink)]': {{ request()->routeIs('admin.*') ? 'true' : 'false' }},
                                            'border-transparent text-gray-500 hover:text-[var(--brand-primary)] hover:border-[var(--brand-accent)]':
                                                !{{ request()->routeIs('admin.*') ? 'true' : 'false' }}
                                        }">
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Admin
                                        </span>
                                        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 translate-y-1"
                                        class="absolute left-0 top-full z-20 mt-2 w-56 origin-top-left rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-200/40"
                                        style="display: none;">
                                        <div class="py-1">
                                                <a href="{{ route('announcements.index') }}"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Announcements
                                                </a>

                                                <a href="{{ route('citizen-services.index') }}"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Citizen Services
                                                </a>

                                            @can('manage-users')
                                                <a href="{{ route('admin.users') }}"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    User Management
                                                </a>
                                            @endcan

                                            @role('system-administrator')
                                                <a href="{{ route('admin.roles') }}"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Role Management
                                                </a>
                                            @endrole
                                            @if (auth()->user() && auth()->user()->hasRole('system-administrator'))
                                                <a href="{{ route('admin.system-settings') }}"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    System Settings
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="flex items-center -mr-2 sm:hidden">
                            <button @click="open = !open"
                                class="inline-flex items-center justify-center p-2 text-slate-500 transition duration-150 ease-in-out rounded-2xl hover:text-[var(--brand-primary)] hover:bg-[var(--brand-mist)] focus:outline-none focus:bg-[var(--brand-mist)] focus:text-[var(--brand-primary)]">
                                <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu for Mobile -->
                <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
                    <div class="brand-mobile-panel">
                        <div class="flex items-center gap-3 px-2 py-3 mb-2 border-b border-slate-100">
                            <div class="brand-user-avatar">{{ $userInitials }}</div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold truncate text-slate-800">{{ Auth::user()->name }}</div>
                                <div class="text-xs truncate text-slate-500">{{ Auth::user()->email }}</div>
                                <div class="inline-flex mt-2 brand-user-role">{{ $currentRoleLabel }}</div>
                            </div>
                        </div>
                    <div class="pt-2 pb-3 space-y-1">
                        <!-- Dashboard - accessible to all authenticated users -->
                        <x-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Dashboard
                            </span>
                        </x-responsive-nav-link>

                        <!-- Residents - only visible with permission -->
                        @can('view-residents')
                            <x-responsive-nav-link href="{{ route('residents.index') }}" :active="request()->routeIs('residents.*')">
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Residents
                                </span>
                            </x-responsive-nav-link>
                        @endcan

                        <!-- Households - only visible with permission -->
                        @can('view-households')
                            <x-responsive-nav-link href="{{ route('households.index') }}" :active="request()->routeIs('households.*')">
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    Households
                                </span>
                            </x-responsive-nav-link>
                        @endcan

                        <!-- Programs & Distributions dropdown - only visible with appropriate permissions -->
                        @if (auth()->user()->hasAnyPermission(['view-programs', 'view-distributions', 'create-distributions']))
                            <div x-data="{ programsOpen: false }">
                                <button @click="programsOpen = !programsOpen"
                                    class="flex items-center w-full text-left pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('programs.*') || request()->routeIs('distributions.*') ? 'border-[var(--brand-secondary)] text-[var(--brand-primary)] bg-[var(--brand-mist)] focus:outline-none focus:text-[var(--brand-primary-strong)] focus:bg-[var(--brand-mist)] focus:border-[var(--brand-primary)]' : 'border-transparent text-gray-600 hover:text-[var(--brand-primary)] hover:bg-[var(--brand-mist)] hover:border-[var(--brand-accent)]' }} text-base font-medium focus:outline-none transition duration-150 ease-in-out">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Programs & Distributions
                                    </span>
                                    <svg class="w-4 h-4 ml-auto" :class="{ 'rotate-90': programsOpen }"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div x-show="programsOpen" class="pl-6 mt-1 space-y-1" style="display: none;">
                                    <!-- Ayuda Programs -->
                                    @can('view-programs')
                                        <x-responsive-nav-link href="{{ route('programs.index') }}" :active="request()->routeIs('programs.index')">
                                            <span class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                Ayuda Programs
                                            </span>
                                        </x-responsive-nav-link>
                                    @endcan

                                    <!-- Distribute Aid -->
                                    @can('create-distributions')
                                        <x-responsive-nav-link href="{{ route('distributions.create') }}"
                                            :active="request()->routeIs('distributions.create')">
                                            <span class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                </svg>
                                                Distribute Aid
                                            </span>
                                        </x-responsive-nav-link>
                                    @endcan

                                    <!-- Distribution History -->
                                    @can('view-distributions')
                                        <x-responsive-nav-link href="{{ route('distributions.index') }}"
                                            :active="request()->routeIs('distributions.index')">
                                            <span class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Distribution History
                                            </span>
                                        </x-responsive-nav-link>
                                    @endcan

                                    <!-- Distribution Batches -->
                                    @can('manage-distribution-batches')
                                        <x-responsive-nav-link href="{{ route('distributions.batches') }}"
                                            :active="request()->routeIs('distributions.batches')">
                                            <span class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                </svg>
                                                Batch Management
                                            </span>
                                        </x-responsive-nav-link>
                                        <x-responsive-nav-link href="{{ route('distributions.barangay-batch') }}"
                                            :active="request()->routeIs('distributions.barangay-batch')">
                                            <span class="flex items-center">
                                                <x-mary-icon name="o-users" class="me-2" />
                                                Barangay Batch Distribution
                                            </span>
                                        </x-responsive-nav-link>
                                        <x-responsive-nav-link href="{{ route('distributions.batch-verification') }}"
                                            :active="request()->routeIs('distributions.batch-verification')">
                                            <span class="flex items-center">
                                                <x-mary-icon name="o-check-circle" class="me-2" />
                                                Batch Verification
                                            </span>
                                        </x-responsive-nav-link>
                                    @endcan
                                    @if (auth()->user() && auth()->user()->hasRole('system-administrator'))
                                        <x-nav-link href="{{ route('admin.system-settings') }}" :active="request()->routeIs('admin.system-settings')">
                                            <span class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                System Settings
                                            </span>
                                        </x-nav-link>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Reports - only visible with permission -->
                        @can('view-reports')
                            <x-responsive-nav-link href="{{ route('report.controller') }}" :active="request()->routeIs('report.controller')">
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Reports
                                </span>
                            </x-responsive-nav-link>
                        @endcan

                        @can('manage-citizen-services')
                            <x-responsive-nav-link href="{{ route('citizen-services.index') }}" :active="request()->routeIs('citizen-services.*')">
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 8h10M7 12h8m-8 4h6m7-11l-8.586 8.586a2 2 0 01-1.414.586H5a1 1 0 01-1-1v-4.586a2 2 0 01.586-1.414L13.172 2.586A2 2 0 0114.586 2H19a1 1 0 011 1v4.414a2 2 0 01-.586 1.414z" />
                                    </svg>
                                    Citizen Services
                                </span>
                            </x-responsive-nav-link>
                        @endcan

                        <!-- QR/RFID Scanner - only visible with permission -->
                        @can('verify-beneficiaries')
                            <x-responsive-nav-link href="{{ route('scanner') }}" :active="request()->routeIs('scanner')">
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    QR Scanner
                                </span>
                            </x-responsive-nav-link>
                        @endcan

                        <!-- Admin section - only visible for admin users -->
                        @if (auth()->user()->can('manage-users') || auth()->user()->hasRole('system-administrator'))
                            <div x-data="{ adminOpen: false }">
                                <button @click="adminOpen = !adminOpen"
                                    class="flex items-center w-full text-left pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('admin.*') ? 'border-[var(--brand-secondary)] text-[var(--brand-primary)] bg-[var(--brand-mist)] focus:outline-none focus:text-[var(--brand-primary-strong)] focus:bg-[var(--brand-mist)] focus:border-[var(--brand-primary)]' : 'border-transparent text-gray-600 hover:text-[var(--brand-primary)] hover:bg-[var(--brand-mist)] hover:border-[var(--brand-accent)]' }} text-base font-medium focus:outline-none transition duration-150 ease-in-out">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Administration
                                    </span>
                                    <svg class="w-4 h-4 ml-auto" :class="{ 'rotate-90': adminOpen }"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div x-show="adminOpen" class="pl-6 mt-1 space-y-1" style="display: none;">
                                    <!-- User Management -->
                                    @can('manage-users')
                                        <x-responsive-nav-link href="{{ route('admin.users') }}" :active="request()->routeIs('admin.users')">
                                            <span class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                </svg>
                                                User Management
                                            </span>
                                        </x-responsive-nav-link>
                                    @endcan

                                    @can('manage-citizen-services')
                                        <x-responsive-nav-link href="{{ route('citizen-services.index') }}" :active="request()->routeIs('citizen-services.*')">
                                            <span class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M7 8h10M7 12h8m-8 4h6m7-11l-8.586 8.586a2 2 0 01-1.414.586H5a1 1 0 01-1-1v-4.586a2 2 0 01.586-1.414L13.172 2.586A2 2 0 0114.586 2H19a1 1 0 011 1v4.414a2 2 0 01-.586 1.414z" />
                                                </svg>
                                                Citizen Services
                                            </span>
                                        </x-responsive-nav-link>
                                    @endcan

                                    <!-- Role Management - admin only -->
                                    @role('system-administrator')
                                        <x-responsive-nav-link href="{{ route('admin.roles') }}" :active="request()->routeIs('admin.roles')">
                                            <span class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                </svg>
                                                Role Management
                                            </span>
                                        </x-responsive-nav-link>
                                    @endrole

                                    <!-- System Settings - admin only -->
                                    @role('system-administrator')
                                        <x-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('admin.settings')">
                                            <span class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                System Settings
                                            </span>
                                        </x-responsive-nav-link>
                                    @endrole

                                    <!-- Audit Log - admin only -->
                                    @can('view-audit-logs')
                                        <x-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('admin.audit-logs')">
                                            <span class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                Audit Logs
                                            </span>
                                        </x-responsive-nav-link>
                                    @endcan
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="items-center hidden px-4">
                            <div>
                                <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                                <div class="text-sm font-medium text-gray-500">{{ Auth::user()->email }}</div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <!-- Account Management -->
                            <x-responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Profile
                                </span>
                            </x-responsive-nav-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf
                                <x-responsive-nav-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Log Out
                                    </span>
                                </x-responsive-nav-link>
                            </form>
                        </div>
                    </div>
                    </div>
                </div>
        </nav>

        <!-- Rest of your content -->
        <!-- Page Heading -->
        @if (isset($header))
            <header class="shadow bg-base">
                <div class="px-4 py-4 mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main class="flex-1 pt-6 pb-6">
            <div class="px-4 mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-6 border-t border-white/70 bg-white/80 backdrop-blur">
            <div class="px-4 mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between space-y-4 md:space-y-0 md:flex-row">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('logo.png') }}" alt="{{ $brandName }}" class="w-auto h-10">
                        <div>
                            <div class="text-sm font-semibold sm:text-base brand-wordmark">{{ $brandName }}</div>
                            <div class="text-xs text-gray-500">&copy; {{ date('Y') }} All rights reserved.</div>
                        </div>
                        <!--
                        <span class="ml-2 text-sm text-gray-500">© {{ date('Y') }} All rights reserved.</span>
                        -->
                    </div>
                    <div class="text-sm text-center text-gray-500">
                        <div>{{ $brandLocation ?: 'Local government digital services platform' }}</div>
                        <div class="mt-1">
                            Connected public services for Alaminos City
                        </div>
                    </div>
                    <div class="text-sm text-right text-gray-500">
                        <div>
                            {{ $settings['contact_email'] ?? '' }} | {{ $settings['contact_phone'] ?? '' }}
                        </div>
                        <div class="mt-1">
                            {{ $settings['office_address'] ?? '' }}
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    <!-- Livewire Scripts -->
    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.2.0/dist/signature_pad.umd.min.js"></script>

    @stack('scripts')

    <x-mary-toast />
</body>

</html>
