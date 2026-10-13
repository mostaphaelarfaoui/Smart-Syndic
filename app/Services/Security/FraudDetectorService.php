<?php

namespace App\Services\Security;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\SecurityIncident;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class FraudDetectorService
{
    /**
     * Run all heuristic security & fraud detection checks across the platform.
     */
    public function scan(): array
    {
        $alerts = [];

        // 1. Check for rapid consecutive payments (Velocity check: > 5 payments in 3 minutes)
        $velocityAlerts = $this->checkPaymentVelocity();
        $alerts = array_merge($alerts, $velocityAlerts);

        // 2. Check for abnormally high cash transactions (> 10,000 MAD in cash)
        $cashAlerts = $this->checkHighCashTransactions();
        $alerts = array_merge($alerts, $cashAlerts);

        // 3. Check for identical duplicate expenses within 24 hours
        $duplicateExpenseAlerts = $this->checkDuplicateExpenses();
        $alerts = array_merge($alerts, $duplicateExpenseAlerts);

        // 4. Check for active residents in suspended/dormant tenants
        $suspendedTenantAlerts = $this->checkSuspendedTenantActivity();
        $alerts = array_merge($alerts, $suspendedTenantAlerts);

        return $alerts;
    }

    private function checkPaymentVelocity(): array
    {
        $alerts = [];
        $threeMinutesAgo = now()->subMinutes(3);

        $tenantsWithBurst = Payment::where('created_at', '>=', $threeMinutesAgo)
            ->selectRaw('tenant_id, count(*) as count, sum(amount_paid) as total_amount')
            ->groupBy('tenant_id')
            ->having('count', '>=', 5)
            ->get();

        foreach ($tenantsWithBurst as $item) {
            $tenant = Tenant::find($item->tenant_id);
            $desc = "تسجيل {$item->count} دفعات مالية في أقل من 3 دقائق بمجموع {$item->total_amount} درهم في إقامة {$tenant?->name}";

            $alerts[] = [
                'type'        => 'BURST_VELOCITY',
                'severity'    => 'HIGH',
                'title'       => 'تدفق دفعات مالي مكثف غير معتاد (Velocity Burst)',
                'description' => $desc,
                'tenant_name' => $tenant?->name ?? 'غير محدد',
                'tenant_id'   => $item->tenant_id,
                'created_at'  => now(),
            ];

            // Auto-log incident once
            SecurityIncident::firstOrCreate([
                'incident_type' => 'SUSPICIOUS_VELOCITY',
                'tenant_id'     => $item->tenant_id,
                'created_at'    => now()->format('Y-m-d H:i:00'),
            ], [
                'severity' => 'HIGH',
                'details'  => $desc,
                'status'   => 'OPEN',
            ]);
        }

        return $alerts;
    }

    private function checkHighCashTransactions(): array
    {
        $alerts = [];
        $suspiciousPayments = Payment::where('method', 'CASH')
            ->where('amount_paid', '>=', 10000)
            ->latest('created_at')
            ->limit(5)
            ->get();

        foreach ($suspiciousPayments as $p) {
            $tenant = Tenant::find($p->tenant_id);
            $alerts[] = [
                'type'        => 'HIGH_CASH_PAYMENT',
                'severity'    => 'MEDIUM',
                'title'       => 'دفعة نقدية (كاش) مرتفعة جداً تستوجب التدقيق',
                'description' => "تسجيل دفعة نقدية بقيمة {$p->amount_paid} درهم نقداً بدون شيك أو تحويل بنكي (رقم الوصل: {$p->receipt_number})",
                'tenant_name' => $tenant?->name ?? 'إقامة غير محددة',
                'tenant_id'   => $p->tenant_id,
                'created_at'  => $p->created_at,
            ];
        }

        return $alerts;
    }

    private function checkDuplicateExpenses(): array
    {
        $alerts = [];
        $duplicates = Expense::selectRaw('tenant_id, title, amount, count(*) as count')
            ->where('created_at', '>=', now()->subDays(2))
            ->groupBy('tenant_id', 'title', 'amount')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $tenant = Tenant::find($dup->tenant_id);
            $alerts[] = [
                'type'        => 'DUPLICATE_EXPENSE',
                'severity'    => 'MEDIUM',
                'title'       => 'شبهة تكرار فاتورة أو مصاريف مزدوجة',
                'description' => "تم تسجيل المصروف '{$dup->title}' بقيمة {$dup->amount} درهم {$dup->count} مرات خلال 48 ساعة",
                'tenant_name' => $tenant?->name ?? 'إقامة غير محددة',
                'tenant_id'   => $dup->tenant_id,
                'created_at'  => now(),
            ];
        }

        return $alerts;
    }

    private function checkSuspendedTenantActivity(): array
    {
        $alerts = [];
        $suspendedTenantsWithRecentPayments = Payment::whereHas('tenant', function ($q) {
                $q->where('status', 'SUSPENDED');
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->distinct('tenant_id')
            ->pluck('tenant_id');

        foreach ($suspendedTenantsWithRecentPayments as $tenantId) {
            $tenant = Tenant::find($tenantId);
            $alerts[] = [
                'type'        => 'SUSPENDED_TENANT_ACTIVITY',
                'severity'    => 'CRITICAL',
                'title'       => 'محاولة تسجيل معاملات لإقامة موقوفة أو معطلة',
                'description' => "تم رصد عمليات مالية جارية تابعة لإقامة بحالة موقوفة: {$tenant?->name}",
                'tenant_name' => $tenant?->name ?? 'إقامة غير محددة',
                'tenant_id'   => $tenantId,
                'created_at'  => now(),
            ];
        }

        return $alerts;
    }
}
