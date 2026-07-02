<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileCitizenComplaintController;
use App\Http\Controllers\Api\MobileComplaintController;
use App\Http\Controllers\Api\MobileComplaintEngagementController;
use App\Http\Controllers\Api\MobileLookupController;
use App\Http\Controllers\Api\MobilePollController;
use App\Http\Controllers\Api\MobileProfileController;
use App\Http\Controllers\Api\MobileSentimentController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [MobileAuthController::class, 'register'])->name('api.mobile.auth.register');
        Route::post('/login', [MobileAuthController::class, 'login'])->name('api.mobile.auth.login');
        Route::middleware('auth:sanctum')->post('/logout', [MobileAuthController::class, 'logout'])->name('api.mobile.auth.logout');
    });

    Route::prefix('lookups')->group(function () {
        Route::get('/categories', [MobileLookupController::class, 'categories'])->name('api.mobile.lookups.categories');
        Route::get('/barangays', [MobileLookupController::class, 'barangays'])->name('api.mobile.lookups.barangays');
        Route::get('/officials', [MobileLookupController::class, 'officials'])->name('api.mobile.lookups.officials');
    });

    Route::get('/complaints', [MobileComplaintController::class, 'index'])->name('api.mobile.complaints.index');
    Route::get('/complaints/{complaint}', [MobileComplaintController::class, 'show'])->whereNumber('complaint')->name('api.mobile.complaints.show');
    Route::get('/complaints/{complaint}/preview-image', [MobileComplaintController::class, 'previewImage'])->whereNumber('complaint')->name('api.mobile.complaints.preview-image');
    Route::post('/complaints', [MobileComplaintController::class, 'store'])->name('api.mobile.complaints.store');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [MobileProfileController::class, 'me'])->name('api.mobile.me.show');
        Route::patch('/me', [MobileProfileController::class, 'update'])->name('api.mobile.me.update');
        Route::post('/me/photo', [MobileProfileController::class, 'updatePhoto'])->name('api.mobile.me.photo.update');

        Route::get('/polls', [MobilePollController::class, 'index'])->name('api.mobile.polls.index');
        Route::get('/polls/{poll}', [MobilePollController::class, 'show'])->whereNumber('poll')->name('api.mobile.polls.show');
        Route::post('/polls/{poll}/vote', [MobilePollController::class, 'vote'])->whereNumber('poll')->name('api.mobile.polls.vote');

        Route::get('/sentiments', [MobileSentimentController::class, 'index'])->name('api.mobile.sentiments.index');
        Route::post('/sentiments/posts', [MobileSentimentController::class, 'storePost'])->name('api.mobile.sentiments.posts.store');
        Route::post('/sentiments/posts/{post}/react', [MobileSentimentController::class, 'reactPost'])->whereNumber('post')->name('api.mobile.sentiments.posts.react');
        Route::get('/sentiments/posts/{post}/media', [MobileSentimentController::class, 'media'])->whereNumber('post')->name('api.mobile.sentiments.posts.media');
        Route::get('/sentiments/posts/{post}/comments', [MobileSentimentController::class, 'comments'])->whereNumber('post')->name('api.mobile.sentiments.comments.index');
        Route::post('/sentiments/posts/{post}/comments', [MobileSentimentController::class, 'storeComment'])->whereNumber('post')->name('api.mobile.sentiments.comments.store');
        Route::post('/sentiments/comments/{comment}/react', [MobileSentimentController::class, 'reactComment'])->whereNumber('comment')->name('api.mobile.sentiments.comments.react');

        Route::post('/complaints/{complaint}/support', [MobileComplaintEngagementController::class, 'support'])->whereNumber('complaint')->name('api.mobile.complaints.support');
        Route::post('/complaints/{complaint}/comments', [MobileComplaintEngagementController::class, 'storeComment'])->whereNumber('complaint')->name('api.mobile.complaints.comments.store');
        Route::post('/complaints/{complaint}/comments/{comment}/react', [MobileComplaintEngagementController::class, 'reactComment'])
            ->whereNumber('complaint')
            ->whereNumber('comment')
            ->name('api.mobile.complaints.comments.react');

        Route::prefix('my')->group(function () {
            Route::get('/complaints', [MobileCitizenComplaintController::class, 'index'])->name('api.mobile.my.complaints.index');
            Route::get('/complaints/{complaint}', [MobileCitizenComplaintController::class, 'show'])->whereNumber('complaint')->name('api.mobile.my.complaints.show');
            Route::put('/complaints/{complaint}', [MobileCitizenComplaintController::class, 'update'])->whereNumber('complaint')->name('api.mobile.my.complaints.update');
            Route::post('/complaints/{complaint}/confirm-resolution', [MobileCitizenComplaintController::class, 'confirmResolution'])
                ->whereNumber('complaint')
                ->name('api.mobile.my.complaints.confirm-resolution');
        });
    });
});
