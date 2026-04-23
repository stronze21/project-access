<div>
    <h1 class="mb-4 text-2xl font-semibold">System Settings</h1>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-5">
        <!-- Settings Navigation -->
        <div class="p-4 bg-white rounded-lg shadow md:col-span-1">
            <nav class="space-y-1">
                <button type="button" wire:click="changeTab('appearance')"
                    class="flex items-center w-full px-3 py-2 text-sm rounded-md transition-colors {{ $activeTab === 'appearance' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="w-5 h-5 mr-3">🎨</span>
                    <span>Appearance</span>
                </button>

                <button type="button" wire:click="changeTab('location')"
                    class="flex items-center w-full px-3 py-2 text-sm rounded-md transition-colors {{ $activeTab === 'location' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="w-5 h-5 mr-3">📍</span>
                    <span>Location</span>
                </button>

                <button type="button" wire:click="changeTab('contact')"
                    class="flex items-center w-full px-3 py-2 text-sm rounded-md transition-colors {{ $activeTab === 'contact' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="w-5 h-5 mr-3">✉️</span>
                    <span>Contact</span>
                </button>
            </nav>
        </div>

        <!-- Settings Content -->
        <div class="md:col-span-4">
            <x-mary-card>
                <!-- Appearance Tab -->
                @if ($activeTab === 'appearance')
                    <h2 class="text-xl font-medium">Appearance Settings</h2>
                    <p class="mb-4 text-sm text-gray-600">Customize the look and feel of your application.</p>

                    <x-mary-form wire:submit.prevent="saveAppearanceSettings">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <x-mary-input label="App Name (First Part)" wire:model="app_name_1" required />
                            <x-mary-input label="App Name (Second Part)" wire:model="app_name_2" required />
                        </div>

                        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2">
                            <div>
                                <label class="block mb-2 text-sm font-medium">App Logo</label>
                                <div class="flex items-center gap-4">
                                    @if ($current_logo_url)
                                        <img src="{{ $current_logo_url }}" alt="Logo"
                                            class="w-16 h-16 border rounded">
                                    @endif
                                    <x-mary-file wire:model="app_logo" hint="Upload a logo" />
                                </div>
                            </div>

                            <div>
                                <label class="block mb-2 text-sm font-medium">App Favicon</label>
                                <div class="flex items-center gap-4">
                                    @if ($current_favicon_url)
                                        <img src="{{ $current_favicon_url }}" alt="Favicon"
                                            class="w-16 h-16 border rounded">
                                    @endif
                                    <x-mary-file wire:model="app_favicon" hint="Upload a favicon" />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-4">
                            <x-mary-button type="submit" class="btn-primary">Save Appearance</x-mary-button>
                        </div>
                    </x-mary-form>
                @endif

                <!-- Location Tab -->
                @if ($activeTab === 'location')
                    <h2 class="text-xl font-medium">Location Settings</h2>
                    <p class="mb-4 text-sm text-gray-600">Configure location information.</p>

                    <form wire:submit.prevent="saveLocationSettings">
                        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-3">
                            <x-mary-input label="Municipality/City" wire:model="municipality" required />
                            <x-mary-input label="Province" wire:model="province" required />
                            <x-mary-input label="Region" wire:model="region" required />
                        </div>

                        <div class="p-4 mb-4 border rounded bg-gray-50">
                            <h3 class="mb-2 text-sm font-medium">PSGC Codes (Optional)</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <x-mary-input label="Region Code" wire:model="region_code" />
                                <x-mary-input label="Province Code" wire:model="province_code" />
                                <x-mary-input label="Municipality Code" wire:model="municipality_code" />
                            </div>
                        </div>

                        <div class="flex justify-end mt-4">
                            <x-mary-button type="submit" class="btn-primary">Save Location</x-mary-button>
                        </div>
                    </form>
                @endif

                <!-- Contact Tab -->
                @if ($activeTab === 'contact')
                    <h2 class="text-xl font-medium">Contact Information</h2>
                    <p class="mb-4 text-sm text-gray-600">Set contact details for your organization.</p>

                    <form wire:submit.prevent="saveContactSettings">
                        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2">
                            <x-mary-input label="Contact Email" wire:model="contact_email" type="email" required />
                            <x-mary-input label="Contact Phone" wire:model="contact_phone" required />
                        </div>

                        <div class="mb-4">
                            <x-mary-textarea label="Office Address" wire:model="office_address" rows="3"
                                required />
                        </div>

                        <div class="flex justify-end mt-4">
                            <x-mary-button type="submit" class="btn-primary">Save Contact Info</x-mary-button>
                        </div>
                    </form>
                @endif
            </x-mary-card>
        </div>
    </div>
</div>
