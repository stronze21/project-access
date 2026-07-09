<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Android release</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">ProjectAccess Mobile</h1>
            <p class="mt-1 text-sm text-slate-600">Manage the public app page, version metadata, and latest APK download.</p>
        </div>

        <a href="{{ route('mobile-app.index') }}" target="_blank" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
            <x-mary-icon name="o-arrow-top-right-on-square" class="mr-2 h-4 w-4" />
            View public page
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-mary-card>
                <h2 class="text-xl font-medium text-slate-900">Release Details</h2>
                <p class="mb-5 text-sm text-slate-600">These details appear on the public app download page.</p>

                <form wire:submit.prevent="saveDetails" class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="md:col-span-3">
                            <x-mary-input label="App Name" wire:model="name" required />
                        </div>
                        <x-mary-input label="Version Name" wire:model="versionName" placeholder="1.0.0" required />
                        <x-mary-input label="Version Code" wire:model="versionCode" placeholder="1" required />
                        <x-mary-input label="Source Project" wire:model="sourceProjectPath" required />
                    </div>

                    <x-mary-textarea label="App Description" wire:model="description" rows="4" required />
                    <x-mary-textarea label="Feature List" wire:model="featuresText" rows="7" hint="One feature per line." required />
                    <x-mary-textarea label="Release Notes" wire:model="releaseNotes" rows="5" />

                    <div class="flex justify-end">
                        <x-mary-button type="submit" class="btn-primary" spinner="saveDetails">
                            Save Details
                        </x-mary-button>
                    </div>
                </form>
            </x-mary-card>
        </div>

        <div>
            <x-mary-card>
                <h2 class="text-xl font-medium text-slate-900">Latest APK</h2>
                <p class="mb-5 text-sm text-slate-600">Upload the signed APK built from the mobile project folder.</p>

                <div class="mb-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    @if ($hasApk)
                        <div class="text-sm font-semibold text-slate-900">{{ $currentApkName ?: 'Current APK' }}</div>
                        <div class="mt-1 text-sm text-slate-600">
                            {{ $currentApkSizeLabel ?: 'Size unavailable' }}
                            @if ($currentApkUploadedAt)
                                <span class="mx-1">|</span>
                                Uploaded {{ $currentApkUploadedAt }}
                            @endif
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('mobile-app.download') }}" class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                <x-mary-icon name="o-arrow-down-tray" class="mr-2 h-4 w-4" />
                                Test Download
                            </a>
                            <button type="button" wire:click="removeApk" wire:confirm="Remove the current APK release?" class="inline-flex items-center rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                                <x-mary-icon name="o-trash" class="mr-2 h-4 w-4" />
                                Remove
                            </button>
                        </div>
                    @else
                        <div class="text-sm font-semibold text-slate-900">No APK uploaded yet</div>
                        <p class="mt-1 text-sm text-slate-600">The public download button will be disabled until an APK is uploaded.</p>
                    @endif
                </div>

                <form wire:submit.prevent="uploadApk" class="space-y-4">
                    <div>
                        <label for="apk" class="block text-sm font-medium text-slate-700">APK File</label>
                        <input id="apk" type="file" wire:model="apk" accept=".apk" class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                        @error('apk')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div wire:loading wire:target="apk" class="mt-2 text-sm text-slate-600">Preparing upload...</div>
                    </div>

                    <x-mary-button type="submit" class="btn-primary w-full" spinner="uploadApk">
                        Upload Latest APK
                    </x-mary-button>
                </form>
            </x-mary-card>
        </div>
    </div>
</div>
