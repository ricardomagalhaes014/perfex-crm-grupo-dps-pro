<?php

namespace app\modules\qrcode\service;

use Payment_pdf;

class PaymentQRCode extends QRCode
{
    function getMergeFieldSlug(): string
    {
        return 'invoice_merge_fields';
    }

    function getMergeFieldParameter()
    {
        return $this->getData()['payment']->invoiceid;
    }

    public function getMergeDataFormat(): string
    {
        return get_option('payment_qr_code_info');
    }

    public function isQrEnabled(): bool
    {
        return get_option('show_payment_qr_code') == 1;
    }

    public function getClientId(): string
    {
        return $this->getData()['payment']->invoice_data->clientid;
    }

    public function getMergeFields(): array
    {
        return  $this->ci->app_merge_fields->format_feature('invoice_merge_fields', $this->getData()['payment']->invoiceid, $this->getData()['payment']->id);
    }

    /**
     * @param  Payment_pdf  $pdf
     * @return void
     */
    public function render($pdf): void
    {
        $this->setQRPosition($pdf, (float) get_option('payment_qr_code_x_position'), (float)get_option('payment_qr_code_y_position'));
        parent::render($pdf);
    }
}
