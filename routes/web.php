<?php

use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\Portal\AnalyticsController;
use App\Http\Controllers\Web\Portal\ApartmentController;
use App\Http\Controllers\Web\Portal\ArchiveController;
use App\Http\Controllers\Web\Portal\AuthController;
use App\Http\Controllers\Web\Portal\CommunityController;
use App\Http\Controllers\Web\Portal\DashboardController;
use App\Http\Controllers\Web\Portal\ExpenseController;
use App\Http\Controllers\Web\Portal\FinancialReportController;
use App\Http\Controllers\Web\Portal\PaymentController;
use App\Http\Controllers\Web\Portal\ResidentController;
use App\Http\Controllers\Web\Portal\SettingsController;
use App\Http\Controllers\Web\SuperAdmin\SuperAdminController;
use App\Models\Payment;
use App\Services\PdfReceiptService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes — Smart Syndic Custom Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('portal.login');
});

Route::get('/login', function () {
    return redirect()->route('portal.login');
})->name('login');

// Redirect any legacy /super-admin requests directly to the modern platform portal
Route::any('/super-admin/{any?}', function () {
    return redirect()->route('superadmin.dashboard');
})->where('any', '.*');

// ─── Direct Receipt Download (Secured & Access-Controlled) ───────────────
Route::get('/receipts/{payment}/download', function (\Illuminate\Http\Request $request, Payment $payment) {
    $isAuthorized = false;

    // 1. Verify cryptographic token in URL query parameter (?token=...)
    $expectedToken = $payment->getReceiptToken();
    $providedToken = $request->query('token') ?? $request->query('signature');
    if ($providedToken && hash_equals($expectedToken, (string) $providedToken)) {
        $isAuthorized = true;
    }

    // 2. Fallback check for Laravel signed route if used
    if (! $isAuthorized && $request->hasValidSignature(false)) {
        $isAuthorized = true;
    }

    // 3. Check Sanctum API token passed via query param or Authorization header
    if (! $isAuthorized) {
        $bearerToken = $request->bearerToken() ?? $request->query('auth_token') ?? $request->query('api_token');
        if ($bearerToken) {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);
            if ($tokenModel && $tokenModel->tokenable) {
                $user = $tokenModel->tokenable;
                if ($user->isSuperAdmin() || ($user->isAdminSyndic() && $user->tenant_id === $payment->tenant_id) || $payment->user_id === $user->id) {
                    $isAuthorized = true;
                }
            }
        }
    }

    // 4. Check web session or Sanctum guard
    if (! $isAuthorized) {
        $user = auth('web')->user() ?? auth('sanctum')->user();
        if ($user) {
            if ($user->isSuperAdmin() || ($user->isAdminSyndic() && $user->tenant_id === $payment->tenant_id) || $payment->user_id === $user->id) {
                $isAuthorized = true;
            }
        }
    }

    if (! $isAuthorized) {
        abort(401, 'يجب تسجيل الدخول أو استخدام رابط موثق لتحميل وصل الأداء.');
    }

    $path = $payment->receipt_pdf_path;
    $disk = config('smartsyndic.uploads.storage_disk', 'public');

    if (! $path || ! Storage::disk($disk)->exists($path) || str_ends_with($path, '.html')) {
        $pdfService = app(PdfReceiptService::class);
        $path = $pdfService->generateReceipt($payment);
        $payment->update(['receipt_pdf_path' => $path]);
    }

    $fullPath = Storage::disk($disk)->path($path);
    $filename = "recu-{$payment->receipt_number}.pdf";

    if (str_ends_with($path, '.html')) {
        return response()->file($fullPath, [
            'Content-Type'        => 'text/html; charset=UTF-8',
            'Content-Disposition' => "inline; filename=\"recu-{$payment->receipt_number}.html\"",
        ]);
    }

    $disposition = $request->boolean('download', false) ? 'attachment' : 'inline';

    return response()->file($fullPath, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
    ]);
})->name('receipts.download');

// ─── Portal Authentication Routes ────────────────────────────────────────
Route::get('/portal/login', [AuthController::class, 'showLogin'])->name('portal.login');
Route::post('/portal/login', [AuthController::class, 'login'])
    ->middleware('throttle:60,1')
    ->name('portal.login.submit');
Route::post('/portal/logout', [AuthController::class, 'logout'])->name('portal.logout');
Route::post('/portal/verify-password', [AuthController::class, 'verifyPassword'])
    ->name('portal.verify-password')
    ->middleware('auth:web');

