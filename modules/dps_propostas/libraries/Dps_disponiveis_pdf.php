<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once APPPATH . 'libraries/pdf/App_pdf.php';

/**
 * PDF simples (TCPDF) com a tabela de unidades disponíveis, gerado ao vivo.
 */
class Dps_disponiveis_pdf extends App_pdf
{
    protected $titulo;
    protected $html_body;

    public function __construct($titulo, $html)
    {
        parent::__construct();
        $this->titulo    = $titulo;
        $this->html_body = $html;
        $this->SetTitle($titulo);
    }

    public function prepare()
    {
        $this->AddPage();
        $this->writeHTML($this->html_body, true, false, true, false, '');

        return $this;
    }
}
