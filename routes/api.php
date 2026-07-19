<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CitizenServicesAdminController;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HouseholdController;
use App\Http\Controllers\Api\ResidentApiController;
use App\Http\Controllers\Api\AyudaProgramController;
use App\Http\Controllers\Api\DistributionController;
use App\Http\Controllers\Api\SystemSettingController;
use App\Http\Controllers\Api\DistributionBatchController;
use App\Http\Controllers\Api\EligibilityCriteriaController;
use App\Http\Controllers\Api\AddressController as ApiAddressController;
use App\Http\Controllers\Api\ResidentPortal\AuthController as ResidentAuthController;
use App\Http\Controllers\Api\PublicServiceLinkController;
use App\Http\Controllers\Api\EmergencyAlertController;
use App\Http\Controllers\Api\ResidentPortal\GrievanceController as ResidentGrievanceController;
use App\Http\Controllers\Api\ResidentPortal\EmergencyController as ResidentEmergencyController;
use App\Http\Controllers\Api\ResidentPortal\ServiceTrackingController as ResidentServiceTrackingController;
use App\Http\Controllers\Api\ResidentPortal\ProfileController as ResidentProfileController;
use App\Http\Controllers\Api\ResidentPortal\HouseholdController as ResidentHouseholdController;
use App\Http\Controllers\Api\ResidentPortal\ProgramController as ResidentProgramController;
use App\Http\Controllers\Api\ResidentPortal\DistributionController as ResidentDistributionController;
use App\Http\Controllers\Api\ResidentPortal\AnnouncementController as ResidentAnnouncementController;
use App\Http\Controllers\Api\ResidentPortal\NotificationController as ResidentNotificationController;
use App\Http\Controllers\Api\ResidentPortal\PublicServicePortalController as ResidentPublicServicePortalController;
use App\Http\Controllers\Api\ResidentPortal\AccountDeletionRequestController as ResidentAccountDeletionRequestController;
use App\Http\Controllers\Api\ResidentPortal\SupportRequestController as ResidentSupportRequestController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Address API routes
Route::prefix('address')->group(function () {
    Route::get('/regions', [AddressController::class, 'getRegions']);
    Route::get('/provinces', [AddressController::class, 'getProvinces']);
    Route::get('/cities', [AddressController::class, 'getCities']);
    Route::get('/barangays', [AddressController::class, 'getBarangays']);
    Route::get('/info', [AddressController::class, 'getAddressInfo']);
});
Route::prefix('api')->middleware(['auth'])->group(function () {
    Route::get('/residents', [ResidentApiController::class, 'index']);
});

// Public routes (login, register)
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/register', [AuthController::class, 'register'])->name('api.register');


