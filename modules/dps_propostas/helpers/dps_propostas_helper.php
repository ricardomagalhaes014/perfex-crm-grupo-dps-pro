<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Configuração dos empreendimentos (dados vivem no simuladorportugal / dpsimobiliario.pt).
 */
function dps_propostas_empreendimentos()
{
    return [
        'boavista' => [
            'nome'         => 'Boavista Towers',
            'states_key'   => 'boavista_states',
            'site'         => 'https://dpsimobiliario.pt/boavistatowers/',
            'dossier'      => 'https://dpsimobiliario.pt/boavistatowers/dossier-boavista-tower.pdf',
            'tem_proposta' => true,
        ],
        'raizes' => [
            'nome' => 'Raizes', 'states_key' => 'raizes_states',
            'site' => 'https://dpsimobiliario.pt/raizes/', 'dossier' => null, 'tem_proposta' => true,
        ],
        'belohorizonte' => [
            'nome' => 'Belo Horizonte', 'states_key' => 'bh_states',
            'site' => 'https://dpsimobiliario.pt/belohorizonte/', 'dossier' => null, 'tem_proposta' => true,
        ],
        'lake' => [
            'nome' => 'Lake Towers', 'states_key' => 'lake_states',
            'site' => 'https://dpsimobiliario.pt/', 'dossier' => null, 'tem_proposta' => false,
        ],
    ];
}

/**
 * Catálogo de unidades (fraccao => {tipologia, area, preco}), extraído do simulador.
 */
function dps_propostas_units()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $path = module_dir_path(DPS_PROPOSTAS_MODULE_NAME, 'units.json');
    $cache = is_file($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
    return $cache;
}

/* ---------------- Evolution API (reutiliza opções do dps_whatsapp) ---------------- */

function dps_propostas_evo_url()
{
    return rtrim((string) get_option('dps_whatsapp_evolution_url'), '/');
}
function dps_propostas_evo_key()
{
    return get_option('dps_whatsapp_evolution_api_key');
}
function dps_propostas_instance($staff_id)
{
    return 'staff-' . (int) $staff_id;
}

function dps_propostas_evo_request($method, $path, $body = null, $timeout = 20)
{
    $url = dps_propostas_evo_url();
    $key = dps_propostas_evo_key();
    if (empty($url) || empty($key)) {
        return ['ok' => false, 'http' => 0, 'error' => 'Evolution nao configurada'];
    }
    $ch = curl_init($url . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $key],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['ok' => ($http >= 200 && $http < 300), 'http' => $http, 'error' => $err, 'raw' => $raw];
}

function dps_propostas_send_text($staff_id, $number, $text)
{
    return dps_propostas_evo_request('POST', '/message/sendText/' . dps_propostas_instance($staff_id), [
        'number'      => $number,
        'textMessage' => ['text' => $text],
    ]);
}

function dps_propostas_send_document($staff_id, $number, $url, $filename, $caption = '')
{
    return dps_propostas_evo_request('POST', '/message/sendMedia/' . dps_propostas_instance($staff_id), [
        'number'       => $number,
        'mediaMessage' => [
            'mediatype' => 'document',
            'fileName'  => $filename,
            'media'     => $url,
            'caption'   => $caption,
        ],
    ], 60);
}

/**
 * Envia um documento em base64 (ex.: PDF gerado no momento).
 */
function dps_propostas_send_document_b64($staff_id, $number, $b64, $filename, $caption = '')
{
    return dps_propostas_evo_request('POST', '/message/sendMedia/' . dps_propostas_instance($staff_id), [
        'number'       => $number,
        'mediaMessage' => [
            'mediatype' => 'document',
            'fileName'  => $filename,
            'media'     => $b64,
            'caption'   => $caption,
        ],
    ], 60);
}

/**
 * Gera (ao vivo) o PDF da tabela de unidades DISPONÍVEIS e devolve em base64.
 */
function dps_propostas_gerar_pdf_disponiveis($emp_nome, $unidades)
{
    include_once module_dir_path(DPS_PROPOSTAS_MODULE_NAME, 'libraries/Dps_disponiveis_pdf.php');

    $rows = '';
    foreach ($unidades as $u) {
        $area  = ($u['area'] !== null && $u['area'] !== '')
            ? rtrim(rtrim(number_format((float) $u['area'], 1, ',', '.'), '0'), ',') . ' m²'
            : '—';
        $preco = ($u['preco'] > 0) ? number_format($u['preco'], 0, ',', '.') . ' €' : '—';
        $rows .= '<tr><td>' . htmlspecialchars($u['fraccao']) . '</td>'
            . '<td>' . htmlspecialchars((string) $u['tipologia']) . '</td>'
            . '<td align="right">' . $area . '</td>'
            . '<td align="right">' . $preco . '</td></tr>';
    }

    $html = '<h2>Unidades Disponíveis — ' . htmlspecialchars($emp_nome) . '</h2>'
        . '<p>' . count($unidades) . ' unidades · ' . date('d/m/Y H:i') . '</p>'
        . '<table border="1" cellpadding="4" cellspacing="0">'
        . '<thead><tr style="background-color:#f0f0f0;font-weight:bold;">'
        . '<th>Fração</th><th>Tipologia</th><th>Área</th><th>Preço</th></tr></thead>'
        . '<tbody>' . $rows . '</tbody></table>';

    $pdf = (new Dps_disponiveis_pdf('Unidades Disponiveis - ' . $emp_nome, $html))->prepare();
    return base64_encode($pdf->Output('disponiveis.pdf', 'S'));
}

