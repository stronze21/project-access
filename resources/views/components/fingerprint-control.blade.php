@props(['purpose' => 'registry', 'residentId' => null, 'inForm' => false])
@php
    $enabled = (bool) config('biometrics.enabled');
    $fingerprint = $enabled && $residentId
        ? \App\Models\ResidentFingerprint::where('resident_id', $residentId)->first(['id', 'finger_position', 'updated_at'])
        : null;
@endphp
<div @class(['min-w-0 max-w-full', 'w-full' => $purpose === 'enroll', 'inline-flex flex-col items-start' => $purpose !== 'enroll']) x-data="fingerprintControl({
    enabled: @js($enabled), purpose: @js($purpose), residentId: @js($residentId),
    enrolled: @js((bool) $fingerprint), enrollmentLabel: @js($fingerprint ? ucfirst(str_replace('_', ' ', $fingerprint->finger_position)).' · Updated '.$fingerprint->updated_at->format('M j, Y') : ''),
    serviceUrl: @js(config('biometrics.service_url')),
    baseUrl: @js(url('/biometrics'))
})">
    @if ($purpose === 'enroll')
        <section class="overflow-hidden border rounded-xl border-base-300 bg-base-100" aria-label="Resident fingerprint">
            <div class="flex items-center gap-3 px-5 pt-5">
                <span class="flex items-center justify-center w-10 h-10 rounded-lg shrink-0 bg-primary/10 text-primary" aria-hidden="true">
                    <x-mary-icon name="o-finger-print" class="w-6 h-6" />
                </span>
                <h2 class="flex-1 min-w-0 font-semibold">Resident Biometrics</h2>
                <span class="text-xs text-base-content/70">Optional</span>
            </div>
            <div class="p-5 pt-3 space-y-4">
            @if (! $residentId)
                <p class="text-sm leading-relaxed">Fingerprint enrollment is optional. Save the resident first, then enroll one finger on the Resident Details page.</p>
                <button type="submit" class="w-full py-2 btn btn-outline min-h-11 h-auto sm:w-auto" wire:loading.attr="disabled" wire:target="save">Save resident first</button>
                @if (! $enabled)
                    <p class="text-sm leading-relaxed text-base-content/70">Fingerprint setup is pending. You can still register the resident.</p>
                @endif
            @else
            <div class="space-y-1">
                <p class="text-sm leading-relaxed" x-text="enrolled ? 'One finger enrolled. You can replace or remove it.' : 'Enroll one finger to find this resident by fingerprint.'"></p>
                <p x-show="enrolled" x-cloak class="text-sm leading-relaxed text-base-content/70" x-text="enrollmentLabel"></p>
                @if ($inForm)
                    <p class="text-sm leading-relaxed text-base-content/70">Fingerprint changes are saved separately from this form.</p>
                @endif
            </div>
            @if ($enabled)
                <label class="flex items-start gap-3 p-3 text-sm leading-relaxed rounded-lg bg-base-200/50">
                    <input type="checkbox" class="mt-0.5 checkbox checkbox-sm shrink-0" x-model="consent" :disabled="busy">
                    <span class="flex-1 min-w-0">The resident agrees to storing their fingerprint template for resident lookup and understands enrollment is optional.</span>
                </label>
                <div class="flex flex-wrap items-end gap-3">
                    <label class="flex-1 min-w-0 basis-40">
                        <span class="block mb-2 text-sm font-medium">Finger to enroll</span>
                        <select x-model="finger" :disabled="busy" class="w-full min-w-0 select select-bordered min-h-11 h-11">
                            @foreach (['right_index', 'right_thumb', 'right_middle', 'right_ring', 'right_little', 'left_index', 'left_thumb', 'left_middle', 'left_ring', 'left_little'] as $position)
                                <option value="{{ $position }}">{{ ucfirst(str_replace('_', ' ', $position)) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="button" class="w-full py-2 btn btn-primary min-h-11 h-auto sm:w-auto" :disabled="busy || !consent" @click="start()" x-text="enrolled ? 'Replace fingerprint' : 'Enroll fingerprint'"></button>
                </div>
                <button type="button" class="w-full py-2 btn btn-ghost text-error min-h-11 h-auto sm:w-auto" x-show="enrolled" x-cloak :disabled="busy" @click="confirmRemove = true">Remove fingerprint</button>
                <div x-show="confirmRemove" x-cloak class="space-y-3">
                    <p class="text-sm leading-relaxed">Remove this enrollment? QR, RFID and search will still work.</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="py-2 btn btn-error min-h-11 h-auto" :disabled="busy" @click="remove()">Remove</button>
                        <button type="button" class="py-2 btn btn-ghost min-h-11 h-auto" @click="confirmRemove = false">Keep fingerprint</button>
                    </div>
                </div>
            @endif
            @if (! $enabled)
                <p class="text-sm leading-relaxed text-base-content/70">Fingerprint setup is pending. QR, RFID and search remain available.</p>
            @endif
            @endif
            <div x-show="message || busy" x-cloak class="space-y-2">
                <p class="text-sm leading-relaxed break-words" role="status" aria-live="polite" x-text="message"></p>
                <button type="button" class="py-2 btn btn-ghost min-h-11 h-auto" x-show="busy" @click="cancel()">Cancel scan</button>
            </div>
            </div>
        </section>
    @else
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="py-2 btn btn-outline min-h-11 h-auto" :disabled="busy || !enabled" @click="start()"
                @if (! $enabled) title="Fingerprint setup is pending" @endif>
                <x-mary-icon name="o-finger-print" class="w-5 h-5 shrink-0" />
                <span>Find by fingerprint</span>
            </button>
            <button type="button" class="py-2 btn btn-ghost min-h-11 h-auto" x-show="busy" x-cloak @click="cancel()">Cancel scan</button>
        </div>
        <p x-show="message" x-cloak class="mt-2 text-sm leading-relaxed break-words max-w-prose" role="status" aria-live="polite" x-text="message"></p>
    @endif
</div>
