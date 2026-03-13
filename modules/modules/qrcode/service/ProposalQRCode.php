<?php

namespace app\modules\qrcode\service;

use Proposal_pdf;

class ProposalQRCode extends QRCode
{
    function getMergeFieldSlug(): string
    {
        return 'proposals_merge_fields';
    }

    function getMergeFieldParameter()
    {
        return $this->getData()['proposal']->id;
    }

    public function getMergeDataFormat(): string
    {
        return get_option('proposal_qr_code_info');
    }

    public function isQrEnabled(): bool
    {
        return get_option('show_proposal_qr_code') == 1;
    }

    public function getClientId(): string
    {
        return $this->getData()['proposal']->rel_id;
    }

    /**
     * @param  Proposal_pdf  $pdf
     * @return void
     */
    public function render($pdf): void
    {
        $this->setQRPosition($pdf, (float) get_option('proposal_qr_code_x_position'), (float) get_option('proposal_qr_code_y_position'));
        parent::render($pdf);
    }
}
