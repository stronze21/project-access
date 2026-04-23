<?php

use App\Livewire\Reports;
use App\Livewire\Dashboard;
use App\Livewire\ResidentList;
use App\Livewire\ResidentShow;
use App\Livewire\HouseholdList;
use App\Livewire\HouseholdShow;
use App\Livewire\QrRfidScanner;
use App\Livewire\HouseholdsList;
use App\Livewire\HouseholdCreate;
use App\Livewire\AyudaProgramShow;
use App\Livewire\DistributionList;
use App\Livewire\DistributionShow;
use App\Livewire\AyudaDistribution;
use App\Livewire\AyudaProgramsList;
use App\Livewire\BatchVerification;
use App\Livewire\DistributionsList;
use App\Livewire\AyudaProgramCreate;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\RoleManagement;
use App\Livewire\Admin\UserManagement;
use App\Livewire\ResidentRegistration;
use App\Livewire\DistributionBatchForm;
use App\Livewire\DistributionBatchList;
use App\Livewire\DistributionBatchShow;
use App\Livewire\HouseholdRegistration;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use App\Livewire\DistributionBatchCreate;
use App\Livewire\DistributionBatchesList;
use App\Livewire\ResidentSignatureUpdate;
use App\Http\Controllers\QrCodeController;
use App\Livewire\AyudaProgram\ProgramForm;
use App\Livewire\AyudaProgram\ProgramList;
use App\Livewire\AyudaProgram\ProgramShow;
use App\Livewire\Reports\ReportController;
use App\Livewire\BarangayBatchDistribution;
use App\Livewire\Reports\DistributionsReport;
use App\Livewire\RegistrationOfficerDashboard;
use App\Http\Controllers\ResidentCsvController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\ResidentIdCardController;
use App\Http\Controllers\BatchImageDownloadController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // Dashboard - accessible to all authenticated users
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Residents Management
    Route::prefix('resident')->middleware('permission:view-residents')->group(function () {
        Route::get('/', ResidentList::class)->name('residents.index');

        // Define literal /create route BEFORE parameter routes
        Route::get('/create', ResidentRegistration::class)
            ->middleware('permission:create-residents')
            ->name('residents.create');

        // Export
        Route::get('/export', [ResidentCsvController::class, 'export'])->name('residents.export');
        Route::get('/export/download', [ResidentCsvController::class, 'download'])->name('residents.export.download');

        // Import
        Route::get('/import', [ResidentCsvController::class, 'import'])->name('residents.import');
        Route::post('/import/process', [ResidentCsvController::class, 'processImport'])->name('residents.import.process');
        Route::get('/import/template', [ResidentCsvController::class, 'downloadTemplate'])->name('residents.import.template');

        // New route for signature update
        Route::get('/{residentId}/update-signature', ResidentSignatureUpdate::class)
            ->middleware('permission:edit-residents')
            ->name('residents.update-signature');

        // Now define parameter routes
        Route::get('/{residentId}/edit', ResidentRegistration::class)
            ->middleware('permission:edit-residents')
            ->name('residents.edit');

        // Batch ID Cards
        Route::get('/id-cards/form', [ResidentIdCardController::class, 'batchForm'])->name('residents.id-cards.form');
        Route::post('/id-cards/batch', [ResidentIdCardController::class, 'generateBatch'])->name('residents.id-cards.batch');

        Route::get('/show/{residentId}/show', ResidentShow::class)->name('residents.show');

        // Single ID Card
        Route::get('/id-card/{id}', [ResidentIdCardController::class, 'show'])->name('residents.id-card');
        Route::get('/id-card/{id}/landscape', [ResidentIdCardController::class, 'showLandscape'])->name('residents.id-card.landscape');
        Route::get('/id-card/{id}/portrait', [ResidentIdCardController::class, 'showPortrait'])->name('residents.id-card.portrait');

        Route::get('/batch-images/download', [BatchImageDownloadController::class, 'download'])
            ->name('residents.batch-images.download');
    });

    // Households Management
    Route::prefix('households')->middleware('permission:view-households')->group(function () {
        Route::get('/', HouseholdsList::class)->name('households.index');

        // Define /create route first
        Route::get('/create', HouseholdCreate::class)
            ->middleware('permission:create-households')
            ->name('households.create');

        // Then parameter routes
        Route::get('/{householdId}/edit', HouseholdCreate::class)
            ->middleware('permission:edit-households')
            ->name('households.edit');

        Route::get('/{householdId}', HouseholdShow::class)->name('households.show');
    });

    // Ayuda Programs
    Route::prefix('programs')->middleware('permission:view-programs')->group(function () {
        Route::get('/', AyudaProgramsList::class)->name('programs.index');

        // Define /create route first
        Route::get('/create', AyudaProgramCreate::class)
            ->middleware('permission:create-programs')
            ->name('programs.create');

        // Then parameter routes
        Route::get('/{programId}/edit', AyudaProgramCreate::class)
            ->middleware('permission:edit-programs')
            ->name('programs.edit');

        Route::get('/{programId}', AyudaProgramShow::class)->name('programs.show');
    });

    // Distributions
    Route::prefix('distributions')->middleware('permission:view-distributions')->group(function () {
        Route::get('/', DistributionsList::class)->name('distributions.index');
        // Additional permissions for creating and managing
        Route::middleware('permission:create-distributions')->group(function () {
            Route::get('/create', AyudaDistribution::class)->name('distributions.create');
            Route::get('/batch-distrib/{batchId}', AyudaDistribution::class)->name('distributions.batch');
        });


        // Distribution batches
        Route::middleware('permission:manage-distribution-batches')->group(function () {
            Route::get('/batches/list', DistributionBatchesList::class)->name('distributions.batches');
            Route::get('/batches/create', DistributionBatchCreate::class)->name('distributions.batches.create');
            Route::get('/batch/{batchId}', DistributionBatchShow::class)->name('distributions.batches.show');
            Route::get('/batch/{batchId}/edit', DistributionBatchCreate::class)->name('distributions.batches.edit');
            Route::get('/barangay-batch', BarangayBatchDistribution::class)
                ->name('distributions.barangay-batch');
            Route::get('/batch-verification', BatchVerification::class)
                ->name('distributions.batch-verification');
        });

        Route::get('/program/{programId}', AyudaDistribution::class)->name('distributions.program');
        Route::get('/{distributionId}', DistributionShow::class)->name('distributions.show');
    });

    // Announcements Management
    Route::prefix('announcements')->middleware('permission:manage-announcements')->group(function () {
        Route::get('/', \App\Livewire\AnnouncementList::class)->name('announcements.index');
    });

    // Reports
    Route::prefix('reports')->middleware('permission:view-reports')->group(function () {
        Route::get('/', Reports::class)->name('reports');
        Route::get('/distributions-report', DistributionsReport::class)->name('report.distribution');
        Route::get('/report-controller', ReportController::class)->name('report.controller');

        // Export functionality requires additional permission
        Route::middleware('permission:export-reports')->group(function () {
            Route::get('/export/downloads', [ReportExportController::class, 'download'])->name('report.export.downloads');
            Route::get('/export/download/{file}', function ($file) {
                return Storage::download($file);
            })->name('report.export.download');
        });
    });

    Route::get('/download-report/{filename}', function ($filename) {
        // Prevent path traversal and validate extension
        $filename = basename($filename);

        if (!str_ends_with($filename, '.csv')) {
            abort(404);
        }

        $filePath = public_path('exports/' . $filename);

        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->download($filePath);
    })
        ->name('download.report');

    // QR Code Generation - requires specific permission
    Route::middleware('permission:generate-qr-codes')->group(function () {
        Route::get('/qrcode/resident/{id}', [QrCodeController::class, 'getResidentQrCode'])->name('qrcode.resident');
        Route::get('/qrcode/household/{id}', [QrCodeController::class, 'getHouseholdQrCode'])->name('qrcode.household');
        Route::get('/qrcode/download/resident/{id}', [QrCodeController::class, 'downloadResidentQrCode'])->name('qrcode.download');
        Route::get('/qrcode/download/household/{id}', [QrCodeController::class, 'downloadHouseholdQrCode'])->name('qrcode.download.household');
    });

    // Scanner - requires verification permission
    Route::middleware('permission:verify-beneficiaries')->group(function () {
        Route::get('/scanner', QrRfidScanner::class)->name('scanner');
        Route::get('/qr-scanner', function () {
            return view('dashboard');
        });
    });

    // Admin routes
    Route::prefix('admin')->group(function () {
        // User management - requires manage-users permission
        Route::middleware('permission:manage-users')->group(function () {
            Route::get('/users', UserManagement::class)->name('admin.users');
        });

        // Role management - system administrator only
        Route::middleware('role:system-administrator')->group(function () {
            Route::get('/roles', RoleManagement::class)->name('admin.roles');
            Route::get('/admin/system-settings', function () {
                return view('admin.system-settings');
            })->name('admin.system-settings');
        });
    });

    Route::middleware(['auth'])
        ->get('/registration-dashboard', RegistrationOfficerDashboard::class)
        ->name('registration.dashboard');

    Route::post('/delete-temp-signature', function (Request $request) {
        $filename = $request->input('filename');
        if ($filename) {
            $filePath = storage_path('app/public/temp_signatures/' . $filename);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        return response()->json(['success' => true]);
    })->name('delete.temp.signature');
});
