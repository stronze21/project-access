const messages = {
    no_match: 'No enrolled resident matched. Try again, or use QR, RFID or search.',
    ambiguous: 'The scan did not identify one resident clearly. Retry or use another lookup option.',
    poor_capture: 'The finger could not be read clearly. Clean the reader, reposition the finger and retry.',
    cancelled: 'Scan cancelled. The existing enrollment was kept.',
    reader_unavailable: 'The fingerprint reader is unavailable. Check its USB connection and retry.',
    sdk_unavailable: 'Fingerprint software is not ready. Ask your administrator to complete workstation setup.',
};

window.fingerprintControl = (options) => ({
    ...options,
    busy: false,
    message: '',
    stage: 'idle',
    samples: 0,
    progressRevision: 0,
    sessionId: null,
    finger: 'right_index',
    consent: false,
    confirmRemove: false,
    run: 0,
    modalClosing: false,

    openModal(autoStart = false) {
        this.confirmRemove = false;
        if (!this.busy) {
            this.message = '';
            this.stage = 'idle';
            this.samples = 0;
        }
        this.$refs?.scanModal?.showModal();
        if (autoStart) return this.start();
    },

    async closeModal() {
        if (this.modalClosing) return;
        this.modalClosing = true;
        try {
            if (this.busy || this.sessionId) {
                this.message = 'Cancelling scan…';
                await this.cancel();
            }
            this.confirmRemove = false;
            this.consent = false;
            this.$refs?.scanModal?.close();
        } finally {
            this.modalClosing = false;
        }
    },

    get stageTitle() {
        return ({ connecting: 'Connecting', opening: 'Preparing reader', waiting: 'Place your finger', captured: 'Sample captured',
            extracting: 'Reading fingerprint', lift: 'Lift your finger', retry: 'Try that scan again', matching: 'Finding resident',
            saving: 'Confirming result', success: 'Scan complete', error: 'Scan needs attention', cancelled: 'Scan cancelled' })[this.stage] || 'Fingerprint scan';
    },

    sampleCaptured(count) {
        if (count <= this.samples) return;
        this.samples = count;
        if (!window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches) {
            this.$refs?.captureGlow?.animate?.([{ opacity: 0 }, { opacity: 0.4 }, { opacity: 0 }], { duration: 650 });
        }
    },

    async watchProgress(id, run) {
        const guidance = {
            connecting: 'Connecting securely to the server…', opening: 'Preparing the fingerprint reader…',
            waiting: this.purpose === 'enroll' ? 'Place the selected finger flat on the sensor.' : 'Place your enrolled finger flat on the sensor.',
            captured: 'Sample accepted.', extracting: 'Reading the captured fingerprint…',
            lift: 'Lift your finger, then place the same finger again.', retry: 'Reposition your finger and try again.',
            matching: 'Comparing the scan with enrolled residents…', saving: 'Waiting for the server to confirm the result…',
        };
        while (this.busy && this.sessionId === id && this.run === run) {
            try {
                const progress = await this.request(`/progress/${id}?after=${this.progressRevision}`, 'GET', null, true);
                if (!this.busy || this.sessionId !== id || this.run !== run) return;
                for (const event of progress.events || []) {
                    if (!Number.isSafeInteger(event.revision) || event.revision <= this.progressRevision) continue;
                    this.progressRevision = event.revision;
                    if (event.stage === 'captured') this.sampleCaptured(event.samples);
                    // Server polling remains authoritative for outcomes and resident selection.
                    if (guidance[event.stage]) {
                        this.stage = event.stage;
                        this.message = guidance[event.stage];
                    }
                }
            } catch {
                // Progress is optional: old services and brief local connection
                // failures must not interrupt an otherwise valid scan.
                return;
            }
            await new Promise(resolve => setTimeout(resolve, 250));
        }
    },

    async request(path, method = 'GET', body = null, local = false) {
        const headers = { Accept: 'application/json', 'Content-Type': 'application/json' };
        if (!local) {
            headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content || '';
        }
        let response;
        try {
            response = await fetch(`${local ? this.serviceUrl : this.baseUrl}${path}`, {
                method, headers, credentials: local ? 'omit' : 'same-origin',
                body: body === null ? null : JSON.stringify(body),
                signal: AbortSignal.timeout(15000),
                cache: 'no-store',
            });
        } catch {
            throw new Error(local
                ? 'Cannot reach the fingerprint service. Check that it is running and allow local device access in your browser.'
                : 'Connection lost. Check your connection and retry.');
        }
        const data = response.status === 204 ? {} : await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(data.message || 'Fingerprint request failed. Retry or use QR, RFID or search.');
        }
        return data;
    },

    async start() {
        if (this.busy || !this.enabled || (this.purpose === 'enroll' && !this.consent)) return;
        this.busy = true;
        this.stage = 'connecting';
        this.samples = 0;
        this.progressRevision = 0;
        this.confirmRemove = false;
        const run = ++this.run;
        this.message = 'Connecting to the fingerprint reader…';
        try {
            const health = await this.request('/health', 'GET', null, true);
            if (run !== this.run) return;
            if (!health.ready) throw new Error(messages.sdk_unavailable);
            const session = await this.request('/sessions', 'POST', {
                purpose: this.purpose, workstation_id: health.workstation_id,
                resident_id: this.residentId, finger_position: this.finger, consent: this.consent,
            });
            // A cancellation while the server was creating a session must not
            // leave a claimable enrollment behind.
            if (run !== this.run) {
                await this.request(`/sessions/${session.id}`, 'DELETE');
                return;
            }
            this.sessionId = session.id;
            await this.request('/capture', 'POST', { session_id: session.id }, true);
            if (run !== this.run) return;
            this.message = this.purpose === 'enroll'
                ? 'Place the selected finger on the reader. Lift and place the same finger again several times until enrollment completes.'
                : 'Place your enrolled finger on the reader.';
            this.stage = 'waiting';
            if (health.progress_supported) void this.watchProgress(session.id, run);
            const deadline = Date.now() + 180000;
            while (run === this.run && Date.now() < deadline) {
                await new Promise(resolve => setTimeout(resolve, 2000));
                if (run !== this.run) return;
                const result = await this.request(`/sessions/${session.id}`);
                if (run !== this.run) return;
                if (result.status === 'completed') {
                    if (result.outcome === 'enrolled') {
                        this.stage = 'success';
                        this.enrolled = true;
                        this.enrollmentLabel = `${this.finger.replaceAll('_', ' ')} · Updated today`;
                        this.consent = false;
                        this.message = 'Fingerprint enrolled successfully.';
                    } else if (result.outcome === 'matched') {
                        this.stage = 'success';
                        this.message = 'Resident found.';
                        this.sessionId = null;
                        if (!window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches) {
                            await new Promise(resolve => setTimeout(resolve, 450));
                            if (run !== this.run) return;
                        }
                        this.busy = false;
                        await this.$wire.fingerprintMatched(session.id);
                    } else {
                        this.stage = result.outcome === 'cancelled' ? 'cancelled' : 'error';
                        this.message = messages[result.outcome] || 'Scan failed. Please retry.';
                    }
                    this.sessionId = null;
                    return;
                }
                if (['expired', 'cancelled', 'consumed'].includes(result.status)) {
                    throw new Error('Scan expired or was cancelled. Please retry.');
                }
            }
            if (run === this.run) throw new Error('Scan timed out. Please retry.');
        } catch (error) {
            if (run === this.run) {
                const id = this.sessionId;
                this.sessionId = null;
                if (id) {
                    await Promise.allSettled([
                        this.request(`/sessions/${id}`, 'DELETE'),
                        this.request('/cancel', 'POST', { session_id: id }, true),
                    ]);
                }
                this.message = error.message;
                this.stage = 'error';
            }
        } finally {
            if (run === this.run) this.busy = false;
        }
    },

    async cancel() {
        ++this.run;
        const id = this.sessionId;
        this.sessionId = null;
        // Keep controls disabled until cancellation has been acknowledged.
        if (id) {
            await Promise.allSettled([
                this.request(`/sessions/${id}`, 'DELETE'),
                this.request('/cancel', 'POST', { session_id: id }, true),
            ]);
        }
        this.busy = false;
        this.stage = 'cancelled';
        this.message = 'Scan cancelled.';
    },

    async remove() {
        this.busy = true;
        this.stage = 'saving';
        this.message = 'Removing fingerprint enrollment…';
        try {
            await this.request(`/residents/${this.residentId}`, 'DELETE');
            this.enrolled = false;
            this.consent = false;
            this.confirmRemove = false;
            this.message = 'Fingerprint removed.';
            this.stage = 'success';
        } catch (error) {
            this.message = error.message;
            this.stage = 'error';
        } finally {
            this.busy = false;
        }
    },

    destroy() {
        if (this.sessionId || this.busy) this.cancel();
    },
});
