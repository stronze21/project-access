@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[var(--brand-secondary)] text-sm font-medium leading-5 text-[var(--brand-ink)] focus:outline-none focus:border-[var(--brand-primary)] transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-[var(--brand-primary)] hover:border-[var(--brand-accent)] focus:outline-none focus:text-[var(--brand-primary)] focus:border-[var(--brand-accent)] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
