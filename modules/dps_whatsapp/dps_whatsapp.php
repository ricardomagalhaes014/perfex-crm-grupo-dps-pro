<?php
defined('BASEPATH') or exit('No direct script access allowed');

define('DPS_WHATSAPP_MODULE_NAME', 'dps_whatsapp');

// Registar menu lateral
hooks()->add_action('admin_init', 'dps_whatsapp_menu');
function dps_whatsapp_menu()
{
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('dps_whatsapp', [
        'name'     => 'WhatsApp',
        'href'     => admin_url('dps_whatsapp'),
        'icon'     => 'fa fa-whatsapp',
        'position' => 48,
        'badge'    => [],
    ]);
}

// Hook: quando uma lead é criada → enviar mensagem de boas-vindas e agendar follow-up
hooks()->add_action('lead_created', 'dps_whatsapp_lead_created');
function dps_whatsapp_lead_created($lead_id)
{
    $CI = &get_instance();
    $CI->load->model('dps_whatsapp/Dps_whatsapp_model');

    /*
     * UMA LEAD NOVA NÃO RECEBE WHATSAPP. Regra do dono (31/07/2026):
     * "não deve ser enviada mensagem às novas leads nunca por WhatsApp".
     *
     * O que estava aqui mandava, no instante em que a lead entrava, uma
     * mensagem sobre o Belo Horizonte — a toda a gente, viesse a lead de que
     * campanha viesse. Quem preenchia um formulário do Aura recebia uma
     * mensagem de outro empreendimento, e recebia-a de um número que nunca
     * tinha contactado.
     *
     * O primeiro contacto passa a ser por EMAIL, com o texto da campanha de
     * onde a lead veio. O WhatsApp fica para quando houver conversa começada.
     *
     * O agendamento de follow-ups mantém-se: esses seguem as automações por
     * estado, que são disparadas por trabalho do comercial e não pela simples
     * entrada da lead.
     */
    $CI->Dps_whatsapp_model->schedule_followup($lead_id);
}

// Hook: quando o estado de uma lead muda → actualizar/reagendar follow-up
hooks()->add_action('lead_status_changed', 'dps_whatsapp_status_changed');
function dps_whatsapp_status_changed($data)
{
    $CI = &get_instance();
    $CI->load->model('dps_whatsapp/Dps_whatsapp_model');
    $lead_id = is_array($data) ? ($data['lead_id'] ?? $data[0] ?? null) : $data;
    if ($lead_id) {
        $CI->Dps_whatsapp_model->reschedule_followup($lead_id);
    }
}

// Cron: processar follow-ups pendentes (chamado pelo cron do Perfex)
hooks()->add_action('perfex_cron', 'dps_whatsapp_process_cron');
function dps_whatsapp_process_cron()
{
    $CI = &get_instance();
    $CI->load->model('dps_whatsapp/Dps_whatsapp_model');
    $CI->Dps_whatsapp_model->process_pending_followups();
}

/* =====================================================================
 * Vigilância das ligações de WhatsApp
 * =====================================================================
 * As sessões da Evolution caem sozinhas (o serviço reinicia e perde-as).
 * Até aqui, o comercial só descobria quando falhava ao enviar uma proposta
 * a um cliente. Passa a haver:
 *   1. verificação de hora a hora, que marca quem caiu e avisa;
 *   2. um aviso permanente no canto superior direito, para quem está com a
 *      ligação em baixo, com um clique para reconectar.
 * ================================================================== */

define('DPS_WA_INTERVALO_VERIFICACAO', 3600);   // 1 hora

/**
 * Estado real da instância de um comercial na Evolution.
 * Devolve true só quando está mesmo utilizável.
 */
function dps_wa_ligacao_viva($staff_id)
{
    $url = rtrim((string) get_option('dps_whatsapp_evolution_url'), '/');
    $key = (string) get_option('dps_whatsapp_evolution_api_key');

    if ($url === '' || $key === '') {
        return null;   // sem configuração não se conclui nada
    }

    $ch = curl_init($url . '/instance/connectionState/staff-' . (int) $staff_id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        // Tempos curtos de propósito: esta chamada corre dentro de um pedido
        // web (o cron do Perfex pode ser disparado por uma visita). Com a
        // Evolution em baixo, 12s x N comerciais prendiam os processos PHP da
        // conta inteira — e o alojamento estrangula tudo, incluindo o outro
        // site. Antes falhar depressa do que pendurar o CRM.
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_HTTPHEADER     => ['apikey: ' . $key],
    ]);
    $resp = (string) curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http === 0) {
        return null;   // a Evolution não respondeu: não penalizar o comercial
    }
    if ($http === 404) {
        return false;  // instância inexistente
    }

    return strpos($resp, '"open"') !== false;
}

/**
 * Marca a ligação de um comercial como caída e avisa-o (uma vez por queda).
 * Chamada pela verificação horária e também quando um envio falha por falta
 * de sessão — assim o aviso aparece na hora, não só na verificação seguinte.
 */
