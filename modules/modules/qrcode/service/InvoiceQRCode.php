<?php

namespace app\modules\qrcode\service;

use Invoice_pdf;

class InvoiceQRCode extends QRCode
{
    function getMergeFieldSlug(): string
    {
        return 'invoice_merge_fields';
    }

    function getMergeFieldParameter()
    {
        return $this->getData()['invoice']->id;
    }

    public function getMergeDataFormat(): string
    {
        return get_option('invoice_qr_code_info');
    }

    public function isQrEnabled(): bool
    {
        return get_option('show_invoice_qr_code') == 1;
    }

    public function getClientId(): string
    {
        return $this->getData()['invoice']->clientid;
    }

    /**
     * @param Invoice_pdf $pdf
     *
     * @return void
     */
    public function render($pdf): void
    {
        $this->setQRPosition($pdf, (float) get_option('invoice_qr_code_x_position'), (float) get_option('invoice_qr_code_y_position'));
        parent::render($pdf);
    }
}
