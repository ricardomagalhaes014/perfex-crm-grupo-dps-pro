<?php

namespace app\modules\qrcode\service;

use Estimate_pdf;

class EstimateQRCode extends QRCode
{
    function getMergeFieldSlug(): string
    {
        return 'estimate_merge_fields';
    }

    function getMergeFieldParameter()
    {
        return $this->getData()['estimate']->id;
    }

    public function getMergeDataFormat(): string
    {
        return get_option('estimate_qr_code_info');
    }

    public function isQrEnabled(): bool
    {
        return get_option('show_estimate_qr_code') == 1;
    }

    public function getClientId(): string
    {
        return $this->getData()['estimate']->clientid;
    }

    /**
     * @param  Estimate_pdf  $pdf
     * @return void
     */
    public function render($pdf): void
    {
        $this->setQRPosition($pdf, (float) get_option('estimate_qr_code_x_position'), (float) get_option('estimate_qr_code_y_position'));
        parent::render($pdf);
    }
}
