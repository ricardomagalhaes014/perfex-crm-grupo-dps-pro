<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cliente da API Moloni v1.
 *
 * Trata de:
 *  - autenticacao por password grant
 *  - refresh automatico do access token (validade 1h) usando o refresh token (validade 14 dias)
 *  - persistencia dos tokens na tabela do modulo
 *  - chamadas genericas a qualquer classe/metodo da API
 *
 * Docs: https://www.moloni.pt/dev/autenticacao/ e https://www.moloni.pt/dev/endpoints/
 */
class Moloni_api
{
    const BASE_URL = 'https://api.moloni.pt/v1/';

    /** Margem de seguranca (segundos) para renovar o token antes de expirar. */
    const EXPIRY_MARGIN = 120;

    protected $ci;

    /** @var array credenciais e tokens em memoria */
    protected $cfg = [];

    /** @var string ultima mensagem de erro */
    protected $last_error = '';

    /** @var array ultima resposta crua (para debug/log) */
    protected $last_raw = null;

    /** @var bool evita loops infinitos de refresh */
    protected $refreshing = false;

    public function __construct($params = [])
    {
        $this->ci = &get_instance();
        $this->ci->load->model('dps_moloni/dps_moloni_model');
        $this->reload_config();

        if (!empty($params)) {
            $this->cfg = array_merge($this->cfg, $params);
        }
    }

    /**
     * Recarrega credenciais e tokens a partir da base de dados.
     */
    public function reload_config()
    {
        $this->cfg = $this->ci->dps_moloni_model->get_settings();
    }

    // ---------------------------------------------------------------- estado

    public function last_error()
    {
        return $this->last_error;
    }

    public function last_raw()
    {
        return $this->last_raw;
    }

    /**
     * Ha credenciais suficientes para tentar autenticar?
     */
    public function is_configured()
    {
        return !empty($this->cfg['dev_id'])
            && !empty($this->cfg['client_secret'])
            && !empty($this->cfg['username'])
            && !empty($this->cfg['password']);
    }

    public function company_id()
    {
        return !empty($this->cfg['company_id']) ? (int) $this->cfg['company_id'] : 0;
    }

    // -------------------------------------------------------- autenticacao

    /**
     * Devolve um access token valido, renovando-o se necessario.
     *
     * @return string|false
     */
    public function access_token()
    {
        $token   = isset($this->cfg['access_token']) ? $this->cfg['access_token'] : '';
        $expires = isset($this->cfg['token_expires']) ? (int) $this->cfg['token_expires'] : 0;

        if ($token && $expires > (time() + self::EXPIRY_MARGIN)) {
            return $token;
        }

        // Token expirado ou inexistente: tentar refresh, depois login completo.
        if (!empty($this->cfg['refresh_token']) && $this->refresh()) {
            return $this->cfg['access_token'];
        }

        if ($this->login()) {
            return $this->cfg['access_token'];
        }

        return false;
    }

    /**
     * Autenticacao inicial (grant_type=password).
     */
    public function login()
    {
        if (!$this->is_configured()) {
            $this->last_error = _l('dps_moloni_err_no_credentials');

            return false;
        }

        $params = [
            'grant_type'    => 'password',
            'client_id'     => $this->cfg['dev_id'],
            'client_secret' => $this->cfg['client_secret'],
            'username'      => $this->cfg['username'],
            'password'      => $this->cfg['password'],
        ];

        $res = $this->grant($params);

        $ok = $this->store_tokens($res, 'login');

        // A autenticacao e exactamente o que se quer ver no registo quando
        // falha. As credenciais sao mascaradas pelo modelo.
        $this->ci->dps_moloni_model->log(
            'grant/password',
            $this->debug_params($params),
            $ok ? ['access_token' => '***', 'ok' => true] : $res,
            $ok ? 'ok' : 'error',
            $ok ? '' : $this->last_error
        );

        return $ok;
    }

