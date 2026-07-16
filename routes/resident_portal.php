<?php

use App\Http\Controllers\ResidentPortalAuthController;
use App\Http\Controllers\ResidentPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('resident-portal')->name('resident-portal.')->middleware('resident.mobile')->group(function (): void {
    Route::middleware('guest:resident')->group(function (): void {
        Route::get('/login', [ResidentPortalAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [ResidentPortalAuthController::class, 'login'])->name('login.store');
        Route::get('/register', [ResidentPortalAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [ResidentPortalAuthController::class, 'register'])->name('register.store');
    });

    Route::middleware(['auth:resident', 'resident.session'])->group(function (): void {
        Route::get('/', [ResidentPortalController::class, 'home'])->name('home');
        Route::post('/logout', [ResidentPortalAuthController::class, 'logout'])->name('logout');
        Route::put('/account/mpin', [ResidentPortalAuthController::class, 'changeMpin'])->name('mpin.update');
        Route::put('/profile', [ResidentPortalController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/photo', [ResidentPortalController::class, 'uploadPhoto'])->name('profile.photo');
        Route::post('/profile/signature', [ResidentPortalController::class, 'uploadSignature'])->name('profile.signature');
        Route::post('/actions/support', [ResidentPortalController::class, 'storeSupport'])->name('support.store');
        Route::post('/actions/account-deletion', [ResidentPortalController::class, 'storeAccountDeletion'])->name('account-deletion.store');
        Route::post('/actions/notifications/read-all', [ResidentPortalController::class, 'readAllNotifications'])->name('notifications.read-all');
        Route::post('/actions/notifications/{notification}/read', [ResidentPortalController::class, 'readNotification'])->name('notifications.read');
        Route::post('/actions/services', [ResidentPortalController::class, 'storeService'])->name('services.store');
        Route::post('/actions/grievances', [ResidentPortalController::class, 'storeGrievance'])->name('grievances.store');
        Route::post('/actions/sos', [ResidentPortalController::class, 'storeSos'])->name('sos.store');
        Route::post('/actions/complaints', [ResidentPortalController::class, 'storeComplaint'])->name('complaints.store');
        Route::post('/actions/complaints/{complaint}/support', [ResidentPortalController::class, 'supportComplaint'])->name('complaints.support');
        Route::put('/actions/complaints/{complaint}', [ResidentPortalController::class, 'updateComplaint'])->name('complaints.update');
        Route::post('/actions/complaints/{complaint}/confirm-resolution', [ResidentPortalController::class, 'confirmComplaintResolution'])->name('complaints.confirm-resolution');
        Route::post('/actions/complaints/{complaint}/comments', [ResidentPortalController::class, 'commentComplaint'])->name('complaints.comments.store');
        Route::post('/actions/polls/{poll}/vote', [ResidentPortalController::class, 'vote'])->name('polls.vote');
        Route::post('/actions/community', [ResidentPortalController::class, 'storePost'])->name('community.store');
        Route::post('/actions/community/{post}/comments', [ResidentPortalController::class, 'commentPost'])->name('community.comments.store');
        Route::post('/actions/community/{post}/react', [ResidentPortalController::class, 'reactPost'])->name('community.react');
        Route::get('/{path?}', [ResidentPortalController::class, 'screen'])
            ->where('path', '^(?!actions(?:/|$)).*')
            ->name('screen');
    });
});
