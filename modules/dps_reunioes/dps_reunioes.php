<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: DPS Reuniões
Description: Reuniões online por Jitsi, agarradas à lead ou ao cliente: link gerado na marcação, aviso por email e WhatsApp, convite a um administrador, lembrete 30 minutos antes e tarefa de follow-up no fim.
Version: 1.0.0
Requires at least: 2.3.*
Author: Grupo DPS
*/

define('DPS_REUNIOES_MODULE_NAME', 'dps_reunioes');

/**
 * Quem pode ser convidado para uma reunião.
 *
 * Só o Cláudio (#46), por decisão do dono (02/08/2026). Antes a lista era
 * "todos os administradores", o que punha lá a direcção inteira e convidava
 * gente que não acompanha reuniões comerciais.
 *
 * Para mudar, é só acrescentar ou trocar o número.
 */
define('DPS_REUNIOES_CONVIDAVEL', [46]);


/**
 * PORQUÊ JITSI E NÃO GOOGLE MEET
 *
 * Um link do Meet não se gera sem OAuth: o Meet só nasce agarrado a um evento
 * do Google Calendar, e isso obriga a credenciais, consentimento e tokens que
 * expiram. O Jitsi não precisa de conta nenhuma — nem nossa nem do cliente —
 * e o link funciona no browser sem instalar nada. Decisão do dono (02/08/2026)
 * depois de lhe ser explicado o custo de cada caminho.
 *
 * A sala leva um pedaço aleatório no nome. Sem isso, quem adivinhasse o
 * número da lead entrava numa reunião a que não foi convidado.
 */

register_activation_hook(DPS_REUNIOES_MODULE_NAME, 'dps_reunioes_activate');

function dps_reunioes_activate()
{
    require_once __DIR__ . '/install.php';
}

hooks()->add_action('admin_init', 'dps_reunioes_ensure_schema');
hooks()->add_action('admin_init', 'dps_reunioes_menu');

/** Entrada no menu lateral. Sem ela o módulo existia mas não se via. */
function dps_reunioes_menu()
{
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('dps_reunioes', [
        'name'     => 'Reuniões online',
        'href'     => admin_url('dps_reunioes'),
        'icon'     => 'fa fa-video-camera',
        'position' => 91,
        'badge'    => [],
    ]);
}
hooks()->add_action('after_cron_run', 'dps_reunioes_cron');

/** Cria a tabela se faltar. Idempotente — pode correr em todos os pedidos. */
function dps_reunioes_ensure_schema()
{
    static $feito = false;
    if ($feito) {
        return;
    }
    $feito = true;

    $CI = &get_instance();
    if (!$CI->db->table_exists(db_prefix() . 'dps_reunioes')) {
        require_once __DIR__ . '/install.php';
    }
}

/* =========================================================================
 * AVISOS
 * ====================================================================== */

/**
 * Data e hora em português, para entrar num email ou num WhatsApp.
 */
function dps_reunioes_quando($data_hora)
{
    $t = strtotime($data_hora);

    return date('d/m/Y', $t) . ' às ' . date('H:i', $t);
}

/**
 * Avisa o cliente por email e por WhatsApp.
 *
 * As duas vias são "melhor esforço" e independentes: se o WhatsApp do
 * comercial não estiver ligado, o email vai na mesma. O contrário também.
 * Uma reunião marcada não pode depender de os dois canais estarem de pé.
 */
