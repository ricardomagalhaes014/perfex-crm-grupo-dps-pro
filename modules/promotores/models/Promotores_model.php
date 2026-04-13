<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Promotores_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id = null)
    {
        if ($id !== null) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'promotores')->row();
        }

        if (!staff_can('view', 'promotores') && staff_can('view_own', 'promotores')) {
            $this->db->where('addedfrom', get_staff_user_id());
        }

        $this->db->order_by('id', 'DESC');
        return $this->db->get(db_prefix() . 'promotores')->result_array();
    }

    public function get_districts()
    {
        if (!staff_can('view', 'promotores') && staff_can('view_own', 'promotores')) {
            $this->db->where('addedfrom', get_staff_user_id());
        }

        $this->db->select('state');
        $this->db->from(db_prefix() . 'promotores');
        $this->db->where('state IS NOT NULL');
        $this->db->where('state !=', '');
        $this->db->group_by('state');
        $this->db->order_by('state', 'ASC');
        $rows = $this->db->get()->result_array();
        return array_values(array_filter(array_map(function ($row) {
            return $row['state'];
        }, $rows)));
    }

    public function add($data, $skipDuplicates = false)
    {
        $name = trim((string) ($data['name'] ?? ''));
        $website = trim((string) ($data['website'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        if ($skipDuplicates && $this->exists_similar($name, $website, $email)) {
            return 0;
        }

        $lastContact = !empty($data['lastcontact']) ? $this->normalize_datetime($data['lastcontact']) : null;

        $insert = [
            'name'        => $name,
            'company'     => trim((string) ($data['company'] ?? '')),
            'phase'       => $this->normalize_phase($data['phase'] ?? 'Não contactado'),
            'phone'       => trim((string) ($data['phone'] ?? '')),
            'email'       => $email,
            'website'     => $website,
            'address'     => trim((string) ($data['address'] ?? '')),
            'city'        => trim((string) ($data['city'] ?? '')),
            'state'       => trim((string) ($data['state'] ?? '')),
            'zip'         => trim((string) ($data['zip'] ?? '')),
            'country'     => !empty($data['country']) ? (int) $data['country'] : null,
            'notes'       => $data['notes'] ?? null,
            'assigned'    => !empty($data['assigned']) ? (int) $data['assigned'] : null,
            'dateadded'   => date('Y-m-d H:i:s'),
            'lastcontact' => $lastContact,
            'addedfrom'   => get_staff_user_id() ? get_staff_user_id() : 0,
        ];

        $this->db->insert(db_prefix() . 'promotores', $insert);
        $id = $this->db->insert_id();

        if ($id) {
            log_activity('Novo promotor criado [ID: ' . $id . ', Nome: ' . $insert['name'] . ']');
        }

        return $id;
    }

    public function update($data, $id)
    {
        $update = [
            'name'        => trim((string) ($data['name'] ?? '')),
            'company'     => trim((string) ($data['company'] ?? '')),
            'phase'       => $this->normalize_phase($data['phase'] ?? 'Não contactado'),
            'phone'       => trim((string) ($data['phone'] ?? '')),
            'email'       => trim((string) ($data['email'] ?? '')),
            'website'     => trim((string) ($data['website'] ?? '')),
            'address'     => trim((string) ($data['address'] ?? '')),
            'city'        => trim((string) ($data['city'] ?? '')),
            'state'       => trim((string) ($data['state'] ?? '')),
            'zip'         => trim((string) ($data['zip'] ?? '')),
            'country'     => !empty($data['country']) ? (int) $data['country'] : null,
            'notes'       => $data['notes'] ?? null,
            'assigned'    => !empty($data['assigned']) ? (int) $data['assigned'] : null,
            'lastcontact' => !empty($data['lastcontact']) ? $this->normalize_datetime($data['lastcontact']) : null,
        ];

        $this->db->where('id', $id);
        $success = $this->db->update(db_prefix() . 'promotores', $update);

        if ($success) {
            log_activity('Promotor atualizado [ID: ' . $id . ', Nome: ' . $update['name'] . ']');
        }

        return $success;
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $row = $this->db->get(db_prefix() . 'promotores')->row();
        if (!$row) {
            return false;
        }

        $this->db->where('id', $id);
        $success = $this->db->delete(db_prefix() . 'promotores');

        if ($success) {
            log_activity('Promotor removido [ID: ' . $id . ', Nome: ' . $row->name . ']');
        }

        return $success;
    }

    public function import_csv($tmpPath, $skipDuplicates = false)
    {
        $handle = fopen($tmpPath, 'r');
        if (!$handle) {
            return ['inserted' => 0, 'errors' => ['Não foi possível abrir o ficheiro CSV.']];
        }

        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            fclose($handle);
            return ['inserted' => 0, 'errors' => ['CSV vazio ou inválido.']];
        }

        $normalizedHeader = array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, $header);

        $inserted = 0;
        $errors   = [];

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count(array_filter($row, function ($v) {
                return trim((string) $v) !== '';
            })) === 0) {
                continue;
            }

            $mapped = [];
            foreach ($normalizedHeader as $index => $column) {
                $mapped[$column] = $row[$index] ?? null;
            }

            $name = trim((string) ($mapped['name'] ?? $mapped['nome'] ?? ''));
            if ($name === '') {
                $errors[] = 'Linha ignorada sem nome.';
                continue;
            }

            $newId = $this->add([
                'name'        => $name,
                'company'     => $mapped['company'] ?? $mapped['empresa'] ?? $name,
                'phase'       => $mapped['phase'] ?? $mapped['fase'] ?? 'Não contactado',
                'phone'       => $mapped['phone'] ?? $mapped['telefone'] ?? null,
                'email'       => $mapped['email'] ?? null,
                'website'     => $mapped['website'] ?? $mapped['site'] ?? null,
                'address'     => $mapped['address'] ?? $mapped['morada'] ?? null,
                'city'        => $mapped['city'] ?? $mapped['cidade'] ?? $mapped['cidade_regiao'] ?? null,
                'state'       => $mapped['state'] ?? $mapped['distrito'] ?? null,
                'zip'         => $mapped['zip'] ?? $mapped['codigo_postal'] ?? null,
                'notes'       => $mapped['notes'] ?? $mapped['notas'] ?? $mapped['notas_importantes'] ?? null,
                'lastcontact' => $mapped['lastcontact'] ?? $mapped['ultimo_contacto'] ?? null,
            ], $skipDuplicates);

            if ($newId) {
                $inserted++;
            }
        }

        fclose($handle);

        return ['inserted' => $inserted, 'errors' => $errors];
    }

    private function exists_similar($name, $website, $email)
    {
        if ($name === '') {
            return false;
        }

        $this->db->from(db_prefix() . 'promotores');
        $this->db->group_start();
        $this->db->where('name', $name);
        if ($website !== '') {
            $this->db->or_where('website', $website);
        }
        if ($email !== '') {
            $this->db->or_where('email', $email);
        }
        $this->db->group_end();

        return $this->db->count_all_results() > 0;
    }

    private function normalize_phase($phase)
    {
        $phase = trim((string) $phase);
        $allowed = promotores_contact_phases();
        return in_array($phase, $allowed, true) ? $phase : 'Não contactado';
    }

    private function normalize_datetime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        if (function_exists('to_sql_date')) {
            return to_sql_date($value, true);
        }

        return null;
    }
}
