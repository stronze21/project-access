@php
    $appTitle = trim((string) ($release['name'] ?? 'ProjectAccess Mobile'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-theme-script />
    <title>{{ $appTitle }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo1.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f5f8fb] font-sans text-slate-950 antialiased dark:bg-slate-950 dark:text-slate-100">
    <header class="border-b border-slate-200/80 bg-white/95 dark:border-slate-800 dark:bg-slate-950/95">
        <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('mobile-app.index') }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('logo1.png') }}" alt="Alaminos City E-Services Solutions" class="h-12 w-auto max-w-[15rem] object-contain sm:h-14 sm:max-w-xs">
            </a>

            <nav class="flex items-center gap-2 text-sm">
                <button
                    type="button"
                    id="mobile-app-theme-toggle"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-[#0f9f84] hover:text-[#23689b] focus:outline-none focus:ring-2 focus:ring-[#0f9f84] focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:ring-offset-slate-950"
                    aria-label="Switch theme"
                >
                    <svg data-theme-icon="light" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg data-theme-icon="dark" xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.5 6.5 0 0 0 9.8 9.8Z" />
                    </svg>
                </button>
                @auth
                    <a href="{{ route('dashboard') }}" class="hidden font-semibold text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white sm:inline">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="hidden font-semibold text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white sm:inline">Login</a>
                @endauth

                @if (($release['has_apk'] ?? false) === true)
                    <a href="{{ route('mobile-app.download') }}" class="inline-flex items-center justify-center rounded-md bg-[#0f9f84] px-4 py-2.5 font-semibold text-white shadow-sm hover:bg-[#0b826d]">
                        <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                        </svg>
                        Download
                    </a>
                @endif
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
    <script>
        (() => {
            const button = document.getElementById('mobile-app-theme-toggle');
            const root = document.documentElement;
            const storageKey = 'project-access-theme';
            const legacyKey = 'theme';

            if (!button) {
                return;
            }

            const icons = {
                light: button.querySelector('[data-theme-icon="light"]'),
                dark: button.querySelector('[data-theme-icon="dark"]'),
            };

            const applyTheme = (theme) => {
                const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
                const dark = normalizedTheme === 'dark';

                root.classList.toggle('dark', dark);
                root.dataset.theme = dark ? 'aces-dark' : 'aces';
                root.style.colorScheme = normalizedTheme;

                icons.light?.classList.toggle('hidden', dark);
                icons.dark?.classList.toggle('hidden', !dark);
                button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');

                return normalizedTheme;
            };

            const storedTheme = () => {
                try {
                    return localStorage.getItem(storageKey) || localStorage.getItem(legacyKey);
                } catch {
                    return null;
                }
            };

            const writeTheme = (theme) => {
                try {
                    localStorage.setItem(storageKey, theme);
                    localStorage.setItem(legacyKey, theme);
                } catch {
                    // Storage may be blocked in private or restricted contexts.
                }
            };

            const currentTheme = () => root.classList.contains('dark') ? 'dark' : 'light';

            applyTheme(storedTheme() || currentTheme());

            button.addEventListener('click', () => {
                const nextTheme = currentTheme() === 'dark' ? 'light' : 'dark';

                writeTheme(nextTheme);

                if (window.ProjectAccessTheme) {
                    window.ProjectAccessTheme.set(nextTheme);
                } else {
                    applyTheme(nextTheme);
                    window.dispatchEvent(new CustomEvent('project-access-theme-changed', {
                        detail: { theme: nextTheme },
                    }));
                }
            });

            window.addEventListener('project-access-theme-changed', (event) => {
                applyTheme(event.detail.theme);
            });
        })();
    </script>
</body>
</html>
