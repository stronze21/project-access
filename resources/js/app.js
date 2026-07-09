import './bootstrap';
import './sigweb';
import './topaz-integration';
import { Html5Qrcode } from "html5-qrcode";
window.Html5Qrcode = Html5Qrcode;

(() => {
    const root = document.documentElement;
    const storageKey = 'project-access-theme';
    const legacyKey = 'theme';
    let applying = false;

    const readStoredTheme = () => {
        try {
            return localStorage.getItem(storageKey) || localStorage.getItem(legacyKey);
        } catch {
            return null;
        }
    };

    const writeStoredTheme = (theme) => {
        try {
            localStorage.setItem(storageKey, theme);
            localStorage.setItem(legacyKey, theme);
        } catch {
            // Browsers can block storage in private or restricted contexts.
        }
    };

    const preferredTheme = () => {
        const storedTheme = readStoredTheme();

        if (storedTheme === 'dark' || storedTheme === 'light') {
            return storedTheme;
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    };

    const applyTheme = (theme = preferredTheme()) => {
        const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
        const dark = normalizedTheme === 'dark';

        applying = true;
        root.classList.toggle('dark', dark);
        root.dataset.theme = dark ? 'aces-dark' : 'aces';
        root.style.colorScheme = normalizedTheme;
        requestAnimationFrame(() => {
            applying = false;
        });

        return normalizedTheme;
    };

    const setTheme = (theme) => {
        const normalizedTheme = theme === 'dark' ? 'dark' : 'light';

        writeStoredTheme(normalizedTheme);
        applyTheme(normalizedTheme);
        window.dispatchEvent(new CustomEvent('project-access-theme-changed', {
            detail: { theme: normalizedTheme },
        }));

        return normalizedTheme;
    };

    window.ProjectAccessTheme = {
        apply: applyTheme,
        set: setTheme,
        toggle: () => setTheme(root.classList.contains('dark') ? 'light' : 'dark'),
        current: () => root.classList.contains('dark') ? 'dark' : 'light',
        isDark: () => root.classList.contains('dark'),
    };

    applyTheme();

    ['DOMContentLoaded', 'pageshow', 'livewire:navigated', 'turbo:load'].forEach((eventName) => {
        window.addEventListener(eventName, () => applyTheme());
    });

    if ('MutationObserver' in window) {
        new MutationObserver(() => {
            if (applying) {
                return;
            }

            const expectedTheme = preferredTheme();
            const expectedDark = expectedTheme === 'dark';
            const expectedDaisyTheme = expectedDark ? 'aces-dark' : 'aces';

            if (root.classList.contains('dark') !== expectedDark || root.dataset.theme !== expectedDaisyTheme) {
                applyTheme(expectedTheme);
            }
        }).observe(root, { attributes: true, attributeFilter: ['class', 'data-theme'] });
    }
})();
