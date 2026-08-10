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

    /*
     * "Em Massa Reunião Online" é registado como item de TOPO, e não como
     * filho de "Reuniões online", porque quem decide a barra lateral é o
     * dps_sidebar_reorg_hook — e esse só sabe mexer em itens de topo. É lá que
     * ele é arrumado para dentro de "Automações", que é onde a equipa procura
     * os envios em massa.
     */
    $CI->app_menu->add_sidebar_menu_item('dps_reunioes_massa', [
        'slug'     => 'dps_reunioes_massa',
        'name'     => 'Em Massa Reunião Online',
        'href'     => admin_url('dps_reunioes/propostas'),
        'icon'     => 'fa fa-users',
        'position' => 92,
    ]);
    $CI->app_menu->add_sidebar_menu_item('dps_reunioes', [
        'name'     => 'Reuniões online',
        'href'     => admin_url('dps_reunioes'),
        'icon'     => 'fa fa-video-camera',
        'position' => 91,
        'badge'    => [],
    ]);

    /*
     * Sem o filho "Todas", carregar no pai deixava de abrir a lista em alguns
     * temas: o Perfex passa a tratar o item como um menu que só abre e fecha.
     */
    $CI->app_menu->add_sidebar_children_item('dps_reunioes', [
        'slug'     => 'dps_reunioes_todas',
        'name'     => 'Todas as reuniões',
        'href'     => admin_url('dps_reunioes'),
        'position' => 1,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_reunioes', [
        'slug'     => 'dps_reunioes_agenda',
        'name'     => 'Agenda partilhada',
        'href'     => admin_url('dps_reunioes/agenda'),
        'position' => 2,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_reunioes', [
        'slug'     => 'dps_reunioes_disp',
        'name'     => 'A minha disponibilidade',
        'href'     => admin_url('dps_reunioes/disponibilidade'),
        'position' => 3,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_reunioes', [
        'slug'     => 'dps_reunioes_equipa',
        'name'     => 'Reunião de equipa',
        'href'     => admin_url('dps_reunioes/equipa'),
        'position' => 4,
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

    /*
     * Corre o install.php quando FALTAR QUALQUER uma das tabelas, e não só a
     * principal. Com a guarda antiga, acrescentar tabelas novas ao install.php
     * não servia de nada em instalações já existentes: a principal existia, o
     * ficheiro nunca corria, e as novas nunca nasciam. Foi o que ia acontecer
     * às propostas de reunião em massa.
     */
    $precisa = false;
    foreach (['dps_reunioes', 'dps_reunioes_campanhas', 'dps_reunioes_propostas'] as $tabela) {
        if (!$CI->db->table_exists(db_prefix() . $tabela)) {
            $precisa = true;
            break;
        }
    }

    if ($precisa) {
        require_once __DIR__ . '/install.php';
    }

    dps_reunioes_ensure_agenda($CI);
}

/**
 * Tabelas da agenda partilhada (o "Calendly" interno).
 *
 * Vivem à parte da tabela de reuniões de propósito: uma reunião é um facto
 * passado ou marcado, a disponibilidade é uma regra que muda quando apetece.
 * Misturá-las obrigava a reescrever reuniões sempre que o horário mudasse.
 */
function dps_reunioes_ensure_agenda($CI)
{
    $charset = $CI->db->char_set;

    /*
     * Horário semanal. Uma linha por bocado de dia — assim cabe "das 10h às
     * 13h e das 15h às 18h" na mesma terça-feira, que é como as pessoas
     * trabalham. dia_semana segue o ISO: 1 = segunda ... 7 = domingo.
     */
    $CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . "dps_reunioes_horario` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) NOT NULL,
        `dia_semana` TINYINT(1) NOT NULL,
        `hora_inicio` TIME NOT NULL,
        `hora_fim` TIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `staff_dia` (`staff_id`, `dia_semana`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . ';');

    /*
     * Excepções: férias, um dia cheio, uma tarde que deixou de dar. Sem horas
     * significa o dia inteiro — é o caso mais comum e não obriga a escrever
     * 00:00 às 23:59.
     */
    $CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . "dps_reunioes_bloqueio` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) NOT NULL,
        `data` DATE NOT NULL,
        `hora_inicio` TIME NULL DEFAULT NULL,
        `hora_fim` TIME NULL DEFAULT NULL,
        `motivo` VARCHAR(191) NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `staff_data` (`staff_id`, `data`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . ';');

    /*
     * As regras de quem publica a agenda. Uma linha por pessoa.
     *
     * `antecedencia_h` evita o pior defeito destas ferramentas: alguém marcar
     * uma reunião para daqui a dez minutos com quem já está a conduzir.
     */
    $CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . "dps_reunioes_partilha` (
        `staff_id` INT(11) NOT NULL,
        `publicada` TINYINT(1) NOT NULL DEFAULT 0,
        `duracao_min` INT(11) NOT NULL DEFAULT 30,
        `antecedencia_h` INT(11) NOT NULL DEFAULT 4,
        `horizonte_dias` INT(11) NOT NULL DEFAULT 21,
        `intervalo_min` INT(11) NOT NULL DEFAULT 0,
        `nota` VARCHAR(255) NULL DEFAULT NULL,
        `updated_at` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . ';');

    /*
     * Participantes internos. A tabela de reuniões já tem `convidado_id` para
     * UM convidado; para a reunião de equipa isso não chega, e reescrever a
     * coluna partia as reuniões que já existem. Esta tabela acrescenta sem
     * mexer no que está feito.
     */
    /*
     * Que eventos da agenda esta reunião gerou. Guarda-se para os poder
     * apagar quando ela for cancelada — sem isto ficavam órfãos no calendário
     * de toda a gente, e ninguém saberia de onde vinham.
     */
    foreach ($CI->db->field_data(db_prefix() . 'dps_reunioes') as $f) {
        if ($f->name === 'eventos') {
            $tem_eventos = true;
        }
    }
    if (empty($tem_eventos)) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . "dps_reunioes` ADD `eventos` VARCHAR(191) NULL DEFAULT NULL");
    }

    $CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . "dps_reunioes_participante` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `reuniao_id` INT(11) NOT NULL,
        `staff_id` INT(11) NOT NULL,
        `estado` VARCHAR(20) NOT NULL DEFAULT 'convidado',
        `respondido_em` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `reuniao_staff` (`reuniao_id`, `staff_id`),
        KEY `staff_id` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . ';');
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

