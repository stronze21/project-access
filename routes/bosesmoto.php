<?php

use App\Http\Controllers\ActionOfficerController;
use App\Http\Controllers\ComplaintAttachmentController;
use App\Http\Controllers\ComplaintAuditController;
use App\Http\Controllers\ComplaintBarangayController;
use App\Http\Controllers\ComplaintCategoryController;
use App\Http\Controllers\ComplaintCommentController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ComplaintManagementController;
use App\Http\Controllers\ComplaintReportController;
use App\Http\Controllers\ComplaintSupportController;
use App\Http\Controllers\ComplaintWorkflowController;
use App\Http\Controllers\DashboardController as BosesmotoDashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ExecutiveDashboardController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PublicOfficialController;
use App\Http\Controllers\ReferenceDataController;
use App\Http\Controllers\SentimentCommentController;
use App\Http\Controllers\SentimentFeedController;
use App\Http\Controllers\SentimentFollowController;
use App\Http\Controllers\SentimentReactionController;
use App\Http\Controllers\SentimentReportController;
use App\Http\Controllers\SosDepartmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('module.enabled:bosesmoto,complaints')->group(function () {
    Route::get('/complaints', [ComplaintController::class, 'publicIndex'])->name('complaints.public.index');
    Route::get('/complaints/similar', [ComplaintController::class, 'similar'])->name('complaints.similar');
    Route::get('/complaints/{complaint}/preview-image', [ComplaintController::class, 'previewImage'])
        ->whereNumber('complaint')
        ->name('complaints.preview-image');
    Route::get('/complaints/submit-anonymous', [ComplaintController::class, 'createAnonymous'])
        ->name('complaints.anonymous.create');
    Route::post('/complaints/anonymous', [ComplaintController::class, 'storeAnonymous'])
        ->name('complaints.anonymous.store');
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'publicShow'])
        ->whereNumber('complaint')
        ->name('complaints.public.show');
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/bosesmoto/dashboard', [BosesmotoDashboardController::class, 'index'])
        ->middleware('module.enabled:bosesmoto')
        ->name('bosesmoto.dashboard');

    Route::prefix('sentiments')->name('sentiments.')->middleware('module.enabled:bosesmoto,sentiments')->group(function () {
        Route::get('/', [SentimentFeedController::class, 'index'])->name('index');
        Route::get('/trending', [SentimentFeedController::class, 'trending'])->name('trending');
        Route::get('/fragment', [SentimentFeedController::class, 'fragment'])->name('fragment');
        Route::post('/posts', [SentimentFeedController::class, 'store'])->name('posts.store');
        Route::put('/posts/{post}', [SentimentFeedController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [SentimentFeedController::class, 'destroy'])->name('posts.destroy');
        Route::post('/posts/{post}/moderate', [SentimentFeedController::class, 'moderate'])->name('posts.moderate');
        Route::get('/posts/{post}/media', [SentimentFeedController::class, 'media'])->name('posts.media');
        Route::post('/posts/{post}/react', [SentimentReactionController::class, 'reactPost'])->name('posts.react');
        Route::get('/posts/{post}/reactors', [SentimentReactionController::class, 'postReactors'])->name('posts.reactors');
        Route::post('/posts/{post}/report', [SentimentReportController::class, 'reportPost'])->name('posts.report');
        Route::post('/posts/{post}/comments', [SentimentCommentController::class, 'store'])->name('comments.store');
        Route::put('/comments/{comment}', [SentimentCommentController::class, 'update'])->name('comments.update');
        Route::delete('/comments/{comment}', [SentimentCommentController::class, 'destroy'])->name('comments.destroy');
        Route::post('/comments/{comment}/moderate', [SentimentCommentController::class, 'moderate'])->name('comments.moderate');
        Route::post('/comments/{comment}/react', [SentimentReactionController::class, 'reactComment'])->name('comments.react');
        Route::get('/comments/{comment}/reactors', [SentimentReactionController::class, 'commentReactors'])->name('comments.reactors');
        Route::post('/comments/{comment}/report', [SentimentReportController::class, 'reportComment'])->name('comments.report');
        Route::post('/users/{user}/follow', [SentimentFollowController::class, 'store'])->name('users.follow');
        Route::delete('/users/{user}/follow', [SentimentFollowController::class, 'destroy'])->name('users.unfollow');
    });

    Route::middleware('module.enabled:bosesmoto,polls')->group(function () {
        Route::get('/polls', [PollController::class, 'index'])->name('polls.index');
        Route::get('/polls/create', [PollController::class, 'create'])
            ->middleware('role:Admin|Super Admin|Mayor|system-administrator|mayor')
            ->name('polls.create');
        Route::post('/polls', [PollController::class, 'store'])
            ->middleware('role:Admin|Super Admin|Mayor|system-administrator|mayor')
            ->name('polls.store');
        Route::get('/polls/{poll}', [PollController::class, 'show'])->whereNumber('poll')->name('polls.show');
        Route::post('/polls/{poll}/vote', [PollController::class, 'vote'])->whereNumber('poll')->name('polls.vote');
    });

    Route::middleware('module.enabled:bosesmoto,complaints')->group(function () {
        Route::prefix('my-complaints')->name('complaints.my.')->group(function () {
            Route::get('/', [ComplaintController::class, 'myIndex'])->name('index');
        });

        Route::get('/complaints/quick-create', [ComplaintController::class, 'createQuick'])->name('complaints.quick.create');
        Route::post('/complaints/quick', [ComplaintController::class, 'storeQuick'])->name('complaints.quick.store');
        Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
        Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');
        Route::get('/complaints/{complaint}/edit', [ComplaintController::class, 'edit'])->name('complaints.edit');
        Route::put('/complaints/{complaint}', [ComplaintController::class, 'update'])->name('complaints.update');
        Route::post('/complaints/{complaint}/confirm-resolution', [ComplaintController::class, 'confirmResolution'])->name('complaints.confirm-resolution');
        Route::post('/complaints/{complaint}/support', [ComplaintSupportController::class, 'store'])->name('complaints.support');
        Route::post('/complaints/{complaint}/comments', [ComplaintCommentController::class, 'store'])->name('complaints.comments.store');
        Route::post('/complaints/{complaint}/comments/{comment}/react', [ComplaintCommentController::class, 'react'])->name('complaints.comments.react');

        Route::prefix('management/complaints')
            ->name('complaints.manage.')
            ->middleware('role:Admin|Super Admin|Mayor|Department Head|Action Officer|system-administrator|mayor|department-head|action-officer')
            ->group(function () {
                Route::get('/', [ComplaintManagementController::class, 'index'])->name('index');
                Route::get('/{complaint}', [ComplaintManagementController::class, 'show'])->name('show');
                Route::post('/{complaint}/assign-department', [ComplaintWorkflowController::class, 'assignDepartment'])->name('assign-department');
                Route::post('/{complaint}/assign-officer', [ComplaintWorkflowController::class, 'assignOfficer'])->name('assign-officer');
                Route::post('/{complaint}/set-priority', [ComplaintWorkflowController::class, 'setPriority'])->name('set-priority');
                Route::post('/{complaint}/status', [ComplaintWorkflowController::class, 'updateStatus'])->name('status');
                Route::post('/{complaint}/internal-note', [ComplaintWorkflowController::class, 'addInternalNote'])->name('internal-note');
                Route::post('/{complaint}/moderate', [ComplaintWorkflowController::class, 'moderate'])->name('moderate');
                Route::post('/{complaint}/override', [ComplaintWorkflowController::class, 'override'])->name('override');
                Route::post('/{complaint}/official-tags', [ComplaintWorkflowController::class, 'syncOfficials'])->name('official-tags');
                Route::post('/{complaint}/attachments', [ComplaintAttachmentController::class, 'store'])->name('attachments.store');
                Route::get('/{complaint}/attachments/{attachment}', [ComplaintAttachmentController::class, 'download'])->name('attachments.download');
                Route::post('/{complaint}/comments/{comment}/hide', [ComplaintCommentController::class, 'hide'])->name('comments.hide');
            });

        Route::prefix('management/reference')->middleware('role:Admin|Super Admin|system-administrator')->group(function () {
            Route::get('/', ReferenceDataController::class)->name('complaints.references.index');
            Route::get('/categories', [ComplaintCategoryController::class, 'index'])->name('complaints.categories.index');
            Route::post('/categories', [ComplaintCategoryController::class, 'store'])->name('complaints.categories.store');
            Route::put('/categories/{category}', [ComplaintCategoryController::class, 'update'])->name('complaints.categories.update');
            Route::delete('/categories/{category}', [ComplaintCategoryController::class, 'destroy'])->name('complaints.categories.destroy');
            Route::get('/departments', [DepartmentController::class, 'index'])->name('complaints.departments.index');
            Route::post('/departments', [DepartmentController::class, 'store'])->name('complaints.departments.store');
            Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('complaints.departments.update');
            Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('complaints.departments.destroy');
            Route::get('/public-officials', [PublicOfficialController::class, 'index'])->name('complaints.officials.index');
            Route::post('/public-officials', [PublicOfficialController::class, 'store'])->name('complaints.officials.store');
            Route::put('/public-officials/{official}', [PublicOfficialController::class, 'update'])->name('complaints.officials.update');
            Route::delete('/public-officials/{official}', [PublicOfficialController::class, 'destroy'])->name('complaints.officials.destroy');
            Route::get('/barangays', [ComplaintBarangayController::class, 'index'])->name('complaints.barangays.index');
            Route::post('/barangays', [ComplaintBarangayController::class, 'store'])->name('complaints.barangays.store');
            Route::put('/barangays/{barangay}', [ComplaintBarangayController::class, 'update'])->name('complaints.barangays.update');
            Route::delete('/barangays/{barangay}', [ComplaintBarangayController::class, 'destroy'])->name('complaints.barangays.destroy');
            Route::get('/action-officers', [ActionOfficerController::class, 'index'])->name('complaints.action-officers.index');
            Route::post('/action-officers', [ActionOfficerController::class, 'store'])->name('complaints.action-officers.store');
            Route::put('/action-officers/{officer}', [ActionOfficerController::class, 'update'])->name('complaints.action-officers.update');
            Route::delete('/action-officers/{officer}', [ActionOfficerController::class, 'destroy'])->name('complaints.action-officers.destroy');
            Route::get('/sos-departments', [SosDepartmentController::class, 'index'])->name('complaints.sos-departments.index');
            Route::post('/sos-departments', [SosDepartmentController::class, 'store'])->name('complaints.sos-departments.store');
            Route::put('/sos-departments/{sosDepartment}', [SosDepartmentController::class, 'update'])->name('complaints.sos-departments.update');
            Route::delete('/sos-departments/{sosDepartment}', [SosDepartmentController::class, 'destroy'])->name('complaints.sos-departments.destroy');
        });

        Route::get('/management/audit-logs', [ComplaintAuditController::class, 'index'])
            ->middleware('role:Admin|Super Admin|Mayor|system-administrator|mayor')
            ->name('complaints.audit.index');
        Route::get('/executive/dashboard', [ExecutiveDashboardController::class, 'index'])
            ->middleware('role:Mayor|mayor')
            ->name('complaints.executive.dashboard');
        Route::get('/reports/monthly', [ComplaintReportController::class, 'monthly'])
            ->middleware('role:Admin|Super Admin|Mayor|system-administrator|mayor')
            ->name('complaints.reports.monthly');
    });
});
