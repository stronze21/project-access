@props(['disabled' => false])

<div x-data="{ visible: false }" class="relative">
    <input
        {{ $disabled ? 'disabled' : '' }}
        x-bind:type="visible ? 'text' : 'password'"
        {!! $attributes->except('type')->merge(['class' => 'rounded-md border-gray-300 pr-10 shadow-sm focus:border-[var(--brand-secondary)] focus:ring-[var(--brand-secondary)]']) !!}
    >

    <button
        type="button"
        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-[var(--brand-primary)] focus:outline-none"
        x-on:click="visible = ! visible"
        x-bind:aria-label="visible ? 'Hide password' : 'Show password'"
        data-password-toggle
    >
        <svg x-show="!visible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <svg x-show="visible" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.6 5.4A10.7 10.7 0 0 1 12 5.25c6 0 9.75 6.75 9.75 6.75a17.6 17.6 0 0 1-3.05 3.82M6.35 6.35A17.7 17.7 0 0 0 2.25 12s3.75 6.75 9.75 6.75a10.6 10.6 0 0 0 4.26-.9" />
        </svg>
    </button>
</div>
