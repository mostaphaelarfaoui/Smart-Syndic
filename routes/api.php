<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterResidentController;
use App\Http\Controllers\Api\V1\Auth\RegisterSyndicController;
use App\Http\Controllers\Api\V1\Auth\VerifyInviteCodeController;
use App\Http\Controllers\Api\V1\Resident\ResidentController;
use App\Http\Controllers\Api\V1\Syndic\ApartmentController;
use App\Http\Controllers\Api\V1\Syndic\DashboardController;
use App\Http\Controllers\Api\V1\Syndic\ExpenseController;
use App\Http\Controllers\Api\V1\Syndic\InvitationCodeController;
use App\Http\Controllers\Api\V1\Syndic\PaymentController;
use App\Http\Controllers\Api\V1\Syndic\PendingResidentController;
use App\Http\Controllers\Api\V1\Syndic\ReportController;
use App\Http\Controllers\Api\V1\Syndic\SuggestionPollController;
use App\Http\Controllers\Api\V1\Syndic\TenantSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Smart Syndic v1
|--------------------------------------------------------------------------
| Base prefix: /api/v1 (configured in bootstrap/app.php)
| All endpoints return JSON and accept Accept-Language: ar|fr|en
|--------------------------------------------------------------------------
*/

// ─── Public / Authentication (Rate Limited) ───────────────────────────
Route::prefix('auth')->group(function () {

    Route::post('/verify-invite-code', VerifyInviteCodeController::class)
        ->middleware('throttle:invite-code')
        ->name('auth.verify-invite-code');

    Route::post('/register-resident', RegisterResidentController::class)
        ->middleware('throttle:auth')
        ->name('auth.register-resident');

    Route::post('/register-syndic', RegisterSyndicController::class)
        ->middleware('throttle:auth')
        ->name('auth.register-syndic');

    Route::post('/login', LoginController::class)
        ->middleware('throttle:auth')
        ->name('auth.login');
});

