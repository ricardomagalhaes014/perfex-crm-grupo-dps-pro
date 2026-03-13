<?php
/**
 * DPS – Sync Perfex → WordPress
 *
 * Executa via CLI/Cron:
 *   php /caminho/para/sync_perfex.php
 *
 * Requisitos:
 *   - PHP 7.4+ com cURL habilitado
 *
 * O QUE VOCÊ PRECISA EDITAR:
 *   1) PERFEX_API_BASE        -> base do seu Perfex (ex.: https://crm.grupo-dps.com)
 *   2) PERFEX_PROPERTIES_PATH -> caminho de listagem de imóveis (palpite: /api/properties)
 *   3) PERFEX_API_TOKEN       -> token do Perfex
 *   4) WP_ENDPOINT_URL        -> endpoint receptor no WordPress
 *   5) LOG_FILE               -> caminho do arquivo de log
 */

const PERFEX_API_BASE        = 'https://crm.grupo-dps.com';
const PERFEX_PROPERTIES_PATH = '/api/properties'; // <-- AJUSTE se o seu endpoint for outro (/api/imoveis etc.)
const PERFEX_API_TOKEN       = 'dLEQMcmfLHUOYXyM8CaR3i4jO4zZ3ybQ'; // <-- INFORME o token correto

const WP_ENDPOINT_URL        = 'https://dpsimobiliario.grupo-dps.com/wp-json/imoveis/v1/receber';

const LOG_FILE               = __DIR__ . '/sync_perfex.log';

// Opções
const PAGE_SIZE              = 100; // se o endpoint suportar paginação
const DRY_RUN                = false; // true = apenas loga, não envia ao WordPress

// ---------------------------------------

date_default_timezone_set('UTC');

function log_line($msg, $context = []) {
    $line = '[' . date('c') . '] ' . $msg;
    if (!empty($context)) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
    $line .= PHP_EOL;
    @file_put_contents(LOG_FILE, $line, FILE_APPEND);
    echo $line;
}

function http_get_json($url, $headers = [], $timeout = 30) {
    $ch = curl_init();
    $hdr = array_merge([
        'Accept: application/json',
        'authtoken: ' . PERFEX_API_TOKEN, // <-- se o Perfex usa outro header (Authorization: Bearer XXX), ajuste aqui
    ], $headers);

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => $hdr,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $body   = curl_exec($ch);
    $errno  = curl_errno($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return [null, $status, "cURL error: $err ($errno)"];
    }

    $json = json_decode($body, true);
    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
        return [null, $status, 'JSON decode error: ' . json_last_error_msg() . ' | body: ' . substr($body, 0, 500)];
    }

    return [$json, $status, null];
}

function http_post_json($url, $payload, $headers = [], $timeout = 30) {
    $ch = curl_init();

    $hdr = array_merge([
        'Content-Type: application/json',
        'Accept: application/json',
    ], $headers);

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => $hdr,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $body   = curl_exec($ch);
    $errno  = curl_errno($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return [null, $status, "cURL error: $err ($errno)"];
    }

    $json = json_decode($body, true);
    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
        return [null, $status, 'JSON decode error: ' . json_last_error_msg() . ' | body: ' . substr($body, 0, 500)];
    }

    return [$json, $status, null];
}

/**
 * Mapeia/normaliza um registro vindo do Perfex para o formato do endpoint WP.
 * Ajuste os nomes dos campos conforme a resposta real do seu Perfex.
 */
