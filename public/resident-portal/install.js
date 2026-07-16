(() => {
    let installPrompt = null;

    const device = window.ProjectAccessDevice || Object.freeze({
        platform: 'unknown',
        deviceType: 'desktop',
        isStandalone: false,
    });
    const platformNames = {
        android: 'Android',
        ios: 'iPhone or iPad',
        macos: 'Mac',
        windows: 'Windows',
        linux: 'Linux',
        unknown: 'your device',
    };

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

        document.querySelectorAll('[data-device-platform-label]').forEach((element) => {
            element.textContent = platformNames[device.platform];
        });

        if (label) {
            label.textContent = device.isStandalone ? 'Open Resident Portal' : 'Install Resident Portal';
        }

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
            if (device.isStandalone) {
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

            if (device.platform === 'ios') {
                showInstructions('<p>Apple requires this shortcut to be added from Safari:</p><ol class="list-decimal space-y-2 pl-5"><li>Tap <strong>Open Resident Portal</strong> below.</li><li>In Safari, tap the <strong>Share</strong> button.</li><li>Choose <strong>Add to Home Screen</strong>, then tap <strong>Add</strong>.</li></ol><p>The installed ACCESS icon opens as a standalone portal without the Safari address bar.</p>');
                return;
            }

            if (device.platform === 'macos') {
                showInstructions('<p>Open the Resident Portal using Safari, Chrome, or Edge.</p><ul class="list-disc space-y-2 pl-5"><li>In Safari, choose <strong>File → Add to Dock</strong>.</li><li>In Chrome or Edge, select <strong>Install app</strong> from the address bar or browser menu.</li></ul>');
                return;
            }

            if (device.platform === 'android') {
                showInstructions('<p>Open the Resident Portal in Chrome, then open the browser menu and choose <strong>Install app</strong> or <strong>Add to Home screen</strong>.</p>');
                return;
            }

            showInstructions('<p>Open the Resident Portal, then use your browser menu or address-bar install icon and select <strong>Install app</strong>.</p><p>Installation requires HTTPS and a supported browser such as Chrome or Edge.</p>');
        });

        window.addEventListener('appinstalled', () => {
            installPrompt = null;
            label.textContent = 'Open Resident Portal';
        });

        if ('serviceWorker' in navigator && window.isSecureContext) {
            navigator.serviceWorker.register('/resident-portal/sw.js', { scope: '/resident-portal/' });
        }
    });
})();
