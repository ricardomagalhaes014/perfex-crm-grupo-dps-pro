<?php

namespace app\modules\qrcode\service;

use Credit_note_pdf;

class CreditNoteQRCode extends QRCode
{
    function getMergeFieldSlug(): string
    {
        return 'credit_note_merge_fields';
    }

    function getMergeFieldParameter()
    {
        return $this->getData()['credit_note']->id;
    }

    public function getMergeDataFormat(): string
    {
        return get_option('credit_note_qr_code_info');
    }

    public function isQrEnabled(): bool
    {
        return get_option('show_credit_note_qr_code') == 1;
    }

    public function getClientId(): string
    {
        return $this->getData()['credit_note']->clientid;
    }

    /**
     * @param  Credit_note_pdf  $pdf
     * @return void
     */
    public function render($pdf): void
    {
        $this->setQRPosition($pdf, (float) get_option('credit_note_qr_code_x_position'), (float) get_option('credit_note_qr_code_y_position'));
        parent::render($pdf);
    }
}