// ─── Authenticated Routes ─────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'tenant.scope', 'tenant.active'])->group(function () {

    Route::put('/auth/profile', [App\Http\Controllers\Api\V1\Auth\ProfileController::class, 'update'])
        ->name('auth.profile-update');

    Route::post('/auth/verify-password', \App\Http\Controllers\Api\V1\Auth\VerifyPasswordController::class)
        ->name('auth.verify-password');

    // ─── Real-Time Notifications Hub ──────────────────────────────
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'index'])->name('api.notifications.index');
        Route::post('/{id}/read', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'markAsRead'])->name('api.notifications.read');
        Route::post('/mark-all-read', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'markAllAsRead'])->name('api.notifications.mark-all-read');
        Route::delete('/{id}', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'destroy'])->name('api.notifications.destroy');
        Route::delete('/clear-all', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'clearAll'])->name('api.notifications.clear-all');
    });

    // ─── Syndic / Admin Operations ────────────────────────────────
    Route::prefix('syndic')
        ->middleware('role:ADMIN_SYNDIC')
        ->group(function () {

            // ─── Dashboard KPIs ──────────────────────────────────
            Route::get('/dashboard/kpis', [DashboardController::class, 'kpis'])
                ->name('syndic.dashboard.kpis');

            // ─── Tenant Settings (Feature 1, 9) ──────────────────
            Route::get('/settings', [TenantSettingsController::class, 'show'])
                ->name('syndic.settings.show');
            Route::put('/settings', [TenantSettingsController::class, 'update'])
                ->name('syndic.settings.update');
            Route::post('/settings/stamp', [TenantSettingsController::class, 'uploadStamp'])
                ->name('syndic.settings.stamp.upload');
            Route::delete('/settings/stamp', [TenantSettingsController::class, 'deleteStamp'])
                ->name('syndic.settings.stamp.delete');

            // ─── Apartments ───────────────────────────────────────
            Route::get('/apartments', [ApartmentController::class, 'index'])
                ->name('syndic.apartments.index');
            Route::post('/apartments', [ApartmentController::class, 'store'])
                ->middleware('throttle:financial')
                ->name('syndic.apartments.store');
            Route::post('/apartments/bulk-generate', [ApartmentController::class, 'bulkGenerate'])
                ->name('syndic.apartments.bulk-generate');
            Route::put('/apartments/{id}', [ApartmentController::class, 'update'])
                ->name('syndic.apartments.update');
            Route::delete('/apartments/{id}', [ApartmentController::class, 'destroy'])
                ->name('syndic.apartments.destroy');
            // Feature 4 — Toggle billing for developer-owned vacant apartments
            Route::match(['put', 'post'], '/apartments/{id}/toggle-billable', [ApartmentController::class, 'toggleBillable'])
                ->name('syndic.apartments.toggle-billable');
            // Vacate apartment and delete resident account
            Route::post('/apartments/{id}/vacate', [ApartmentController::class, 'vacate'])
                ->name('syndic.apartments.vacate');

            // ─── Pending Resident Management ──────────────────────
            Route::get('/pending-residents', [PendingResidentController::class, 'index'])
                ->name('syndic.pending-residents.index');
            Route::post('/pending-residents/{id}/approve', [PendingResidentController::class, 'approve'])
                ->name('syndic.pending-residents.approve');
            Route::post('/pending-residents/{id}/reject', [PendingResidentController::class, 'reject'])
                ->name('syndic.pending-residents.reject');

            // ─── Payments & Collections (Feature 12 workflow) ─────
            Route::get('/payments', [PaymentController::class, 'index'])
                ->name('syndic.payments.index');
            Route::post('/payments', [PaymentController::class, 'store'])
                ->middleware('throttle:financial')
                ->name('syndic.payments.store');
            // Feature 12 — Workflow transitions
            Route::put('/payments/{id}', [PaymentController::class, 'update'])
                ->name('syndic.payments.update');
            Route::delete('/payments/{id}', [PaymentController::class, 'destroy'])
                ->name('syndic.payments.destroy');
            Route::post('/payments/{id}/confirm', [PaymentController::class, 'confirm'])
                ->name('syndic.payments.confirm');
            Route::post('/payments/{id}/send', [PaymentController::class, 'send'])
                ->name('syndic.payments.send');

            // ─── Expenses ─────────────────────────────────────────
            Route::get('/expenses', [ExpenseController::class, 'index'])
                ->name('syndic.expenses.index');
            Route::post('/expenses', [ExpenseController::class, 'store'])
                ->middleware('throttle:financial')
                ->name('syndic.expenses.store');

            // ─── Financial Reports ────────────────────────────────
            Route::get('/reports/export', [ReportController::class, 'export'])
                ->name('syndic.reports.export');

            // ─── Suggestions & Polls ──────────────────────────────
            Route::get('/suggestions', [SuggestionPollController::class, 'suggestions'])
                ->name('syndic.suggestions.index');
            Route::post('/suggestions/{id}/convert-to-poll', [SuggestionPollController::class, 'convertToPoll'])
                ->name('syndic.suggestions.convert-to-poll');
            Route::post('/suggestions/{id}/accept', [SuggestionPollController::class, 'acceptSuggestion'])
                ->name('syndic.suggestions.accept');
            Route::post('/suggestions/{id}/reject', [SuggestionPollController::class, 'rejectSuggestion'])
                ->name('syndic.suggestions.reject');
            Route::get('/polls', [SuggestionPollController::class, 'polls'])
                ->name('syndic.polls.index');
            Route::post('/polls', [SuggestionPollController::class, 'createPoll'])
                ->name('syndic.polls.store');

            // ─── Invitation Code ──────────────────────────────────
            Route::post('/invitation-code/regenerate', [InvitationCodeController::class, 'regenerate'])
                ->name('syndic.invitation-code.regenerate');
        });

    // ─── Resident Operations ──────────────────────────────────────
    Route::prefix('resident')
        ->middleware('role:RESIDENT')
        ->group(function () {

            Route::get('/profile', [ResidentController::class, 'profile'])
                ->name('resident.profile');

            Route::get('/my-balance', [ResidentController::class, 'myBalance'])
                ->name('resident.my-balance');

            Route::get('/payments', [ResidentController::class, 'payments'])
                ->name('resident.payments');

            Route::get('/transparency-ledger', [ResidentController::class, 'transparencyLedger'])
                ->name('resident.transparency-ledger');

            Route::get('/suggestions', [ResidentController::class, 'mySuggestions'])
                ->name('resident.suggestions.index');

            Route::post('/suggestions', [ResidentController::class, 'submitSuggestion'])
                ->name('resident.suggestions.store');

            Route::get('/polls', [ResidentController::class, 'polls'])
                ->name('resident.polls.index');

            Route::post('/polls/{id}/vote', [ResidentController::class, 'castVote'])
                ->name('resident.polls.vote');
        });
});
