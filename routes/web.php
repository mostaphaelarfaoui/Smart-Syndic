<?php

use App\Models\Payment;
use App\Services\PdfReceiptService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/syndic/login');
});

Route::get('/login', function () {
    return redirect('/syndic/login');
})->name('login');

// Direct download route for payment receipts (forces download as PDF with filename)
Route::get('/receipts/{payment}/download', function (Payment $payment) {
    $path = $payment->receipt_pdf_path;
    $disk = config('smartsyndic.uploads.storage_disk', 'public');

    // If file doesn't exist or is HTML, regenerate as real PDF
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
