<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-password-input id="password" class="block mt-1 w-full" name="password" required autocomplete="current-password" />
            </div>

            <div class="block mt-4">
                <label for="remember_me" class="flex items-center">
                    <x-checkbox id="remember_me" name="remember" />
                    <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                @if (Route::has('password.request'))
                    <a class="brand-link underline text-sm text-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-[var(--brand-secondary)] focus:ring-offset-2" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <x-button class="ms-4">
                    {{ __('Log in') }}
                </x-button>
            </div>
        </form>

        <div class="my-5 flex items-center gap-3" aria-hidden="true">
            <span class="h-px flex-1 bg-slate-200"></span>
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Public access</span>
            <span class="h-px flex-1 bg-slate-200"></span>
        </div>

        <a
            href="{{ route('mobile-app.index') }}"
            class="inline-flex w-full items-center justify-center rounded-md border border-[var(--brand-primary)] bg-white px-4 py-2.5 text-sm font-semibold text-[var(--brand-primary)] shadow-sm transition hover:bg-[var(--brand-mist)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-secondary)] focus:ring-offset-2"
        >
            <svg class="mr-2 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <rect x="6" y="2.5" width="12" height="19" rx="2.5" stroke-width="1.8" />
                <path d="M10 5h4M10.5 18.5h3" stroke-width="1.8" stroke-linecap="round" />
            </svg>
            Mobile App
        </a>
    </x-authentication-card>
</x-guest-layout>