/**
 * Lê a disponibilidade ao vivo do simulador (save_states.php).
 * Devolve ['count'=>int, 'codes'=>[...]] para a chave dada.
 */
function dps_propostas_disponibilidade($slug)
{
    $emps = dps_propostas_empreendimentos();
    if (! isset($emps[$slug])) {
        return ['ok' => false, 'count' => 0, 'por_tipologia' => [], 'codes' => []];
    }
    $states_key = $emps[$slug]['states_key'];

    $ch = curl_init('https://dpsimobiliario.pt/simuladorportugal/save_states.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 12,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    $data = json_decode((string) $raw, true);
    if (! is_array($data) || ! isset($data[$states_key]) || ! is_array($data[$states_key])) {
        return ['ok' => false, 'count' => 0, 'por_tipologia' => [], 'codes' => []];
    }

    $units = dps_propostas_units();
    $cat   = isset($units[$slug]) ? $units[$slug] : [];

    $codes    = [];
    $byTipo   = [];
    $unidades = [];
    foreach ($data[$states_key] as $code => $estado) {
        if (trim((string) $estado) !== 'Disponível') {
            continue;
        }
        $codes[] = $code;
        $u     = isset($cat[$code]) ? $cat[$code] : null;
        $tip   = ($u && ! empty($u['tipologia'])) ? $u['tipologia'] : 'Outros';
        $area  = ($u && isset($u['area'])) ? $u['area'] : null;
        $preco = ($u && isset($u['preco'])) ? (float) $u['preco'] : 0;
        if (! isset($byTipo[$tip])) {
            $byTipo[$tip] = ['tipologia' => $tip, 'n' => 0, 'min' => null];
        }
        $byTipo[$tip]['n']++;
        if ($preco > 0 && ($byTipo[$tip]['min'] === null || $preco < $byTipo[$tip]['min'])) {
            $byTipo[$tip]['min'] = $preco;
        }
        $unidades[] = ['fraccao' => $code, 'tipologia' => $tip, 'area' => $area, 'preco' => $preco];
    }
    ksort($byTipo);
    // Ordenar unidades por tipologia e depois por preço.
    usort($unidades, function ($a, $b) {
        return [$a['tipologia'], $a['preco']] <=> [$b['tipologia'], $b['preco']];
    });

    return ['ok' => true, 'count' => count($codes), 'por_tipologia' => array_values($byTipo), 'codes' => $codes, 'unidades' => $unidades];
}

/**
 * Instância do comercial está ligada?
 */
function dps_propostas_staff_connected($staff_id)
{
    $CI  = &get_instance();
    $cfg = $CI->db->select('is_connected')->where('staff_id', (int) $staff_id)
        ->get(db_prefix() . 'dps_whatsapp_config')->row();
    return $cfg && (int) $cfg->is_connected === 1;
}

/**
 * Renderiza a aba "Propostas" na ficha da lead (hook after_lead_tabs_content).
 */
function dps_propostas_render_lead_tab($lead)
{
    if (empty($lead) || ! is_staff_member()) {
        return;
    }
    $CI   = &get_instance();
    $rows = $CI->db->where('lead_id', (int) $lead->id)->order_by('id', 'DESC')
        ->get(db_prefix() . 'dps_propostas')->result();

    $staff_id = get_staff_user_id();
    $CI->load->view('dps_propostas/lead_tab', [
        'lead'     => $lead,
        'emps'     => dps_propostas_empreendimentos(),
        'rows'     => $rows,
        'staff_id' => $staff_id,
        'token'    => dps_propostas_proposta_token($lead->id, $staff_id),
    ]);
}

/**
 * Token HMAC para autenticar o callback da proposta vindo do simulador.
 * Segredo em ficheiro fora do repositório.
 */
function dps_propostas_proposta_token($lead_id, $staff_id)
{
    $secret = @file_get_contents('/home/u172337921/.dps_proposta_secret');
    $secret = $secret !== false ? trim($secret) : '';
    if ($secret === '') {
        return '';
    }
    return hash_hmac('sha256', (int) $lead_id . '|' . (int) $staff_id . '|' . date('Y-m-d'), $secret);
}
