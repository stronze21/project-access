<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 rounded-md border border-transparent bg-[var(--brand-primary)] font-semibold text-xs uppercase tracking-widest text-white transition ease-in-out duration-150 hover:bg-[var(--brand-primary-strong)] focus:bg-[var(--brand-primary-strong)] active:bg-[var(--brand-ink)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-secondary)] focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