function dps_wa_marcar_caida($staff_id, $motivo = '')
{
    $CI       = &get_instance();
    $staff_id = (int) $staff_id;
    $tabela   = db_prefix() . 'dps_whatsapp_config';

    if (!$CI->db->table_exists($tabela)) {
        return;
    }

    $actual = $CI->db->where('staff_id', $staff_id)->get($tabela)->row_array();

    // Já estava marcada como caída: não voltar a avisar (evita spam de hora a hora).
    if ($actual && (int) $actual['is_connected'] === 0) {
        return;
    }

    $CI->db->where('staff_id', $staff_id)->update($tabela, ['is_connected' => 0]);

    $texto = 'O seu WhatsApp desligou-se do CRM' . ($motivo !== '' ? ' (' . $motivo . ')' : '')
        . '. Vá a WhatsApp e leia o QR outra vez — enquanto não o fizer, não consegue enviar propostas.';

    if (function_exists('add_notification')) {
        add_notification([
            'description' => $texto,
            'touserid'    => $staff_id,
            'link'        => 'dps_whatsapp',
            'fromcompany' => true,
        ]);
    }

    // Avisar também a direção, para não depender de o comercial reparar.
    $nome = function_exists('get_staff_full_name') ? get_staff_full_name($staff_id) : ('#' . $staff_id);
    foreach ($CI->db->where('admin', 1)->where('active', 1)->get(db_prefix() . 'staff')->result_array() as $adm) {
        if ((int) $adm['staffid'] === $staff_id) {
            continue;
        }
        add_notification([
            'description' => 'WhatsApp de ' . $nome . ' caiu — precisa de reler o QR.',
            'touserid'    => (int) $adm['staffid'],
            'link'        => 'dps_whatsapp',
            'fromcompany' => true,
        ]);
    }

    log_activity('DPS WhatsApp: ligação de ' . $nome . ' marcada como caída. ' . $motivo);
}

/**
 * Verificação horária de todas as ligações.
 * Corre no cron do Perfex; a janela evita repetir a cada passagem do cron.
 */
hooks()->add_action('after_cron_run', 'dps_wa_verificar_ligacoes');
function dps_wa_verificar_ligacoes()
{
    $ultima = (int) get_option('dps_wa_ultima_verificacao');

    if ($ultima && (time() - $ultima) < DPS_WA_INTERVALO_VERIFICACAO) {
        return;
    }

    update_option('dps_wa_ultima_verificacao', time());

    $CI     = &get_instance();
    $tabela = db_prefix() . 'dps_whatsapp_config';

    if (!$CI->db->table_exists($tabela)) {
        return;
    }

    $sem_resposta = 0;

    try {
        foreach ($CI->db->get($tabela)->result_array() as $linha) {
            $staff_id = (int) $linha['staff_id'];
            $viva     = dps_wa_ligacao_viva($staff_id);

            if ($viva === null) {
                // Indeterminado. Se for porque a Evolution não respondeu, as
                // seguintes também não vão responder: sair já em vez de somar
                // uma espera por cada comercial.
                if (++$sem_resposta >= 2) {
                    log_activity('DPS WhatsApp: Evolution sem resposta — verificação adiada.');

                    return;
                }
                continue;
            }

            $sem_resposta = 0;

            if ($viva === false) {
                dps_wa_marcar_caida($staff_id, 'detetado na verificação automática');
            } elseif ((int) $linha['is_connected'] === 0) {
                // Voltou sozinha ao ar: repor o estado, sem avisos.
                $CI->db->where('staff_id', $staff_id)->update($tabela, ['is_connected' => 1]);
            }
        }
    } catch (\Throwable $e) {
        log_activity('DPS WhatsApp: verificação de ligações falhou — ' . $e->getMessage());
    }
}

/**
 * Aviso no canto superior direito, para quem tem a ligação em baixo.
 * Fica ao lado do perfil, é visível em qualquer página e leva direto ao
 * ecrã de reconexão.
 */
hooks()->add_action('app_admin_footer', 'dps_wa_aviso_topo');
function dps_wa_aviso_topo()
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return;
    }

    $CI     = &get_instance();
    $tabela = db_prefix() . 'dps_whatsapp_config';

    if (!$CI->db->table_exists($tabela)) {
        return;
    }

    $cfg = $CI->db->where('staff_id', (int) get_staff_user_id())->get($tabela)->row_array();

    // Sem configuração nenhuma não se incomoda: ainda não usa o WhatsApp.
    if (!$cfg || (int) $cfg['is_connected'] === 1) {
        return;
    }
    ?>
    <script>
    (function () {
      'use strict';
      if (document.getElementById('dps-wa-aviso')) { return; }

      var a = document.createElement('a');
      a.id = 'dps-wa-aviso';
      a.href = '<?php echo admin_url('dps_whatsapp'); ?>';
      a.title = 'O seu WhatsApp está desligado — clique para ler o QR e reconectar';
      a.style.cssText = 'display:inline-flex;align-items:center;gap:7px;background:#e04545;color:#fff;'
        + 'text-decoration:none;font-weight:700;font-size:12px;line-height:1;padding:8px 13px;'
        + 'border-radius:20px;margin-right:12px;white-space:nowrap;'
        + 'box-shadow:0 2px 8px rgba(224,69,69,.35);';
      a.innerHTML = '<i class="fa fa-whatsapp" style="font-size:15px;"></i> WhatsApp desligado — reconectar';

      // Encaixar na barra de topo, à esquerda do perfil. Se o tema mudar e
      // nenhum destes existir, cai para um aviso flutuante no mesmo canto.
      var alvos = ['#top-search', '.navbar-right', '#header .pull-right', 'header .tw-flex.tw-items-center'];
      var posto = false;

      for (var i = 0; i < alvos.length && !posto; i++) {
        var el = document.querySelector(alvos[i]);
        if (el && el.parentNode) {
          el.parentNode.insertBefore(a, el);
          posto = true;
        }
      }

      if (!posto) {
        a.style.position = 'fixed';
        a.style.top = '14px';
        a.style.right = '18px';
        a.style.zIndex = '99999';
        document.body.appendChild(a);
      }
    })();
    </script>
    <?php
}
