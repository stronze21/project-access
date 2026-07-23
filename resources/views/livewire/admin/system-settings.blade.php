<div>
    <h1 class="mb-4 text-2xl font-semibold">System Settings</h1>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-5">
        <div class="p-4 bg-white rounded-lg shadow md:col-span-1">
            <nav class="space-y-1">
                @foreach ([
                    'appearance' => 'Appearance',
                    'location' => 'Location',
                    'contact' => 'Contact',
                    'email' => 'Email',
                    'modules' => 'Modules',
                ] as $tab => $label)
                    <button type="button" wire:click="changeTab('{{ $tab }}')"
                        class="flex items-center w-full px-3 py-2 text-sm rounded-md transition-colors {{ $activeTab === $tab ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        <div class="md:col-span-4">
            <x-mary-card>
                @if ($activeTab === 'appearance')
                    <h2 class="text-xl font-medium">Appearance Settings</h2>
                    <p class="mb-4 text-sm text-gray-600">Customize the look and feel of your application.</p>

                    <x-mary-form wire:submit.prevent="saveAppearanceSettings">
                        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2">
                            <x-mary-input label="App Name (First Part)" wire:model="app_name_1" required />
                            <x-mary-input label="App Name (Second Part)" wire:model="app_name_2" required />
                        </div>

                        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2">
                            <div>
                                <label class="block mb-2 text-sm font-medium">App Logo</label>
                                <div class="flex items-center gap-4">
                                    @if ($current_logo_url)
                                        <img src="{{ $current_logo_url }}" alt="Logo" class="w-16 h-16 border rounded">
                                    @endif
                                    <x-mary-file wire:model="app_logo" hint="Upload a logo" />
                                </div>
                            </div>

                            <div>
                                <label class="block mb-2 text-sm font-medium">App Favicon</label>
                                <div class="flex items-center gap-4">
                                    @if ($current_favicon_url)
                                        <img src="{{ $current_favicon_url }}" alt="Favicon" class="w-16 h-16 border rounded">
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

                @if ($activeTab === 'contact')
                    <h2 class="text-xl font-medium">Contact Information</h2>
                    <p class="mb-4 text-sm text-gray-600">Set contact details for your organization.</p>

                    <form wire:submit.prevent="saveContactSettings">
                        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2">
                            <x-mary-input label="Contact Email" wire:model="contact_email" type="email" required />
                            <x-mary-input label="Contact Phone" wire:model="contact_phone" required />
                        </div>

                        <div class="mb-4">
                            <x-mary-textarea label="Office Address" wire:model="office_address" rows="3" required />
                        </div>

                        <div class="flex justify-end mt-4">
                            <x-mary-button type="submit" class="btn-primary">Save Contact Info</x-mary-button>
                        </div>
                    </form>
                @endif

                @if ($activeTab === 'modules')
                    <h2 class="text-xl font-medium">Module Settings</h2>
                    <p class="mb-4 text-sm text-gray-600">Turn BosesMoTo and its pages on or off.</p>

                    <form wire:submit.prevent="saveModuleSettings">
                        <div class="space-y-3">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="font-semibold text-slate-900">BosesMoTo Module</div>
                                        <div class="text-sm text-slate-600">Master switch for all BosesMoTo web and mobile API routes.</div>
                                    </div>
                                    <x-mary-toggle label="Enabled" wire:model.live="moduleStates.bosesmoto" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="mb-3">
                                        <div class="font-semibold text-slate-900">Complaints</div>
                                        <div class="text-sm text-slate-600">Public reports, submissions, queues, references, audit, and reports.</div>
                                    </div>
                                    <x-mary-toggle label="Enabled" wire:model.live="moduleStates.complaints" />
                                </div>

                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="mb-3">
                                        <div class="font-semibold text-slate-900">Sentiments</div>
                                        <div class="text-sm text-slate-600">Feed, posts, comments, reactions, follows, and reports.</div>
                                    </div>
                                    <x-mary-toggle label="Enabled" wire:model.live="moduleStates.sentiments" />
                                </div>

                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="mb-3">
                                        <div class="font-semibold text-slate-900">Polls</div>
                                        <div class="text-sm text-slate-600">Poll list, details, creation, and voting.</div>
                                    </div>
                                    <x-mary-toggle label="Enabled" wire:model.live="moduleStates.polls" />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-4">
                            <x-mary-button type="submit" class="btn-primary">Save Modules</x-mary-button>
                        </div>
                    </form>
                @endif

                @if ($activeTab === 'email')
                    <h2 class="text-xl font-medium">Email Delivery</h2>
                    <p class="mb-4 text-sm text-gray-600">Configure confirmation-code and system email delivery without editing the environment file. Stored passwords are encrypted.</p>
                    <form wire:submit.prevent="saveMailSettings" class="space-y-4">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4"><x-mary-toggle label="Use database email settings" wire:model="mail_dynamic_enabled" /><p class="mt-1 text-xs text-blue-800">When disabled, the application uses the existing .env mail configuration.</p></div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-mary-select label="Mailer" wire:model="mail_mailer" :options="[['id'=>'smtp','name'=>'SMTP'],['id'=>'log','name'=>'Log only (testing)']]" />
                            <x-mary-select label="Connection" wire:model="mail_scheme" :options="[['id'=>'smtp','name'=>'SMTP / STARTTLS'],['id'=>'smtps','name'=>'Implicit TLS (SMTPS)']]" />
                            <x-mary-input label="SMTP host" wire:model="mail_host" />
                            <x-mary-input label="SMTP port" type="number" wire:model="mail_port" />
                            <x-mary-input label="Username" wire:model="mail_username" autocomplete="off" />
                            <x-mary-input label="Password" type="password" wire:model="mail_password" autocomplete="new-password" hint="{{ $mail_password_configured ? 'A password is configured. Leave blank to keep it.' : 'Enter the SMTP password.' }}" />
                            <x-mary-input label="From address" type="email" wire:model="mail_from_address" />
                            <x-mary-input label="From name" wire:model="mail_from_name" />
                        </div>
                        <div class="flex justify-end"><x-mary-button type="submit" class="btn-primary" spinner="saveMailSettings">Save Email Settings</x-mary-button></div>
                    </form>
                    <div class="mt-6 border-t pt-5"><h3 class="font-semibold text-slate-800">Send a test email</h3><div class="mt-3 flex flex-col gap-3 sm:flex-row"><x-mary-input type="email" wire:model="mail_test_recipient" placeholder="recipient@example.com" class="flex-1" /><x-mary-button wire:click="sendTestEmail" spinner="sendTestEmail" class="btn-outline" label="Send Test" /></div></div>
                @endif
            </x-mary-card>
        </div>
    </div>
</div>
