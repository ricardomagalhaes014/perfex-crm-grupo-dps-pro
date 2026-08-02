<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: DPS Google Calendar
Description: Cada comercial liga a sua conta Google e as reuniões passam a aparecer no calendário dele, criadas, actualizadas e apagadas pelo CRM.
Version: 1.0.0
Requires at least: 2.3.*
Author: Grupo DPS
*/

define('DPS_GOOGLE_MODULE_NAME', 'dps_google');

/**
 * Só os eventos do calendário. Não se pede leitura do calendário todo nem
 * nada do Gmail: quanto menos se pede, menos assusta o ecrã de autorização e
 * menos há a explicar ao Google se um dia houver verificação.
 */
define('DPS_GOOGLE_SCOPE', 'https://www.googleapis.com/auth/calendar.events openid email');

define('DPS_GOOGLE_AUTH_URL',  'https://accounts.google.com/o/oauth2/v2/auth');
define('DPS_GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('DPS_GOOGLE_API',       'https://www.googleapis.com/calendar/v3');

register_activation_hook(DPS_GOOGLE_MODULE_NAME, 'dps_google_activate');

function dps_google_activate()
{
    require_once __DIR__ . '/install.php';
}

hooks()->add_action('admin_init', 'dps_google_ensure_schema');
hooks()->add_action('admin_init', 'dps_google_menu');

function dps_google_ensure_schema()
{
    static $feito = false;
    if ($feito) {
        return;
    }
    $feito = true;

    $CI = &get_instance();
    if (!$CI->db->table_exists(db_prefix() . 'dps_google_contas')) {
        require_once __DIR__ . '/install.php';
    }
}

function dps_google_menu()
{
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('dps_google', [
        'name'     => 'Google Calendar',
        'href'     => admin_url('dps_google'),
        'icon'     => 'fa fa-calendar',
        'position' => 92,
        'badge'    => [],
    ]);
}

/**
 * O endereço de retorno que tem de ser colado no Google Cloud, tal e qual.
 *
 * Escrito uma vez e usado nos dois sítios (o pedido e a troca do código) —
 * se estes dois não forem exactamente iguais, o Google recusa com
 * redirect_uri_mismatch, que é o erro mais comum a montar isto.
 */
function dps_google_redirect_uri()
{
    return admin_url('dps_google/callback');
}

/* =========================================================================
 * CONTAS LIGADAS
 * ====================================================================== */

function dps_google_conta($staff_id)
{
    $CI = &get_instance();

    return $CI->db->where('staff_id', (int) $staff_id)
                  ->get(db_prefix() . 'dps_google_contas')->row_array();
}

/**
 * Devolve um token de acesso válido, renovando-o se já expirou.
 *
 * O token de acesso dura uma hora; o de renovação dura até ser revogado. É
 * por isso que se pede access_type=offline e prompt=consent na autorização —
 * sem os dois, o Google devolve o refresh_token só à PRIMEIRA vez e nunca
 * mais, e a ligação morre em silêncio uma hora depois.
 *
 * @return string|null token, ou null se a conta não está ligada ou foi revogada
 */
function dps_google_token($staff_id)
{
    $conta = dps_google_conta($staff_id);
    if (!$conta || empty($conta['refresh_token'])) {
        return null;
    }

    // Com 60 segundos de folga: um token que expira "agora" já expirou quando
    // o pedido chegar ao Google.
    if (!empty($conta['access_token']) && strtotime($conta['expires_at']) > time() + 60) {
        return $conta['access_token'];
    }

    $r = dps_google_post(DPS_GOOGLE_TOKEN_URL, [
        'client_id'     => get_option('dps_google_client_id'),
        'client_secret' => get_option('dps_google_client_secret'),
        'refresh_token' => $conta['refresh_token'],
        'grant_type'    => 'refresh_token',
    ]);

    if (empty($r['access_token'])) {
        /*
         * A renovação falhou. A causa mais comum não é um erro nosso: é a
         * aplicação estar em "Testing" no Google Cloud, onde as autorizações
         * são revogadas ao fim de 7 dias. Regista-se para não se andar a
         * adivinhar, e marca-se a conta como precisando de nova ligação.
         */
        $CI = &get_instance();
        $CI->db->where('staff_id', (int) $staff_id)
               ->update(db_prefix() . 'dps_google_contas', [
                   'ultimo_erro' => substr(json_encode($r, JSON_UNESCAPED_UNICODE), 0, 400),
                   'access_token' => null,
               ]);
        log_activity('Google Calendar: renovação falhou para o staff ' . (int) $staff_id
            . ' — ' . ($r['error_description'] ?? $r['error'] ?? 'sem detalhe'));

        return null;
    }

    $CI = &get_instance();
    $CI->db->where('staff_id', (int) $staff_id)
           ->update(db_prefix() . 'dps_google_contas', [
               'access_token' => $r['access_token'],
               'expires_at'   => date('Y-m-d H:i:s', time() + (int) ($r['expires_in'] ?? 3600)),
               'ultimo_erro'  => null,
           ]);

    return $r['access_token'];
}

/* =========================================================================
 * CHAMADAS AO GOOGLE
 * ====================================================================== */

function dps_google_post($url, array $campos, $token = null)
{
    $ch = curl_init($url);
    $cabecalhos = ['Content-Type: application/x-www-form-urlencoded'];
    if ($token) {
        $cabecalhos[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($campos),
        CURLOPT_HTTPHEADER     => $cabecalhos,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $corpo = curl_exec($ch);
    curl_close($ch);

    return json_decode((string) $corpo, true) ?: [];
}

/**
 * Chamada à API do calendário com corpo em JSON.
 *
 * @param string $metodo POST, PATCH ou DELETE
 */
function dps_google_api($metodo, $caminho, array $corpo, $token)
{
    $ch = curl_init(DPS_GOOGLE_API . $caminho);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_POSTFIELDS     => $corpo ? json_encode($corpo, JSON_UNESCAPED_UNICODE) : null,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 25,
    ]);
    $resposta = curl_exec($ch);
    $codigo   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'ok'     => $codigo >= 200 && $codigo < 300,
        'codigo' => $codigo,
        'dados'  => json_decode((string) $resposta, true) ?: [],
    ];
}

