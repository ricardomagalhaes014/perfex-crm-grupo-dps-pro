<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_moloni_model extends App_Model
{
    /** Campos que sao guardados cifrados. */
    protected $secret_keys = ['client_secret', 'password', 'access_token', 'refresh_token'];

    /** Valores por omissao das definicoes. */
    protected $defaults = [
        'dev_id'                 => '',
        'client_secret'          => '',
        'username'               => '',
        'password'               => '',
        'company_id'             => '',
        'company_name'           => '',
        // series de documentos
        'set_invoice'            => '',   // fatura ao promotor
        'set_receipt'            => '',   // recibo/fatura-recibo do comercial
        // artigos e impostos por omissao
        'product_commission'     => '',   // product_id usado nas linhas de comissao
        'tax_invoice'            => '',   // tax_id para faturas ao promotor
        'tax_receipt'            => '',   // tax_id para documentos de comissao
        'exemption_reason'       => '',   // codigo de isencao quando a taxa e 0
        'document_class_invoice' => 'invoices',
        'document_class_receipt' => 'invoiceReceipts',
        // comportamento
        'always_draft'           => '1',
        'auto_create_customers'  => '1',
        // mapeamento da fonte de dados
        'src_table'              => '',
        'src_col_id'             => '',
        'src_col_client'         => '',
        'src_col_commercial'     => '',
        'src_col_sale_value'     => '',
        'src_col_commission'     => '',
        'src_col_received'       => '',
        'src_col_receipt_flag'   => '',
        'src_col_receipt_number' => '',
        'src_col_project'        => '',
        'src_col_unit'           => '',
        'src_col_date'           => '',
        // O painel do negocio guarda o que e editavel numa tabela separada,
        // ligada a tabela de vendas por uma chave. Suportamos esse padrao.
        'src_overlay_table'      => '',
        'src_overlay_fk'         => '',
        'ov_col_received'        => '',
        'ov_col_receipt_flag'    => '',
        'ov_col_receipt_number'  => '',
        'ov_col_moloni_doc'      => '',
        // Quando a coluna do comercial guarda um staffid em vez do nome.
        'src_commercial_is_staff' => '0',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->ensure_schema();
    }

    /**
     * Colunas acrescentadas depois da instalacao inicial.
     *
     * O "Atualizar banco de dados" do Perfex corre as migracoes do modulo,
     * nao o install.php, e um modulo distribuido por zip pode chegar a uma
     * instalacao em qualquer versao. Verificar aqui, uma vez por pedido, e
     * barato e evita erros de SQL a meio de uma operacao.
     */
    protected function ensure_schema()
    {
        static $checked = false;

        if ($checked) {
            return;
        }

        $checked = true;

        $links = db_prefix() . 'dps_moloni_links';

        if ($this->db->table_exists($links) && !$this->db->field_exists('is_paid', $links)) {
            $this->db->query('ALTER TABLE `' . $links . '`
                ADD COLUMN `is_paid` tinyint(1) NOT NULL DEFAULT 0 AFTER `status`;');
        }

        $overrides = db_prefix() . 'dps_moloni_overrides';

        if (!$this->db->table_exists($overrides)) {
            $this->db->query('CREATE TABLE `' . $overrides . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `beneficiary` varchar(190) NOT NULL,
                `rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
                `excluded` text NULL,
                `note` varchar(255) NULL,
                `active` tinyint(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        }
    }

    // ------------------------------------------------------------ overrides

    /**
     * Comissoes de override: uma percentagem que um comercial recebe sobre
     * as vendas de toda a equipa, alem da sua propria comissao.
     *
     * A lista de excluidos guarda os comerciais cujas vendas nao entram na
     * base de calculo.
     */
    public function get_overrides($only_active = true)
    {
        $this->db->order_by('beneficiary', 'asc');

        if ($only_active) {
            $this->db->where('active', 1);
        }

        return $this->db->get(db_prefix() . 'dps_moloni_overrides')->result_array();
    }

    public function save_override($data)
    {
        $row = [
            'beneficiary' => trim((string) (isset($data['beneficiary']) ? $data['beneficiary'] : '')),
            'rate'        => (float) str_replace(',', '.', (string) (isset($data['rate']) ? $data['rate'] : 0)),
            'excluded'    => trim((string) (isset($data['excluded']) ? $data['excluded'] : '')),
            'note'        => trim((string) (isset($data['note']) ? $data['note'] : '')),
            'active'      => !empty($data['active']) ? 1 : 0,
        ];

        if ($row['beneficiary'] === '' || $row['rate'] <= 0) {
            return false;
        }

        $id = isset($data['id']) ? (int) $data['id'] : 0;

        if ($id > 0) {
            $this->db->where('id', $id)->update(db_prefix() . 'dps_moloni_overrides', $row);

            return $id;
        }

        $existing = $this->db->where('beneficiary', $row['beneficiary'])
            ->get(db_prefix() . 'dps_moloni_overrides')->row_array();

        if ($existing) {
            $this->db->where('id', $existing['id'])
                ->update(db_prefix() . 'dps_moloni_overrides', $row);

            return (int) $existing['id'];
        }

        $this->db->insert(db_prefix() . 'dps_moloni_overrides', $row);

        return (int) $this->db->insert_id();
    }

    public function delete_override($id)
    {
        $this->db->where('id', (int) $id)->delete(db_prefix() . 'dps_moloni_overrides');

        return $this->db->affected_rows() > 0;
    }

    /**
     * Nomes distintos de comerciais nas vendas, para as listas do ecra.
     */
    public function commercials()
    {
        $names = [];

        foreach ($this->get_sales() as $sale) {
            $name = trim((string) $sale['commercial']);

            if ($name !== '' && !in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        sort($names);

        return $names;
    }

    // ------------------------------------------------------------ definicoes

    public function get_settings()
    {
        $rows = $this->db->get(db_prefix() . 'dps_moloni_settings')->result_array();

        $out = $this->defaults;

        foreach ($rows as $row) {
            $value = $row['value'];

            if (in_array($row['name'], $this->secret_keys, true) && $value !== '') {
                $value = $this->decrypt_value($value);
            }

            $out[$row['name']] = $value;
        }

        return $out;
    }

    public function get_setting($name, $fallback = '')
    {
        $settings = $this->get_settings();

        return isset($settings[$name]) && $settings[$name] !== '' ? $settings[$name] : $fallback;
    }

    /**
     * Grava definicoes. Campos secretos vazios sao ignorados para nao
     * apagar por engano um valor ja guardado.
     */
    public function save_settings($data)
    {
        foreach ($data as $name => $value) {
            $is_secret = in_array($name, $this->secret_keys, true);

            if ($is_secret && ($value === '' || $value === null)) {
                continue;
            }

            $stored = $is_secret ? $this->encrypt_value((string) $value) : (string) $value;

            $exists = $this->db->where('name', $name)
                ->get(db_prefix() . 'dps_moloni_settings')->row();

            if ($exists) {
                $this->db->where('name', $name)
                    ->update(db_prefix() . 'dps_moloni_settings', ['value' => $stored]);
            } else {
                $this->db->insert(db_prefix() . 'dps_moloni_settings', [
                    'name'  => $name,
                    'value' => $stored,
                ]);
            }
        }

        return true;
    }

    public function save_tokens($tokens)
    {
        // Os tokens seguem o mesmo caminho, mas aqui o vazio e significativo
        // (serve para invalidar), por isso escrevemos sempre.
        foreach ($tokens as $name => $value) {
            $stored = in_array($name, $this->secret_keys, true) && $value !== ''
                ? $this->encrypt_value((string) $value)
                : (string) $value;

            $exists = $this->db->where('name', $name)
                ->get(db_prefix() . 'dps_moloni_settings')->row();

            if ($exists) {
                $this->db->where('name', $name)
                    ->update(db_prefix() . 'dps_moloni_settings', ['value' => $stored]);
            } else {
                $this->db->insert(db_prefix() . 'dps_moloni_settings', [
                    'name'  => $name,
                    'value' => $stored,
                ]);
            }
        }

        return true;
    }

    // --------------------------------------------------------------- cifra

    protected function crypt_key()
    {
        $key = '';

        if (defined('ENCRYPTION_KEY') && ENCRYPTION_KEY) {
            $key = ENCRYPTION_KEY;
        } elseif ($this->config->item('encryption_key')) {
            $key = $this->config->item('encryption_key');
        }

        // Deriva uma chave de 32 bytes estavel a partir do que existir.
        return hash('sha256', 'dps_moloni|' . $key, true);
    }

    protected function encrypt_value($plain)
    {
        if ($plain === '') {
            return '';
        }

        $iv     = openssl_random_pseudo_bytes(16);
        $cipher = openssl_encrypt($plain, 'aes-256-cbc', $this->crypt_key(), OPENSSL_RAW_DATA, $iv);

        if ($cipher === false) {
            return '';
        }

        return 'enc:' . base64_encode($iv . $cipher);
    }

    protected function decrypt_value($stored)
    {
        if (strpos($stored, 'enc:') !== 0) {
            // Valor gravado antes de existir cifra: devolve como esta.
            return $stored;
        }

        $raw = base64_decode(substr($stored, 4), true);

        if ($raw === false || strlen($raw) <= 16) {
            return '';
        }

        $iv     = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain  = openssl_decrypt($cipher, 'aes-256-cbc', $this->crypt_key(), OPENSSL_RAW_DATA, $iv);

        return $plain === false ? '' : $plain;
    }

    // ------------------------------------------- importar de outro modulo

    /**
     * Procura credenciais Moloni ja guardadas noutro sitio da instalacao
     * (tipicamente o modulo do Painel do Negocio) e copia-as para aqui.
     *
     * As credenciais nunca saem do servidor: sao lidas e regravadas
     * internamente, cifradas. O que e devolvido e apenas o relatorio do que
     * foi encontrado, com os valores mascarados.
     *
     * @return array
     */
    public function import_credentials()
    {
        $report  = ['found' => [], 'imported' => [], 'sources' => []];
        $matches = [];

        // 1. Tabela de opcoes do Perfex.
        $options_table = db_prefix() . 'options';

        if ($this->table_exists($options_table)) {
            $rows = $this->db->query(
                'SELECT `name`, `value` FROM ' . $this->ident($options_table)
                . " WHERE `name` LIKE '%moloni%'"
            )->result_array();

            foreach ($rows as $row) {
                $matches[$row['name']] = $row['value'];
                $report['sources'][]   = $options_table . '.' . $row['name'];
            }
        }

        // 2. Tabelas de definicoes de outros modulos, no formato name/value.
        foreach ($this->db->list_tables() as $table) {
            if ($table === db_prefix() . 'dps_moloni_settings') {
                continue;
            }

            if (!preg_match('/(setting|config|option)/i', $table)) {
                continue;
            }

            $columns = $this->table_columns($table);

            if (!in_array('name', $columns, true) || !in_array('value', $columns, true)) {
                continue;
            }

            $rows = $this->db->query(
                'SELECT `name`, `value` FROM ' . $this->ident($table)
                . " WHERE `name` LIKE '%moloni%' OR `name` LIKE '%dev_id%'"
                . " OR `name` LIKE '%client_secret%'"
            )->result_array();

            foreach ($rows as $row) {
                $matches[$row['name']] = $row['value'];
                $report['sources'][]   = $table . '.' . $row['name'];
            }
        }

        // 3. Traduzir os nomes encontrados para os do modulo.
        $patterns = [
            'dev_id'        => '/(dev_?id|client_?id)/i',
            'client_secret' => '/(client_?secret|secret)/i',
            'username'      => '/(username|user|email|login)/i',
            'password'      => '/(password|pass|pwd)/i',
            'company_id'    => '/company/i',
        ];

        $to_save = [];

        foreach ($patterns as $field => $regex) {
            foreach ($matches as $name => $value) {
                if ($value === '' || $value === null) {
                    continue;
                }

                if (!preg_match($regex, $name)) {
                    continue;
                }

                // 'client_id' nao pode ser apanhado pelo padrao do secret.
                if ($field === 'client_secret' && preg_match('/client_?id/i', $name)) {
                    continue;
                }

                $to_save[$field]     = $value;
                $report['found'][]   = $field . ' <- ' . $name;
                $report['imported'][$field] = $this->mask($value);
                break;
            }
        }

        if (!empty($to_save)) {
            $this->save_settings($to_save);
        }

        return $report;
    }

    /**
     * Mascara um valor para poder aparecer num relatorio sem o revelar.
     */
    protected function mask($value)
    {
        $value = (string) $value;
        $len   = mb_strlen($value);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $value;
        }

        // Numeros curtos (dev id, company id) nao sao segredo.
        if (ctype_digit($value) && $len <= 12) {
            return $value;
        }

        return mb_substr($value, 0, 3) . str_repeat('*', max(0, $len - 6)) . mb_substr($value, -3);
    }

    // ---------------------------------------------------------------- logs

    public function log($endpoint, $request, $response, $status, $message)
    {
        // Nunca guardar credenciais nos logs.
        if (is_array($request)) {
            foreach (['password', 'client_secret', 'access_token', 'refresh_token'] as $k) {
                if (isset($request[$k])) {
                    $request[$k] = '***';
                }
            }
        }

        $this->db->insert(db_prefix() . 'dps_moloni_log', [
            'endpoint'    => $endpoint,
            'request'     => $this->truncate_json($request),
            'response'    => $this->truncate_json($response),
            'status'      => $status,
            'message'     => mb_substr((string) $message, 0, 500),
            'staff_id'    => function_exists('get_staff_user_id') ? (int) get_staff_user_id() : 0,
            'date_create' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function truncate_json($data)
    {
        $json = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);

        return mb_substr((string) $json, 0, 8000);
    }

    public function get_logs($limit = 200)
    {
        return $this->db->order_by('id', 'desc')
            ->limit($limit)
            ->get(db_prefix() . 'dps_moloni_log')
            ->result_array();
    }

    public function clear_logs($older_than_days = 0)
    {
        if ($older_than_days > 0) {
            $this->db->where('date_create <', date('Y-m-d H:i:s', strtotime('-' . (int) $older_than_days . ' days')));
        }

        $this->db->delete(db_prefix() . 'dps_moloni_log');
    }

    // ------------------------------------------------------ ligacoes de docs

    /**
     * Regista a ligacao entre uma linha de venda do CRM e um documento Moloni.
     */
    public function link_document($data)
    {
        $data = array_merge([
            'sale_id'       => 0,
            'kind'          => 'receipt',
            'document_id'   => 0,
            'document_type' => '',
            'document_set'  => '',
            'number'        => '',
            'net_value'     => 0,
            'total_value'   => 0,
            'doc_date'      => null,
            'status'        => 0,
            'is_paid'       => 0,
            'source'        => 'manual',
            'staff_id'      => function_exists('get_staff_user_id') ? (int) get_staff_user_id() : 0,
            'date_create'   => date('Y-m-d H:i:s'),
        ], $data);

        // Um documento Moloni pertence a uma unica venda: a procura e pelo
        // document_id, para que reatribuir substitua em vez de duplicar.
        $existing = $this->db
            ->where('document_id', $data['document_id'])
            ->get(db_prefix() . 'dps_moloni_links')->row_array();

        if ($existing) {
            $this->db->where('id', $existing['id'])
                ->update(db_prefix() . 'dps_moloni_links', $data);

            return (int) $existing['id'];
        }

        $this->db->insert(db_prefix() . 'dps_moloni_links', $data);

        return (int) $this->db->insert_id();
    }

    public function links_for_sale($sale_id)
    {
        return $this->db->where('sale_id', (int) $sale_id)
            ->order_by('id', 'asc')
            ->get(db_prefix() . 'dps_moloni_links')
            ->result_array();
    }

    public function all_links()
    {
        return $this->db->get(db_prefix() . 'dps_moloni_links')->result_array();
    }

    public function delete_link($id)
    {
        $this->db->where('id', (int) $id)->delete(db_prefix() . 'dps_moloni_links');

        return $this->db->affected_rows() > 0;
    }

    /**
     * Documentos Moloni ja associados, indexados por document_id, para
     * evitar propor de novo o que ja foi conciliado.
     */
    public function linked_document_ids()
    {
        $rows = $this->db->select('document_id')
            ->get(db_prefix() . 'dps_moloni_links')->result_array();

        return array_map('intval', array_column($rows, 'document_id'));
    }

    // ------------------------------------------------------ mapa de entidades

    public function map_entity($vat, $customer_id, $name = '', $kind = 'customer')
    {
        $vat = $this->clean_vat($vat);
        if ($vat === '') {
            return false;
        }

        $existing = $this->db->where('vat', $vat)->where('kind', $kind)
            ->get(db_prefix() . 'dps_moloni_entities')->row_array();

        $data = [
            'vat'         => $vat,
            'kind'        => $kind,
            'customer_id' => (int) $customer_id,
            'name'        => mb_substr((string) $name, 0, 190),
            'date_sync'   => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->where('id', $existing['id'])
                ->update(db_prefix() . 'dps_moloni_entities', $data);

            return (int) $existing['id'];
        }

        $this->db->insert(db_prefix() . 'dps_moloni_entities', $data);

        return (int) $this->db->insert_id();
    }

    public function entity_by_vat($vat, $kind = 'customer')
    {
        $vat = $this->clean_vat($vat);
        if ($vat === '') {
            return null;
        }

        return $this->db->where('vat', $vat)->where('kind', $kind)
            ->get(db_prefix() . 'dps_moloni_entities')->row_array();
    }

    public function all_entities()
    {
        return $this->db->order_by('name', 'asc')
            ->get(db_prefix() . 'dps_moloni_entities')->result_array();
    }

    public function clean_vat($vat)
    {
        return preg_replace('/[^0-9A-Za-z]/', '', (string) $vat);
    }

    // ---------------------------------------------------- mapa de promotores

    /**
     * Liga um empreendimento ao NIF do promotor que o factura.
     *
     * E o sinal mais fiavel da conciliacao: nomes parecidos enganam
     * ("DM Towers" e "Boavista Towers"), um NIF nao.
     */
    public function set_promoter($project, $vat, $name = '')
    {
        $project = trim((string) $project);
        $vat     = $this->clean_vat($vat);

        if ($project === '' || $vat === '') {
            return false;
        }

        $existing = $this->db->where('kind', 'promoter')->where('name', $project)
            ->get(db_prefix() . 'dps_moloni_entities')->row_array();

        $data = [
            'vat'         => $vat,
            'kind'        => 'promoter',
            'name'        => mb_substr($project, 0, 190),
            'customer_id' => 0,
            'date_sync'   => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->where('id', $existing['id'])
                ->update(db_prefix() . 'dps_moloni_entities', $data);

            return (int) $existing['id'];
        }

        $this->db->insert(db_prefix() . 'dps_moloni_entities', $data);

        return (int) $this->db->insert_id();
    }

    /**
     * Empreendimento normalizado => NIF do promotor.
     */
    public function promoter_map()
    {
        $rows = $this->db->where('kind', 'promoter')
            ->get(db_prefix() . 'dps_moloni_entities')->result_array();

        $map = [];

        foreach ($rows as $row) {
            $map[dps_moloni_norm_key($row['name'])] = $row['vat'];
        }

        return $map;
    }

    public function promoters()
    {
        return $this->db->where('kind', 'promoter')->order_by('name', 'asc')
            ->get(db_prefix() . 'dps_moloni_entities')->result_array();
    }

    /**
     * Empreendimentos distintos existentes nas vendas, para a lista do ecra.
     */
    public function projects()
    {
        $projects = [];

        foreach ($this->get_sales() as $sale) {
            $label = trim((string) $sale['project']);

            if ($label !== '' && !in_array($label, $projects, true)) {
                $projects[] = $label;
            }
        }

        sort($projects);

        return $projects;
    }

    // -------------------------------------------- descoberta da fonte de dados

    /**
     * Tabelas candidatas a conter as vendas/comissoes do painel.
     */
    public function candidate_tables()
    {
        $tables = $this->db->list_tables();
        $prefix = db_prefix();

        $candidates = [];
        foreach ($tables as $table) {
            $short = strpos($table, $prefix) === 0 ? substr($table, strlen($prefix)) : $table;

            if (preg_match('/(venda|sale|comiss|commission|painel|panel|negocio)/i', $short)) {
                $candidates[] = $table;
            }
        }

        sort($candidates);

        return $candidates;
    }

    public function all_tables()
    {
        $tables = $this->db->list_tables();
        sort($tables);

        return $tables;
    }

    /**
     * Nome de tabela/coluna protegido sem passar pelo prefixador do CI.
     *
     * protect_identifiers() acrescenta db_prefix() a nomes que nao o tenham e
     * trata o texto depois do ultimo espaco como alias, o que estraga tabelas
     * legadas sem prefixo e colunas com espacos. Aqui so escapamos.
     */
    protected function ident($name)
    {
        return '`' . str_replace('`', '', (string) $name) . '`';
    }

    public function table_columns($table)
    {
        if (!$this->table_exists($table)) {
            return [];
        }

        $rows = $this->db->query('SHOW COLUMNS FROM ' . $this->ident($table))->result_array();

        $columns = [];
        foreach ($rows as $row) {
            if (isset($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }

        return $columns;
    }

    /**
     * Comparacao directa contra list_tables(), sem prefixacao implicita.
     */
    public function table_exists($table)
    {
        if ($table === '' || $table === null) {
            return false;
        }

        return in_array($table, $this->db->list_tables(), true);
    }

    /**
     * Sugestao automatica de colunas com base no nome.
     */
    public function suggest_columns($table)
    {
        $columns = $this->table_columns($table);

        $patterns = [
            'src_col_id'             => ['/^id$/i', '/venda_id/i', '/sale_id/i'],
            'src_col_client'         => ['/cliente/i', '/client/i', '/customer/i', '/comprador/i'],
            'src_col_commercial'     => ['/comercial/i', '/salesman/i', '/vendedor/i', '/staff/i'],
            'src_col_sale_value'     => ['/valor_venda/i', '/sale_value/i', '/^valor$/i', '/preco/i'],
            'src_col_commission'     => ['/comissao_comercial/i', '/commission/i', '/comissao/i'],
            'src_col_received'       => ['/comissao_recebida/i', '/recebida/i', '/received/i'],
            'src_col_receipt_flag'   => ['/recibo_emitido/i', '/emitido/i'],
            'src_col_receipt_number' => ['/recibo_numero/i', '/numero_recibo/i', '/recibo/i'],
            'src_col_project'        => ['/empreendimento/i', '/projeto/i', '/project/i'],
            'src_col_unit'           => ['/^un$/i', '/unidade/i', '/^unit$/i', '/fraccao/i', '/fracao/i'],
            'src_col_date'           => ['/data/i', '/date/i'],
        ];

        $out = [];
        foreach ($patterns as $key => $regexes) {
            foreach ($regexes as $regex) {
                foreach ($columns as $column) {
                    if (preg_match($regex, $column)) {
                        $out[$key] = $column;
                        break 2;
                    }
                }
            }
        }

        return $out;
    }

    /**
     * O mapeamento esta completo o suficiente para ler vendas?
     *
     * Verifica tambem que as colunas indispensaveis ainda existem: se alguem
     * renomear uma coluna na tabela de origem, e melhor cair no ecra de
     * mapeamento do que rebentar com um erro de SQL.
     */
    public function mapping_ready()
    {
        $s = $this->get_settings();

        if (!$this->table_exists($s['src_table'])) {
            return false;
        }

        if ($s['src_col_id'] === '' || $s['src_col_commission'] === '') {
            return false;
        }

        $available = $this->table_columns($s['src_table']);

        return in_array($s['src_col_id'], $available, true)
            && in_array($s['src_col_commission'], $available, true);
    }

    // ------------------------------------------------------ leitura de vendas

    /**
     * Le as linhas de venda da tabela mapeada e devolve-as num formato
     * normalizado, independente dos nomes reais das colunas.
     */
    public function get_sales($filters = [])
    {
        if (!$this->mapping_ready()) {
            return [];
        }

        $s     = $this->get_settings();
        $table = $s['src_table'];

        $map = [
            'id'             => $s['src_col_id'],
            'client'         => $s['src_col_client'],
            'commercial'     => $s['src_col_commercial'],
            'sale_value'     => $s['src_col_sale_value'],
            'commission'     => $s['src_col_commission'],
            'received'       => $s['src_col_received'],
            'receipt_flag'   => $s['src_col_receipt_flag'],
            'receipt_number' => $s['src_col_receipt_number'],
            'project'        => $s['src_col_project'],
            'unit'           => $s['src_col_unit'],
            'date'           => $s['src_col_date'],
        ];

        $available = $this->table_columns($table);

        // So sobrevivem as colunas que existem mesmo na tabela. Tudo o que
        // vier a seguir — select, where, order by — usa apenas estas.
        $map = array_filter($map, function ($column) use ($available) {
            return $column !== '' && in_array($column, $available, true);
        });

        if (!isset($map['id'])) {
            return [];
        }

        $select = [];
        foreach ($map as $alias => $column) {
            $select[] = 'm.' . $this->ident($column) . ' AS ' . $this->ident($alias);
        }

        $joins = '';

        // ---- tabela sobreposta (o que o painel deixa editar) -------------
        $overlay = $this->overlay_map();

        if ($overlay !== null) {
            foreach ($overlay['map'] as $alias => $column) {
                // A sobreposta manda: substitui a coluna homonima da principal.
                $select = array_values(array_filter($select, function ($piece) use ($alias) {
                    return strpos($piece, ' AS ' . $this->ident($alias)) === false;
                }));

                $select[] = 'o.' . $this->ident($column) . ' AS ' . $this->ident($alias);
            }

            $joins .= ' LEFT JOIN ' . $this->ident($overlay['table']) . ' o'
                . ' ON o.' . $this->ident($overlay['fk']) . ' = m.' . $this->ident($map['id']);
        }

        // ---- nome do comercial quando a coluna guarda um staffid ---------
        if ($s['src_commercial_is_staff'] === '1' && isset($map['commercial'])) {
            $select = array_values(array_filter($select, function ($piece) {
                return strpos($piece, ' AS ' . $this->ident('commercial')) === false;
            }));

            $select[] = "TRIM(CONCAT(COALESCE(st.`firstname`,''),' ',COALESCE(st.`lastname`,''))) AS "
                . $this->ident('commercial');

            $joins .= ' LEFT JOIN ' . $this->ident(db_prefix() . 'staff') . ' st'
                . ' ON st.`staffid` = m.' . $this->ident($map['commercial']);
        }

        $sql   = 'SELECT ' . implode(', ', $select)
               . ' FROM ' . $this->ident($table) . ' m' . $joins;
        $where = [];
        $binds = [];

        if (!empty($filters['id'])) {
            $where[]  = 'm.' . $this->ident($map['id']) . ' = ?';
            $binds[]  = (int) $filters['id'];
        }

        if (!empty($filters['from']) && isset($map['date'])) {
            $where[] = 'm.' . $this->ident($map['date']) . ' >= ?';
            $binds[] = $filters['from'];
        }

        if (!empty($filters['to']) && isset($map['date'])) {
            $where[] = 'm.' . $this->ident($map['date']) . ' <= ?';
            $binds[] = $filters['to'];
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY m.' . $this->ident($map['id']) . ' DESC';

        if (!empty($filters['limit'])) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        $rows = $this->db->query($sql, $binds)->result_array();

        foreach ($rows as &$row) {
            foreach (['id', 'client', 'commercial', 'sale_value', 'commission', 'received',
                'receipt_flag', 'receipt_number', 'project', 'unit', 'date', ] as $alias) {
                if (!array_key_exists($alias, $row)) {
                    $row[$alias] = null;
                }
            }

            $row['sale_value'] = (float) $row['sale_value'];
            $row['commission'] = (float) $row['commission'];
            $row['received']   = (float) $row['received'];
        }
        unset($row);

        return $rows;
    }

    public function get_sale($id)
    {
        $rows = $this->get_sales(['id' => $id, 'limit' => 1]);

        return !empty($rows) ? $rows[0] : null;
    }

    /**
     * Mapeamento da tabela sobreposta, ou null se nao estiver configurada
     * (ou se as colunas ja nao existirem).
     */
    public function overlay_map()
    {
        $s = $this->get_settings();

        if ($s['src_overlay_table'] === '' || $s['src_overlay_fk'] === '') {
            return null;
        }

        if (!$this->table_exists($s['src_overlay_table'])) {
            return null;
        }

        $available = $this->table_columns($s['src_overlay_table']);

        if (!in_array($s['src_overlay_fk'], $available, true)) {
            return null;
        }

        $map = array_filter([
            'received'       => $s['ov_col_received'],
            'receipt_flag'   => $s['ov_col_receipt_flag'],
            'receipt_number' => $s['ov_col_receipt_number'],
            'moloni_doc'     => $s['ov_col_moloni_doc'],
        ], function ($column) use ($available) {
            return $column !== '' && in_array($column, $available, true);
        });

        if (empty($map)) {
            return null;
        }

        return [
            'table'     => $s['src_overlay_table'],
            'fk'        => $s['src_overlay_fk'],
            'map'       => $map,
            'available' => $available,
        ];
    }

    /**
     * Escreve de volta o numero de recibo e a flag.
     *
     * Quando existe tabela sobreposta, e la que se escreve — criando a linha
     * se ainda nao existir, que e como o painel do negocio funciona.
     */
    public function update_sale_receipt($sale_id, $number, $flag = 1, $received = null, $moloni_doc = null)
    {
        if (!$this->mapping_ready()) {
            return false;
        }

        $overlay = $this->overlay_map();

        if ($overlay !== null) {
            return $this->update_overlay_receipt($overlay, $sale_id, $number, $flag, $received, $moloni_doc);
        }

        $s         = $this->get_settings();
        $available = $this->table_columns($s['src_table']);
        $sets      = [];
        $binds     = [];

        if ($s['src_col_receipt_number'] !== '' && in_array($s['src_col_receipt_number'], $available, true)) {
            $sets[]  = $this->ident($s['src_col_receipt_number']) . ' = ?';
            $binds[] = $number;
        }

        if ($s['src_col_receipt_flag'] !== '' && in_array($s['src_col_receipt_flag'], $available, true)) {
            $sets[]  = $this->ident($s['src_col_receipt_flag']) . ' = ?';
            $binds[] = $flag;
        }

        if ($received !== null && $s['src_col_received'] !== '' && in_array($s['src_col_received'], $available, true)) {
            $sets[]  = $this->ident($s['src_col_received']) . ' = ?';
            $binds[] = $received;
        }

        if (empty($sets) || !in_array($s['src_col_id'], $available, true)) {
            return false;
        }

        $binds[] = (int) $sale_id;

        $this->db->query(
            'UPDATE ' . $this->ident($s['src_table'])
            . ' SET ' . implode(', ', $sets)
            . ' WHERE ' . $this->ident($s['src_col_id']) . ' = ?',
            $binds
        );

        // affected_rows() e 0 quando o UPDATE nao encontrou a linha e tambem
        // quando os valores ja la estavam; distinguimos confirmando que a
        // linha existe.
        return $this->sale_exists($sale_id);
    }

    /**
     * Upsert na tabela sobreposta.
     */
    protected function update_overlay_receipt($overlay, $sale_id, $number, $flag, $received, $moloni_doc)
    {
        $values = [];

        // $number/$flag a null significa "nao mexer no lado do recibo".
        if ($number !== null && isset($overlay['map']['receipt_number'])) {
            $values[$overlay['map']['receipt_number']] = $number;
        }

        if ($flag !== null && isset($overlay['map']['receipt_flag'])) {
            $values[$overlay['map']['receipt_flag']] = $flag;
        }

        if ($received !== null && isset($overlay['map']['received'])) {
            $values[$overlay['map']['received']] = $received;
        }

        if ($moloni_doc !== null && isset($overlay['map']['moloni_doc'])) {
            $values[$overlay['map']['moloni_doc']] = $moloni_doc;
        }

        if (empty($values)) {
            return false;
        }

        $exists = $this->db->query(
            'SELECT 1 AS ok FROM ' . $this->ident($overlay['table'])
            . ' WHERE ' . $this->ident($overlay['fk']) . ' = ? LIMIT 1',
            [(int) $sale_id]
        )->row_array();

        if ($exists) {
            $sets  = [];
            $binds = [];

            foreach ($values as $column => $value) {
                $sets[]  = $this->ident($column) . ' = ?';
                $binds[] = $value;
            }

            $binds[] = (int) $sale_id;

            $this->db->query(
                'UPDATE ' . $this->ident($overlay['table'])
                . ' SET ' . implode(', ', $sets)
                . ' WHERE ' . $this->ident($overlay['fk']) . ' = ?',
                $binds
            );

            return true;
        }

        $columns = [$this->ident($overlay['fk'])];
        $marks   = ['?'];
        $binds   = [(int) $sale_id];

        foreach ($values as $column => $value) {
            $columns[] = $this->ident($column);
            $marks[]   = '?';
            $binds[]   = $value;
        }

        $this->db->query(
            'INSERT INTO ' . $this->ident($overlay['table'])
            . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $marks) . ')',
            $binds
        );

        return $this->db->insert_id() > 0;
    }

    /**
     * Escreve o lado do dinheiro que ENTRA: a comissao que a DPS recebe do
     * promotor e o ID da fatura na Moloni.
     *
     * Deliberadamente separado de update_sale_receipt, que trata do lado que
     * SAI (o recibo do comercial). Sao dois documentos diferentes, de duas
     * entidades diferentes, e nao se devem sobrepor.
     */
    public function update_sale_invoice($sale_id, $received, $moloni_doc = null)
    {
        if (!$this->mapping_ready()) {
            return false;
        }

        $overlay = $this->overlay_map();

        if ($overlay !== null) {
            $values = [];

            if (isset($overlay['map']['received'])) {
                $values['received'] = $received;
            }

            if ($moloni_doc !== null && isset($overlay['map']['moloni_doc'])) {
                $values['moloni_doc'] = $moloni_doc;
            }

            if (empty($values)) {
                return false;
            }

            return $this->update_overlay_receipt(
                $overlay,
                $sale_id,
                null,
                null,
                isset($values['received']) ? $values['received'] : null,
                isset($values['moloni_doc']) ? $values['moloni_doc'] : null
            );
        }

        $s         = $this->get_settings();
        $available = $this->table_columns($s['src_table']);

        if ($s['src_col_received'] === '' || !in_array($s['src_col_received'], $available, true)) {
            return false;
        }

        $this->db->query(
            'UPDATE ' . $this->ident($s['src_table'])
            . ' SET ' . $this->ident($s['src_col_received']) . ' = ?'
            . ' WHERE ' . $this->ident($s['src_col_id']) . ' = ?',
            [$received, (int) $sale_id]
        );

        return $this->sale_exists($sale_id);
    }

    /**
     * Recalcula a comissao recebida de cada venda a partir dos documentos
     * ligados que estao efectivamente pagos.
     *
     * Torna a coluna derivada em vez de escrita a mao: se um documento for
     * desligado, ou se uma fatura ainda nao tiver recibo, o valor corrige-se
     * sozinho na proxima passagem.
     *
     * @return array [linhas actualizadas, total recebido]
     */
    public function recalc_received()
    {
        if (!$this->mapping_ready()) {
            return [0, 0.0];
        }

        $paid = [];

        foreach ($this->all_links() as $link) {
            if ($link['kind'] !== 'invoice' || (int) $link['is_paid'] !== 1) {
                continue;
            }

            $sale_id = (int) $link['sale_id'];
            $paid[$sale_id] = (isset($paid[$sale_id]) ? $paid[$sale_id] : 0) + (float) $link['net_value'];
        }

        $updated = 0;
        $total   = 0.0;

        foreach ($this->get_sales() as $sale) {
            $sale_id = (int) $sale['id'];
            $should  = isset($paid[$sale_id]) ? round($paid[$sale_id], 2) : 0.0;
            $current = round((float) $sale['received'], 2);

            if (abs($should - $current) < 0.005) {
                $total += $should;
                continue;
            }

            if ($this->update_sale_invoice($sale_id, $should)) {
                $updated++;
            }

            $total += $should;
        }

        return [$updated, $total];
    }

    /**
     * A linha de venda existe mesmo na tabela de origem?
     */
    public function sale_exists($sale_id)
    {
        if (!$this->mapping_ready()) {
            return false;
        }

        $s = $this->get_settings();

        $row = $this->db->query(
            'SELECT 1 AS ok FROM ' . $this->ident($s['src_table'])
            . ' WHERE ' . $this->ident($s['src_col_id']) . ' = ? LIMIT 1',
            [(int) $sale_id]
        )->row_array();

        return !empty($row);
    }

    /**
     * "Buscar facturas": lê o Moloni e preenche sozinho o que não tem dúvida.
     *
     * ESTE MÉTODO NÃO EXISTIA. O botão do quadro de comissões (e o do Painel do
     * Negócio) chamava-o desde sempre — Dps_vendas::moloni_sincronizar() faz
     * `$this->dps_moloni_model->sincronizar(true)` — e rebentava com "Call to
     * undefined method". Por isso emitiam-se facturas no Moloni, carregava-se no
     * botão, e nada acontecia (03/08/2026).
     *
     * O trabalho já existia todo, espalhado: a página de Conciliação lê os
     * documentos e usa dps_moloni_match() para propor ligações com um grau de
     * confiança. O que faltava era o caminho automático.
     *
     * O QUE APLICA SOZINHO, e porquê:
     *   - 'certeza' -> o documento traz a referência DPS#<id>. Não há dúvida.
     *   - 'alta'    -> o valor bate certo E o nome coincide.
     *
     * O QUE DEIXA PARA CONFIRMAR À MÃO:
     *   - 'media'   -> o valor bate mas o nome não. Duas comissões iguais no
     *                  mesmo mês são vulgares, e trocá-las é atribuir dinheiro
     *                  à pessoa errada.
     *   - qualquer documento que sirva a duas vendas, ou venda que apanhe dois
     *     documentos: escolher por ele seria adivinhar.
     *
     * Preferiu-se pecar por defeito. Uma factura por preencher vê-se no quadro;
     * uma factura preenchida na venda errada não se vê — e altera contas.
     *
     * @param  bool  $write_back  escrever também o número na tabela de vendas
     * @return array ok, erro, facturas_lidas, achados[], duvidas[]
     */
    public function sincronizar($write_back = true)
    {
        $CI = &get_instance();
        $CI->load->helper('dps_moloni/dps_moloni');
        $CI->load->library('dps_moloni/moloni_api');

        $api = $CI->moloni_api;

        if (!$this->mapping_ready()) {
            return ['ok' => false, 'erro' => 'O mapeamento das colunas do Moloni não está feito (Moloni → Mapeamento).'];
        }
        if (!$api->is_configured()) {
            return ['ok' => false, 'erro' => 'Faltam as credenciais do Moloni (Moloni → Definições).'];
        }
        if (!$api->company_id()) {
            return ['ok' => false, 'erro' => 'Falta escolher a empresa do Moloni (Moloni → Definições).'];
        }

        /*
         * Seis meses para trás. Chega para apanhar o que foi emitido há pouco
         * sem obrigar a ler anos de facturação a cada clique.
         */
        $de  = date('Y-m-d', strtotime('-6 months'));
        $ate = date('Y-m-d');

        $documentos = $api->documents_between($de, $ate);

        if ($documentos === false) {
            return ['ok' => false, 'erro' => $api->last_error() ?: 'O Moloni não respondeu.'];
        }

        $vendas     = $this->get_sales();
        $sugestoes  = dps_moloni_match(
            $documentos,
            $vendas,
            $this->linked_document_ids(),
            $this->promoter_map()
        );

        /*
         * Contar quantas vezes cada documento e cada venda aparecem: só se
         * aplica sozinho o que aparece UMA vez de cada lado.
         */
        $por_documento = [];
        $por_venda     = [];

        foreach ($sugestoes as $s) {
            $doc_id = (int) ($s['document']['document_id'] ?? 0);
            $venda  = (int) ($s['sale']['id'] ?? 0);
            $por_documento[$doc_id] = ($por_documento[$doc_id] ?? 0) + 1;
            $por_venda[$venda]      = ($por_venda[$venda] ?? 0) + 1;
        }

        $achados = [];
        $duvidas = [];
        $usados  = array_flip(array_map('intval', $this->linked_document_ids()));

        foreach ($sugestoes as $s) {
            $conf   = $s['confidence'] ?? '';
            $doc    = $s['document'];
            $venda  = $s['sale'];
            $doc_id = (int) ($doc['document_id'] ?? 0);
            $vid    = (int) ($venda['id'] ?? 0);

            if (!$doc_id || !$vid || isset($usados[$doc_id])) {
                continue;
            }

            $unidade = (string) ($venda['unit'] ?? $venda['id']);

            if ($conf !== 'certeza' && $conf !== 'alta') {
                $duvidas[] = ['venda' => $vid, 'unidade' => $unidade,
                              'motivo' => $s['reason'] ?? 'confiança insuficiente'];
                continue;
            }
            if (($por_documento[$doc_id] ?? 0) > 1 || ($por_venda[$vid] ?? 0) > 1) {
                $duvidas[] = ['venda' => $vid, 'unidade' => $unidade,
                              'motivo' => 'o mesmo valor serve mais do que uma venda'];
                continue;
            }

            $numero = dps_moloni_doc_number($doc);

            $this->link_document([
                'sale_id'       => $vid,
                'kind'          => $s['kind'] ?? 'invoice',
                'document_id'   => $doc_id,
                'document_type' => $doc['document_type']['name'] ?? '',
                'document_set'  => $doc['document_set']['name'] ?? '',
                'number'        => $numero,
                'net_value'     => dps_moloni_doc_value($doc),
                'total_value'   => dps_moloni_doc_total($doc),
                'doc_date'      => isset($doc['date']) ? substr((string) $doc['date'], 0, 10) : null,
                'status'        => (int) ($doc['status'] ?? 0),
                'is_paid'       => dps_moloni_doc_is_paid($doc) ? 1 : 0,
                'source'        => 'sincronizacao',
            ]);

            if ($write_back) {
                /*
                 * A escrita de volta segue EXACTAMENTE o caminho da conciliação
                 * manual (Dps_moloni::conciliar_aplicar). Duas regras que se
                 * copiam de lá e que não são detalhe:
                 *
                 *  1. Uma FACTURA só conta como recebimento depois de
                 *     liquidada. Emitir não é receber — passar o valor a
                 *     "recebido" só porque a factura saiu punha dinheiro na
                 *     tesouraria que ainda não entrou.
                 *  2. O que se guarda na venda é o ID do documento, não o
                 *     número impresso.
                 */
                if (($s['kind'] ?? 'receipt') === 'invoice') {
                    $liquidada = dps_moloni_doc_is_paid($doc);

                    $this->update_sale_invoice(
                        $vid,
                        $liquidada ? dps_moloni_doc_value($doc) : null,
                        $doc_id
                    );
                } else {
                    // Recibos somam-se: uma venda pode ter mais do que um.
                    $numeros = [];
                    foreach ($this->links_for_sale($vid) as $lig) {
                        if (($lig['kind'] ?? '') === 'invoice') {
                            continue;
                        }
                        if (!empty($lig['number'])) {
                            $numeros[] = $lig['number'];
                        }
                    }

                    $this->update_sale_receipt($vid, implode(' + ', array_unique($numeros)), 1);
                }
            }

            /*
             * E ESCREVER TAMBÉM O CAMPO QUE O PAINEL LÊ.
             *
             * O write_back acima vai para a tabela de sobreposição
             * (tbldps_painel_vendas.moloni_doc_id) — é o que o mapeamento diz.
             * Mas o quadro "A emitir factura" do Painel do Negócio decide pelo
             * fatura_moloni_cpcv / fatura_moloni_escritura da própria venda:
             *
             *     } elseif (!$tem_factura[$qual]) { $estado = 'a_emitir'; }
             *
             * São campos diferentes. Sem esta escrita, emitia-se a factura no
             * Moloni, o botão dizia que tinha ligado, e a venda continuava
             * teimosamente em "A emitir factura" — que foi o que aconteceu.
             *
             * QUAL DAS DUAS TRANCHES: só se escreve quando não há dúvida —
             * quando o empreendimento factura tudo num momento só (Boavista e
             * Gaia Douro são 100% no CPCV), ou quando a outra tranche já está
             * facturada. Num empreendimento 50/50 com as duas por facturar,
             * escolher seria adivinhar em que momento a factura foi emitida, e
             * isso desloca dinheiro entre trimestres. Nesse caso fica dúvida.
             */
            $tranche = $this->tranche_por_facturar($vid);

            if ($tranche !== null) {
                $this->db->where('id', $vid)->update(db_prefix() . 'simulador_vendas', [
                    'fatura_moloni_' . $tranche => $numero,
                ]);
            } else {
                $duvidas[] = ['venda' => $vid, 'unidade' => $unidade,
                              'motivo' => 'ligada ao documento, mas há duas tranches por facturar — '
                                        . 'diga qual é no Painel do Negócio'];
            }

            $usados[$doc_id] = true;
            $achados[]       = ['venda' => $vid, 'unidade' => $unidade, 'numero' => $numero];
        }

        $this->log('sincronizar', ['de' => $de, 'ate' => $ate],
            ['lidas' => count($documentos), 'aplicadas' => count($achados), 'duvidas' => count($duvidas)],
            'ok', '');

        return [
            'ok'             => true,
            'facturas_lidas' => count($documentos),
            'achados'        => $achados,
            'duvidas'        => $duvidas,
        ];
    }

    /**
     * Em que tranche entra a factura desta venda: 'cpcv', 'escritura' ou null.
     *
     * null quer dizer "não sei" — as duas tranches existem e nenhuma está
     * facturada. Preferível a escolher: pôr a factura do CPCV na escritura
     * muda o mês em que o dinheiro conta.
     */
    private function tranche_por_facturar($venda_id)
    {
        $venda = $this->db->select('empreendimento, fatura_moloni_cpcv, fatura_moloni_escritura')
            ->where('id', (int) $venda_id)
            ->get(db_prefix() . 'simulador_vendas')->row_array();

        if (!$venda) {
            return null;
        }

        $tem_cpcv      = trim((string) $venda['fatura_moloni_cpcv']) !== '';
        $tem_escritura = trim((string) $venda['fatura_moloni_escritura']) !== '';

        if ($tem_cpcv && $tem_escritura) {
            return null;                       // já não falta nenhuma
        }
        if ($tem_cpcv) {
            return 'escritura';
        }
        if ($tem_escritura) {
            return 'cpcv';
        }

        // Nenhuma facturada: só decide se o empreendimento tiver uma tranche só.
        $regra = $this->db->select('cpcv_pct, escritura_pct')
            ->where('empreendimento', $venda['empreendimento'])
            ->get(db_prefix() . 'dps_painel_recebimento')->row_array();

        if (!$regra) {
            return null;
        }

        $cpcv      = (float) $regra['cpcv_pct'];
        $escritura = (float) $regra['escritura_pct'];

        if ($escritura <= 0 && $cpcv > 0) {
            return 'cpcv';
        }
        if ($cpcv <= 0 && $escritura > 0) {
            return 'escritura';
        }

        return null;                           // 50/50 — não se adivinha
    }
}
