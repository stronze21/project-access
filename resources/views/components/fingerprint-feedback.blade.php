@props(['controls' => true])
<div class="fingerprint-feedback" x-show="busy || message" x-cloak
    :data-stage="stage" :aria-busy="busy ? 'true' : 'false'">
    <div class="flex items-start gap-4">
        <div class="fingerprint-sensor" aria-hidden="true">
            <x-mary-icon name="o-finger-print" class="fingerprint-mark" x-show="stage !== 'success' && stage !== 'error'" />
            <x-mary-icon name="o-check-circle" class="fingerprint-mark" x-show="stage === 'success'" />
            <x-mary-icon name="o-exclamation-circle" class="fingerprint-mark" x-show="stage === 'error'" />
            <div class="fingerprint-sweep" x-show="busy && ['waiting', 'extracting', 'matching'].includes(stage)"></div>
            <div class="fingerprint-capture-glow" x-ref="captureGlow"></div>
        </div>
        <div class="flex-1 min-w-0" role="status" aria-live="polite" aria-atomic="true">
            <p class="text-sm font-semibold" x-text="stageTitle"></p>
            <p class="mt-1 text-sm leading-relaxed break-words" x-text="message"></p>
            <p class="mt-2 text-xs text-base-content/70" x-show="samples > 0 && purpose === 'enroll'"
                x-text="samples === 1 ? '1 sample captured' : `${samples} samples captured`"></p>
        </div>
    </div>
    @if ($controls)
    <div class="flex justify-end gap-2 mt-3">
        <button type="button" class="btn btn-sm btn-ghost" x-show="busy && stage !== 'success'" @click="cancel()">Cancel scan</button>
        <button type="button" class="btn btn-sm btn-ghost" x-show="!busy" @click="message = ''; stage = 'idle'">Dismiss</button>
    </div>
    @endif
</div>