Route::prefix('residents/id-card')->middleware(['auth:sanctum', 'permission:view-residents'])->group(function () {
    Route::get('/{residentId}', [ResidentController::class, 'getForIdCard']);
    Route::post('/batch', [ResidentController::class, 'batchForIdCard']);
    Route::get('/search', [ResidentController::class, 'searchForIdCard']);
    Route::get('/allowed/list', [ResidentController::class, 'getAllowedForIdCard']);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->name('api.')->group(function () {
    // User information
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles');
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard routes
    Route::prefix('dashboard')->middleware('permission:view-reports')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::get('/monthly-distributions', [DashboardController::class, 'monthlyDistributions']);
        Route::get('/program-distributions', [DashboardController::class, 'programDistributions']);
        Route::get('/barangay-distributions', [DashboardController::class, 'barangayDistributions']);
        Route::get('/resident-statistics', [DashboardController::class, 'residentStatistics']);
        Route::get('/recent-registrations', [DashboardController::class, 'recentRegistrations']);
        Route::get('/recent-distributions', [DashboardController::class, 'recentDistributions']);
        Route::get('/upcoming-batches', [DashboardController::class, 'upcomingBatches']);
    });

    // Address/Location data routes
    Route::prefix('address')->group(function () {
        Route::get('/regions', [ApiAddressController::class, 'regions']);
        Route::get('/provinces', [ApiAddressController::class, 'provinces']);
        Route::get('/cities', [ApiAddressController::class, 'cities']);
        Route::get('/barangays', [ApiAddressController::class, 'barangays']);
        Route::get('/region/{code}', [ApiAddressController::class, 'region']);
        Route::get('/province/{code}', [ApiAddressController::class, 'province']);
        Route::get('/city/{code}', [ApiAddressController::class, 'city']);
        Route::get('/barangay/{code}', [ApiAddressController::class, 'barangay']);
    });

    // Resident routes
    Route::get('/residents/pending-signatures', [ResidentController::class, 'pendingSignatures'])->middleware('permission:view-residents');
    Route::apiResource('residents', ResidentController::class)
        ->middlewareFor(['index', 'show'], 'permission:view-residents')
        ->middlewareFor('store', 'permission:create-residents')
        ->middlewareFor('update', 'permission:edit-residents')
        ->middlewareFor('destroy', 'permission:delete-residents');
    Route::patch('/residents/{id}/household', [ResidentController::class, 'updateHousehold'])->middleware('permission:edit-residents');
    Route::post('/residents/{id}/photo', [ResidentController::class, 'uploadPhoto'])->middleware('permission:edit-residents');
    Route::post('/residents/{id}/qr-code', [ResidentController::class, 'generateQrCode'])->middleware('permission:generate-qr-codes');
    Route::get('/households/{id}/residents', [ResidentController::class, 'byHousehold'])->middleware('permission:view-households');
    Route::patch('/residents/{id}/signature', [ResidentController::class, 'updateSignature'])->middleware('permission:edit-residents');


    // Household routes
    Route::apiResource('households', HouseholdController::class)
        ->middlewareFor(['index', 'show'], 'permission:view-households')
        ->middlewareFor('store', 'permission:create-households')
        ->middlewareFor('update', 'permission:edit-households')
        ->middlewareFor('destroy', 'permission:delete-households');
    Route::get('/households/{id}/residents', [HouseholdController::class, 'residents'])->middleware('permission:view-households');
    Route::post('/households/{id}/qr-code', [HouseholdController::class, 'generateQrCode'])->middleware('permission:generate-qr-codes');
    Route::post('/households/{id}/update-stats', [HouseholdController::class, 'updateStats'])->middleware('permission:edit-households');

    // Ayuda Program routes
    Route::get('/programs/active', [AyudaProgramController::class, 'active'])->middleware('permission:view-programs');
    Route::apiResource('programs', AyudaProgramController::class)
        ->middlewareFor(['index', 'show'], 'permission:view-programs')
        ->middlewareFor('store', 'permission:create-programs')
        ->middlewareFor('update', 'permission:edit-programs')
        ->middlewareFor('destroy', 'permission:delete-programs');
    Route::put('/programs/{id}/criteria', [AyudaProgramController::class, 'updateCriteria'])->middleware('permission:manage-eligibility-criteria');
    Route::post('/programs/{id}/check-eligibility', [AyudaProgramController::class, 'checkEligibility'])->middleware('permission:view-programs');

    // Eligibility Criteria routes
    Route::get('/programs/{programId}/criteria', [EligibilityCriteriaController::class, 'index'])->middleware('permission:view-programs');
    Route::post('/programs/{programId}/criteria', [EligibilityCriteriaController::class, 'store'])->middleware('permission:manage-eligibility-criteria');
    Route::apiResource('criteria', EligibilityCriteriaController::class)->except(['index', 'store'])->middleware('permission:manage-eligibility-criteria');
    Route::get('/criteria-options', [EligibilityCriteriaController::class, 'options'])->middleware('permission:view-programs');

    // Distribution routes
    Route::apiResource('distributions', DistributionController::class)
        ->middlewareFor(['index', 'show'], 'permission:view-distributions')
        ->middlewareFor('store', 'permission:create-distributions')
        ->middlewareFor(['update', 'destroy'], 'permission:approve-distributions');
    Route::post('/distributions/{id}/receipt', [DistributionController::class, 'uploadReceipt'])->middleware('permission:create-distributions');
    Route::post('/distributions/{id}/verify', [DistributionController::class, 'verify'])->middleware('permission:verify-beneficiaries');
    Route::get('/residents/{id}/distributions', [DistributionController::class, 'byResident'])->middleware('permission:view-distributions');
    Route::get('/households/{id}/distributions', [DistributionController::class, 'byHousehold'])->middleware('permission:view-distributions');
    Route::get('/programs/{id}/distributions', [DistributionController::class, 'byProgram'])->middleware('permission:view-distributions');
    Route::get('/batches/{id}/distributions', [DistributionController::class, 'byBatch'])->middleware('permission:view-distributions');

    // Distribution Batch routes
    Route::get('/batches/today', [DistributionBatchController::class, 'today'])->middleware('permission:view-distributions');
    Route::get('/batches/active', [DistributionBatchController::class, 'active'])->middleware('permission:view-distributions');
    Route::apiResource('batches', DistributionBatchController::class)->middleware('permission:manage-distribution-batches');
    Route::get('/batches/{id}/distributions', [DistributionBatchController::class, 'distributions'])->middleware('permission:view-distributions');
    Route::post('/batches/{id}/update-stats', [DistributionBatchController::class, 'updateStats'])->middleware('permission:manage-distribution-batches');

    // System Settings routes (admin only)
    Route::middleware('permission:configure-system')->group(function () {
    Route::get('/settings', [SystemSettingController::class, 'index']);
    Route::get('/settings/{key}', [SystemSettingController::class, 'show']);
    Route::post('/settings', [SystemSettingController::class, 'store']);
    Route::put('/settings/{key}', [SystemSettingController::class, 'update']);
    Route::delete('/settings/{key}', [SystemSettingController::class, 'destroy']);
    Route::get('/settings/group/{group}', [SystemSettingController::class, 'byGroup']);
    Route::post('/settings/clear-cache', [SystemSettingController::class, 'clearCache']);
    });

    Route::middleware('permission:manage-citizen-services')->prefix('citizen-services')->group(function () {
        Route::get('/service-requests', [CitizenServicesAdminController::class, 'serviceRequests']);
        Route::put('/service-requests/{id}', [CitizenServicesAdminController::class, 'updateServiceRequest']);

        Route::get('/grievances', [CitizenServicesAdminController::class, 'grievances']);
        Route::put('/grievances/{id}', [CitizenServicesAdminController::class, 'updateGrievance']);

        Route::get('/sos-alerts', [CitizenServicesAdminController::class, 'sosAlerts']);
        Route::put('/sos-alerts/{id}', [CitizenServicesAdminController::class, 'updateSosAlert']);

        Route::get('/portal-links', [PublicServiceLinkController::class, 'index']);
        Route::post('/portal-links', [PublicServiceLinkController::class, 'store']);
        Route::put('/portal-links/{id}', [PublicServiceLinkController::class, 'update']);
        Route::delete('/portal-links/{id}', [PublicServiceLinkController::class, 'destroy']);

        Route::get('/emergency-alerts', [EmergencyAlertController::class, 'index']);
        Route::post('/emergency-alerts', [EmergencyAlertController::class, 'store']);
        Route::put('/emergency-alerts/{id}', [EmergencyAlertController::class, 'update']);
    });
});

