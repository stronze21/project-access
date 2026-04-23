<?php

namespace App\Livewire;

use App\Models\Resident;
use App\Models\Distribution;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Hash;

class ResidentShow extends Component
{
    use WithPagination;
    use Toast;

    public $resident;
    public $residentId;
    public $showQrCode = false;
    public $perPage = 10;

    // Portal account fields
    public $showPortalAccountModal = false;
    public $portalEmail;
    public $portalPassword;
    public $portalPasswordConfirmation;
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
        $this->resident = Resident::with('household')->findOrFail($this->residentId);
        $this->portalEmail = $this->resident->email;
    }

    /**
     * Toggle QR code visibility
     */
    public function toggleQrCode()
    {
        $this->showQrCode = !$this->showQrCode;
    }

    /**
     * Set resident status
     */
    public function setResidentStatus($status)
    {
        $this->resident->is_active = $status === 'active';
        $this->resident->save();

        $this->success("Resident marked as " . ($this->resident->is_active ? 'active' : 'inactive'));

        $this->loadResident();
    }

    /**
     * Open portal account modal
     */
    public function openPortalAccountModal()
    {
        $this->portalEmail = $this->resident->email;
        $this->resetPassword = false;
        $this->portalPassword = '';
        $this->portalPasswordConfirmation = '';
        $this->showPortalAccountModal = true;
        $this->resetValidation();
    }

    /**
     * Save portal account
     */
    public function savePortalAccount()
    {
        $rules = [
            'portalEmail' => 'required|email|unique:residents,email,' . $this->residentId,
        ];

        if ($this->resetPassword) {
            $rules['portalPassword'] = 'required|min:8|same:portalPasswordConfirmation';
            $rules['portalPasswordConfirmation'] = 'required|min:8';
        }

        $this->validate($rules, [
            'portalEmail.required' => 'Email is required',
            'portalEmail.email' => 'Please enter a valid email address',
            'portalEmail.unique' => 'This email is already in use',
            'portalPassword.required' => 'Password is required',
            'portalPassword.min' => 'Password must be at least 8 characters',
            'portalPassword.same' => 'Password confirmation does not match',
        ]);

        try {
            $data = [
                'email' => $this->portalEmail,
            ];

            if ($this->resetPassword && $this->portalPassword) {
                $data['password'] = Hash::make($this->portalPassword);
            }

            $this->resident->update($data);

            $this->success('Portal account updated successfully');
            $this->showPortalAccountModal = false;
            $this->loadResident();
        } catch (\Exception $e) {
            $this->error('Error updating portal account: ' . $e->getMessage());
        }
    }

    /**
     * Reset portal account password
     */
    public function resetPortalPassword()
    {
        $this->validate([
            'portalPassword' => 'required|min:8|same:portalPasswordConfirmation',
            'portalPasswordConfirmation' => 'required|min:8',
        ]);

        try {
            $this->resident->update([
                'password' => Hash::make($this->portalPassword),
            ]);

            $this->success('Password reset successfully');
            $this->showPortalAccountModal = false;
            $this->portalPassword = '';
            $this->portalPasswordConfirmation = '';
        } catch (\Exception $e) {
            $this->error('Error resetting password: ' . $e->getMessage());
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
            ]);

            $this->success('Portal access disabled successfully');
            $this->loadResident();
        } catch (\Exception $e) {
            $this->error('Error disabling portal access: ' . $e->getMessage());
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
            'distributions' => $this->getDistributions()
        ]);
    }
}
