<?php

namespace app\modules\qrcode\service;

use App_pdf;

class ExpenseQRCode extends QRCode
{

    public function getQRData(): string
    {
        // N/A
        return;
    }

    public function render(App_pdf $pdf): void
    {
        // N/A
    }

    function getMergeFieldSlug(): string
    {
        // TODO: Implement getMergeFieldSlug() method.
    }

    function getMergeDataFormat(): string
    {
        // TODO: Implement getMergeDataFormat() method.
    }

    function isQrEnabled(): bool
    {
        // TODO: Implement isQrEnabled() method.
    }

    function getMergeFieldParameter()
    {
        // TODO: Implement getMergeFieldParameter() method.
    }

    function getClientId(): string
    {
        // TODO: Implement getClientId() method.
    }
}