    /**
     * Renovacao do token (grant_type=refresh_token).
     */
    public function refresh()
    {
        if (empty($this->cfg['refresh_token'])) {
            return false;
        }

        $this->refreshing = true;

        $res = $this->grant([
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->cfg['dev_id'],
            'client_secret' => $this->cfg['client_secret'],
            'refresh_token' => $this->cfg['refresh_token'],
        ]);

        $ok = $this->store_tokens($res, 'refresh');

        $this->refreshing = false;

        return $ok;
    }

    /**
     * Chamada ao endpoint /grant/.
     *
     * A Moloni le estes parametros da query string — um POST com o mesmo
     * conteudo no corpo responde "Missing parameters". Como a credencial vai
     * no URL, esta e a unica chamada em que o URL nunca e registado nem
     * devolvido em mensagens de erro.
     *
     * @return array|false
     */
    protected function grant($params)
    {
        $url = self::BASE_URL . 'grant/?' . $this->build_query($params);

        return $this->raw_request($url, 'GET', null);
    }

    /**
     * Versao dos parametros segura para registo: em vez do valor, o
     * comprimento e os primeiros caracteres. Chega para perceber se um campo
     * foi enviado vazio ou com lixo, sem revelar a credencial.
     */
    protected function debug_params($params)
    {
        $out = [];

        foreach ($params as $key => $value) {
            if (in_array($key, ['client_secret', 'password', 'refresh_token'], true)) {
                $length     = mb_strlen((string) $value);
                $out[$key]  = $length === 0
                    ? '(vazio)'
                    : '(' . $length . ' caracteres, comeca por "' . mb_substr((string) $value, 0, 2) . '")';
                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * Guarda os tokens devolvidos pelo endpoint /grant/.
     */
    protected function store_tokens($res, $context)
    {
        if (!is_array($res) || empty($res['access_token'])) {
            $this->last_error = $this->extract_error($res, $context);

            // Um refresh token invalido nao serve para mais nada: limpa-se
            // para que a proxima chamada faca login completo.
            if ($context === 'refresh') {
                $this->ci->dps_moloni_model->save_tokens([
                    'access_token'  => '',
                    'refresh_token' => '',
                    'token_expires' => 0,
                ]);
                $this->cfg['access_token']  = '';
                $this->cfg['refresh_token'] = '';
                $this->cfg['token_expires'] = 0;
            }

            return false;
        }

        $expires_in = isset($res['expires_in']) ? (int) $res['expires_in'] : 3600;

        $tokens = [
            'access_token'  => $res['access_token'],
            'refresh_token' => isset($res['refresh_token']) ? $res['refresh_token'] : '',
            'token_expires' => time() + $expires_in,
        ];

        $this->ci->dps_moloni_model->save_tokens($tokens);
        $this->cfg = array_merge($this->cfg, $tokens);

        return true;
    }

    // ------------------------------------------------------------- chamadas

    /**
     * Chamada generica: $api->call('customers', 'getAll', ['qty' => 50]).
     *
     * O company_id e injectado automaticamente quando existe em definicoes,
     * excepto para as classes que nao o aceitam (companies).
     *
     * @return array|false
     */
    public function call($class, $method, $params = [], $inject_company = true)
    {
        $token = $this->access_token();
        if (!$token) {
            return false;
        }

        if ($inject_company && !isset($params['company_id']) && $this->company_id()) {
            $params['company_id'] = $this->company_id();
        }

        $url = self::BASE_URL . rawurlencode($class) . '/' . rawurlencode($method) . '/?access_token=' . urlencode($token);

        $res = $this->raw_post($url, $params);

        // Token invalidado do lado da Moloni: tentar uma vez renovar e repetir.
        if ($this->is_invalid_token($res) && !$this->refreshing) {
            if ($this->refresh() || $this->login()) {
                $url = self::BASE_URL . rawurlencode($class) . '/' . rawurlencode($method)
                     . '/?access_token=' . urlencode($this->cfg['access_token']);
                $res = $this->raw_post($url, $params);
            }
        }

        if ($this->is_error($res)) {
            $this->last_error = $this->extract_error($res, $class . '/' . $method);
            $this->ci->dps_moloni_model->log($class . '/' . $method, $params, $res, 'error', $this->last_error);

            return false;
        }

        $this->ci->dps_moloni_model->log($class . '/' . $method, $params, $res, 'ok', '');

        return $res;
    }

    /**
     * POST form-encoded com suporte a arrays aninhados (products[0][name]=...).
     */
    protected function raw_post($url, $params)
    {
        return $this->raw_request($url, 'POST', $this->build_query($params));
    }

    /**
     * Pedido HTTP cru. $body a null faz um GET.
     */
    protected function raw_request($url, $method, $body)
    {
        $ch = curl_init();

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
            ],
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST]       = true;
            $options[CURLOPT_POSTFIELDS] = $body;
            $options[CURLOPT_HTTPHEADER] = [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ];
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            $this->last_error = 'cURL (' . $errno . '): ' . $err;
            $this->last_raw   = null;

            return false;
        }

        $this->last_raw = $response;

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->last_error = _l('dps_moloni_err_bad_json') . ' ' . substr((string) $response, 0, 300);

            return false;
        }

