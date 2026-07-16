<x-guest-layout>
    <div class="relative min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:flex lg:items-center lg:px-8 lg:py-8">
        <div class="pointer-events-none absolute -left-32 -top-32 h-80 w-80 rounded-full bg-[var(--brand-secondary)]/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 -right-24 h-96 w-96 rounded-full bg-[var(--brand-primary)]/15 blur-3xl"></div>

        <main class="relative mx-auto grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-white/80 bg-white/95 shadow-2xl shadow-slate-300/45 backdrop-blur dark:border-slate-700/80 dark:bg-slate-900/95 dark:shadow-slate-950/60 lg:min-h-[720px] lg:grid-cols-[1.08fr_.92fr]">
            <section class="relative hidden overflow-hidden bg-[#174f7a] p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="pointer-events-none absolute inset-0 opacity-30" aria-hidden="true">
                    <div class="absolute -right-32 -top-28 h-96 w-96 rounded-full border-[70px] border-[#28aaa7]"></div>
                    <div class="absolute -bottom-40 -left-32 h-96 w-96 rounded-full border-[64px] border-white/20"></div>
                    <svg class="absolute bottom-0 right-0 h-80 w-80 text-white/10" viewBox="0 0 320 320" fill="none">
                        <path d="M20 270 156 38l144 232H20Z" stroke="currentColor" stroke-width="2" />
                        <path d="m75 270 81-139 86 139H75Z" stroke="currentColor" stroke-width="2" />
                    </svg>
                </div>

                <div class="relative">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-lg shadow-slate-950/10">
                        <img src="{{ asset('logo1.png') }}" alt="ACCESS" class="h-14 w-14 rounded-xl object-contain">
                        <span class="pr-2">
                            <span class="block text-sm font-extrabold tracking-wide text-[#174f7a]">ALAMINOS CITY</span>
                            <span class="block text-[10px] font-bold uppercase tracking-[.22em] text-[#168f8d]">E-Services Solutions</span>
                        </span>
                    </a>
                </div>

                <div class="relative max-w-xl py-12">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[.18em] text-teal-100 backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-[#5ce0ce] shadow-[0_0_0_5px_rgba(92,224,206,.12)]"></span>
                        Authorized personnel portal
                    </div>
                    <h1 class="text-5xl font-extrabold leading-[1.06] tracking-[-.045em]">Alaminos City Citizen's<br><span class="text-[#6de1d2]">E-Services Solution</span></h1>
                    <p class="mt-6 max-w-lg text-base leading-7 text-blue-50/80">A secure workspace for Alaminos City teams to coordinate resident records, assistance programs, reports, and essential public services.</p>

                    <div class="mt-10 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                            <svg class="h-6 w-6 text-[#6de1d2]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-1 2 2 4-4" /></svg>
                            <p class="mt-3 text-sm font-bold">Resident-centered</p>
                            <p class="mt-1 text-xs leading-5 text-blue-100/65">One coordinated service record.</p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                            <svg class="h-6 w-6 text-[#f4c47f]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Zm-3-10 2 2 4-4" /></svg>
                            <p class="mt-3 text-sm font-bold">Protected access</p>
                            <p class="mt-1 text-xs leading-5 text-blue-100/65">Role-based and accountable.</p>
                        </div>
                    </div>
                </div>

                <p class="relative text-xs font-medium text-blue-100/55">City Government of Alaminos · Pangasinan, Philippines</p>
            </section>

            <section class="flex min-h-[calc(100vh-3rem)] items-center px-6 py-12 sm:px-12 lg:min-h-0 lg:px-14 lg:py-16">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-10 flex items-center gap-3 lg:hidden">
                        <img src="{{ asset('logo1.png') }}" alt="ACCESS" class="h-14 w-14 rounded-2xl object-contain shadow-md">
                        <div>
                            <p class="font-extrabold tracking-wide text-[#174f7a] dark:text-sky-300">ALAMINOS CITY ACCESS</p>
                            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#168f8d] dark:text-teal-300">E-Services Solutions</p>
                        </div>
                    </div>

                    <p class="text-xs font-extrabold uppercase tracking-[.2em] text-[var(--brand-secondary-strong)]">Staff portal</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-4xl">Welcome back</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-300">Sign in with your authorized city account to continue.</p>

                    <x-validation-errors class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm dark:border-red-900/70 dark:bg-red-950/35" />

                    @session('status')
                        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/35 dark:text-emerald-300">
                            {{ $value }}
                        </div>
                    @endsession

                    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                        @csrf

                        <div>
                            <x-label for="email" value="{{ __('Email address') }}" class="text-sm font-bold text-slate-700 dark:text-slate-200" />
                            <x-input id="email" class="mt-2 block h-12 w-full rounded-xl border-slate-300 bg-white px-4 text-base dark:border-slate-600 dark:bg-slate-800 dark:text-white" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@alaminoscity.gov.ph" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-4">
                                <x-label for="password" value="{{ __('Password') }}" class="text-sm font-bold text-slate-700 dark:text-slate-200" />
                                @if (Route::has('password.request'))
                                    <a class="brand-link rounded text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[var(--brand-secondary)] focus:ring-offset-2 dark:focus:ring-offset-slate-900" href="{{ route('password.request') }}">Forgot password?</a>
                                @endif
                            </div>
                            <x-password-input id="password" class="mt-2 block h-12 w-full rounded-xl border-slate-300 bg-white px-4 text-base dark:border-slate-600 dark:bg-slate-800 dark:text-white" name="password" required autocomplete="current-password" placeholder="Enter your password" />
                        </div>

                        <label for="remember_me" class="flex w-fit cursor-pointer items-center gap-2.5 text-sm text-slate-600 dark:text-slate-300">
                            <x-checkbox id="remember_me" name="remember" class="h-4 w-4" />
                            <span>{{ __('Keep me signed in on this device') }}</span>
                        </label>

                        <x-button class="flex h-12 w-full justify-center rounded-xl text-sm shadow-lg shadow-blue-900/15">
                            {{ __('Sign in securely') }}
                            <svg class="ml-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" /></svg>
                        </x-button>
                    </form>

                    <div class="my-8 flex items-center gap-4" aria-hidden="true">
                        <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
                        <span class="text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-400">Resident access</span>
                        <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
                    </div>

                    <a href="{{ route('mobile-app.index') }}" class="group flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-left transition hover:border-[var(--brand-secondary)] hover:bg-[var(--brand-mist)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-secondary)] focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-800/70 dark:focus:ring-offset-slate-900">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#e4f4f2] text-[#168f8d] dark:bg-teal-400/10 dark:text-teal-300">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="6" y="2.5" width="12" height="19" rx="2.5" stroke-width="1.8" /><path d="M10 5h4M10.5 18.5h3" stroke-width="1.8" stroke-linecap="round" /></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <strong class="block text-sm text-slate-800 dark:text-white">Resident Mobile App</strong>
                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Download or open citizen services</span>
                        </span>
                        <svg class="h-4 w-4 text-slate-400 transition group-hover:translate-x-1 group-hover:text-[var(--brand-primary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" /></svg>
                    </a>

                    <p class="mt-8 text-center text-xs leading-5 text-slate-400">Authorized use only. Activity may be logged for security and accountability.</p>
                </div>
            </section>
        </main>
    </div>
</x-guest-layout>
