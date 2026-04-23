@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[var(--brand-secondary)] text-start text-base font-medium text-[var(--brand-primary)] bg-[var(--brand-mist)] focus:outline-none focus:text-[var(--brand-primary-strong)] focus:bg-[var(--brand-mist)] focus:border-[var(--brand-primary)] transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-[var(--brand-primary)] hover:bg-[var(--brand-mist)] hover:border-[var(--brand-accent)] focus:outline-none focus:text-[var(--brand-primary)] focus:bg-[var(--brand-mist)] focus:border-[var(--brand-accent)] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