function dps_reunioes_avisar_cliente(array $r, $tipo = 'marcada')
{
    $CI = &get_instance();

    $quando   = dps_reunioes_quando($r['data_hora']);
    $primeiro = trim(explode(' ', trim((string) $r['cliente_nome']))[0]);

    if ($tipo === 'lembrete') {
        $assunto = 'A sua reunião é daqui a 30 minutos';
        $intro   = 'A sua reunião começa dentro de 30 minutos.';
    } else {
        $assunto = 'A sua reunião foi agendada';
        $intro   = 'A sua reunião foi agendada.';
    }

    /* ------------------------------- email ------------------------------- */
    if (!empty($r['cliente_email']) && filter_var($r['cliente_email'], FILTER_VALIDATE_EMAIL)) {
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;color:#222;font-size:15px;line-height:1.6;">'
            . '<p>Olá ' . html_escape($primeiro ?: 'boa tarde') . ',</p>'
            . '<p>' . $intro . '</p>'
            . '<table style="border-collapse:collapse;margin:18px 0;">'
            . '<tr><td style="padding:6px 18px 6px 0;color:#666;">Data</td><td style="padding:6px 0;"><strong>' . date('d/m/Y', strtotime($r['data_hora'])) . '</strong></td></tr>'
            . '<tr><td style="padding:6px 18px 6px 0;color:#666;">Hora</td><td style="padding:6px 0;"><strong>' . date('H:i', strtotime($r['data_hora'])) . '</strong></td></tr>'
            . '<tr><td style="padding:6px 18px 6px 0;color:#666;">Consultor</td><td style="padding:6px 0;"><strong>' . html_escape((string) $r['comercial']) . '</strong></td></tr>'
            . '</table>'
            . '<p style="margin:24px 0;"><a href="' . html_escape($r['link']) . '" '
            . 'style="background:#1a1a2e;color:#fff;padding:14px 28px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;">Entrar na reunião</a></p>'
            . '<p style="font-size:13px;color:#666;">Ou copie este endereço: ' . html_escape($r['link']) . '</p>'
            . '<p style="font-size:13px;color:#666;">Basta abrir o link no telemóvel ou no computador — não precisa de instalar nada nem de criar conta.</p>'
            . '<p style="margin-top:26px;">Com os melhores cumprimentos,<br><strong>' . html_escape((string) $r['comercial']) . '</strong><br>'
            . '<span style="color:#666;">DPS Imobiliário</span></p></div>';

        try {
            $CI->load->library('email');
            $CI->email->clear(true);
            $CI->email->from(get_option('smtp_email') ?: get_option('email'), get_option('companyname') ?: 'DPS Imobiliário');
            $CI->email->to($r['cliente_email']);
            $CI->email->subject($assunto);
            $CI->email->set_mailtype('html');
            $CI->email->message($html);
            @$CI->email->send(false);
        } catch (Throwable $e) {
            log_activity('Reunião #' . $r['id'] . ': email ao cliente falhou — ' . $e->getMessage());
        }
    }

    /* ------------------------------ WhatsApp ----------------------------- */
    $texto = 'Olá ' . ($primeiro ?: '') . ",\n\n" . $intro . "\n\n"
        . '📅 ' . $quando . "\n"
        . '👤 ' . $r['comercial'] . "\n\n"
        . "Entrar na reunião:\n" . $r['link'] . "\n\n"
        . 'Abre no telemóvel ou no computador, sem instalar nada.';

    dps_reunioes_whatsapp((string) $r['cliente_telefone'], $texto, (int) $r['staff_id']);
}

/**
 * Envia um WhatsApp pela Evolution API.
 *
 * Escrito aqui e não reaproveitado do dps_vendas porque este envio é para o
 * CLIENTE e sai pela instância do COMERCIAL — é ele que o cliente conhece.
 * O outro serve para avisar staff.
 */
function dps_reunioes_whatsapp($numero, $texto, $staff_remetente)
{
    $evo_url = rtrim((string) get_option('dps_whatsapp_evolution_url'), '/');
    $evo_key = (string) get_option('dps_whatsapp_evolution_api_key');
    $num     = preg_replace('/[^0-9]/', '', (string) $numero);

    if ($evo_url === '' || $evo_key === '' || $num === '') {
        return false;
    }

    // Números portugueses sem indicativo. Sem isto o Jitsi ia por email e o
    // WhatsApp falhava em silêncio, que é o pior dos dois mundos.
    if (strlen($num) === 9) {
        $num = '351' . $num;
    }

    $remetente = (int) $staff_remetente;
    if ($remetente <= 0) {
        $remetente = 1;
    }

    $ch = curl_init($evo_url . '/message/sendText/staff-' . $remetente);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $evo_key],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['number' => $num, 'text' => $texto]),
    ]);
    $resposta = curl_exec($ch);
    $codigo   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $codigo >= 200 && $codigo < 300;
}

/**
 * Convida um administrador, com aceitar/recusar dentro do CRM.
 */
