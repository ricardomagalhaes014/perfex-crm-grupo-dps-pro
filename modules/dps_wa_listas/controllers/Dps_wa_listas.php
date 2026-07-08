<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_wa_listas extends AdminController
{
    public function index()
    {
        if (! is_admin()) {
            access_denied('WhatsApp Listas');
        }

        // Processar a fila manualmente, se pedido.
        if ($this->input->post('process_now')) {
            dps_wa_listas_process_queue();
            set_alert('success', 'Fila processada.');
            redirect(admin_url('dps_wa_listas'));
        }

        // Estados = nomes de etiqueta recomendados.
        $statuses = [];
        foreach ($this->db->order_by('statusorder')->get(db_prefix() . 'leads_status')->result() as $s) {
            $statuses[] = $s->name;
        }

        // Instâncias ligadas + etiquetas existentes.
        $instances = [];
        $connected = $this->db->where('is_connected', 1)->get(db_prefix() . 'dps_whatsapp_config')->result();
        foreach ($connected as $c) {
            $inst   = dps_wa_listas_instance_name($c->staff_id);
            $labels = dps_wa_listas_find_labels($inst); // nome_min => id
            $instances[] = [
                'staff_id' => (int) $c->staff_id,
                'name'     => get_staff_full_name($c->staff_id),
                'instance' => $inst,
                'labels'   => array_keys($labels),
            ];
        }

        // Estatísticas da fila.
        $stats = ['pending' => 0, 'done' => 0, 'skipped' => 0, 'failed' => 0];
        if ($this->db->table_exists(db_prefix() . 'dps_wa_label_queue')) {
            foreach ($stats as $st => $_) {
                $stats[$st] = (int) $this->db->where('status', $st)->count_all_results(db_prefix() . 'dps_wa_label_queue');
            }
            $data['recent'] = $this->db->order_by('id', 'DESC')->limit(15)
                ->get(db_prefix() . 'dps_wa_label_queue')->result();
        } else {
            $data['recent'] = [];
        }

        $data['title']       = 'WhatsApp Listas (DPS)';
        $data['statuses']    = $statuses;
        $data['instances']   = $instances;
        $data['stats']       = $stats;
        $data['evo_url']     = dps_wa_listas_evo_url();
        $data['enabled']     = get_option('dps_wa_listas_enabled') != '0';
        $this->load->view('setup', $data);
    }
}
