<?php

use App\Http\Controllers\Web\Portal\AnalyticsController;
use App\Http\Controllers\Web\Portal\ApartmentController;
use App\Http\Controllers\Web\Portal\ArchiveController;
use App\Http\Controllers\Web\Portal\AuthController;
use App\Http\Controllers\Web\Portal\CommunityController;
use App\Http\Controllers\Web\Portal\DashboardController;
use App\Http\Controllers\Web\Portal\ExpenseController;
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

// ─── Direct Receipt Download ─────────────────────────────────────────────
Route::get('/receipts/{payment}/download', function (Payment $payment) {
    $path = $payment->receipt_pdf_path;
    $disk = config('smartsyndic.uploads.storage_disk', 'public');

    if (! $path || ! Storage::disk($disk)->exists($path) || str_ends_with($path, '.html')) {
        $pdfService = app(PdfReceiptService::class);
        $path = $pdfService->generateReceipt($payment);
        $payment->update(['receipt_pdf_path' => $path]);
    }

    $fullPath = Storage::disk($disk)->path($path);
    $filename = "recu-{$payment->receipt_number}.pdf";

    return response()->download($fullPath, $filename, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ]);
})->name('receipts.download');

// ─── Portal Authentication Routes ────────────────────────────────────────
Route::get('/portal/login', [AuthController::class, 'showLogin'])->name('portal.login');
Route::post('/portal/login', [AuthController::class, 'login'])->name('portal.login.submit');
Route::post('/portal/logout', [AuthController::class, 'logout'])->name('portal.logout');

// ─── Syndic Management Portal ────────────────────────────────────────────
Route::middleware(['auth:web'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Apartments
    Route::get('/apartments', [ApartmentController::class, 'index'])->name('apartments.index');
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

    // Analytics (New!)
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Archives & Audit Logs (New!)
    Route::get('/archives', [ArchiveController::class, 'index'])->name('archives.index');

    // Community & Polls
    Route::get('/community', [CommunityController::class, 'index'])->name('community.index');
    Route::post('/community/suggestions/{id}/update', [CommunityController::class, 'updateSuggestionStatus'])->name('community.suggestions.update');
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
    
    // Tenants & Organizations
    Route::get('/tenants', [SuperAdminController::class, 'tenants'])->name('tenants.index');
    Route::post('/tenants/{id}/approve', [SuperAdminController::class, 'approveTenant'])->name('tenants.approve');
    Route::post('/tenants/{id}/suspend', [SuperAdminController::class, 'suspendTenant'])->name('tenants.suspend');

    // Server & Database Health
    Route::get('/server', [SuperAdminController::class, 'server'])->name('server.index');
    Route::post('/server/vacuum', [SuperAdminController::class, 'vacuumDb'])->name('server.vacuum');
    Route::get('/server/backup', [SuperAdminController::class, 'backupDb'])->name('server.backup');

    // Security, Fraud & Firewall
    Route::get('/security', [SuperAdminController::class, 'security'])->name('security.index');
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
});
