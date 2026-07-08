<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_propostas extends AdminController
{
    private function lead_or_die($lead_id)
    {
        $lead = $this->db->select('id, name, phonenumber, status')
            ->where('id', (int) $lead_id)->get(db_prefix() . 'leads')->row();
        return $lead;
    }

    private function status_name($id)
    {
        $r = $this->db->select('name')->where('id', (int) $id)->get(db_prefix() . 'leads_status')->row();
        return $r ? $r->name : null;
    }

    /**
     * Envia informação (dossier + unidades disponíveis) pela WhatsApp do comercial.
     */
    public function enviar_info()
    {
        if (! is_staff_member()) {
            ajax_access_denied();
        }

        $lead_id = (int) $this->input->post('lead_id');
        $key     = $this->input->post('empreendimento');
        $emps    = dps_propostas_empreendimentos();

        if (! $lead_id || ! isset($emps[$key])) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $emp      = $emps[$key];
        $staff_id = get_staff_user_id();

        if (! dps_propostas_staff_connected($staff_id)) {
            echo json_encode(['success' => false, 'message' => 'O teu WhatsApp não está ligado (Definições → WhatsApp).']);
            return;
        }

        $lead = $this->lead_or_die($lead_id);
        if (! $lead) {
            echo json_encode(['success' => false, 'message' => 'Lead não encontrada.']);
            return;
        }
        $number = preg_replace('/[^0-9]/', '', (string) $lead->phonenumber);
        if ($number === '') {
            echo json_encode(['success' => false, 'message' => 'A lead não tem telefone.']);
            return;
        }

        $disp  = dps_propostas_disponibilidade($emp['states_key']);
        $lista = $disp['count'] > 0
            ? implode(', ', array_slice($disp['codes'], 0, 60)) . ($disp['count'] > 60 ? '…' : '')
            : '—';

        $primeiro = trim(explode(' ', (string) $lead->name)[0]);
        $msg  = 'Olá' . ($primeiro ? " {$primeiro}" : '') . "! 👋\n\n";
        $msg .= '🏢 *' . $emp['nome'] . "*\n\n";
        if (! empty($emp['dossier'])) {
            $msg .= "📄 Dossier comercial:\n" . $emp['dossier'] . "\n\n";
        }
        $msg .= "🌐 Mais informação:\n" . $emp['site'] . "\n\n";
        $msg .= '🏠 Unidades disponíveis: *' . $disp['count'] . "*\n" . $lista;

        $r  = dps_propostas_send_text($staff_id, $number, $msg);
        $ok = $r['ok'];

        $anexos = [];
        foreach ($emp['anexos'] as $ax) {
            $ra = dps_propostas_send_document($staff_id, $number, $ax['url'], $ax['nome']);
            if ($ra['ok']) {
                $anexos[] = $ax['nome'];
            }
        }

        $this->db->insert(db_prefix() . 'dps_propostas', [
            'lead_id'          => $lead_id,
            'staff_id'         => $staff_id,
            'tipo'             => 'info',
            'empreendimento'   => $emp['nome'],
            'unidade'          => null,
            'lead_status_id'   => (int) $lead->status,
            'lead_status_nome' => $this->status_name($lead->status),
            'ficheiro'         => $emp['dossier'],
            'detalhe'          => 'Disponíveis: ' . $disp['count'] . ($anexos ? '; anexos: ' . implode(', ', $anexos) : ''),
            'wa_ok'            => $ok ? 1 : 0,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        echo json_encode([
            'success' => $ok,
            'message' => $ok
                ? ('Informação enviada para ' . $lead->name . ' — ' . $disp['count'] . ' unidades disponíveis' . ($anexos ? ' (+' . count($anexos) . ' anexos)' : '') . '.')
                : 'Falha no envio pelo WhatsApp.',
        ]);
    }

    /**
     * Regista + envia uma proposta (PDF). Usado pelo callback do simulador (Fase B)
     * ou com um URL de PDF já existente.
     */
    public function registar_proposta()
    {
        if (! is_staff_member()) {
            ajax_access_denied();
        }

        $lead_id   = (int) $this->input->post('lead_id');
        $emp_nome  = $this->input->post('empreendimento');
        $unidade   = $this->input->post('unidade');
        $file_url  = $this->input->post('file_url');
        $file_name = $this->input->post('file_name') ?: 'Proposta.pdf';

        if (! $lead_id || ! $unidade) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $staff_id = get_staff_user_id();
        if (! dps_propostas_staff_connected($staff_id)) {
            echo json_encode(['success' => false, 'message' => 'O teu WhatsApp não está ligado.']);
            return;
        }

        $lead = $this->lead_or_die($lead_id);
        if (! $lead) {
            echo json_encode(['success' => false, 'message' => 'Lead não encontrada.']);
            return;
        }
        $number = preg_replace('/[^0-9]/', '', (string) $lead->phonenumber);

        $ok = false;
        if ($file_url && $number !== '') {
            $primeiro = trim(explode(' ', (string) $lead->name)[0]);
            $caption  = 'Proposta' . ($emp_nome ? ' — ' . $emp_nome : '') . ' — Unidade ' . $unidade;
            $r  = dps_propostas_send_document($staff_id, $number, $file_url, $file_name, $caption);
            $ok = $r['ok'];
        }

        $this->db->insert(db_prefix() . 'dps_propostas', [
            'lead_id'          => $lead_id,
            'staff_id'         => $staff_id,
            'tipo'             => 'proposta',
            'empreendimento'   => $emp_nome,
            'unidade'          => $unidade,
            'lead_status_id'   => (int) $lead->status,
            'lead_status_nome' => $this->status_name($lead->status),
            'ficheiro'         => $file_url,
            'detalhe'          => null,
            'wa_ok'            => $ok ? 1 : 0,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        echo json_encode(['success' => true, 'message' => $ok ? 'Proposta enviada e registada.' : 'Proposta registada (envio WhatsApp não confirmado).']);
    }
}
