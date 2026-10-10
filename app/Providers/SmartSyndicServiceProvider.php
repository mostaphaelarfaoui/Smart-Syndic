<?php

namespace App\Providers;

use App\Services\AuditService;
use App\Services\FileUploadService;
use App\Services\FinancialReportService;
use App\Services\InvitationCodeService;
use App\Services\PaymentService;
use App\Services\PdfReceiptService;
use App\Services\WhatsAppService;
use Illuminate\Support\ServiceProvider;

/**
 * SmartSyndicServiceProvider — Registers all domain services.
 */
class SmartSyndicServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditService::class);
        $this->app->singleton(FileUploadService::class);
        $this->app->singleton(InvitationCodeService::class);
        $this->app->singleton(PdfReceiptService::class);
        $this->app->singleton(FinancialReportService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(WhatsAppService::class);
    }

    public function boot(): void
    {
        //
    }
}
