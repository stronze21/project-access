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
            login.querySelector('form')?.addEventListener('submit', (event) => {
                if (value.length !== 6) { event.preventDefault(); boxes[0]?.focus(); }
            });
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
