<div>
    <x-mary-card title="{{ $isEdit ? 'Edit Ayuda Program' : 'Create New Ayuda Program' }}">
        <x-slot:menu>
            @if ($isEdit)
                <x-mary-button link="{{ route('programs.show', $programId) }}" label="View"
                    class="tagged-color btn-primary" size="sm" />
            @endif
            <x-mary-button link="{{ route('programs.index') }}" label="All Programs"
                class="tagged-color btn-secondary btn-outline btn-secline" size="sm" />
        </x-slot:menu>

        <form wire:submit="save">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-6">
                <!-- Program Details -->
                <div>
                    <h3 class="mb-4 text-lg font-semibold">Program Details</h3>

                    <div class="mb-4">
                        <x-mary-input label="Program Name" wire:model="name" required
                            error="{{ $errors->first('name') }}" />
                    </div>

                    <div class="mb-4">
                        <x-mary-input label="Program Code" wire:model="code"
                            placeholder="Optional: System will generate if empty"
                            hint="Unique identifier for the program (e.g. ESP-COVID-2023)"
                            error="{{ $errors->first('code') }}" />
                    </div>

                    <div class="mb-4">
                        <x-mary-textarea label="Program Description" wire:model="description"
                            placeholder="Describe the purpose and details of this program" rows="3"
                            error="{{ $errors->first('description') }}" />
                    </div>

                    <div class="mb-4">
                        <x-mary-select label="Program Type" :options="$assistanceTypes" wire:model.live="type" required
                            error="{{ $errors->first('type') }}" />
                    </div>

                    @if ($type === 'cash' || $type === 'mixed')
                        <div class="mb-4">
                            <x-mary-input label="Amount per Beneficiary" wire:model="amount" type="number"
                                step="0.01" required placeholder="Enter amount in PHP" prefix="₱"
                                error="{{ $errors->first('amount') }}" />
                        </div>
                    @endif

                    @if ($type === 'goods' || $type === 'mixed')
                        <div class="mb-4">
                            <x-mary-textarea label="Goods Description" wire:model="goodsDescription"
                                placeholder="Describe the goods to be distributed" rows="3"
                                error="{{ $errors->first('goodsDescription') }}" />
                        </div>
                    @endif

                    @if ($type === 'services' || $type === 'mixed')
                        <div class="mb-4">
                            <x-mary-textarea label="Services Description" wire:model="servicesDescription"
                                placeholder="Describe the services to be provided" rows="3"
                                error="{{ $errors->first('servicesDescription') }}" />
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-mary-datetime label="Start Date" wire:model="startDate" required
                            error="{{ $errors->first('startDate') }}" />
                        <x-mary-datetime label="End Date" wire:model="endDate" hint="Leave blank for ongoing programs"
                            error="{{ $errors->first('endDate') }}" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-mary-select label="Distribution Frequency" :options="$assistanceFrequencies" wire:model="frequency" required
                            error="{{ $errors->first('frequency') }}" />
                        <x-mary-input label="Distribution Count" wire:model="distributionCount" type="number"
                            min="1" required hint="How many times aid will be distributed"
                            error="{{ $errors->first('distributionCount') }}" />
                    </div>

                    <div class="mb-4">
                        <x-mary-input label="Total Budget (Optional)" wire:model="totalBudget" type="number"
                            step="0.01" placeholder="Enter total program budget in PHP" prefix="₱"
                            hint="Maximum amount to be distributed in this program"
                            error="{{ $errors->first('totalBudget') }}" />
                    </div>

                    <div class="mb-4">
                        <x-mary-input label="Maximum Beneficiaries (Optional)" wire:model="maxBeneficiaries"
                            type="number" min="0" placeholder="Enter maximum number of beneficiaries"
                            hint="Maximum number of beneficiaries for this program"
                            error="{{ $errors->first('maxBeneficiaries') }}" />
                    </div>

                    <div class="flex mb-4 space-x-4">
                        <x-mary-checkbox label="Requires Verification" wire:model="requiresVerification"
                            hint="Distribution requires additional verification" />
                        <x-mary-checkbox label="Active Program" wire:model="isActive"
                            hint="Program is active and available for distribution" />
                    </div>
                </div>

                <!-- Eligibility Criteria -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Eligibility Criteria</h3>
                        <x-mary-button type="button" wire:click="addCriterion" icon="o-plus" size="sm"
                            label="Add Criterion" />
                    </div>

                    <div class="space-y-6">
                        @foreach ($criteria as $index => $criterion)
                            <div
                                class="bg-base-50 p-4 rounded-lg border {{ !empty($criterion['name']) || !empty($criterion['value']) ? 'border-blue-200' : 'border-gray-200' }}">
                                <div class="flex items-start justify-between mb-3">
                                    <h4 class="font-medium">Criterion #{{ $index + 1 }}</h4>
                                    <div class="flex items-center space-x-2">
                                        <x-mary-checkbox label="Required"
                                            wire:model="criteria.{{ $index }}.required" />
                                        <x-mary-button type="button"
                                            wire:click="removeCriterion({{ $index }})" icon="o-trash"
                                            class="btn-error" />
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <x-mary-input label="Criterion Name"
                                        wire:model="criteria.{{ $index }}.name"
                                        placeholder="E.g. Low Income, Senior Citizen, etc."
                                        error="{{ $errors->first('criteria.' . $index . '.name') }}" />
                                </div>

                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <x-mary-select label="Criterion Type"
                                        wire:model.live="criteria.{{ $index }}.type"
                                        error="{{ $errors->first('criteria.' . $index . '.type') }}" :options="$criterionTypes"
                                        option-value="key" option-label="name" />

                                    <x-mary-select label="Operator"
                                        wire:model.live="criteria.{{ $index }}.operator"
                                        error="{{ $errors->first('criteria.' . $index . '.operator') }}"
                                        :options="$operators" option-value="key" option-label="name" />
                                </div>

                                <div class="mb-3">
                                    <x-mary-input label="Value" wire:model="criteria.{{ $index }}.value"
                                        placeholder="Enter the value to compare against"
                                        error="{{ $errors->first('criteria.' . $index . '.value') }}" />
                                </div>

                                <div class="text-xs text-gray-500">
                                    @if ($criteria[$index]['type'] === 'age')
                                        <p>Examples: "18" for 18 years old, "60" for senior citizens</p>
                                    @elseif($criteria[$index]['type'] === 'income' || $criteria[$index]['type'] === 'household_income')
                                        <p>Examples: "15000" for ₱15,000 income threshold</p>
                                    @elseif($criteria[$index]['type'] === 'location')
                                        <p>Examples: "Barangay 1" for single location, or "Barangay 1,Barangay 2" for
                                            multiple</p>
                                    @elseif($criteria[$index]['type'] === 'gender')
                                        <p>Examples: "male", "female", or "other"</p>
                                    @elseif(in_array($criteria[$index]['type'], [
                                            'pwd',
                                            'senior',
                                            'voter',
                                            'solo_parent',
                                            'pregnant',
                                            'lactating',
                                            'indigenous',
                                        ]))
                                        <p>Use "true" or "false" values</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-8 space-x-3">
                <x-mary-button label="Cancel" class="tagged-color btn-secondary btn-outline btn-secline"
                    link="{{ route('programs.index') }}" />
                <x-mary-button type="button" label="Reset Form" wire:click="resetForm"
                    class="tagged-color btn-warning" />
                <x-mary-button type="submit" label="{{ $isEdit ? 'Update Program' : 'Create Program' }}"
                    icon="o-paper-airplane" />
            </div>
        </form>
    </x-mary-card>
</div>
