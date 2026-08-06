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

    /*
     * Dia inteiro, para o que não tem hora.
     *
     * Uma tarefa tem prazo ("31/07"), não hora. Inventar-lhe as 9h punha no
     * calendário um compromisso que ninguém marcou, e empurrava para baixo o
     * que estava mesmo marcado para essa hora. No Google, dia inteiro é
     * 'date' em vez de 'dateTime', e o fim é o dia SEGUINTE — a data final é
     * exclusiva, e com o mesmo dia nos dois a API recusa.
     */
    if (!empty($ev['dia_inteiro'])) {
        $corpo['start'] = ['date' => date('Y-m-d', strtotime($ev['inicio']))];
        $corpo['end']   = ['date' => date('Y-m-d', strtotime($ev['inicio']) + 86400)];
    }

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

/* =========================================================================
 * A AGENDA DO CRM NO GOOGLE CALENDAR
 *
 * Não são só as reuniões online. O que a pessoa marca na agenda do Perfex —
 * eventos e lembretes — passa a aparecer no calendário Google dela.
 *
 * COMO SE EVITA REESCREVER TUDO DE 5 EM 5 MINUTOS. De cada item guarda-se
 * uma impressão digital dos campos que interessam. Se não mudou, não se toca:
 * sem isso o cron reescrevia dezenas de eventos a cada passagem, gastava
 * quota da API e fazia o telemóvel de toda a gente apitar sem razão.
 *
 * JANELA. Só o que está a menos de 7 dias no passado e daí para a frente.
 * Sincronizar anos de histórico enchia o calendário de coisas mortas e
 * demorava horas na primeira passagem.
 * ====================================================================== */
hooks()->add_action('after_cron_run', 'dps_google_cron_agenda');