// ─── Syndic Management Portal ────────────────────────────────────────────
Route::middleware(['auth:web', 'role:ADMIN_SYNDIC,SUPER_ADMIN'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Apartments
    Route::get('/apartments', [ApartmentController::class, 'index'])->name('apartments.index');
    Route::get('/apartments/{id}/history', [ApartmentController::class, 'history'])->name('apartments.history');
    Route::post('/apartments', [ApartmentController::class, 'store'])->name('apartments.store');
    Route::post('/apartments/bulk-generate', [ApartmentController::class, 'bulkGenerate'])->name('apartments.bulk-generate');
    Route::post('/apartments/{id}/vacate', [ApartmentController::class, 'vacate'])->name('apartments.vacate');
    Route::post('/apartments/{id}/toggle-billable', [ApartmentController::class, 'toggleBillable'])->name('apartments.toggle-billable');
    Route::delete('/apartments/{id}', [ApartmentController::class, 'destroy'])->name('apartments.destroy');


    // Residents
    Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
    Route::post('/residents/{id}/approve', [ResidentController::class, 'approve'])->name('residents.approve');
    Route::post('/residents/{id}/reject', [ResidentController::class, 'reject'])->name('residents.reject');
    Route::post('/residents/regenerate-code', [ResidentController::class, 'regenerateInviteCode'])->name('residents.regenerate-code');
    Route::delete('/residents/{id}', [ResidentController::class, 'destroy'])->name('residents.destroy');

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/payments/{id}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');

    // Expenses
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    // Analytics & Financial Reports
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/reports', [FinancialReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/financial-pdf', [FinancialReportController::class, 'generate'])->name('reports.financial-pdf');

    // Archives & Audit Logs (New!)
    Route::get('/archives', [ArchiveController::class, 'index'])->name('archives.index');

    // Community & Polls
    Route::get('/community', [CommunityController::class, 'index'])->name('community.index');
    Route::post('/community/suggestions/{id}/update', [CommunityController::class, 'updateSuggestionStatus'])->name('community.suggestions.update');
    Route::post('/community/suggestions/{id}/convert-to-poll', [CommunityController::class, 'convertToPoll'])->name('community.suggestions.convert');
    Route::post('/community/polls', [CommunityController::class, 'storePoll'])->name('community.polls.store');
    Route::post('/community/polls/{id}/toggle', [CommunityController::class, 'togglePoll'])->name('community.polls.toggle');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/stamp', [SettingsController::class, 'uploadStamp'])->name('settings.stamp.upload');
    Route::delete('/settings/stamp', [SettingsController::class, 'deleteStamp'])->name('settings.stamp.delete');
});