/**
 * Avisa os convidados internos de uma reunião interna ou de equipa.
 *
 * Notificação no CRM sempre (é o que aparece no sino e não se perde), e
 * WhatsApp a quem tiver número. O email fica de fora aqui de propósito:
 * para dentro de casa, uma notificação e uma mensagem chegam, e mais um
 * email é mais uma coisa que ninguém lê.
 *
 * @param array $r          a reunião, já lida com get()
 * @param array $convidados staffids
 * @param string $tipo      'marcada' (a dois) ou 'equipa'
 */
function dps_reunioes_avisar_interno(array $r, array $convidados, $tipo = 'marcada')
{
    if (empty($convidados)) {
        return;
    }

    $CI     = &get_instance();
    $quando = dps_reunioes_quando($r['data_hora']);
    $quem   = $r['comercial'] ?: get_staff_full_name((int) $r['staff_id']);
    $link   = admin_url('dps_reunioes/ver/' . (int) $r['id']);

    $texto = $tipo === 'equipa'
        ? 'Reunião de equipa — ' . $r['assunto'] . ' — ' . $quando . '. Marcada por ' . $quem . '.'
        : $quem . ' marcou uma reunião consigo em ' . $quando . ' (' . $r['assunto'] . ').';

    foreach (array_unique(array_map('intval', $convidados)) as $id) {
        if ($id <= 0) {
            continue;
        }

        add_notification([
            'description' => $texto,
            'touserid'    => $id,
            'link'        => 'dps_reunioes/ver/' . (int) $r['id'],
            'fromcompany' => true,
        ]);

        $d = $CI->db->select('phonenumber')->where('staffid', $id)
                    ->get(db_prefix() . 'staff')->row_array();

        if (!empty($d['phonenumber'])) {
            dps_reunioes_whatsapp(
                $d['phonenumber'],
                $texto . "\n\nSala: " . $r['link'] . "\nDetalhes: " . $link,
                (int) $r['staff_id']
            );
        }
    }
}