function dps_google_cron_agenda()
{
    $CI = &get_instance();

    if (!$CI->db->table_exists(db_prefix() . 'dps_google_sync')) {
        $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_google_sync` (
            `tipo` VARCHAR(20) NOT NULL,
            `ref_id` INT(11) NOT NULL,
            `staff_id` INT(11) NOT NULL,
            `google_event_id` VARCHAR(191) NOT NULL,
            `impressao` VARCHAR(40) NOT NULL,
            `date_updated` DATETIME NOT NULL,
            PRIMARY KEY (`tipo`, `ref_id`, `staff_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    }

    $contas = $CI->db->select('staff_id')
                     ->where('refresh_token IS NOT NULL')
                     ->get(db_prefix() . 'dps_google_contas')->result_array();

    $desde = date('Y-m-d H:i:s', strtotime('-7 days'));

    /*
     * Eventos de agenda que nasceram de uma reunião.
     *
     * O dps_reunioes escreve um evento por participante e guarda os ids na
     * coluna 'eventos'. Como agora as reuniões entram directamente, estes
     * eventos têm de ser saltados na passagem da agenda — senão a mesma
     * reunião aparecia duas vezes no calendário.
     */
    $eventos_de_reunioes = [];
    if ($CI->db->table_exists(db_prefix() . 'dps_reunioes')) {
        foreach ($CI->db->select('eventos')->get(db_prefix() . 'dps_reunioes')->result_array() as $r) {
            foreach (explode(',', (string) $r['eventos']) as $id) {
                $id = (int) trim($id);
                if ($id) {
                    $eventos_de_reunioes[$id] = true;
                }
            }
        }
    }

    foreach ($contas as $conta) {
        $staff = (int) $conta['staff_id'];

        $itens = [];

        /* ---- Eventos da agenda ---- */
        foreach ($CI->db->select('eventid, title, description, start, end')
                        ->where('userid', $staff)
                        ->where('start >=', $desde)
                        ->get(db_prefix() . 'events')->result_array() as $e) {
            // As reuniões entram por si (ver mais abaixo). Sem esta guarda, a
            // mesma reunião ia ao calendário duas vezes: uma pelo evento que o
            // dps_reunioes escreve na agenda, outra pela reunião em si.
            if (isset($eventos_de_reunioes[(int) $e['eventid']])) {
                continue;
            }
            $itens[] = [
                'tipo'   => 'evento',
                'ref_id' => (int) $e['eventid'],
                'titulo' => trim((string) $e['title']) ?: 'Evento',
                'desc'   => trim(strip_tags((string) $e['description'])),
                'inicio' => $e['start'],
                'fim'    => !empty($e['end']) ? $e['end']
                            : date('Y-m-d H:i:s', strtotime($e['start']) + 1800),
            ];
        }

        /* ---- Lembretes por fazer ---- */
        foreach ($CI->db->select('id, description, date, rel_type, rel_id')
                        ->where('staff', $staff)
                        ->where('date >=', $desde)
                        ->where("(is_complete IS NULL OR is_complete <> '1')")
                        ->get(db_prefix() . 'reminders')->result_array() as $l) {
            $texto = trim(strip_tags((string) $l['description']));

            // A ligação de volta ao registo: um lembrete sem contexto no
            // telemóvel obriga a abrir o CRM para saber de quem é.
            $link = '';
            if ($l['rel_type'] === 'lead') {
                $link = admin_url('leads/index/' . (int) $l['rel_id']);
            } elseif ($l['rel_type'] === 'customer') {
                $link = admin_url('clients/client/' . (int) $l['rel_id']);
            }

            $itens[] = [
                'tipo'   => 'lembrete',
                'ref_id' => (int) $l['id'],
                'titulo' => '🔔 ' . (mb_substr($texto, 0, 60) ?: 'Lembrete'),
                'desc'   => $texto . ($link ? "\n\n" . $link : ''),
                'inicio' => $l['date'],
                'fim'    => date('Y-m-d H:i:s', strtotime($l['date']) + 1800),
            ];
        }

        /* ----------------------------------------------------------------
         * Reuniões
         *
         * Lidas da própria tabela e não da agenda. A ponte que põe a reunião
         * na agenda só passou a existir a 05/08/2026: as reuniões marcadas
         * antes disso não tinham entrada nenhuma, e por isso não chegavam ao
         * Google. Ler a origem cobre também esse passado e deixa de depender
         * de a ponte correr bem.
         *
         * Entra quem lá vai estar: o anfitrião, o convidado e os
         * participantes. Pedido do dono (06/08/2026).
         * ------------------------------------------------------------- */
        if ($CI->db->table_exists(db_prefix() . 'dps_reunioes')) {
            $reunioes = $CI->db->query(
                'SELECT r.* FROM ' . db_prefix() . 'dps_reunioes r
                  WHERE r.data_hora >= ?
                    AND (COALESCE(r.estado, "") NOT IN ("cancelada", "cancelado"))
                    AND (r.staff_id = ? OR r.convidado_id = ? OR EXISTS (
                          SELECT 1 FROM ' . db_prefix() . 'dps_reunioes_participante p
                           WHERE p.reuniao_id = r.id AND p.staff_id = ?))',
                [$desde, $staff, $staff, $staff]
            )->result_array();

            foreach ($reunioes as $r) {
                $titulo = trim((string) ($r['assunto'] ?? '')) ?: 'Reunião';

                $desc = [];
                if (!empty($r['link'])) {
                    $desc[] = 'Sala: ' . $r['link'];
                }
                if (!empty($r['cliente_telefone'])) {
                    $desc[] = 'Telefone: ' . $r['cliente_telefone'];
                }
                if ($r['rel_type'] === 'lead' && !empty($r['rel_id'])) {
                    $desc[] = admin_url('leads/index/' . (int) $r['rel_id']);
                }

                $itens[] = [
                    'tipo'   => 'reuniao',
                    'ref_id' => (int) $r['id'],
                    'titulo' => $titulo,
                    'desc'   => implode("\n", $desc),
                    'inicio' => $r['data_hora'],
                    'fim'    => date('Y-m-d H:i:s',
                        strtotime($r['data_hora']) + max(10, (int) ($r['duracao_min'] ?: 30)) * 60),
                ];
            }
        }

        /* ----------------------------------------------------------------
         * Tarefas por fazer, com prazo
         *
         * A tarefa tem prazo, não hora: vai como dia inteiro. Só as que ainda
         * estão por fazer — uma tarefa concluída no calendário é ruído, e são
         * às centenas.
         *
         * DE HOJE EM DIANTE, e não os 7 dias para trás das outras fontes: o
         * Cláudio tem 238 tarefas por fazer com prazo de 31/07, todas do
         * mesmo dia. Empilhá-las no calendário tornava-o ilegível e não
         * dizia nada que a lista de tarefas do CRM não diga melhor.
         * ------------------------------------------------------------- */
        foreach ($CI->db->query(
            'SELECT t.id, t.name, t.description, t.startdate, t.duedate, t.rel_type, t.rel_id
               FROM ' . db_prefix() . 'tasks t
               JOIN ' . db_prefix() . 'task_assigned a ON a.taskid = t.id AND a.staffid = ?
              WHERE t.status <> 5
                AND COALESCE(t.duedate, t.startdate) >= ?',
            [$staff, date('Y-m-d')]
        )->result_array() as $t) {
            $quando = $t['duedate'] ?: $t['startdate'];
            if (empty($quando)) {
                continue;
            }

            $link = admin_url('tasks/view/' . (int) $t['id']);
            $texto = trim(strip_tags((string) $t['description']));

            $itens[] = [
                'tipo'        => 'tarefa',
                'ref_id'      => (int) $t['id'],
                'titulo'      => mb_substr(trim((string) $t['name']) ?: 'Tarefa', 0, 120),
                'desc'        => trim($texto . "\n\n" . $link),
                'inicio'      => $quando,
                'fim'         => $quando,
                'dia_inteiro' => true,
            ];
        }

        /* ---- Criar ou actualizar ---- */
        $vivos = [];
        foreach ($itens as $it) {
            $chave = $it['tipo'] . ':' . $it['ref_id'];
            $vivos[$chave] = true;

            $impressao = sha1($it['titulo'] . '|' . $it['desc'] . '|' . $it['inicio'] . '|' . $it['fim']
                . '|' . (!empty($it['dia_inteiro']) ? 'D' : 'H'));

            $mapa = $CI->db->where(['tipo' => $it['tipo'], 'ref_id' => $it['ref_id'], 'staff_id' => $staff])
                           ->get(db_prefix() . 'dps_google_sync')->row_array();

            if ($mapa && $mapa['impressao'] === $impressao) {
                continue;                       // não mudou nada: não se toca
            }

            $ev_id = dps_google_evento_guardar($staff, [
                'titulo'      => $it['titulo'],
                'descricao'   => $it['desc'],
                'inicio'      => $it['inicio'],
                'fim'         => $it['fim'],
                'dia_inteiro' => !empty($it['dia_inteiro']),
            ], $mapa['google_event_id'] ?? null);

            if (!$ev_id) {
                continue;                       // já ficou registado no log
            }

            $linha = [
                'tipo' => $it['tipo'], 'ref_id' => $it['ref_id'], 'staff_id' => $staff,
                'google_event_id' => $ev_id, 'impressao' => $impressao,
                'date_updated' => date('Y-m-d H:i:s'),
            ];

            /*
             * Gravado numa só instrução, em vez de decidir entre INSERT e
             * UPDATE pelo que se leu antes.
             *
             * Duas passagens do cron a apanharem-se uma à outra liam ambas
             * "não existe" e tentavam ambas inserir: a segunda rebentava com
             * Duplicate entry 'reuniao-3-1' e a excepção abortava o resto da
             * sincronização dessa volta. Aconteceu a 06/08/2026 às 11:40.
             */
            $CI->db->query(
                'INSERT INTO ' . db_prefix() . 'dps_google_sync
                    (tipo, ref_id, staff_id, google_event_id, impressao, date_updated)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    google_event_id = VALUES(google_event_id),
                    impressao       = VALUES(impressao),
                    date_updated    = VALUES(date_updated)',
                [$linha['tipo'], $linha['ref_id'], $linha['staff_id'],
                 $linha['google_event_id'], $linha['impressao'], $linha['date_updated']]
            );
        }

        /*
         * ---- Apagar o que deixou de existir ----
         *
         * Um lembrete concluído ou um evento apagado no CRM tem de sair do
         * calendário. Sem isto, o Google ficava com fantasmas que ninguém
         * conseguia tirar de lá senão à mão.
         */
        foreach ($CI->db->where('staff_id', $staff)
                        ->get(db_prefix() . 'dps_google_sync')->result_array() as $m) {
            if (isset($vivos[$m['tipo'] . ':' . $m['ref_id']])) {
                continue;
            }
            dps_google_evento_apagar($staff, $m['google_event_id']);
            $CI->db->where(['tipo' => $m['tipo'], 'ref_id' => $m['ref_id'], 'staff_id' => $staff])
                   ->delete(db_prefix() . 'dps_google_sync');
        }
    }
}