        return $decoded;
    }

    /**
     * http_build_query serve, mas normaliza booleanos e nulos de forma
     * que a Moloni nem sempre aceita. Fazemos a conversao explicita.
     */
    protected function build_query($params)
    {
        $normalize = function ($value) use (&$normalize) {
            if (is_bool($value)) {
                return $value ? 1 : 0;
            }
            if (is_null($value)) {
                return '';
            }
            if (is_array($value)) {
                return array_map($normalize, $value);
            }

            return $value;
        };

        return http_build_query($normalize($params), '', '&', PHP_QUERY_RFC3986);
    }

    // --------------------------------------------------------------- erros

    protected function is_error($res)
    {
        if ($res === false) {
            return true;
        }

        return is_array($res) && (isset($res['error']) || isset($res['errors']));
    }

    protected function is_invalid_token($res)
    {
        if (!is_array($res) || !isset($res['error'])) {
            return false;
        }

        $error = is_string($res['error']) ? $res['error'] : '';

        return in_array($error, ['invalid_token', 'invalid_grant', 'access_denied'], true);
    }

    protected function extract_error($res, $context)
    {
        if ($res === false) {
            return $this->last_error ?: ('Sem resposta da Moloni (' . $context . ')');
        }

        if (is_array($res)) {
            if (isset($res['error_description']) && is_string($res['error_description'])) {
                return $res['error_description'];
            }
            if (isset($res['error']) && is_string($res['error'])) {
                return $res['error'];
            }
            if (isset($res['errors'])) {
                return is_string($res['errors']) ? $res['errors'] : json_encode($res['errors']);
            }
        }

        return 'Erro desconhecido (' . $context . ')';
    }

    // ------------------------------------------------- atalhos mais usados

    /**
     * Empresas da conta. Nao leva company_id.
     */
    public function companies()
    {
        return $this->call('companies', 'getAll', [], false);
    }

    public function company_detail($company_id = null)
    {
        return $this->call('companies', 'getOne', [
            'company_id' => $company_id ?: $this->company_id(),
        ], false);
    }

    public function document_sets()
    {
        return $this->call('documentSets', 'getAll');
    }

    public function taxes()
    {
        return $this->call('taxes', 'getAll');
    }

    public function payment_methods()
    {
        return $this->call('paymentMethods', 'getAll');
    }

    public function products($search = '', $qty = 50, $offset = 0)
    {
        if ($search !== '') {
            return $this->call('products', 'getBySearch', [
                'search' => $search,
                'qty'    => $qty,
                'offset' => $offset,
            ]);
        }

        return $this->call('products', 'getAll', ['qty' => $qty, 'offset' => $offset]);
    }

    public function product_categories()
    {
        return $this->call('productCategories', 'getAll');
    }

    /**
     * Procura de cliente por NIF. A Moloni tem getByVat, mas devolve erro
     * quando nao ha resultados: normalizamos para array vazio.
     */
    public function customer_by_vat($vat)
    {
        $vat = trim((string) $vat);
        if ($vat === '') {
            return [];
        }

        $res = $this->call('customers', 'getByVat', ['vat' => $vat]);

        return is_array($res) ? $res : [];
    }

    public function customer_by_search($search)
    {
        $res = $this->call('customers', 'getBySearch', ['search' => $search, 'qty' => 50]);

        return is_array($res) ? $res : [];
    }

    public function customer_insert($data)
    {
        return $this->call('customers', 'insert', $data);
    }

    public function customer_update($data)
    {
        return $this->call('customers', 'update', $data);
    }

    /**
     * Proximo numero de cliente (a Moloni exige 'number' no insert).
     */
    public function next_customer_number()
    {
        $res = $this->call('customers', 'getNextNumber');

        if (is_array($res) && isset($res['number'])) {
            return $res['number'];
        }

        return null;
    }

    /**
     * Documentos emitidos, com filtros opcionais.
     * A API limita qty a 50 por chamada: fazemos paginacao ate ao limite pedido.
     *
     * Devolve false quando a primeira pagina falha, para que quem chama
     * consiga distinguir "erro na API" de "nao ha documentos".
     *
     * @return array|false
     */
    public function documents($filters = [], $max = 200)
    {
        $out    = [];
        $offset = 0;

        while (count($out) < $max) {
            $params = array_merge($filters, ['qty' => 50, 'offset' => $offset]);
            $page   = $this->call('documents', 'getAll', $params);

            if ($page === false) {
                // Falha logo a primeira pagina: e erro, nao lista vazia.
                return $offset === 0 ? false : array_slice($out, 0, $max);
            }

            if (!is_array($page) || empty($page)) {
                break;
            }

            $out = array_merge($out, $page);

            if (count($page) < 50) {
                break;
            }

            $offset += 50;
        }

        return array_slice($out, 0, $max);
    }

    /**
     * Documentos num intervalo de datas.
     *
     * A API nao aceita intervalos (so uma data exacta), por isso paginamos e
     * paramos assim que uma pagina inteira fica anterior a $from. O limite de
     * paginas evita varrer a conta toda quando a ordenacao nao ajuda.
     *
     * @return array|false
     */
    public function documents_between($from, $to, $max_pages = 40)
    {
        $out    = [];
        $offset = 0;
        $pages  = 0;

        while ($pages < $max_pages) {
            $page = $this->call('documents', 'getAll', ['qty' => 50, 'offset' => $offset]);

            if ($page === false) {
                return $pages === 0 ? false : $out;
            }

            if (!is_array($page) || empty($page)) {
                break;
            }

            $all_older = true;

            foreach ($page as $doc) {
                $date = isset($doc['date']) ? substr($doc['date'], 0, 10) : '';

                if ($date === '') {
                    $all_older = false;
                    continue;
                }

                if ($date >= $from) {
                    $all_older = false;
                }

                if ($date >= $from && $date <= $to) {
                    $out[] = $doc;
                }
            }

            // Pagina inteira anterior ao inicio do intervalo: com a ordenacao
            // habitual (mais recentes primeiro) o resto tambem sera.
            if ($all_older) {
                break;
            }

            if (count($page) < 50) {
                break;
            }

            $offset += 50;
            $pages++;
        }

        return $out;
    }

    public function document_one($document_id)
    {
        return $this->call('documents', 'getOne', ['document_id' => (int) $document_id]);
    }

    public function document_pdf_link($document_id)
    {
        $res = $this->call('documents', 'getPDFLink', ['document_id' => (int) $document_id]);

        if (is_array($res) && isset($res['url'])) {
            return $res['url'];
        }

        return null;
    }

    /**
     * Cria um documento. $class e a classe Moloni do tipo de documento
     * (invoices, receipts, invoiceReceipts, simplifiedInvoices, ...).
     */
    public function document_insert($class, $data)
    {
        return $this->call($class, 'insert', $data);
    }
}
