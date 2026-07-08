<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Configuração dos empreendimentos (dados vivem no simuladorportugal / dpsimobiliario.pt).
 */
function dps_propostas_empreendimentos()
{
    return [
        'boavista' => [
            'nome'       => 'Boavista Towers',
            'states_key' => 'boavista_states',
            'site'       => 'https://dpsimobiliario.pt/boavistatowers/',
            'dossier'    => 'https://dpsimobiliario.pt/boavistatowers/dossier-boavista-tower.pdf',
            'anexos'     => [
                ['url' => 'https://dpsimobiliario.pt/boavistatowers/tabela-fraccoes-bloco1.pdf', 'nome' => 'Tabela Fracoes Bloco 1.pdf'],
                ['url' => 'https://dpsimobiliario.pt/boavistatowers/tabela-fraccoes-bloco2.pdf', 'nome' => 'Tabela Fracoes Bloco 2.pdf'],
            ],
        ],
        'raizes' => [
            'nome' => 'Raizes', 'states_key' => 'raizes_states',
            'site' => 'https://dpsimobiliario.pt/raizes/', 'dossier' => null, 'anexos' => [],
        ],
        'belohorizonte' => [
            'nome' => 'Belo Horizonte', 'states_key' => 'bh_states',
            'site' => 'https://dpsimobiliario.pt/belohorizonte/', 'dossier' => null, 'anexos' => [],
        ],
        'lake' => [
            'nome' => 'Lake Towers', 'states_key' => 'lake_states',
            'site' => 'https://dpsimobiliario.pt/', 'dossier' => null, 'anexos' => [],
        ],
    ];
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
 * Lê a disponibilidade ao vivo do simulador (save_states.php).
 * Devolve ['count'=>int, 'codes'=>[...]] para a chave dada.
 */
function dps_propostas_disponibilidade($states_key)
{
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
        return ['count' => 0, 'codes' => [], 'ok' => false];
    }
    $codes = [];
    foreach ($data[$states_key] as $code => $estado) {
        if (trim((string) $estado) === 'Disponível') {
            $codes[] = $code;
        }
    }
    return ['count' => count($codes), 'codes' => $codes, 'ok' => true];
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

    $CI->load->view('dps_propostas/lead_tab', [
        'lead' => $lead,
        'emps' => dps_propostas_empreendimentos(),
        'rows' => $rows,
    ]);
}