/*
|--------------------------------------------------------------------------
| Resident Portal Mobile API Routes
|--------------------------------------------------------------------------
|
| These routes are for the resident-facing native mobile app.
| Residents authenticate with their resident_id/email + password via Sanctum.
| All protected routes are prefixed with /api/resident-portal/
|
*/

// Resident Portal - Public (auth) routes
Route::prefix('resident-portal')->name('api.resident-portal.')->group(function () {
    Route::post('/login', [ResidentAuthController::class, 'login'])->name('login');
    Route::post('/register', [ResidentAuthController::class, 'register'])->name('register');
    Route::post('/reset-mpin', [ResidentAuthController::class, 'resetMpin'])->middleware('throttle:5,1')->name('reset-mpin');
});

// Resident Portal - Protected routes (require Sanctum token)
Route::prefix('resident-portal')->name('api.resident-portal.')->middleware(['auth:sanctum', 'idempotent'])->group(function () {
    // Auth
    Route::post('/logout', [ResidentAuthController::class, 'logout'])->name('logout');
    Route::post('/change-password', [ResidentAuthController::class, 'changePassword'])->name('change-password');
    Route::post('/refresh-token', [ResidentAuthController::class, 'refreshToken'])->name('refresh-token');
    Route::get('/sessions', [ResidentAuthController::class, 'sessions'])->name('sessions.index');
    Route::delete('/sessions/{tokenId}', [ResidentAuthController::class, 'revokeSession'])->whereNumber('tokenId')->name('sessions.destroy');

    // Profile
    Route::get('/profile', [ResidentProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ResidentProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ResidentProfileController::class, 'uploadPhoto'])->name('profile.photo');
    Route::post('/profile/signature', [ResidentProfileController::class, 'uploadSignature'])->name('profile.signature');
    Route::get('/profile/qr-code', [ResidentProfileController::class, 'qrCode'])->name('profile.qr-code');
    Route::get('/profile/id-card', [ResidentProfileController::class, 'idCard'])->name('profile.id-card');
    Route::get('/account-deletion-requests', [ResidentAccountDeletionRequestController::class, 'index'])
        ->name('account-deletion-requests.index');
    Route::post('/account-deletion-requests', [ResidentAccountDeletionRequestController::class, 'store'])
        ->name('account-deletion-requests.store');
    Route::get('/account-deletion-requests/{id}', [ResidentAccountDeletionRequestController::class, 'show'])
        ->name('account-deletion-requests.show');
    Route::get('/support-requests', [ResidentSupportRequestController::class, 'index'])->name('support-requests.index');
    Route::post('/support-requests', [ResidentSupportRequestController::class, 'store'])->name('support-requests.store');
    Route::get('/support-requests/{id}', [ResidentSupportRequestController::class, 'show'])->name('support-requests.show');

    // Household
    Route::get('/household', [ResidentHouseholdController::class, 'show'])->name('household.show');
    Route::get('/household/members', [ResidentHouseholdController::class, 'members'])->name('household.members');

    // Ayuda Programs
    Route::get('/programs', [ResidentProgramController::class, 'index'])->name('programs.index');
    Route::get('/programs/{id}', [ResidentProgramController::class, 'show'])->name('programs.show');
    Route::get('/programs/{id}/eligibility', [ResidentProgramController::class, 'checkEligibility'])->name('programs.eligibility');

    // Ayuda (user's distributions ordered by undistributed first)
    Route::get('/ayuda', [ResidentDistributionController::class, 'ayuda'])->name('ayuda');

    // Distributions
    Route::get('/distributions', [ResidentDistributionController::class, 'index'])->name('distributions.index');
    Route::get('/distributions/summary', [ResidentDistributionController::class, 'summary'])->name('distributions.summary');
    Route::get('/distributions/upcoming', [ResidentDistributionController::class, 'upcoming'])->name('distributions.upcoming');
    Route::get('/distributions/{id}', [ResidentDistributionController::class, 'show'])->name('distributions.show');

    // Announcements
    Route::get('/announcements', [ResidentAnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/{id}', [ResidentAnnouncementController::class, 'show'])->name('announcements.show');

    // Notifications
    Route::get('/notifications', [ResidentNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [ResidentNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [ResidentNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [ResidentNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/register-device', [ResidentNotificationController::class, 'registerDevice'])->name('notifications.register-device');
    Route::post('/notifications/unregister-device', [ResidentNotificationController::class, 'unregisterDevice'])->name('notifications.unregister-device');

    // Service Tracking
    Route::get('/services', [ResidentServiceTrackingController::class, 'index'])->name('services.index');
    Route::get('/service-types', [ResidentServiceTrackingController::class, 'types'])->name('service-types.index');
    Route::post('/services', [ResidentServiceTrackingController::class, 'store'])->name('services.store');
    Route::get('/services/{id}', [ResidentServiceTrackingController::class, 'show'])->name('services.show');

    // Public Service Portal
    Route::get('/public-services', [ResidentPublicServicePortalController::class, 'index'])->name('public-services.index');

    // Feedback and Grievance
    Route::get('/grievances', [ResidentGrievanceController::class, 'index'])->name('grievances.index');
    Route::post('/grievances', [ResidentGrievanceController::class, 'store'])->name('grievances.store');
    Route::get('/grievances/{id}', [ResidentGrievanceController::class, 'show'])->name('grievances.show');

    // Emergency and Alerts
    Route::get('/emergency/alerts', [ResidentEmergencyController::class, 'index'])->name('emergency.alerts');
    Route::get('/emergency/alerts/{id}', [ResidentEmergencyController::class, 'show'])->name('emergency.alerts.show');
    Route::get('/emergency/sos/departments', [ResidentEmergencyController::class, 'sosDepartments'])->name('emergency.sos.departments');
    Route::post('/emergency/sos', [ResidentEmergencyController::class, 'sos'])->name('emergency.sos');
    Route::get('/emergency/sos/history', [ResidentEmergencyController::class, 'sosHistory'])->name('emergency.sos.history');
});

require __DIR__.'/bosesmoto_api.php';