function dps_reunioes_convidar(array $r)
{
    if (empty($r['convidado_id'])) {
        return;
    }

    $texto = 'Convite para reunião online — ' . $r['cliente_nome']
        . ' em ' . dps_reunioes_quando($r['data_hora'])
        . ', com ' . $r['comercial'] . '.';

    add_notification([
        'description' => $texto . ' Responda na ficha da reunião.',
        'touserid'    => (int) $r['convidado_id'],
        'link'        => 'dps_reunioes/ver/' . (int) $r['id'],
        'fromcompany' => true,
    ]);

    $CI = &get_instance();
    $d  = $CI->db->select('email, phonenumber')->where('staffid', (int) $r['convidado_id'])
                 ->get(db_prefix() . 'staff')->row_array();

    if (!empty($d['phonenumber'])) {
        dps_reunioes_whatsapp($d['phonenumber'],
            $texto . "\n\nAceitar ou recusar: " . admin_url('dps_reunioes/ver/' . (int) $r['id']),
            (int) $r['staff_id']);
    }
}

/* =========================================================================
 * CRON — lembretes e follow-up
 * ====================================================================== */

/**
 * Corre a cada passagem do cron do Perfex (5 em 5 minutos).
 *
 * Dois trabalhos, ambos "uma vez só" por reunião: o lembrete dos 30 minutos e
 * a tarefa de follow-up depois de a reunião passar. As colunas de controlo
 * (lembrete_30_em, followup_task_id) são o que impede a repetição — sem elas
 * o cliente levava a mesma mensagem de cinco em cinco minutos.
 */
function dps_reunioes_cron()
{
    $CI = &get_instance();
    $CI->load->model('dps_reunioes/dps_reunioes_model');
    $t = db_prefix() . 'dps_reunioes';

    /* ---- Lembrete 30 minutos antes, ao cliente e ao comercial ---- */
    $proximas = $CI->db->select('id')->from($t)
        ->where('estado', 'agendada')
        ->where('lembrete_30_em IS NULL')
        ->where('data_hora >', date('Y-m-d H:i:s'))
        ->where('data_hora <=', date('Y-m-d H:i:s', strtotime('+30 minutes')))
        ->get()->result_array();

    foreach ($proximas as $p) {
        $r = $CI->dps_reunioes_model->get($p['id']);
        if (!$r) {
            continue;
        }

        dps_reunioes_avisar_cliente($r, 'lembrete');

        add_notification([
            'description' => 'Reunião com ' . $r['cliente_nome'] . ' daqui a 30 minutos.',
            'touserid'    => (int) $r['staff_id'],
            'link'        => 'dps_reunioes/ver/' . (int) $r['id'],
            'fromcompany' => true,
        ]);
        if (!empty($r['comercial_tel'])) {
            dps_reunioes_whatsapp($r['comercial_tel'],
                'Reunião daqui a 30 minutos com ' . $r['cliente_nome'] . "\n" . $r['link'],
                (int) $r['staff_id']);
        }

        $CI->db->where('id', (int) $r['id'])->update($t, ['lembrete_30_em' => date('Y-m-d H:i:s')]);
    }

    /* ---- Tarefa de follow-up depois de a reunião terminar ---- */
    $passadas = $CI->db->select('id')->from($t)
        ->where('estado', 'agendada')
        ->where('followup_task_id IS NULL')
        ->where('DATE_ADD(data_hora, INTERVAL duracao_min MINUTE) <=', date('Y-m-d H:i:s'))
        ->get()->result_array();

    foreach ($passadas as $p) {
        $r = $CI->dps_reunioes_model->get($p['id']);
        if (!$r) {
            continue;
        }

        $CI->load->model('tasks_model');

        $task_id = $CI->tasks_model->add([
            'name'        => 'Follow-up da reunião com ' . $r['cliente_nome'],
            'description' => 'Reunião de ' . dps_reunioes_quando($r['data_hora'])
                . '.<br><br>Registe o resultado na ficha da reunião: realizada ou não compareceu, '
                . 'e a duração.<br><br><a href="' . admin_url('dps_reunioes/ver/' . (int) $r['id']) . '">Abrir a reunião</a>',
            'priority'    => 2,
            'startdate'   => date('Y-m-d'),
            'duedate'     => date('Y-m-d', strtotime('+2 days')),
            'assignees'   => [(int) $r['staff_id']],
            'rel_type'    => $r['rel_type'] === 'lead' ? 'lead' : 'customer',
            'rel_id'      => (int) $r['rel_id'],
            'is_public'   => 0,
        ]);

        if ($task_id) {
            $CI->db->where('id', (int) $r['id'])->update($t, ['followup_task_id' => (int) $task_id]);
            log_activity('Reunião #' . $r['id'] . ' — tarefa de follow-up #' . $task_id . ' criada');
        }
    }
}