/**
 * Cria ou actualiza um evento no calendário de um comercial.
 *
 * Devolve o id do evento no Google, para que a próxima alteração o encontre
 * em vez de criar um segundo. Sem guardar este id, mudar a hora de uma
 * reunião deixava a antiga lá e criava outra ao lado.
 *
 * @param array $ev ['titulo','descricao','inicio','fim','convidados'=>[emails],'local']
 *
 * @return string|null id do evento, ou null se não deu
 */
function dps_google_evento_guardar($staff_id, array $ev, $event_id = null)
{
    $token = dps_google_token($staff_id);
    if (!$token) {
        return null;
    }

    $fuso = get_option('default_timezone') ?: 'Europe/Lisbon';

    $corpo = [
        'summary'     => $ev['titulo'],
        'description' => $ev['descricao'] ?? '',
        'start'       => ['dateTime' => date('c', strtotime($ev['inicio'])), 'timeZone' => $fuso],
        'end'         => ['dateTime' => date('c', strtotime($ev['fim'])),    'timeZone' => $fuso],
    ];

    if (!empty($ev['local'])) {
        $corpo['location'] = $ev['local'];
    }

    /*
     * Lembretes fixados, em vez dos que cada pessoa tiver por omissão.
     *
     * Sem isto, o evento herda as preferências do calendário de quem o
     * recebe — que podem ser 10 minutos, 30, ou nenhum. Uma reunião com
     * cliente não pode depender de a pessoa ter configurado bem o telemóvel.
     *
     * Trinta minutos para se preparar, dez para entrar.
     */
    $corpo['reminders'] = [
        'useDefault' => false,
        'overrides'  => [
            ['method' => 'popup', 'minutes' => 30],
            ['method' => 'popup', 'minutes' => 10],
        ],
    ];

    if (!empty($ev['convidados'])) {
        $corpo['attendees'] = [];
        foreach ($ev['convidados'] as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $corpo['attendees'][] = ['email' => $email];
            }
        }
    }

    // sendUpdates=all: o Google trata de enviar o convite a quem foi posto
    // como participante. Sem isto o evento existe mas ninguém é avisado.
    $extra = '?sendUpdates=all';

    $r = $event_id
        ? dps_google_api('PATCH', '/calendars/primary/events/' . rawurlencode($event_id) . $extra, $corpo, $token)
        : dps_google_api('POST', '/calendars/primary/events' . $extra, $corpo, $token);

    if (!$r['ok']) {
        log_activity('Google Calendar: evento falhou (staff ' . (int) $staff_id . ', HTTP '
            . $r['codigo'] . ') — ' . ($r['dados']['error']['message'] ?? 'sem detalhe'));

        return null;
    }

    return $r['dados']['id'] ?? null;
}

function dps_google_evento_apagar($staff_id, $event_id)
{
    $token = dps_google_token($staff_id);
    if (!$token || !$event_id) {
        return false;
    }

    $r = dps_google_api('DELETE',
        '/calendars/primary/events/' . rawurlencode($event_id) . '?sendUpdates=all', [], $token);

    // 410 = já lá não estava. Para o nosso efeito é o mesmo que ter apagado.
    return $r['ok'] || $r['codigo'] === 410;
}
