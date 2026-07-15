<?php

namespace App\Livewire;

use App\Models\Distribution;
use App\Models\Resident;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ResidentShow extends Component
{
    use Toast;
    use WithPagination;

    public $resident;

    public $residentId;

    public $showQrCode = false;

    public $perPage = 10;

    // Portal account fields
    public $showPortalAccountModal = false;

    public $portalEmail;

    public $portalMpin;

    public $portalMpinConfirmation;

    public $resetPassword = false;

    /**
     * Mount the component
     */
    public function mount($residentId)
    {
        $this->residentId = $residentId;
        $this->loadResident();
    }

    /**
     * Load resident data
     */
    protected function loadResident()
    {
        $this->resident = Resident::with(['household', 'sourceIncomeType'])->findOrFail($this->residentId);
        $this->portalEmail = $this->resident->email;
    }

    /**
     * Toggle QR code visibility
     */
    public function toggleQrCode()
    {
        $this->showQrCode = ! $this->showQrCode;
    }

    /**
     * Set resident status
     */
    public function setResidentStatus($status)
    {
        $this->resident->is_active = $status === 'active';
        $this->resident->save();

        $this->success('Resident marked as '.($this->resident->is_active ? 'active' : 'inactive'));

        $this->loadResident();
    }

    /**
     * Open portal account modal
     */
    public function openPortalAccountModal()
    {
        $this->portalEmail = $this->resident->email;
        $this->resetPassword = false;
        $this->portalMpin = '';
        $this->portalMpinConfirmation = '';
        $this->showPortalAccountModal = true;
        $this->resetValidation();
    }

    /**
     * Save portal account
     */
    public function savePortalAccount()
    {
        $rules = [
            'portalEmail' => 'required|email|unique:residents,email,'.$this->residentId,
        ];

        if ($this->resetPassword) {
            $rules['portalMpin'] = 'required|digits:6|same:portalMpinConfirmation';
            $rules['portalMpinConfirmation'] = 'required|digits:6';
        }

        $this->validate($rules, [
            'portalEmail.required' => 'Email is required',
            'portalEmail.email' => 'Please enter a valid email address',
            'portalEmail.unique' => 'This email is already in use',
            'portalMpin.required' => 'MPIN is required',
            'portalMpin.digits' => 'MPIN must be exactly 6 digits',
            'portalMpin.same' => 'MPIN confirmation does not match',
        ]);

        try {
            $data = [
                'email' => $this->portalEmail,
            ];

            if ($this->resetPassword && $this->portalMpin) {
                $data['mpin'] = Hash::make($this->portalMpin);
            }

            $this->resident->update($data);

            $this->success('Portal account updated successfully');
            $this->showPortalAccountModal = false;
            $this->loadResident();
        } catch (\Exception $e) {
            $this->error('Error updating portal account: '.$e->getMessage());
        }
    }

    /**
     * Reset portal account MPIN
     */
    public function resetPortalPassword()
    {
        $this->validate([
            'portalMpin' => 'required|digits:6|same:portalMpinConfirmation',
            'portalMpinConfirmation' => 'required|digits:6',
        ]);

        try {
            $this->resident->update([
                'mpin' => Hash::make($this->portalMpin),
            ]);

            $this->success('MPIN reset successfully');
            $this->showPortalAccountModal = false;
            $this->portalMpin = '';
            $this->portalMpinConfirmation = '';
        } catch (\Exception $e) {
            $this->error('Error resetting MPIN: '.$e->getMessage());
        }
    }

    /**
     * Disable portal access
     */
    public function disablePortalAccess()
    {
        try {
            $this->resident->update([
                'email' => null,
                'password' => null,
                'mpin' => null,
            ]);

            $this->success('Portal access disabled successfully');
            $this->loadResident();
        } catch (\Exception $e) {
            $this->error('Error disabling portal access: '.$e->getMessage());
        }
    }

    /**
     * Get resident's distributions
     */
    public function getDistributions()
    {
        return Distribution::where('resident_id', $this->residentId)
            ->with(['ayudaProgram', 'batch'])
            ->orderByDesc('distribution_date')
            ->paginate($this->perPage);
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.resident-show', [
            'distributions' => $this->getDistributions(),
        ]);
    }
}