/* =========================================================================
 * O bloco na ficha da LEAD
 *
 * O cliente tem um registo de separadores próprio; a lead não tem. O único
 * ponto de extensão é o after_lead_tabs_content, que fica FORA do contentor
 * dos painéis — um <div class="tab-pane"> ali nunca seria mostrado pelo
 * Bootstrap.
 *
 * Por isso o bloco é escrito ali e depois MUDADO de sítio por JavaScript:
 * acrescenta-se o separador à lista e move-se o painel para dentro do
 * contentor. Se esse JavaScript falhar por alguma razão, o bloco fica na
 * mesma visível no fundo da janela — degrada, não desaparece.
 * ====================================================================== */
hooks()->add_action('after_lead_tabs_content', 'dps_reunioes_bloco_lead');

function dps_reunioes_bloco_lead($lead)
{
    if (empty($lead) || empty($lead->id)) {
        return;
    }

    $CI = &get_instance();

    $rel_type  = 'lead';
    $rel_id    = (int) $lead->id;
    $pre_nome  = (string) ($lead->name ?? '');
    $pre_email = (string) ($lead->email ?? '');
    $pre_tel   = (string) ($lead->phonenumber ?? '');

    echo '<div id="dps_reunioes_lead" class="mtop20">';
    $CI->load->view('dps_reunioes/bloco_ficha',
        compact('rel_type', 'rel_id', 'pre_nome', 'pre_email', 'pre_tel'));
    echo '</div>';
    ?>
<script>
(function () {
    /*
     * NAO SE PoE UMA VEZ E FICA.
     *
     * Este tema reconstroi a tira de separadores da janela da lead (e a que
     * tem as setas nas pontas). Um <li> acrescentado uma vez e deitado fora
     * na reconstrucao seguinte, e o botao da barra de accoes idem. Foi por
     * isso que nem o separador nem o botao apareceram, mesmo com o HTML todo
     * no sitio certo — confirmado por registo: a funcao corre e a vista
     * carrega.
     *
     * Em vez de adivinhar quando a reconstrucao acontece, verifica-se de meio
     * em meio segundo durante 15 segundos e repoe-se o que faltar. Passado
     * isso pára — a janela ja estabilizou ha muito.
     */
    var painel = document.getElementById('dps_reunioes_lead');
    if (!painel) { return; }

    function saltarParaReunioes() {
        var a = document.querySelector('a[href="#dps_reunioes_lead"]');
        if (a && window.jQuery) { window.jQuery(a).tab('show'); }
        var alvo = document.getElementById('dps_reunioes_lead');
        if (alvo) { alvo.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    }

    function repor() {
        var modal = painel.closest('.modal') || document;

        // 1) Botao na barra de accoes, ao lado do "Enviar info / Proposta".
        var barra = modal.querySelector('#dps_action_bar');
        if (barra && !barra.querySelector('#dps_btn_reuniao')) {
            var b = document.createElement('button');
            b.type = 'button';
            b.id = 'dps_btn_reuniao';
            b.className = 'btn btn-sm';
            b.style.cssText = 'background:#0f8b8d;color:#fff;';
            b.innerHTML = '<i class="fa fa-video-camera"></i> Marcar reunião';
            b.onclick = saltarParaReunioes;
            barra.appendChild(b);
        }

        // 2) Separador, se houver tira onde o pendurar.
        var lista = modal.querySelector('ul.nav-tabs');
        var pai   = modal.querySelector('.tab-content');
        if (lista && pai && !lista.querySelector('a[href="#dps_reunioes_lead"]')) {
            var li = document.createElement('li');
            li.setAttribute('role', 'presentation');
            li.innerHTML = '<a href="#dps_reunioes_lead" role="tab" data-toggle="tab">'
                         + '<i class="fa fa-video-camera menu-icon"></i> Reuniões</a>';
            lista.appendChild(li);
            painel.classList.add('tab-pane');
            painel.classList.remove('mtop20');
            pai.appendChild(painel);
        }
    }

    repor();
    var voltas = 0;
    var iv = setInterval(function () {
        repor();
        if (++voltas > 30) { clearInterval(iv); }
    }, 500);
})();
</script>
    <?php
}
