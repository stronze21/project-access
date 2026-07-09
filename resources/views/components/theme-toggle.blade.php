<button
    type="button"
    x-data="{
        dark: document.documentElement.classList.contains('dark'),
        init() {
            this.dark = window.ProjectAccessTheme
                ? window.ProjectAccessTheme.isDark()
                : document.documentElement.classList.contains('dark');

            window.addEventListener('project-access-theme-changed', (event) => {
                this.dark = event.detail.theme === 'dark';
            });
        },
        setTheme(value) {
            this.dark = value;

            if (window.ProjectAccessTheme) {
                window.ProjectAccessTheme.set(value ? 'dark' : 'light');
                return;
            }

            const theme = value ? 'dark' : 'light';
            document.documentElement.classList.toggle('dark', value);
            document.documentElement.dataset.theme = value ? 'aces-dark' : 'aces';
            document.documentElement.style.colorScheme = theme;
            localStorage.setItem('project-access-theme', theme);
            localStorage.setItem('theme', theme);
            window.dispatchEvent(new CustomEvent('project-access-theme-changed', {
                detail: { theme }
            }));
        }
    }"
    x-on:click="setTheme(!dark)"
    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-[var(--brand-secondary)] hover:text-[var(--brand-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-secondary)] focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:ring-offset-slate-950"
    x-bind:aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
>
    <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
    </svg>
    <svg x-show="dark" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.5 6.5 0 0 0 9.8 9.8Z" />
    </svg>
</button>
