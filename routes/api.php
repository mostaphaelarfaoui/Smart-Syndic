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

    // ─── Syndic / Admin Operations ────────────────────────────────
    Route::prefix('syndic')
        ->middleware('role:ADMIN_SYNDIC')
        ->group(function () {

            // Dashboard KPIs
            Route::get('/dashboard/kpis', [DashboardController::class, 'kpis'])
                ->name('syndic.dashboard.kpis');

            // Real Estate & Apartments
            Route::get('/apartments', [ApartmentController::class, 'index'])
                ->name('syndic.apartments.index');
            Route::post('/apartments', [ApartmentController::class, 'store'])
                ->middleware('throttle:financial')
                ->name('syndic.apartments.store');

            // Pending Resident Management
            Route::get('/pending-residents', [PendingResidentController::class, 'index'])
                ->name('syndic.pending-residents.index');
            Route::post('/pending-residents/{id}/approve', [PendingResidentController::class, 'approve'])
                ->name('syndic.pending-residents.approve');
            Route::post('/pending-residents/{id}/reject', [PendingResidentController::class, 'reject'])
                ->name('syndic.pending-residents.reject');

            // Payments & Collections
            Route::get('/payments', [PaymentController::class, 'index'])
                ->name('syndic.payments.index');
            Route::post('/payments', [PaymentController::class, 'store'])
                ->middleware('throttle:financial')
                ->name('syndic.payments.store');

            // Expenses
            Route::get('/expenses', [ExpenseController::class, 'index'])
                ->name('syndic.expenses.index');
            Route::post('/expenses', [ExpenseController::class, 'store'])
                ->middleware('throttle:financial')
                ->name('syndic.expenses.store');

            // Financial Reports
            Route::get('/reports/export', [ReportController::class, 'export'])
                ->name('syndic.reports.export');

            // Suggestions & Polls
            Route::get('/suggestions', [SuggestionPollController::class, 'suggestions'])
                ->name('syndic.suggestions.index');
            Route::post('/suggestions/{id}/convert-to-poll', [SuggestionPollController::class, 'convertToPoll'])
                ->name('syndic.suggestions.convert-to-poll');
            Route::post('/polls', [SuggestionPollController::class, 'createPoll'])
                ->name('syndic.polls.store');

            // Invitation Code
            Route::post('/invitation-code/regenerate', [InvitationCodeController::class, 'regenerate'])
                ->name('syndic.invitation-code.regenerate');
        });

    // ─── Resident Operations ──────────────────────────────────────
    Route::prefix('resident')
        ->middleware('role:RESIDENT')
        ->group(function () {

            Route::get('/my-balance', [ResidentController::class, 'myBalance'])
                ->name('resident.my-balance');

            Route::get('/payments', [ResidentController::class, 'payments'])
                ->name('resident.payments');

            Route::get('/transparency-ledger', [ResidentController::class, 'transparencyLedger'])
                ->name('resident.transparency-ledger');

            Route::post('/suggestions', [ResidentController::class, 'submitSuggestion'])
                ->name('resident.suggestions.store');

            Route::get('/polls', [ResidentController::class, 'polls'])
                ->name('resident.polls.index');

            Route::post('/polls/{id}/vote', [ResidentController::class, 'castVote'])
                ->name('resident.polls.vote');
        });
});