function map_perfex_to_wp(array $p) {
    // Exemplos de nomes comuns; ajuste conforme sua API real
    $id        = $p['id']            ?? $p['property_id'] ?? $p['codigo'] ?? null;
    $nome      = $p['name']          ?? $p['nome']        ?? $p['titulo'] ?? '';
    $descricao = $p['description']   ?? $p['descricao']   ?? '';
    $ativo     = $p['active']        ?? $p['ativo']       ?? $p['status'] ?? '0';
    $cidade    = $p['city']          ?? $p['cidade']      ?? '';
    $preco     = $p['sale_price']    ?? $p['preco_venda'] ?? $p['preco']  ?? '';
    $quartos   = $p['bedrooms']      ?? $p['quartos']     ?? '';
    $estilo    = $p['property_style']?? $p['estilo_propriedade'] ?? $p['tipologia'] ?? '';
    $imagem    = $p['image']         ?? $p['imagem']      ?? '';

    // Normalização do "ativo" aceito no plugin
    $ativo_val = in_array(strtolower((string)$ativo), ['1','true','yes','sim','on','active','enabled','ativo','activo'], true) ? '1' : '0';

    return [
        'id'                  => (string)$id,    // obrigatório pelo plugin
        'nome'                => (string)$nome,
        'descricao'           => (string)$descricao,
        'ativo'               => $ativo_val,
        'cidade'              => (string)$cidade,
        'preco_venda'         => (string)$preco,
        'quartos'             => (string)$quartos,
        'estilo_propriedade'  => (string)$estilo,
        // se tiver uma URL absoluta de imagem, mande aqui:
        'imagem'              => (string)$imagem,
    ];
}

function build_perfex_url($page = 1, $per_page = PAGE_SIZE) {
    // Se sua API não tiver paginação, retorne apenas a URL base.
    // Se tiver, ajuste os nomes dos parâmetros de paginação (page/per_page, start/limit etc.)
    $base = rtrim(PERFEX_API_BASE, '/');
    $path = '/' . ltrim(PERFEX_PROPERTIES_PATH, '/');
    $qs   = http_build_query(['page' => $page, 'per_page' => $per_page]);
    return $base . $path . '?' . $qs;
}

function sync_all() {
    log_line('==== INÍCIO SYNC PERFEX → WP ====');

    $page      = 1;
    $totalSent = 0;
    $totalErr  = 0;

    while (true) {
        $url = build_perfex_url($page);
        log_line('GET Perfex', ['url' => $url]);

        list($data, $status, $err) = http_get_json($url);

        if ($err) {
            log_line('ERRO GET Perfex', ['status' => $status, 'err' => $err]);
            break;
        }

        if ($status >= 400) {
            log_line('ERRO HTTP Perfex', ['status' => $status, 'body' => $data]);
            break;
        }

        // Dependendo da sua API, $data pode ser:
        // - um array de imóveis diretamente
        // - um objeto com chave 'data' ou 'items'
        $items = [];
        if (is_array($data)) {
            // Se vier diretamente um array de registros
            $items = $data;
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $items = $data['data'];
        } elseif (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        }

        if (empty($items)) {
            log_line('Nenhum item retornado/última página', ['page' => $page]);
            break;
        }

        foreach ($items as $idx => $raw) {
            $mapped = map_perfex_to_wp($raw);

            // Validação mínima
            if (empty($mapped['id'])) {
                $totalErr++;
                log_line('SKIP sem ID', ['raw' => $raw]);
                continue;
            }

            if (DRY_RUN) {
                log_line('DRY-RUN -> NÃO ENVIADO', ['mapped' => $mapped]);
                $totalSent++;
                continue;
            }

            list($resp, $wstatus, $werr) = http_post_json(WP_ENDPOINT_URL, $mapped);

            if ($werr || $wstatus >= 400) {
                $totalErr++;
                log_line('ERRO POST WP', ['status' => $wstatus, 'err' => $werr, 'resp' => $resp, 'payload' => $mapped]);
            } else {
                $totalSent++;
                log_line('OK POST WP', ['post_id' => $resp['post_id'] ?? null, 'status_wp' => $resp['status_wp'] ?? null, 'id' => $mapped['id']]);
            }

            // opcional: dormir um pouco para não estressar o WP/CDN
            usleep(120000); // 120ms
        }

        // Se a sua API indicar “last_page” etc. ajuste abaixo
        if (count($items) < PAGE_SIZE) {
            log_line('Fim por página curta', ['page' => $page, 'count' => count($items)]);
            break;
        }

        $page++;
    }

    log_line('==== FIM SYNC ====', ['enviados' => $totalSent, 'erros' => $totalErr]);
}

// Execução
//if (php_sapi_name() !== 'cli') {
//    header('Content-Type: text/plain; charset=UTF-8');
//    echo "Execute via CLI.\n";
//    exit;
}//

sync_all();