@props(['purpose' => 'registry', 'residentId' => null, 'inForm' => false])
@php
    $enabled = (bool) config('biometrics.enabled');
    $fingerprint = $enabled && $residentId
        ? \App\Models\ResidentFingerprint::where('resident_id', $residentId)->first(['id', 'finger_position', 'updated_at'])
        : null;
@endphp
<div @class(['min-w-0 max-w-full', 'w-full' => $purpose === 'enroll', 'inline-flex shrink-0' => $purpose !== 'enroll'])
    x-id="['fingerprint-dialog-title']" x-data="fingerprintControl({
        enabled: @js($enabled), purpose: @js($purpose), residentId: @js($residentId),
        enrolled: @js((bool) $fingerprint), enrollmentLabel: @js($fingerprint ? ucfirst(str_replace('_', ' ', $fingerprint->finger_position)).' · Updated '.$fingerprint->updated_at->format('M j, Y') : ''),
        serviceUrl: @js(config('biometrics.service_url')), baseUrl: @js(url('/biometrics'))
    })">
    @if ($purpose === 'enroll')
        <section class="p-5 space-y-4 border rounded-xl border-base-300 bg-base-100" aria-label="Resident fingerprint">
            <div class="flex items-center gap-3">
                <span class="flex items-center justify-center w-10 h-10 rounded-lg shrink-0 bg-primary/10 text-primary" aria-hidden="true">
                    <x-mary-icon name="o-finger-print" class="w-6 h-6" />
                </span>
                <h2 class="flex-1 min-w-0 font-semibold">Resident Biometrics</h2>
                <span class="text-xs text-base-content/70">Optional</span>
            </div>
            @if (! $residentId)
                <p class="text-sm leading-relaxed">Fingerprint enrollment is optional. Save the resident first, then enroll one finger on the Resident Details page.</p>
                <button type="submit" class="w-full btn btn-outline min-h-11" wire:loading.attr="disabled" wire:target="save">Save resident first</button>
                @if (! $enabled)
                    <p class="text-sm text-base-content/70">Fingerprint setup is pending. You can still register the resident.</p>
                @endif
            @else
                <div class="space-y-1">
                    <p class="text-sm leading-relaxed" x-text="enrolled ? 'One finger enrolled. You can replace or remove it.' : 'Enroll one finger to find this resident by fingerprint.'"></p>
                    <p x-show="enrolled" x-cloak class="text-sm leading-relaxed text-base-content/70" x-text="enrollmentLabel"></p>
                </div>
                <button type="button" class="w-full btn btn-outline min-h-11" :disabled="!enabled || busy"
                    @click="openModal()" x-text="enrolled ? 'Manage fingerprint' : 'Enroll fingerprint'"></button>
                @if (! $enabled)
                    <p class="text-sm leading-relaxed text-base-content/70">Fingerprint setup is pending. QR, RFID and search remain available.</p>
                @endif
            @endif
        </section>
    @else
        <button type="button" class="btn btn-outline min-h-11" :disabled="busy || !enabled" @click="openModal(true)"
            @if (! $enabled) title="Fingerprint setup is pending" @endif>
            <x-mary-icon name="o-finger-print" class="w-5 h-5 shrink-0" />
            <span>Find by fingerprint</span>
        </button>
    @endif

    @if ($residentId || $purpose !== 'enroll')
        <dialog x-ref="scanModal" class="fingerprint-dialog" :aria-labelledby="$id('fingerprint-dialog-title')"
            wire:ignore.self @cancel.prevent="closeModal()" @click="if ($event.target === $refs.scanModal) closeModal()">
            <div class="p-5 sm:p-6">
                <header class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold" :id="$id('fingerprint-dialog-title')">
                            {{ $purpose === 'enroll' ? 'Manage resident fingerprint' : 'Find resident by fingerprint' }}
                        </h2>
                        <p class="mt-1 text-sm text-base-content/70">
                            {{ $purpose === 'enroll' ? 'Enroll, replace, or remove one finger.' : 'Scan an enrolled finger to find a resident.' }}
                        </p>
                    </div>
                    <button type="button" class="btn btn-sm btn-circle btn-ghost shrink-0" aria-label="Close fingerprint dialog"
                        :disabled="modalClosing" @click="closeModal()">
                        <x-mary-icon name="o-x-mark" class="w-5 h-5" />
                    </button>
                </header>

                <div class="mt-5 space-y-4">
                    <label class="block" x-show="readers.length > 1" x-cloak>
                        <span class="block mb-2 text-sm font-medium">Fingerprint reader</span>
                        <select class="w-full select select-bordered min-h-11" x-model="readerSerial"
                            :disabled="busy" @change="readerSelectedManually = true">
                            <option value="">Choose a connected reader</option>
                            <template x-for="reader in readers" :key="reader.serial">
                                <option :value="reader.serial" x-text="`${reader.name} · ${reader.serial}`"></option>
                            </template>
                        </select>
                    </label>
                    @if ($purpose === 'enroll')
                        @if ($inForm)
                            <p class="text-sm leading-relaxed text-base-content/70">Fingerprint changes are saved separately from this form.</p>
                        @endif
                        <label class="block">
                            <span class="block mb-2 text-sm font-medium">Finger to enroll</span>
                            <select x-model="finger" :disabled="busy" class="w-full select select-bordered min-h-11">
                                @foreach (['right_index', 'right_thumb', 'right_middle', 'right_ring', 'right_little', 'left_index', 'left_thumb', 'left_middle', 'left_ring', 'left_little'] as $position)
                                    <option value="{{ $position }}">{{ ucfirst(str_replace('_', ' ', $position)) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="flex items-start gap-3 p-3 text-sm leading-relaxed rounded-lg bg-base-200/50">
                            <input type="checkbox" class="mt-0.5 checkbox checkbox-sm shrink-0" x-model="consent" :disabled="busy">
                            <span class="min-w-0">The resident agrees to storing their fingerprint template for resident lookup and understands enrollment is optional.</span>
                        </label>
                        <div x-show="confirmRemove" x-cloak class="space-y-3">
                            <p class="text-sm leading-relaxed">Remove this enrollment? QR, RFID and search will still work.</p>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <button type="button" class="btn btn-error min-h-11" :disabled="busy" @click="remove()">Remove fingerprint</button>
                                <button type="button" class="btn btn-outline min-h-11" :disabled="busy" @click="confirmRemove = false">Keep fingerprint</button>
                            </div>
                        </div>
                    @endif
                    <x-fingerprint-feedback :controls="false" />
                </div>

                <footer class="grid grid-cols-1 gap-2 pt-5 mt-5 border-t border-base-300 sm:grid-cols-2">
                    @if ($purpose === 'enroll')
                        <button type="button" class="btn btn-primary min-h-11" :class="enrolled ? '' : 'sm:col-span-2'" :disabled="busy || !consent || confirmRemove" @click="start()"
                            x-text="busy ? 'Please wait…' : (enrolled ? 'Replace fingerprint' : 'Enroll fingerprint')"></button>
                        <button type="button" class="btn btn-outline text-error min-h-11" x-show="enrolled" x-cloak
                            :disabled="busy || confirmRemove" @click="confirmRemove = true">Remove fingerprint</button>
                    @else
                        <button type="button" class="btn btn-primary min-h-11 sm:col-span-2" :disabled="busy" @click="start()">Scan again</button>
                    @endif
                    <button type="button" class="btn btn-ghost min-h-11 sm:col-span-2" :disabled="modalClosing" @click="closeModal()"
                        x-text="busy ? 'Cancel scan and close' : 'Close'"></button>
                </footer>
            </div>
        </dialog>
    @endif
</div>