/**
 * Põe a reunião na AGENDA de quem lá vai estar.
 *
 * Uma reunião marcada que não aparece no calendário é uma reunião que se
 * esquece: o comercial marca-a, fecha o CRM, e à hora está noutra coisa. E
 * como o dps_google sincroniza a tblevents com o Google, entrar aqui é também
 * entrar no telemóvel de cada um.
 *
 * Um evento POR PESSOA (o anfitrião e cada convidado), porque a tblevents tem
 * um só dono por linha — é assim que cada um a vê na sua agenda e não na dos
 * outros.
 *
 * @return string ids dos eventos criados, separados por vírgula
 */
function dps_reunioes_criar_eventos(array $r)
{
    $CI = &get_instance();

    if (empty($r['data_hora'])) {
        return '';
    }

    $inicio = date('Y-m-d H:i:s', strtotime($r['data_hora']));
    $fim    = date('Y-m-d H:i:s', strtotime($r['data_hora']) + max(10, (int) ($r['duracao_min'] ?? 30)) * 60);

    $quem = [(int) ($r['staff_id'] ?? 0)];
    if (!empty($r['convidado_id'])) {
        $quem[] = (int) $r['convidado_id'];
    }
    foreach ($CI->db->where('reuniao_id', (int) $r['id'])
                    ->get(db_prefix() . 'dps_reunioes_participante')->result_array() as $p) {
        $quem[] = (int) $p['staff_id'];
    }

    $titulo = trim((string) ($r['assunto'] ?? '')) ?: 'Reunião online';
    if (!empty($r['cliente_nome']) && stripos($titulo, (string) $r['cliente_nome']) === false
        && !in_array($r['cliente_nome'], ['Reunião interna', 'Reunião de equipa'], true)) {
        $titulo .= ' — ' . $r['cliente_nome'];
    }

    $ids = [];

    foreach (array_unique(array_filter($quem)) as $staff) {
        $CI->db->insert(db_prefix() . 'events', [
            'title'       => mb_substr($titulo, 0, 180),
            'description' => 'Sala: ' . (string) ($r['link'] ?? ''),
            'userid'      => $staff,
            'start'       => $inicio,
            'end'         => $fim,
            'public'      => 0,
            'color'       => '#28B8DA',
            'isstartnotified' => 0,
            'reminder_before' => 30,
            'reminder_before_type' => 'minutes',
        ]);

        $novo = (int) $CI->db->insert_id();
        if ($novo) {
            $ids[] = $novo;
        }
    }

    $lista = implode(',', $ids);

    if ($lista !== '' && !empty($r['id'])) {
        $CI->db->where('id', (int) $r['id'])
               ->update(db_prefix() . 'dps_reunioes', ['eventos' => $lista]);
    }

    return $lista;
}

/**
 * Tira da agenda os eventos de uma reunião cancelada.
 */
function dps_reunioes_apagar_eventos($reuniao_id)
{
    $CI = &get_instance();

    $r = $CI->db->select('eventos')->where('id', (int) $reuniao_id)
                ->get(db_prefix() . 'dps_reunioes')->row_array();

    if (empty($r['eventos'])) {
        return;
    }

    $ids = array_filter(array_map('intval', explode(',', $r['eventos'])));

    if ($ids) {
        $CI->db->where_in('eventid', $ids)->delete(db_prefix() . 'events');
    }

    $CI->db->where('id', (int) $reuniao_id)
           ->update(db_prefix() . 'dps_reunioes', ['eventos' => null]);
}

/* ===========================================================================
 * PROPOSTAS DE REUNIÃO EM MASSA
 * ======================================================================== */

