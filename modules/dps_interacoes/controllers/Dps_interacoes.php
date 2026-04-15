<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_interacoes extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data['title']      = 'Interacções por Comercial';
        $data['comerciais'] = [];
        $data['statuses']   = [];
        $data['periodo']    = 'last_7';
        $data['status_id']  = 0;
        $data['label']      = 'Últimos 7 dias';
        $data['date_from']  = date('Y-m-d 00:00:00', strtotime('-6 days'));
        $data['date_to']    = date('Y-m-d 23:59:59');

        $this->load->view('interacoes', $data);
    }
}
