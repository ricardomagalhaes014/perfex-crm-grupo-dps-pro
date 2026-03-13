<?php

namespace app\modules\qrcode\service;

use App_pdf;
use CI_Controller;

abstract class QRCode
{
    private array $data;
    private string $qrcode;
    protected CI_Controller $ci;

    public function __construct($data)
    {
        $this->ci = &get_instance();
        $this->data = $data;
    }

    public function getMergeFields(): array
    {
        return $this->ci->app_merge_fields->format_feature($this->getMergeFieldSlug(), $this->getMergeFieldParameter());
    }

    abstract function getMergeFieldSlug(): string;

    abstract function getMergeDataFormat(): string;

    abstract function isQrEnabled(): bool;

    abstract function getMergeFieldParameter();

    abstract function getClientId(): string;

    public function getQRData(): string
    {
        if (!$this->isQrEnabled()) {
            return '';
        }
        $data = $this->getMergeDataFormat();
        $invoiceMergeFields = $this->getMergeFields();
        $clientMergeFields = $this->ci->app_merge_fields->format_feature('client_merge_fields', $this->getClientId());
        $otherMergeFields = $this->ci->app_merge_fields->format_feature('other_merge_fields');

        $mergeFields = array_merge($invoiceMergeFields, $clientMergeFields, $otherMergeFields);
        return str_replace(array_keys($mergeFields), array_values($mergeFields), $data);
    }

    public function render(App_pdf $pdf): void
    {
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $width = get_option('qr_code_width') ?: 50;
        $height = get_option('qr_code_height') ?: 50;
        $styleQR = ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false];
        $pdf->write2DBarcode($this->getQRData(), 'QRCODE,M', $x, $y, $width, $height, $styleQR);
    }

    /**
     * @return string
     */
    public function getQrcode(): string
    {
        return $this->qrcode;
    }

    /**
     * @param  string  $qrcode
     * @return QRCode
     */
    public function setQrcode(string $qrcode): QRCode
    {
        $this->qrcode = $qrcode;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param  array  $data
     * @return QRCode
     */
    public function setData(array $data): QRCode
    {
        $this->data = $data;
        return $this;
    }

    public function setQRPosition(App_pdf $pdf, float $x, float $y)
    {
        if (!$x || !$y) {
            $pdf->writeHTML('<br /><br />');
        }

        if ($x) {
            $pdf->SetX($x);
        }

        if ($y) {
            $pdf->SetY($y, false);
        }
    }
}
