<script>
    (() => {
        const storageKey = 'project-access-theme';
        const legacyKey = 'theme';
        const storedTheme = localStorage.getItem(storageKey) || localStorage.getItem(legacyKey);
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const dark = storedTheme ? storedTheme === 'dark' : prefersDark;

        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.dataset.theme = dark ? 'aces-dark' : 'aces';
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    })();
</script>