// ─── Super Admin Central Portal ──────────────────────────────────────────
Route::middleware(['auth:web', 'role:SUPER_ADMIN'])->prefix('platform')->name('superadmin.')->group(function () {
    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/search', [SuperAdminController::class, 'globalSearch'])->name('search');
    
    // Tenants & Organizations
    Route::get('/tenants', [SuperAdminController::class, 'tenants'])->name('tenants.index');
    Route::get('/tenants/{id}', [SuperAdminController::class, 'showTenant'])->name('tenants.show');
    Route::post('/tenants/{id}/approve', [SuperAdminController::class, 'approveTenant'])->name('tenants.approve');
    Route::post('/tenants/{id}/reject', [SuperAdminController::class, 'rejectTenant'])->name('tenants.reject');
    Route::post('/tenants/{id}/suspend', [SuperAdminController::class, 'suspendTenant'])->name('tenants.suspend');
    Route::delete('/tenants/{id}', [SuperAdminController::class, 'destroyTenant'])->name('tenants.destroy');

    // Global Users Directory
    Route::get('/users', [SuperAdminController::class, 'users'])->name('users.index');
    Route::post('/users/create-superadmin', [SuperAdminController::class, 'storeSuperAdmin'])->name('users.create-superadmin');
    Route::post('/users/{id}/toggle-status', [SuperAdminController::class, 'toggleUserStatus'])->name('users.toggle-status');
    Route::post('/users/{id}/reset-password', [SuperAdminController::class, 'resetUserPassword'])->name('users.reset-password');
    Route::post('/users/{id}/unblock', [SuperAdminController::class, 'unblockUser'])->name('users.unblock');

    // Server & Database Health
    Route::get('/server', [SuperAdminController::class, 'server'])->name('server.index');
    Route::post('/server/vacuum', [SuperAdminController::class, 'vacuumDb'])->name('server.vacuum');
    Route::get('/server/backup', [SuperAdminController::class, 'backupDb'])->name('server.backup');
    Route::post('/server/backup/create', [SuperAdminController::class, 'createBackupSnapshot'])->name('server.backup.create');
    Route::get('/server/backup/download/{filename}', [SuperAdminController::class, 'downloadBackupSnapshot'])->name('server.backup.download-file');
    Route::delete('/server/backup/{filename}', [SuperAdminController::class, 'deleteBackupSnapshot'])->name('server.backup.delete-file');
    Route::get('/api-stats', [SuperAdminController::class, 'apiStats'])->name('api-stats.index');
    Route::delete('/api-stats/tokens/{id}', [SuperAdminController::class, 'revokeApiToken'])->name('api-stats.revoke-token');

    // Security, Fraud & Firewall
    Route::get('/security', [SuperAdminController::class, 'security'])->name('security.index');
    Route::get('/login-history', [SuperAdminController::class, 'loginHistory'])->name('login-history.index');
    Route::post('/security/incidents/{id}/resolve', [SuperAdminController::class, 'resolveIncident'])->name('security.incidents.resolve');
    Route::post('/security/block-ip', [SuperAdminController::class, 'blockIp'])->name('security.block-ip');
    Route::delete('/security/unblock-ip/{id}', [SuperAdminController::class, 'unblockIp'])->name('security.unblock-ip');

    // Live Logs Viewer
    Route::get('/logs', [SuperAdminController::class, 'logs'])->name('logs.index');
    Route::post('/logs/clear', [SuperAdminController::class, 'clearLogs'])->name('logs.clear');
    Route::get('/logs/download', [SuperAdminController::class, 'downloadLogs'])->name('logs.download');

    // Platform Intelligence & Executive Reports
    Route::get('/reports', [SuperAdminController::class, 'reports'])->name('reports.index');
    Route::get('/reports/export-csv', [SuperAdminController::class, 'exportReportCsv'])->name('reports.export-csv');

    // Audit Trail
    Route::get('/audit', [SuperAdminController::class, 'auditLogs'])->name('audit.index');

    // Broadcasts & Announcements (Feature 3)
    Route::get('/announcements', [SuperAdminController::class, 'announcements'])->name('announcements.index');
    Route::post('/announcements', [SuperAdminController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::post('/announcements/{id}/toggle', [SuperAdminController::class, 'toggleAnnouncement'])->name('announcements.toggle');
    Route::delete('/announcements/{id}', [SuperAdminController::class, 'destroyAnnouncement'])->name('announcements.destroy');

    // SaaS Subscriptions & Quotas (Feature 4)
    Route::get('/subscriptions', [SuperAdminController::class, 'subscriptions'])->name('subscriptions.index');
    Route::post('/subscriptions/{id}/update', [SuperAdminController::class, 'updateSubscription'])->name('subscriptions.update');
    Route::post('/subscriptions/{id}/extend', [SuperAdminController::class, 'extendSubscription'])->name('subscriptions.extend');

    // System Settings & Feature Flags (Feature 5)
    Route::get('/settings', [SuperAdminController::class, 'settings'])->name('settings.index');
    Route::post('/settings', [SuperAdminController::class, 'updateSettings'])->name('settings.update');
    Route::post('/settings/maintenance/toggle', [SuperAdminController::class, 'toggleMaintenance'])->name('settings.maintenance.toggle');

    // Impersonation Support Actions
    Route::post('/tenants/{id}/impersonate', [SuperAdminController::class, 'impersonateTenant'])->name('tenants.impersonate');
    Route::post('/users/{id}/impersonate', [SuperAdminController::class, 'impersonateUser'])->name('users.impersonate');
});

// ─── Leave Impersonation Session ─────────────────────────────────────────
Route::post('/impersonate/leave', [SuperAdminController::class, 'leaveImpersonation'])->name('impersonate.leave')->middleware('auth:web');

// ─── Real-Time Notifications Hub ─────────────────────────────────────────
Route::middleware('auth:web')->prefix('portal/notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::delete('/clear-all', [NotificationController::class, 'clearAll'])->name('clear-all');
});
