<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $tab      = $request->input('tab', 'audit'); // 'audit' or 'documents'

        // 1. Audit Logs Query
        $auditQuery = AuditLog::where('target_tenant_id', $tenantId)
            ->with('actor');

        if ($request->filled('action')) {
            $auditQuery->where('action', $request->action);
        }

        $auditLogs = $auditQuery->latest('created_at')->paginate(25, ['*'], 'audit_page')->withQueryString();

        // 2. Documents Archive
        $archivedReceipts = Payment::where('tenant_id', $tenantId)
            ->whereNotNull('receipt_pdf_path')
            ->with(['apartment.residents', 'recorder'])
            ->latest('payment_date')
            ->limit(30)
            ->get();

        $archivedInvoices = Expense::where('tenant_id', $tenantId)
            ->where('invoice_proof_path', '!=', '')
            ->whereNotNull('invoice_proof_path')
            ->latest('expense_date')
            ->limit(30)
            ->get();

        return view('portal.archives.index', compact('tab', 'auditLogs', 'archivedReceipts', 'archivedInvoices'));
    }
}
