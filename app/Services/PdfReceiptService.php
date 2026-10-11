<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * PdfReceiptService — Generates payment receipt PDFs (§8.1).
 *
 * Produces A5 Landscape vectorized PDFs with the full receipt structure:
 * header, payment grid, amount in words, QR code, and signature blocks.
 *
 * Uses barryvdh/laravel-snappy for headless wkhtmltopdf rendering.
 */
class PdfReceiptService
{
    /**
     * Generate a PDF receipt for a payment and store it.
     *
     * @return string The storage path of the generated PDF.
     */
    public function generateReceipt(Payment $payment): string
    {
        $payment->load(['apartment.building', 'apartment.residents', 'recorder', 'tenant']);

        $resident = $payment->apartment->residents()->first();

        $data = [
            'payment'        => $payment,
            'tenant'         => $payment->tenant,
            'apartment'      => $payment->apartment,
            'building'       => $payment->apartment->building,
            'resident'       => $resident,
            'receipt_number' => $payment->receipt_number,
            'amount_words'   => $this->amountToWords($payment->amount_paid),
            'qr_data'        => $this->generateQrData($payment),
        ];

        // Render HTML from Blade template
        $html = view('pdf.receipt', $data)->render();

        $disk = config('smartsyndic.uploads.storage_disk', 'public');

        try {
            // Primary PDF engine: mPDF (Native UTF-8, proper Arabic ligatures & RTL shaping)
            $mpdf = new \Mpdf\Mpdf([
                'mode'              => 'utf-8',
                'format'            => 'A5-L',
                'margin_left'       => 8,
                'margin_right'      => 8,
                'margin_top'        => 6,
                'margin_bottom'     => 6,
                'autoArabic'        => true,
                'autoLangToFont'    => true,
                'useSubstitutions'  => true,
            ]);

            $mpdf->SetDirectionality('rtl');
            $mpdf->WriteHTML($html);

            $filename = 'receipts/' . Str::uuid() . '.pdf';
            Storage::disk($disk)->put($filename, $mpdf->Output('', 'S'));

            return $filename;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('mPDF receipt generation failed: ' . $e->getMessage() . '. Trying DomPDF fallback.');

            try {
                // Secondary fallback: DomPDF
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('a5', 'landscape');
                $pdf->setWarnings(false);

                $filename = 'receipts/' . Str::uuid() . '.pdf';
                Storage::disk($disk)->put($filename, $pdf->output());

                return $filename;
            } catch (\Throwable $e2) {
                \Illuminate\Support\Facades\Log::warning('DomPDF fallback failed: ' . $e2->getMessage() . '. Saving as printable HTML.');

                $htmlFilename = 'receipts/' . Str::uuid() . '.html';
                Storage::disk($disk)->put($htmlFilename, $html);

                return $htmlFilename;
            }
        }
    }

    /**
     * Convert amount to words in French and Arabic.
     */
    public function amountToWords(float $amount): array
    {
        $formatter_fr = new \NumberFormatter('fr_MA', \NumberFormatter::SPELLOUT);
        $formatter_ar = new \NumberFormatter('ar_MA', \NumberFormatter::SPELLOUT);

        $whole    = (int) $amount;
        $centimes = (int) round(($amount - $whole) * 100);

        $fr = $formatter_fr->format($whole) . ' dirhams';
        $ar = $formatter_ar->format($whole) . ' درهم';

        if ($centimes > 0) {
            $fr .= ' et ' . $formatter_fr->format($centimes) . ' centimes';
            $ar .= ' و ' . $formatter_ar->format($centimes) . ' سنتيم';
        }

        return [
            'fr' => ucfirst($fr),
            'ar' => $ar,
        ];
    }

    /**
     * Generate QR code data string linking to encrypted validation endpoint.
     */
    private function generateQrData(Payment $payment): string
    {
        return url("/api/v1/verify-receipt/{$payment->receipt_number}");
    }
}