/**
 * O texto que vai no convite. Fica em opção editável porque a forma de o dizer
 * muda muito mais depressa do que se faz um deploy.
 *
 * Marcas disponíveis: {nome} {comercial} {quando} {link}
 */
function dps_reunioes_texto_convite_por_omissao()
{
    return "Olá {nome}, é o {comercial} da DPS Imobiliário.\n\n"
         . "Proponho-lhe uma chamada {quando} para vermos juntos as simulações "
         . "e percebermos se este projeto encaixa no que procura.\n\n"
         . "Confirma-me este horário? Basta carregar aqui:\n{link}";
}

/**
 * "terça-feira, 11 de agosto às 15:00".
 *
 * Não se usa a dps_reunioes_quando(), que dá "11/08/2026 às 15:00": num convite
 * o dia da semana é o que faz a pessoa perceber logo se pode, sem ir ao
 * calendário. A tradução é à mão porque o strftime está obsoleto e o locale do
 * servidor não é de confiança em alojamento partilhado.
 */
function dps_reunioes_quando_extenso($data_hora)
{
    $t = strtotime($data_hora);

    $dias  = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira',
              'quinta-feira', 'sexta-feira', 'sábado'];
    $meses = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
              'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    return $dias[(int) date('w', $t)] . ', ' . (int) date('j', $t)
         . ' de ' . $meses[(int) date('n', $t)] . ' às ' . date('H:i', $t);
}

/**
 * Os horários possíveis de um dia: 09:00 às 19:30, de 30 em 30.
 */
function dps_reunioes_horarios_do_dia($data)
{
    $inicio = get_option('dps_reunioes_hora_inicio') ?: '09:00';
    $fim    = get_option('dps_reunioes_hora_fim') ?: '19:30';

    $t   = strtotime($data . ' ' . $inicio);
    $ate = strtotime($data . ' ' . $fim);

    $horarios = [];
    while ($t <= $ate) {
        $horarios[] = date('Y-m-d H:i:00', $t);
        $t          = strtotime('+30 minutes', $t);
    }

    return $horarios;
}

/**
 * Motor das propostas, pendurado no admin_init.
 *
 * NÃO se usa o cron do Perfex de propósito: neste servidor o cron.php é um
 * script antigo de outro sistema e o after_cron_run não dispara — foi por isso
 * que os follow-ups do WhatsApp nunca chegaram a funcionar. Pendurar no
 * admin_init faz o trabalho andar sempre que alguém usa o CRM, que é quando
 * interessa.
 *
 * Corre no máximo uma vez por minuto e trata poucos de cada vez: isto está no
 * caminho de TODAS as páginas do CRM e não pode pesar.
 */
hooks()->add_action('admin_init', 'dps_reunioes_motor');
function dps_reunioes_motor()
{
    $ultima = (int) get_option('dps_reunioes_motor_em');
    if ($ultima && (time() - $ultima) < 60) {
        return;
    }
    update_option('dps_reunioes_motor_em', time());

    $CI = &get_instance();

    if (!$CI->db->table_exists(db_prefix() . 'dps_reunioes_propostas')) {
        return;
    }

    /*
     * O lembrete dos 30 minutos e a tarefa de follow-up vivem na
     * dps_reunioes_cron(), que estava presa ao cron morto. Chamá-la aqui é
     * seguro: cada trabalho tem coluna de controlo própria (lembrete_30_em,
     * followup_task_id) e não repete. Se o cron algum dia voltar a funcionar,
     * o que ele encontrar já estará feito.
     */
    dps_reunioes_cron();

    dps_reunioes_expirar_propostas();
    dps_reunioes_enviar_propostas_pendentes();
}

/**
 * Propostas cuja hora já passou deixam de valer e libertam o horário.
 */
function dps_reunioes_expirar_propostas()
{
    $CI = &get_instance();

    $CI->db->where('estado', 'pendente');
    $CI->db->where('data_hora <=', date('Y-m-d H:i:s'));
    $CI->db->update(db_prefix() . 'dps_reunioes_propostas', [
        'estado'        => 'expirada',
        'respondido_em' => date('Y-m-d H:i:s'),
    ]);
}

