<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_moloni extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('dps_moloni/dps_moloni_model');
        $this->load->helper('dps_moloni/dps_moloni');

        if (!has_permission('dps_moloni', '', 'view') && !is_admin()) {
            access_denied('dps_moloni');
        }
    }

    /**
     * Instancia o cliente da API a pedido (evita autenticar em ecras que
     * nao precisam da Moloni).
     */
    protected function api()
    {
        static $api = null;

        if ($api === null) {
            $this->load->library('dps_moloni/moloni_api');
            $api = $this->moloni_api;
        }

        return $api;
    }

    protected function can_edit()
    {
        return is_admin() || has_permission('dps_moloni', '', 'edit');
    }

    protected function require_edit()
    {
        if (!$this->can_edit()) {
            access_denied('dps_moloni');
        }
    }

    // ------------------------------------------------------------ financeiro

    public function index()
    {
        $settings = $this->dps_moloni_model->get_settings();
        $sales    = $this->dps_moloni_model->get_sales();
        $links    = $this->dps_moloni_model->all_links();

        $by_sale = [];
        foreach ($links as $link) {
            $by_sale[(int) $link['sale_id']][] = $link;
        }

        $overrides = dps_moloni_overrides_calc($sales, $this->dps_moloni_model->get_overrides());

        $data['settings']    = $settings;
        $data['sales']       = $sales;
        $data['links']       = $by_sale;
        $data['overrides']   = $overrides;
        $data['totals']      = dps_moloni_totals($sales, $by_sale);

        // As comissoes de override sao dinheiro a pagar tal como as outras.
        $data['totals']['overrides']      = dps_moloni_overrides_total($overrides);
        $data['totals']['commission_due'] += $data['totals']['overrides'];
        $data['totals']['result']         = $data['totals']['commission_recv'] - $data['totals']['commission_due'];
        $data['by_project']  = dps_moloni_group($sales, 'project');
        $data['by_agent']    = dps_moloni_group($sales, 'commercial');
        $data['mapping_ok']  = $this->dps_moloni_model->mapping_ready();
        $data['title']       = _l('dps_moloni_financeiro');

        $this->load->view('dps_moloni/financeiro', $data);
    }

    // ------------------------------------------------------------ definicoes

    public function definicoes()
    {
        if ($this->input->post()) {
            $this->require_edit();

            $post = $this->input->post(null, false);

            unset($post['undefined']);

            $fields = [
                'dev_id', 'client_secret', 'username', 'password', 'company_id',
                'set_invoice', 'set_receipt', 'product_commission',
                'tax_invoice', 'tax_receipt', 'exemption_reason',
                'document_class_invoice', 'document_class_receipt',
                'always_draft', 'auto_create_customers',
            ];

            $to_save = [];
            foreach ($fields as $field) {
                if ($field === 'always_draft' || $field === 'auto_create_customers') {
                    $to_save[$field] = isset($post[$field]) && $post[$field] ? '1' : '0';
                    continue;
                }

                if (isset($post[$field])) {
                    $to_save[$field] = trim((string) $post[$field]);
                }
            }

            // Se mudou de empresa, os catalogos anteriores deixam de servir.
            $previous = $this->dps_moloni_model->get_setting('company_id');
            if (isset($to_save['company_id']) && $to_save['company_id'] !== $previous) {
                $to_save['set_invoice']        = '';
                $to_save['set_receipt']        = '';
                $to_save['product_commission'] = '';
                $to_save['tax_invoice']        = '';
                $to_save['tax_receipt']        = '';
            }

            $this->dps_moloni_model->save_settings($to_save);

            set_alert('success', _l('dps_moloni_settings_saved'));

            redirect(admin_url('dps_moloni/definicoes'));
        }

        $settings = $this->dps_moloni_model->get_settings();

        $data['settings']  = $settings;
        $data['connected'] = false;
        $data['companies'] = [];
        $data['sets']      = [];
        $data['taxes']     = [];
        $data['products']  = [];
        $data['api_error'] = '';

        // Se ja ha credenciais, tenta carregar os catalogos para os selects.
        $api = $this->api();

        if ($api->is_configured()) {
            $companies = $api->companies();

            if ($companies === false) {
                $data['api_error'] = $api->last_error();
            } else {
                $data['connected'] = true;
                $data['companies'] = is_array($companies) ? $companies : [];

                if ($api->company_id()) {
                    $sets     = $api->document_sets();
                    $taxes    = $api->taxes();
                    $products = $api->products('', 50);

                    $data['sets']     = is_array($sets) ? $sets : [];
                    $data['taxes']    = is_array($taxes) ? $taxes : [];
                    $data['products'] = is_array($products) ? $products : [];
                }
            }
        }

        $data['callback_url'] = site_url('moloni-callback.php');
        $data['title']        = _l('dps_moloni_definicoes');

        $this->load->view('dps_moloni/definicoes', $data);
    }

    /**
     * Copia credenciais Moloni ja guardadas noutro modulo desta instalacao.
     *
     * Evita ter de as voltar a escrever a mao: passam do sitio onde estao
     * para a tabela deste modulo, sem sairem do servidor.
     */
    public function importar_credenciais()
    {
        $this->require_edit();

        $report = $this->dps_moloni_model->import_credentials();

        if (empty($report['imported'])) {
            set_alert('warning', _l('dps_moloni_import_none'));

            redirect(admin_url('dps_moloni/definicoes'));
        }

        // Mostrar a origem de cada campo permite perceber logo se a heuristica
        // apanhou a chave errada.
        set_alert('success', _l('dps_moloni_import_ok') . ' ' . implode(' · ', $report['found']));

        redirect(admin_url('dps_moloni/definicoes'));
    }

    /**
     * Testa a ligacao e mostra as empresas devolvidas.
     */
    public function testar()
    {
        $api = $this->api();

        if (!$api->is_configured()) {
            set_alert('warning', _l('dps_moloni_err_no_credentials'));

            redirect(admin_url('dps_moloni/definicoes'));
        }

        $companies = $api->companies();

        if ($companies === false || !is_array($companies)) {
            set_alert('danger', _l('dps_moloni_test_failed') . ' ' . $api->last_error());

            redirect(admin_url('dps_moloni/definicoes'));
        }

        $names = [];
        foreach ($companies as $company) {
            $label = isset($company['name']) ? $company['name'] : ('#' . $company['company_id']);
            $names[] = $label . ' (#' . $company['company_id'] . ')';
        }

        set_alert('success', _l('dps_moloni_test_ok') . ' ' . implode(', ', $names));

        redirect(admin_url('dps_moloni/definicoes'));
    }

    // ------------------------------------------------------------ mapeamento

    public function mapeamento()
    {
        if ($this->input->post()) {
            $this->require_edit();

            $fields = [
                'src_table', 'src_col_id', 'src_col_client', 'src_col_commercial',
                'src_col_sale_value', 'src_col_commission', 'src_col_received',
                'src_col_receipt_flag', 'src_col_receipt_number',
                'src_col_project', 'src_col_unit', 'src_col_date',
                'src_overlay_table', 'src_overlay_fk',
                'ov_col_received', 'ov_col_receipt_flag', 'ov_col_receipt_number', 'ov_col_moloni_doc',
            ];

            $to_save = [];
            foreach ($fields as $field) {
                $to_save[$field] = trim((string) $this->input->post($field));
            }

            $to_save['src_commercial_is_staff'] = $this->input->post('src_commercial_is_staff') ? '1' : '0';

            $this->dps_moloni_model->save_settings($to_save);

            set_alert('success', _l('dps_moloni_mapping_saved'));

            redirect(admin_url('dps_moloni/mapeamento'));
        }

        $settings = $this->dps_moloni_model->get_settings();

        $data['settings']   = $settings;
        $data['candidates'] = $this->dps_moloni_model->candidate_tables();
        $data['tables']     = $this->dps_moloni_model->all_tables();
        $data['columns'] = $settings['src_table'] !== ''
            ? $this->dps_moloni_model->table_columns($settings['src_table'])
            : [];
        $data['overlay_columns'] = $settings['src_overlay_table'] !== ''
            ? $this->dps_moloni_model->table_columns($settings['src_overlay_table'])
            : [];
        $data['preview'] = $this->dps_moloni_model->mapping_ready()
            ? $this->dps_moloni_model->get_sales(['limit' => 5])
            : [];
        $data['title'] = _l('dps_moloni_mapeamento');

        $this->load->view('dps_moloni/mapeamento', $data);
    }

    /**
     * AJAX: colunas de uma tabela + sugestao automatica.
     */
    public function colunas()
    {
        $table = $this->input->get('table');

        if (!$this->dps_moloni_model->table_exists($table)) {
            echo json_encode(['columns' => [], 'suggested' => []]);

            return;
        }

        echo json_encode([
            'columns'   => $this->dps_moloni_model->table_columns($table),
            'suggested' => $this->dps_moloni_model->suggest_columns($table),
        ]);
    }

    // ----------------------------------------------------------- conciliacao

    public function conciliacao()
    {
        $api = $this->api();

        $from = $this->input->get('from') ?: date('Y-m-01', strtotime('-6 months'));
        $to   = $this->input->get('to') ?: date('Y-m-d');

        $data['from']       = $from;
        $data['to']         = $to;
        $data['documents']  = [];
        $data['sales']      = $this->dps_moloni_model->get_sales();
        $data['matches']    = [];
        $data['api_error']  = '';
        $data['mapping_ok'] = $this->dps_moloni_model->mapping_ready();

        if (!$api->is_configured()) {
            $data['api_error'] = _l('dps_moloni_err_no_credentials');
        } elseif (!$api->company_id()) {
            $data['api_error'] = _l('dps_moloni_err_no_company');
        } else {
            $documents = $api->documents_between($from, $to);

            if ($documents === false) {
                $data['api_error'] = $api->last_error();
            } else {
                $data['documents'] = $documents;
                $data['matches']   = dps_moloni_match($documents, $data['sales'],
                    $this->dps_moloni_model->linked_document_ids(),
                    $this->dps_moloni_model->promoter_map());
            }
        }

        $data['title'] = _l('dps_moloni_conciliacao');

        $this->load->view('dps_moloni/conciliacao', $data);
    }

    /**
     * Aplica as conciliacoes escolhidas: grava a ligacao e, opcionalmente,
     * escreve o numero do documento de volta na tabela de vendas.
     */
    public function conciliar_aplicar()
    {
        $this->require_edit();

        $apply = $this->input->post('apply', false);

        if (!is_array($apply) || empty($apply)) {
            set_alert('warning', _l('dps_moloni_nothing_selected'));

            redirect(admin_url('dps_moloni/conciliacao'));
        }

        $write_back = (bool) $this->input->post('write_back');
        $applied    = 0;
        $skipped    = 0;

        // Um documento so pode ficar ligado a uma venda. Se o utilizador
        // seleccionar o mesmo documento para duas vendas diferentes (acontece
        // quando duas comissoes tem o mesmo valor), fica so a primeira.
        $seen = array_flip($this->dps_moloni_model->linked_document_ids());

        foreach ($apply as $payload) {
            $decoded = json_decode(base64_decode((string) $payload, true), true);

            if (!is_array($decoded) || empty($decoded['sale_id']) || empty($decoded['document_id'])) {
                continue;
            }

            $document_id = (int) $decoded['document_id'];

            if (isset($seen[$document_id])) {
                $skipped++;
                continue;
            }

            $seen[$document_id] = true;

            $this->dps_moloni_model->link_document([
                'sale_id'       => (int) $decoded['sale_id'],
                'kind'          => isset($decoded['kind']) ? $decoded['kind'] : 'receipt',
                'document_id'   => (int) $decoded['document_id'],
                'document_type' => isset($decoded['document_type']) ? $decoded['document_type'] : '',
                'document_set'  => isset($decoded['document_set']) ? $decoded['document_set'] : '',
                'number'        => isset($decoded['number']) ? $decoded['number'] : '',
                'net_value'     => isset($decoded['net_value']) ? (float) $decoded['net_value'] : 0,
                'total_value'   => isset($decoded['total_value']) ? (float) $decoded['total_value'] : 0,
                'doc_date'      => isset($decoded['date']) ? substr($decoded['date'], 0, 10) : null,
                'status'        => isset($decoded['status']) ? (int) $decoded['status'] : 0,
                'is_paid'       => isset($decoded['is_paid']) ? (int) $decoded['is_paid'] : 0,
                'source'        => 'conciliacao',
            ]);

            if ($write_back) {
                $kind = isset($decoded['kind']) ? $decoded['kind'] : 'receipt';

                if ($kind === 'invoice') {
                    // Fatura ao promotor: e dinheiro que entra — mas so
                    // depois de liquidada. Uma Fatura por si so nao e
                    // recebimento; so conta quando ha Fatura-Recibo.
                    $is_paid = isset($decoded['is_paid']) && (int) $decoded['is_paid'] === 1;

                    $written = $this->dps_moloni_model->update_sale_invoice(
                        (int) $decoded['sale_id'],
                        $is_paid && isset($decoded['net_value']) ? (float) $decoded['net_value'] : null,
                        $document_id
                    );
                } else {
                    $numbers = [];
                    foreach ($this->dps_moloni_model->links_for_sale((int) $decoded['sale_id']) as $link) {
                        if ($link['kind'] === 'invoice') {
                            continue;
                        }
                        if ($link['number'] !== '' && $link['number'] !== null) {
                            $numbers[] = $link['number'];
                        }
                    }

                    $written = $this->dps_moloni_model->update_sale_receipt(
                        (int) $decoded['sale_id'],
                        implode(' + ', array_unique($numbers)),
                        1
                    );
                }

                if (!$written) {
                    set_alert('warning', sprintf(_l('dps_moloni_write_back_failed'), (int) $decoded['sale_id']));
                }
            }

            $applied++;
        }

        set_alert('success', sprintf(_l('dps_moloni_reconciled_n'), $applied)
            . ($skipped > 0 ? ' ' . sprintf(_l('dps_moloni_skipped_n'), $skipped) : ''));

        redirect(admin_url('dps_moloni/conciliacao'));
    }

    /**
     * Comissoes de override (percentagem sobre a carteira da equipa).
     */
    public function overrides()
    {
        if ($this->input->post()) {
            $this->require_edit();

            $saved = $this->dps_moloni_model->save_override([
                'id'          => $this->input->post('id'),
                'beneficiary' => $this->input->post('beneficiary'),
                'rate'        => $this->input->post('rate'),
                'excluded'    => implode(', ', (array) $this->input->post('excluded')),
                'note'        => $this->input->post('note'),
                'active'      => $this->input->post('active'),
            ]);

            set_alert($saved ? 'success' : 'warning',
                $saved ? _l('dps_moloni_override_saved') : _l('dps_moloni_override_invalid'));

            redirect(admin_url('dps_moloni/overrides'));
        }

        $sales = $this->dps_moloni_model->get_sales();

        $data['overrides']   = $this->dps_moloni_model->get_overrides(false);
        $data['calculated']  = dps_moloni_overrides_calc($sales, $this->dps_moloni_model->get_overrides());
        $data['commercials'] = $this->dps_moloni_model->commercials();
        $data['title']       = _l('dps_moloni_overrides');

        $this->load->view('dps_moloni/overrides', $data);
    }

    public function remover_override($id)
    {
        $this->require_edit();

        $this->dps_moloni_model->delete_override($id);

        set_alert('success', _l('deleted'));

        redirect(admin_url('dps_moloni/overrides'));
    }

    /**
     * Repoe a comissao recebida a partir dos documentos pagos.
     */
    public function recalcular()
    {
        $this->require_edit();

        list($updated, $total) = $this->dps_moloni_model->recalc_received();

        set_alert('success', sprintf(_l('dps_moloni_recalc_done'), $updated, dps_moloni_money($total)));

        redirect(admin_url('dps_moloni'));
    }

    public function desligar($link_id)
    {
        $this->require_edit();

        $this->dps_moloni_model->delete_link($link_id);

        set_alert('success', _l('dps_moloni_link_removed'));

        redirect($this->input->server('HTTP_REFERER') ?: admin_url('dps_moloni'));
    }

    // -------------------------------------------------------------- emissao

    /**
     * Ecra de resumo antes de criar o documento na Moloni.
     *
     * $kind: 'invoice' (fatura ao promotor) ou 'receipt' (comissao do comercial)
     */
    public function emitir($kind, $sale_id)
    {
        $this->require_edit();

        $sale = $this->dps_moloni_model->get_sale($sale_id);

        if (!$sale) {
            set_alert('warning', _l('dps_moloni_sale_not_found'));

            redirect(admin_url('dps_moloni'));
        }

        $settings = $this->dps_moloni_model->get_settings();
        $api      = $this->api();

        $data['kind']     = $kind === 'invoice' ? 'invoice' : 'receipt';
        $data['sale']     = $sale;
        $data['settings'] = $settings;
        $data['amount']   = $data['kind'] === 'invoice'
            ? (float) $sale['received']
            : (float) $sale['commission'];
        $data['entity_name'] = $data['kind'] === 'invoice' ? $sale['client'] : $sale['commercial'];
        $data['sets']        = [];
        $data['taxes']       = [];
        $data['api_error']   = '';

        if ($api->is_configured() && $api->company_id()) {
            $sets  = $api->document_sets();
            $taxes = $api->taxes();

            $data['sets']  = is_array($sets) ? $sets : [];
            $data['taxes'] = is_array($taxes) ? $taxes : [];

            if ($sets === false) {
                $data['api_error'] = $api->last_error();
            }
        } else {
            $data['api_error'] = _l('dps_moloni_err_no_company');
        }

        $data['title'] = _l('dps_moloni_emitir');

        $this->load->view('dps_moloni/emitir', $data);
    }

    /**
     * Cria efectivamente o documento na Moloni (sempre rascunho quando a
     * definicao 'always_draft' esta activa).
     */
    public function emitir_confirmar()
    {
        $this->require_edit();

        $sale_id = (int) $this->input->post('sale_id');
        $kind    = $this->input->post('kind') === 'invoice' ? 'invoice' : 'receipt';

        $sale = $this->dps_moloni_model->get_sale($sale_id);

        if (!$sale) {
            set_alert('warning', _l('dps_moloni_sale_not_found'));

            redirect(admin_url('dps_moloni'));
        }

        $settings = $this->dps_moloni_model->get_settings();
        $api      = $this->api();

        if (!$api->is_configured() || !$api->company_id()) {
            set_alert('danger', _l('dps_moloni_err_no_company'));

            redirect(admin_url('dps_moloni/emitir/' . $kind . '/' . $sale_id));
        }

        $vat  = $this->dps_moloni_model->clean_vat($this->input->post('vat'));
        $name = trim((string) $this->input->post('entity_name'));

        $customer_id = $this->resolve_customer($vat, $name, $settings);

        if (!$customer_id) {
            set_alert('danger', _l('dps_moloni_customer_failed') . ' ' . $api->last_error());

            redirect(admin_url('dps_moloni/emitir/' . $kind . '/' . $sale_id));
        }

        $amount  = (float) str_replace(',', '.', $this->input->post('amount'));
        $set_id  = (int) $this->input->post('document_set_id');
        $tax_id  = (int) $this->input->post('tax_id');
        $date    = $this->input->post('date') ?: date('Y-m-d');
        $notes   = trim((string) $this->input->post('notes'));

        if ($amount <= 0) {
            set_alert('warning', _l('dps_moloni_invalid_amount'));

            redirect(admin_url('dps_moloni/emitir/' . $kind . '/' . $sale_id));
        }

        if (!$set_id) {
            set_alert('warning', _l('dps_moloni_no_set'));

            redirect(admin_url('dps_moloni/emitir/' . $kind . '/' . $sale_id));
        }

        // Rascunho por omissao. So emite fechado se a definicao permitir E
        // o utilizador tiver pedido explicitamente.
        $status = 0;
        if ($settings['always_draft'] !== '1' && $this->input->post('close_document')) {
            $status = 1;
        }

        $product_id = (int) ($settings['product_commission'] ?: 0);

        $line = [
            'name'  => $this->input->post('description') ?: _l('dps_moloni_default_line'),
            'qty'   => 1,
            'price' => round($amount, 2),
            'order' => 0,
        ];

        if ($product_id) {
            $line['product_id'] = $product_id;
        }

        if ($tax_id) {
            $line['taxes'] = [
                ['tax_id' => $tax_id, 'order' => 0, 'cumulative' => 0],
            ];
        } elseif ($settings['exemption_reason'] !== '') {
            $line['exemption_reason'] = $settings['exemption_reason'];
        }

        $payload = [
            'date'            => $date,
            'expiration_date' => $this->input->post('expiration_date') ?: $date,
            'document_set_id' => $set_id,
            'customer_id'     => $customer_id,
            'our_reference'   => 'DPS#' . $sale_id,
            'status'          => $status,
            'products'        => [$line],
        ];

        if ($notes !== '') {
            $payload['notes'] = $notes;
        }

        $class = $kind === 'invoice'
            ? ($settings['document_class_invoice'] ?: 'invoices')
            : ($settings['document_class_receipt'] ?: 'invoiceReceipts');

        $result = $api->document_insert($class, $payload);

        if ($result === false || empty($result['document_id'])) {
            set_alert('danger', _l('dps_moloni_issue_failed') . ' ' . $api->last_error());

            redirect(admin_url('dps_moloni/emitir/' . $kind . '/' . $sale_id));
        }

        $document_id = (int) $result['document_id'];

        // Vai buscar o documento criado para guardar numero e valores reais.
        $doc = $api->document_one($document_id);

        $this->dps_moloni_model->link_document([
            'sale_id'       => $sale_id,
            'kind'          => $kind,
            'document_id'   => $document_id,
            'document_type' => $class,
            'document_set'  => is_array($doc) && isset($doc['document_set']['name']) ? $doc['document_set']['name'] : '',
            'number'        => is_array($doc) && isset($doc['number']) ? $doc['number'] : '',
            'net_value'     => is_array($doc) && isset($doc['net_value']) ? (float) $doc['net_value'] : $amount,
            'total_value'   => is_array($doc) && isset($doc['gross_value']) ? (float) $doc['gross_value'] : $amount,
            'doc_date'      => $date,
            'status'        => $status,
            'source'        => 'emissao',
        ]);

        set_alert('success', $status === 0
            ? _l('dps_moloni_draft_created')
            : _l('dps_moloni_document_created'));

        redirect(admin_url('dps_moloni'));
    }

    /**
     * Encontra (ou cria) o customer_id da Moloni para um NIF.
     */
    protected function resolve_customer($vat, $name, $settings)
    {
        if ($vat === '') {
            return 0;
        }

        $cached = $this->dps_moloni_model->entity_by_vat($vat);

        if ($cached && !empty($cached['customer_id'])) {
            return (int) $cached['customer_id'];
        }

        $api   = $this->api();
        $found = $api->customer_by_vat($vat);

        if (!empty($found) && isset($found[0]['customer_id'])) {
            $this->dps_moloni_model->map_entity($vat, $found[0]['customer_id'],
                isset($found[0]['name']) ? $found[0]['name'] : $name);

            return (int) $found[0]['customer_id'];
        }

        if ($settings['auto_create_customers'] !== '1') {
            return 0;
        }

        $number = $api->next_customer_number();

        $created = $api->customer_insert([
            'vat'        => $vat,
            'number'     => $number ?: $vat,
            'name'       => $name !== '' ? $name : $vat,
            'language_id' => 1,
            'country_id'  => 1,
            'address'     => '.',
            'city'        => '.',
            'zip_code'    => '0000-000',
        ]);

        if ($created === false || empty($created['customer_id'])) {
            return 0;
        }

        $this->dps_moloni_model->map_entity($vat, $created['customer_id'], $name);

        return (int) $created['customer_id'];
    }

    // ------------------------------------------------------------- entidades

    public function entidades()
    {
        $data['entities']  = $this->dps_moloni_model->all_entities();
        $data['promoters'] = $this->dps_moloni_model->promoters();
        $data['projects']  = $this->dps_moloni_model->projects();
        $data['api_error'] = '';
        $data['title']     = _l('dps_moloni_entidades');

        $this->load->view('dps_moloni/entidades', $data);
    }

    /**
     * Liga um empreendimento ao NIF do promotor que o factura.
     */
    public function guardar_promotor()
    {
        $this->require_edit();

        $project = trim((string) $this->input->post('project'));
        $vat     = $this->input->post('promoter_vat');
        $name    = trim((string) $this->input->post('promoter_name'));

        if (!$this->dps_moloni_model->set_promoter($project, $vat, $name)) {
            set_alert('warning', _l('dps_moloni_invalid_vat'));
        } else {
            set_alert('success', _l('dps_moloni_promoter_saved'));
        }

        redirect(admin_url('dps_moloni/entidades'));
    }

    /**
     * Procura um NIF na Moloni e guarda o mapeamento.
     */
    public function sincronizar_entidade()
    {
        $this->require_edit();

        $vat  = $this->dps_moloni_model->clean_vat($this->input->post('vat'));
        $name = trim((string) $this->input->post('name'));

        if ($vat === '') {
            set_alert('warning', _l('dps_moloni_invalid_vat'));

            redirect(admin_url('dps_moloni/entidades'));
        }

        $api   = $this->api();
        $found = $api->customer_by_vat($vat);

        if (!empty($found) && isset($found[0]['customer_id'])) {
            $this->dps_moloni_model->map_entity($vat, $found[0]['customer_id'],
                isset($found[0]['name']) ? $found[0]['name'] : $name);

            set_alert('success', _l('dps_moloni_entity_linked') . ' #' . $found[0]['customer_id']);
        } else {
            set_alert('warning', _l('dps_moloni_entity_not_found'));
        }

        redirect(admin_url('dps_moloni/entidades'));
    }

    public function remover_entidade($id)
    {
        $this->require_edit();

        $this->db->where('id', (int) $id)->delete(db_prefix() . 'dps_moloni_entities');

        set_alert('success', _l('deleted'));

        redirect(admin_url('dps_moloni/entidades'));
    }

    // ------------------------------------------------------------------ logs

    public function logs()
    {
        $data['logs']  = $this->dps_moloni_model->get_logs(200);
        $data['title'] = _l('dps_moloni_logs');

        $this->load->view('dps_moloni/logs', $data);
    }

    public function limpar_logs()
    {
        $this->require_edit();

        $this->dps_moloni_model->clear_logs();

        set_alert('success', _l('dps_moloni_logs_cleared'));

        redirect(admin_url('dps_moloni/logs'));
    }

    /**
     * Redirecciona para o PDF do documento na Moloni.
     */
    public function pdf($document_id)
    {
        $url = $this->api()->document_pdf_link($document_id);

        if (!$url) {
            set_alert('warning', _l('dps_moloni_pdf_failed'));

            redirect($this->input->server('HTTP_REFERER') ?: admin_url('dps_moloni'));
        }

        redirect($url);
    }
}
