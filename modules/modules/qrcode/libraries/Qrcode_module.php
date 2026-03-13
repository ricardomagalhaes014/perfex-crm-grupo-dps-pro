<?php

use app\modules\qrcode\service\CreditNoteQRCode;
use app\modules\qrcode\service\EstimateQRCode;
use app\modules\qrcode\service\InvoiceQRCode;
use app\modules\qrcode\service\PaymentQRCode;
use app\modules\qrcode\service\ProposalQRCode;

class Qrcode_module
{
    public function render(App_pdf $pdf, string $type): void
    {
        $pdfData = $pdf->get_view_vars('');
        switch ($type) {
            case 'invoice':
                $qr = new InvoiceQRCode($pdfData);
                break;
            case 'payment':
                $qr = new PaymentQRCode($pdfData);
                break;
            case 'credit_note':
                $qr = new CreditNoteQRCode($pdfData);
                break;
            case 'estimate':
                $qr = new EstimateQRCode($pdfData);
                break;
            case 'proposal':
                $qr = new ProposalQRCode($pdfData);
                break;
            case 'expenses':
//                $qr = new ExpenseQRCode($pdfData);
//                break;
            default:
                return;
        }
        $qr->render($pdf);
    }
}
