(() => {
    let installPrompt = null;

    const ready = (callback) => document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', callback, { once: true })
        : callback();

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
    });

    ready(() => {
        const button = document.getElementById('resident-portal-install');
        const dialog = document.getElementById('resident-portal-install-dialog');
        const message = dialog?.querySelector('[data-install-message]');
        const label = button?.querySelector('[data-install-label]');

        if (!button || !dialog || !message) {
            return;
        }

        const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;

        const showInstructions = (html) => {
            message.innerHTML = html;
            dialog.showModal();
        };

        dialog.querySelectorAll('[data-install-dialog-close]').forEach((close) => {
            close.addEventListener('click', () => dialog.close());
        });

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });

        button.addEventListener('click', async () => {
            if (isStandalone) {
                window.location.assign(button.dataset.portalUrl);
                return;
            }

            if (installPrompt) {
                await installPrompt.prompt();
                const choice = await installPrompt.userChoice;
                installPrompt = null;

                if (choice.outcome === 'accepted') {
                    label.textContent = 'Installed';
                }
                return;
            }

            if (isIos) {
                showInstructions('<p>Apple requires this shortcut to be added from Safari:</p><ol class="list-decimal space-y-2 pl-5"><li>Tap <strong>Open Resident Portal</strong> below.</li><li>In Safari, tap the <strong>Share</strong> button.</li><li>Choose <strong>Add to Home Screen</strong>, then tap <strong>Add</strong>.</li></ol><p>The installed ACCESS icon opens as a standalone portal without the Safari address bar.</p>');
                return;
            }

            showInstructions('<p>Open the Resident Portal, then use your browser menu and select <strong>Install app</strong> or <strong>Add to Home screen</strong>.</p><p>Installation prompts require HTTPS. On local HTTP development domains, open the portal normally or enable local HTTPS first.</p>');
        });

        window.addEventListener('appinstalled', () => {
            installPrompt = null;
            label.textContent = 'Installed';
        });

        if ('serviceWorker' in navigator && window.isSecureContext) {
            navigator.serviceWorker.register('/resident-portal/sw.js', { scope: '/resident-portal/' });
        }
    });
})();
