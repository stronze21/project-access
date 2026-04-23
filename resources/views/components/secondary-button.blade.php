<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 rounded-md border border-slate-200 bg-white font-semibold text-xs uppercase tracking-widest text-slate-700 shadow-sm transition ease-in-out duration-150 hover:border-[var(--brand-secondary)] hover:bg-[var(--brand-mist)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-secondary)] focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