/**
 * Envia um punhado de convites por passagem.
 *
 * O limite diário de WhatsApp é a regra da casa (20 por dia) e existe para as
 * contas dos comerciais não serem bloqueadas. Enviar uma campanha de 80 leads
 * de uma vez era queimar o número de quem a lançou.
 */
function dps_reunioes_enviar_propostas_pendentes($quantos = 5)
{
    $CI = &get_instance();
    $t  = db_prefix() . 'dps_reunioes_propostas';

    $pendentes = $CI->db->select('*')->from($t)
        ->where('estado', 'pendente')
        ->where('enviado_em IS NULL')
        ->order_by('id', 'asc')
        ->limit($quantos)
        ->get()->result_array();

    if (empty($pendentes)) {
        return;
    }

    $limite_wa = (int) (get_option('dps_reunioes_wa_por_dia') ?: 20);

    foreach ($pendentes as $p) {
        $texto = dps_reunioes_montar_convite($p);
        $enviou = [];

        $quer_wa = in_array($p['canal'], ['whatsapp', 'ambos'], true);

        if ($quer_wa && !empty($p['cliente_telefone'])) {
            $hoje_wa = (int) $CI->db->where('staff_id', (int) $p['staff_id'])
                ->where('enviado_por LIKE', '%whatsapp%')
                ->where('DATE(enviado_em)', date('Y-m-d'))
                ->count_all_results($t);

            if ($hoje_wa < $limite_wa) {
                if (dps_reunioes_whatsapp($p['cliente_telefone'], $texto, (int) $p['staff_id'])) {
                    $enviou[] = 'whatsapp';
                }
            } else {
                // Fica para amanhã: não se marca como enviada.
                continue;
            }
        }

        if (in_array($p['canal'], ['email', 'ambos'], true) && !empty($p['cliente_email'])) {
            if (dps_reunioes_email_convite($p, $texto)) {
                $enviou[] = 'email';
            }
        }

        $CI->db->where('id', (int) $p['id'])->update($t, [
            'enviado_em' => date('Y-m-d H:i:s'),
            'enviado_por' => implode('+', $enviou) ?: 'nenhum',
            'erro_envio' => empty($enviou) ? 'sem canal disponível (falta telefone ou email)' : null,
        ]);
    }
}

function dps_reunioes_montar_convite(array $p)
{
    $modelo = get_option('dps_reunioes_texto_convite') ?: dps_reunioes_texto_convite_por_omissao();

    return strtr($modelo, [
        '{nome}'      => trim((string) $p['cliente_nome']) ?: 'boa tarde',
        '{comercial}' => get_staff_full_name((int) $p['staff_id']),
        '{quando}'    => dps_reunioes_quando_extenso($p['data_hora']),
        '{link}'      => dps_reunioes_link_publico($p['chave']),
    ]);
}

/**
 * O link que o cliente carrega. Vive na raiz e não em admin/, porque quem o
 * abre não tem conta no CRM.
 */
function dps_reunioes_link_publico($chave)
{
    return site_url('reuniao/confirmar/' . rawurlencode($chave));
}

function dps_reunioes_email_convite(array $p, $texto)
{
    if (empty($p['cliente_email'])) {
        return false;
    }

    $CI = &get_instance();
    $CI->load->library('email');

    try {
        $CI->email->clear(true);
        $CI->email->from(get_option('smtp_email') ?: get_option('email'), get_option('companyname') ?: 'DPS Imobiliário');
        $CI->email->to($p['cliente_email']);
        $CI->email->subject('Proposta de reunião — ' . dps_reunioes_quando_extenso($p['data_hora']));
        $CI->email->message(nl2br(e($texto)));

        return (bool) $CI->email->send(true);
    } catch (Exception $e) {
        log_activity('Reuniões: falha no email da proposta ' . $p['id'] . ' — ' . $e->getMessage());

        return false;
    }
}
