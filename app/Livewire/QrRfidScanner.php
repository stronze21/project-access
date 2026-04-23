<?php

namespace App\Livewire;

use App\Services\QrCodeService;
use App\Services\RfidService;
use Livewire\Component;
use Mary\Traits\Toast;

class QrRfidScanner extends Component
{
    use Toast;

    // Configuration
    public $title = 'Scan QR Code or RFID';
    public $description = 'Use camera to scan QR code or enter RFID number';
    public $showCamera = true;
    public $showRfidInput = true;
    public $autoProcess = true; // Auto process after scanning

    // Scanner state
    public $isScanning = false;
    public $scanResult = null;
    public $rfidInput = '';

    // Result data
    public $resultType = null; // 'resident', 'household', etc.
    public $resultId = null;
    public $resultFound = false;
    public $resultMessage = '';
    public $resultObject = null;

    // Events
    protected $listeners = [
        'qrCodeScanned' => 'processQrCode'
    ];

    // Services
    protected $qrCodeService;
    protected $rfidService;

    /**
     * Constructor
     */
    public function boot(QrCodeService $qrCodeService, RfidService $rfidService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->rfidService = $rfidService;
    }

    /**
     * Mount the component.
     */
    public function mount(
        $title = null,
        $description = null,
        $showCamera = null,
        $showRfidInput = null,
        $autoProcess = null
    ) {
        if ($title !== null) $this->title = $title;
        if ($description !== null) $this->description = $description;
        if ($showCamera !== null) $this->showCamera = $showCamera;
        if ($showRfidInput !== null) $this->showRfidInput = $showRfidInput;
        if ($autoProcess !== null) $this->autoProcess = $autoProcess;
    }

    /**
     * Toggle camera scanner.
     */
    public function toggleScanner()
    {
        $this->isScanning = !$this->isScanning;

        if (!$this->isScanning) {
            $this->dispatch('qr-scanner-close');
        }
    }

    /**
     * Process a scanned QR code.
     */
    public function processQrCode($code)
    {
        $this->isScanning = false;
        $this->scanResult = $code;
        $result = $this->qrCodeService->processQrCode($code);

        $this->resultType = $result['type'];
        $this->resultId = $result['id'];
        $this->resultFound = $result['found'];
        $this->resultMessage = $result['message'];
        $this->resultObject = $result['object'];

        if ($result['found']) {
            $this->success($result['message']);

            if ($this->autoProcess) {
                $this->processResult();
            }
        } else {
            $this->warning($result['message']);
        }

        // Emit event with result
        $this->dispatch('scan-result', $result);
    }

    /**
     * Process RFID input.
     */
    public function processRfid()
    {
        if (empty($this->rfidInput)) {
            $this->warning('Please enter an RFID number');
            return;
        }

        $result = $this->rfidService->processRfid($this->rfidInput);

        $this->resultType = $result['type'];
        $this->resultId = $result['id'];
        $this->resultFound = $result['found'];
        $this->resultMessage = $result['message'];
        $this->resultObject = $result['object'];

        if ($result['found']) {
            $this->success($result['message']);

            if ($this->autoProcess) {
                $this->processResult();
            }
        } else {
            $this->warning($result['message']);
        }

        // Emit event with result
        $this->dispatch('scan-result', $result);
    }

    /**
     * Process the scan result - navigate to appropriate page.
     */
    public function processResult()
    {
        if (!$this->resultFound || !$this->resultId) {
            return;
        }

        if ($this->resultType === 'resident') {
            // Redirect to resident page
            return redirect()->route('residents.show', $this->resultId);
        } elseif ($this->resultType === 'household') {
            // Redirect to household page
            return redirect()->route('households.show', $this->resultId);
        }
    }

    /**
     * Clear the current result.
     */
    public function clearResult()
    {
        $this->scanResult = null;
        $this->resultType = null;
        $this->resultId = null;
        $this->resultFound = false;
        $this->resultMessage = '';
        $this->resultObject = null;
        $this->rfidInput = '';
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.qr-rfid-scanner');
    }
}
