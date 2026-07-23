(() => {
    const ready = (fn) => document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn();

    ready(() => {
        const login = document.querySelector('[data-mpin-login]');
        if (login) {
            const identifier = login.querySelector('[data-identifier-panel]');
            const unlock = login.querySelector('[data-mpin-panel]');
            const loginInput = login.querySelector('#login');
            const valueInput = login.querySelector('[data-mpin-value]');
            const boxes = [...login.querySelectorAll('[data-mpin-box]')];
            const loader = login.querySelector('[data-login-loader]');
            const submit = login.querySelector('[data-login-submit]');
            let value = '';

            const paint = () => {
                valueInput.value = value;
                boxes.forEach((box, index) => box.classList.toggle('filled', index < value.length));
            };
            login.querySelector('[data-mpin-continue]')?.addEventListener('click', () => {
                if (!loginInput.value.trim()) { loginInput.focus(); return; }
                identifier.hidden = true;
                unlock.hidden = false;
            });
            login.querySelector('[data-mpin-back]')?.addEventListener('click', () => {
                unlock.hidden = true;
                identifier.hidden = false;
                value = ''; paint(); loginInput.focus();
            });
            login.querySelectorAll('[data-mpin-digit]').forEach((button) => button.addEventListener('click', () => {
                if (value.length < 6) value += button.dataset.mpinDigit;
                paint();
            }));
            login.querySelector('[data-mpin-delete]')?.addEventListener('click', () => { value = value.slice(0, -1); paint(); });
            login.querySelector('[data-mpin-clear]')?.addEventListener('click', () => { value = ''; paint(); });
            login.querySelector('form')?.addEventListener('submit', async (event) => {
                if (value.length !== 6) { event.preventDefault(); boxes[0]?.focus(); return; }
                event.preventDefault();
                const form = event.currentTarget;
                const formData = new FormData(form);
                loader.hidden = false;
                submit.disabled = true;
                login.querySelectorAll('button,input').forEach((control) => { control.disabled = true; });
                const controller = new AbortController();
                const timeout = window.setTimeout(() => controller.abort(), 25000);
                try {
                    const response = await fetch(form.action, {
                        method: 'POST', body: formData, signal: controller.signal,
                        credentials: 'same-origin',
                    });
                    window.location.assign(response.url || form.action);
                } catch (error) {
                    loader.hidden = true;
                    login.querySelectorAll('button,input').forEach((control) => { control.disabled = false; });
                    submit.disabled = false;
                    let alert = login.querySelector('[data-login-timeout-error]');
                    if (!alert) {
                        alert = document.createElement('div');
                        alert.dataset.loginTimeoutError = '';
                        alert.className = 'portal-alert danger';
                        alert.setAttribute('role', 'alert');
                        unlock.prepend(alert);
                    }
                    alert.textContent = error.name === 'AbortError'
                        ? 'The server is taking too long to respond. Please try again.'
                        : 'Sign in could not be completed. Check your connection and try again.';
                } finally {
                    window.clearTimeout(timeout);
                }
            });
        }

        const activationForm = document.querySelector('[data-activation-form]');
        if (activationForm) {
            const submit = activationForm.querySelector('[data-activation-submit]');
            const checkboxes = [...activationForm.querySelectorAll('[data-legal-checkbox]')];
            const updateSubmit = () => {
                submit.disabled = submit.dataset.emailReady !== '1'
                    || !checkboxes.every((checkbox) => !checkbox.disabled && checkbox.checked);
            };

            checkboxes.forEach((checkbox) => {
                checkbox.disabled = true;
                checkbox.checked = false;
                checkbox.addEventListener('change', updateSubmit);
            });

            document.querySelectorAll('[data-legal-dialog]').forEach((dialog) => {
                const key = dialog.dataset.legalDialog;
                const checkbox = activationForm.querySelector(`[data-legal-checkbox="${key}"]`);
                const hint = activationForm.querySelector(`[data-legal-hint="${key}"]`);
                const done = dialog.querySelector('[data-legal-done]');
                const footer = dialog.querySelector('.legal-modal-footer');
                const status = dialog.querySelector('[data-legal-status]');
                const frame = dialog.querySelector('[data-legal-frame]');
                const scroller = dialog.querySelector('[data-legal-scroll]');
                let completed = false;

                const unlock = () => {
                    if (completed) return;
                    completed = true;
                    checkbox.disabled = false;
                    checkbox.closest('.auth-consent')?.classList.add('is-unlocked');
                    footer.classList.add('is-complete');
                    done.disabled = false;
                    done.textContent = 'Done';
                    updateSubmit();
                };

                const atBottom = (scrollTop, viewportHeight, contentHeight) =>
                    contentHeight - (scrollTop + viewportHeight) <= 6;

                const checkInlineScroll = () => {
                    if (atBottom(scroller.scrollTop, scroller.clientHeight, scroller.scrollHeight)) unlock();
                };

                if (scroller) scroller.addEventListener('scroll', checkInlineScroll, { passive: true });
                if (frame) frame.addEventListener('load', () => {
                    const frameWindow = frame.contentWindow;
                    const frameDocument = frame.contentDocument;
                    if (!frameWindow || !frameDocument) return;
                    const checkFrameScroll = () => {
                        const root = frameDocument.documentElement;
                        const body = frameDocument.body;
                        const top = frameWindow.scrollY || root.scrollTop || body.scrollTop;
                        const height = Math.max(root.scrollHeight, body.scrollHeight);
                        if (atBottom(top, frameWindow.innerHeight, height)) unlock();
                    };
                    frameWindow.addEventListener('scroll', checkFrameScroll, { passive: true });
                    frameDocument.addEventListener('click', (event) => {
                        if (event.target.closest('a')) event.preventDefault();
                    });
                    requestAnimationFrame(checkFrameScroll);
                });

                document.querySelectorAll(`[data-legal-open="${key}"]`).forEach((trigger) => {
                    trigger.addEventListener('click', () => {
                        if (frame && !frame.getAttribute('src')) frame.src = frame.dataset.src;
                        dialog.showModal();
                        if (scroller) requestAnimationFrame(checkInlineScroll);
                    });
                });
                dialog.querySelector('[data-legal-close]').addEventListener('click', () => dialog.close());
                done.addEventListener('click', () => dialog.close());
                dialog.addEventListener('click', (event) => {
                    if (event.target === dialog) dialog.close();
                });
            });

            updateSubmit();
        }

        document.querySelectorAll('[data-get-location]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!navigator.geolocation) { button.textContent = 'Location is not supported'; return; }
                const form = button.closest('form');
                button.disabled = true;
                button.innerHTML = '<span class="material-symbols-rounded">progress_activity</span> Getting location…';
                navigator.geolocation.getCurrentPosition((position) => {
                    form.querySelector('[data-latitude]').value = position.coords.latitude.toFixed(7);
                    form.querySelector('[data-longitude]').value = position.coords.longitude.toFixed(7);
                    const label = form.querySelector('[data-location-label]');
                    if (label && !label.value) label.value = `${position.coords.latitude.toFixed(5)}, ${position.coords.longitude.toFixed(5)}`;
                    button.innerHTML = '<span class="material-symbols-rounded">location_on</span> Location attached';
                    button.disabled = false;
                }, () => {
                    button.textContent = 'Allow location access or enter it manually';
                    button.disabled = false;
                }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 });
            });
        });

        document.querySelectorAll('[data-flip-card]').forEach((card) => {
            const hint = card.parentElement?.querySelector('[data-flip-hint]');
            card.addEventListener('click', () => {
                const isFlipped = card.classList.toggle('is-flipped');
                card.setAttribute('aria-pressed', String(isFlipped));
                card.setAttribute('aria-label', `Show the ${isFlipped ? 'front' : 'back'} of the resident ID card`);
                if (hint) {
                    hint.lastChild.textContent = ` Tap the card to view its ${isFlipped ? 'front' : 'back'}`;
                }
            });
        });

        document.querySelectorAll('[data-resident-photo]').forEach((photo) => {
            photo.addEventListener('error', () => {
                const fallback = document.createElement('span');
                fallback.className = 'portal-id-photo placeholder material-symbols-rounded filled';
                fallback.textContent = 'person';
                fallback.setAttribute('aria-label', 'Resident photo unavailable');
                photo.replaceWith(fallback);
            }, { once: true });
        });

        document.querySelectorAll('[data-signature-form]').forEach((form) => {
            const canvas = form.querySelector('[data-signature-pad]');
            const context = canvas.getContext('2d');
            const hidden = form.querySelector('[data-signature-value]');
            let drawing = false;
            let hasInk = false;
            context.lineWidth = 2.2;
            context.lineCap = 'round';
            context.strokeStyle = '#0f172a';
            const point = (event) => {
                const rect = canvas.getBoundingClientRect();
                return { x: (event.clientX - rect.left) * canvas.width / rect.width, y: (event.clientY - rect.top) * canvas.height / rect.height };
            };
            canvas.addEventListener('pointerdown', (event) => { event.preventDefault(); drawing = true; const p = point(event); context.beginPath(); context.moveTo(p.x, p.y); });
            canvas.addEventListener('pointermove', (event) => { if (!drawing) return; event.preventDefault(); const p = point(event); context.lineTo(p.x, p.y); context.stroke(); hasInk = true; });
            ['pointerup', 'pointerleave', 'pointercancel'].forEach((name) => canvas.addEventListener(name, () => { drawing = false; }));
            form.querySelector('[data-signature-clear]').addEventListener('click', () => { context.clearRect(0, 0, canvas.width, canvas.height); hasInk = false; hidden.value = ''; });
            form.addEventListener('submit', (event) => { if (!hasInk) { event.preventDefault(); return; } hidden.value = canvas.toDataURL('image/png'); });
        });
    });
})();
function showConnectivityStatus() {
    let banner = document.querySelector('[data-connectivity-status]');
    if (!banner) {
        banner = document.createElement('div');
        banner.dataset.connectivityStatus = '';
        banner.className = 'connectivity-banner';
        banner.setAttribute('role', 'status');
        document.body.prepend(banner);
    }
    banner.textContent = navigator.onLine ? 'Back online' : 'You are offline. Online actions will be unavailable.';
    banner.classList.toggle('is-offline', !navigator.onLine);
    banner.classList.toggle('is-online', navigator.onLine);
    if (navigator.onLine) window.setTimeout(() => banner.classList.remove('is-online'), 2500);
}

window.addEventListener('online', showConnectivityStatus);
window.addEventListener('offline', showConnectivityStatus);
document.addEventListener('DOMContentLoaded', () => {
    if (!navigator.onLine) showConnectivityStatus();
    document.querySelectorAll('form[action$="/logout"]').forEach((form) => form.addEventListener('submit', () => {
        navigator.serviceWorker?.controller?.postMessage({ type: 'CLEAR_RESIDENT_CACHES' });
    }));
});

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/resident-portal/sw.js').then((registration) => {
        registration.addEventListener('updatefound', () => {
            const worker = registration.installing;
            worker?.addEventListener('statechange', () => {
                if (worker.state === 'installed' && navigator.serviceWorker.controller
                    && window.confirm('A new ACCESS version is available. Reload now?')) {
                    worker.postMessage({ type: 'SKIP_WAITING' });
                }
            });
        });
    });
    navigator.serviceWorker.addEventListener('controllerchange', () => window.location.reload());
}
