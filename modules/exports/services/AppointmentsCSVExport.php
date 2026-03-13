<?php

namespace app\modules\exports\services;

defined('BASEPATH') or exit('No direct script access allowed');

use CI_DB_mysqli_driver;

class AppointmentsCSVExport extends CSVExport
{
    public function __construct(?string $fromDate, ?string $toDate)
    {
        // Define que os campos personalizados pertencem a "appointments"
        parent::__construct($fromDate, $toDate, 'appointly');
    }

    public function queryData(): CI_DB_mysqli_driver
    {
        // Seleciona todos os campos da tabela principal
        $this->ci->db->select(db_prefix() . 'appointly_appointments.*');
        $this->ci->db->from(db_prefix() . 'appointly_appointments');

        // Aplica filtro de datas, se fornecido
        $this->applyDateFilter(db_prefix() . 'appointly_appointments.date');

        // Junta os campos personalizados ao resultado
        $this->selectCustomFields(db_prefix() . 'appointly_appointments.id');

        return $this->ci->db;
    }

    public function excludedFields(): array
    {
        return ['hash', 'google_event_id', 'google_added_by', 'google_meet_link'];
    }

    protected function formatHeading(string $header): string
    {
        return ucwords(str_replace('_', ' ', $header));
    }
}