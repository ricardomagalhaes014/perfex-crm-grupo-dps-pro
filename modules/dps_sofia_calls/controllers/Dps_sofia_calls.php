<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_sofia_calls extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_sofia_calls/Dps_sofia_calls_model');
    }

    public function index()
    {
        $data['title']         = 'Sofia Calls';
        $data['lead_statuses'] = $this->Dps_sofia_calls_model->get_lead_statuses();
        $data['staff_list']    = $this->Dps_sofia_calls_model->get_staff_list();
        $data['campaigns']     = $this->Dps_sofia_calls_model->get_campaigns(20);
        
        /*
         * A lista de agentes vem da ElevenLabs, não de uma lista escrita à mão.
         *
         * Estava cravada aqui com nove agentes. Oito deles já não existem
         * nesta conta (dão 404) e o "Boavista Tower", que existe, não estava na
         * lista — por isso não havia forma de escolher o Boavista numa
         * campanha. Uma lista escrita à mão fica desactualizada no dia em que
         * alguém cria ou apaga um agente, e ninguém se lembra de a vir corrigir.
         *
         * Se a API não responder, mostra-se o que estiver guardado da última
         * vez: melhor uma lista de ontem do que um ecrã sem opções.
         */
        $data['agents_list'] = $this->agentes_elevenlabs();
        
        $this->load->view('dps_sofia_calls/sofia_calls/index', $data);
    }

    public function create_campaign()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $data = [
            'name'           => $this->input->post('name'),
            'lead_status_id' => (int) $this->input->post('lead_status_id'),
            'staff_id'       => (int) $this->input->post('staff_id'),
            'focus_text'     => $this->input->post('focus_text'),
            'agent_id'       => $this->input->post('agent_id'),
        ];

        if (empty($data['name']) || empty($data['lead_status_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nome e estado obrigatorios']);
            exit;
        }

        $campaign_id = $this->Dps_sofia_calls_model->create_campaign($data);

        header('Content-Type: application/json');
        echo json_encode([
            'success'     => true,
            'campaign_id' => $campaign_id,
            'message'     => 'Campanha criada em estado pausado. Clique em Iniciar quando quiser comecar.',
        ]);
        exit;
    }

    public function campaign_action()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id     = (int) $this->input->post('id');
        $action = $this->input->post('action');

        $allowed = ['active', 'paused', 'stopped'];
        if (!in_array($action, $allowed)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acao invalida']);
            exit;
        }

        $ok = $this->Dps_sofia_calls_model->update_campaign_status($id, $action);

        // Quando inicia a campanha, disparar imediatamente a primeira chamada
        $call_result = null;
        if ($action === 'active' && $ok) {
            $call_result = $this->Dps_sofia_calls_model->make_immediate_call($id);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'call' => $call_result]);
        exit;
    }

    public function make_call()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $campaign_id = (int) $this->input->post('campaign_id');
        $result      = $this->Dps_sofia_calls_model->make_immediate_call($campaign_id);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function update_campaign()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id = (int) $this->input->post('id');
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID invalido']);
            exit;
        }

        $data = [
            'name'           => $this->input->post('name'),
            'lead_status_id' => (int) $this->input->post('lead_status_id'),
            'staff_id'       => (int) $this->input->post('staff_id'),
            'focus_text'     => $this->input->post('focus_text'),
            'agent_id'       => $this->input->post('agent_id'),
        ];

        if (empty($data['name']) || empty($data['lead_status_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nome e estado obrigatorios']);
            exit;
        }

        $ok = $this->Dps_sofia_calls_model->update_campaign($id, $data);

        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
        exit;
    }

    public function delete_campaign()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id = (int) $this->input->post('id');
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
            exit;
        }

        $ok = $this->Dps_sofia_calls_model->delete_campaign($id);

        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
        exit;
    }

    public function campaign_detail()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id    = (int) $this->input->post('id');
        $stats = $this->Dps_sofia_calls_model->get_campaign_stats($id);
        $logs  = $this->Dps_sofia_calls_model->get_call_logs($id, 50);

        header('Content-Type: application/json');
        echo json_encode(['stats' => $stats, 'logs' => $logs]);
        exit;
    }

    /**
     * Endpoint de diagnóstico — acessível via GET:
     * https://crm.grupo-dps.com/admin/dps_sofia_calls/diag
     * https://crm.grupo-dps.com/admin/dps_sofia_calls/diag?run=1  (executa process_pending_calls)
     */
    public function diag()
    {
        // Apenas admins
        if (!is_admin()) show_404();

        $api_key = (string) get_option('sofia_calls_elevenlabs_api_key');
        $out     = [];

        // Chamadas em 'calling'
        $this->db->where('status', 'calling');
        $this->db->select('id, campaign_id, lead_name, phone_number, elevenlabs_call_id, started_at,
            TIMESTAMPDIFF(SECOND, started_at, NOW()) as elapsed_secs');
        $calling = $this->db->get(db_prefix() . 'dps_sofia_call_logs')->result_array();
        $out['calling_count'] = count($calling);
        $out['calling_calls'] = $calling;

        // Campanhas ativas
        $this->db->where('status', 'active');
        $out['active_campaigns'] = $this->db->get(db_prefix() . 'dps_sofia_campaigns')->result_array();

        // Verificar hook cron
        $this->db->where('hook_name', 'perfex_cron');
        $out['cron_hooks'] = $this->db->get(db_prefix() . 'hooks')->result_array();

        // Testar ElevenLabs para a primeira chamada presa
        if (!empty($calling) && !empty($calling[0]['elevenlabs_call_id'])) {
            $conv_id = $calling[0]['elevenlabs_call_id'];
            $ch = curl_init('https://api.elevenlabs.io/v1/convai/conversations/' . $conv_id);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['xi-api-key: ' . $api_key],
                CURLOPT_TIMEOUT        => 10,
            ]);
            $resp = curl_exec($ch);
            $err  = curl_error($ch);
            curl_close($ch);
            $out['elevenlabs_test'] = [
                'conv_id'    => $conv_id,
                'response'   => $resp ? json_decode($resp, true) : null,
                'curl_error' => $err ?: null,
            ];
        }

        // Executar process_pending_calls se ?run=1
        if ($this->input->get('run') == '1') {
            $out['process_result'] = 'A executar process_pending_calls...';
            $this->Dps_sofia_calls_model->process_pending_calls();
            $out['process_result'] = 'Concluido. Verifique os logs.';

            // Estado após execução
            $this->db->where('status', 'calling');
            $out['calling_after'] = $this->db->count_all_results(db_prefix() . 'dps_sofia_call_logs');
        }

        header('Content-Type: application/json');
        echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Onde a chave da ElevenLabs se escreve.
     *
     * Existe porque a chave estava cravada no código — e código vai para o
     * repositório. Aqui fica na base de dados, e revogá-la na ElevenLabs passa
     * a ser suficiente: não sobra nenhuma cópia em ficheiro.
     *
     * O campo nunca devolve o que lá está: mostra-se só o tamanho e os últimos
     * caracteres, o bastante para se reconhecer qual é sem a expor no ecrã.
     */
    public function definicoes()
    {
        if (!is_admin()) {
            access_denied('dps_sofia_calls');
        }

        if ($this->input->method(true) === 'POST') {
            $nova = trim((string) $this->input->post('api_key', false));

            // Em branco = não mexer. Sem isto, gravar o formulário para mudar
            // só o número de telefone apagava a chave.
            if ($nova !== '') {
                update_option('sofia_calls_elevenlabs_api_key', $nova);
                log_activity('Sofia Calls: chave da ElevenLabs substituída');
            }

            update_option('sofia_calls_phone_number_id', trim((string) $this->input->post('phone_number_id')));

            $sim = (int) $this->input->post('simultaneas');
            update_option('sofia_calls_simultaneas', (string) max(1, min(10, $sim ?: 3)));
            set_alert('success', 'Definições guardadas.');
            redirect(admin_url('dps_sofia_calls/definicoes'));
        }

        $chave = (string) get_option('sofia_calls_elevenlabs_api_key');

        $data['chave_definida']  = $chave !== '';
        $data['chave_tamanho']   = strlen($chave);
        $data['chave_fim']       = $chave !== '' ? substr($chave, -4) : '';
        $data['phone_number_id'] = (string) get_option('sofia_calls_phone_number_id');
        $data['simultaneas']     = (int) (get_option('sofia_calls_simultaneas') ?: 3);
        $data['title']           = 'Sofia Calls — Definições';

        $this->load->view('definicoes', $data);
    }


    /**
     * Agentes da conta ElevenLabs, com a chave gravada em Definições.
     *
     * Guardados numa opção para o ecrã não depender de uma chamada externa a
     * cada abertura — e para continuar a funcionar se a ElevenLabs estiver em
     * baixo.
     */
    private function agentes_elevenlabs()
    {
        $chave = (string) get_option('sofia_calls_elevenlabs_api_key');
        $cache = json_decode((string) get_option('sofia_calls_agentes_cache'), true);
        $cache = is_array($cache) ? $cache : [];

        if ($chave === '') {
            return $cache;
        }

        $ch = curl_init('https://api.elevenlabs.io/v1/convai/agents?page_size=100');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_HTTPHEADER     => ['xi-api-key: ' . $chave],
        ]);
        $raw  = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http !== 200) {
            log_activity('Sofia Calls: nao consegui listar agentes na ElevenLabs (HTTP ' . $http . ')');
            return $cache;
        }

        $j     = json_decode((string) $raw, true);
        $lista = [];
        foreach (($j['agents'] ?? []) as $a) {
            if (empty($a['agent_id'])) {
                continue;
            }
            $lista[] = [
                'agent_id' => $a['agent_id'],
                'name'     => (string) ($a['name'] ?? $a['agent_id']),
            ];
        }

        if (! $lista) {
            return $cache;
        }

        usort($lista, function ($x, $y) {
            return strcasecmp($x['name'], $y['name']);
        });

        update_option('sofia_calls_agentes_cache', json_encode($lista, JSON_UNESCAPED_UNICODE));

        return $lista;
    }


    /**
     * Relatório das chamadas: quem disse sim, quem disse não, quem não atendeu.
     *
     * O resultado é escrito pelo webhook de fim de chamada (sofia-webhook.php),
     * que liga a conversa do ElevenLabs ao registo da campanha pelo
     * conversation_id. Antes disto a tabela sabia se a chamada tinha sido
     * atendida, mas não o que a pessoa respondeu — e sem isso não havia
     * relatório nenhum para mostrar ao fim do dia.
     */
    public function relatorio()
    {
        if (!is_admin() && !is_staff_member()) {
            access_denied('dps_sofia_calls');
        }

        $campanha = (int) $this->input->get('campanha');

        $this->db->select('c.id, c.name');
        $this->db->order_by('c.id', 'DESC');
        $data['campanhas'] = $this->db->get(db_prefix() . 'dps_sofia_campaigns c')->result_array();

        // Contagem por resultado. Só conta o que já foi marcado ou tentado —
        // as pendentes ainda não são chamadas feitas.
        $this->db->select("COALESCE(NULLIF(l.resultado, ''), '') AS r, COUNT(*) AS n", false);
        $this->db->from(db_prefix() . 'dps_sofia_call_logs l');
        $this->db->where_not_in('l.status', ['pending']);
        if ($campanha > 0) {
            $this->db->where('l.campaign_id', $campanha);
        }
        $this->db->group_by("COALESCE(NULLIF(l.resultado, ''), '')", false);

        $contagem = [];
        $total    = 0;
        foreach ($this->db->get()->result_array() as $linha) {
            $contagem[$linha['r']] = (int) $linha['n'];
            $total += (int) $linha['n'];
        }

        $this->db->select('l.*, c.name AS campanha');
        $this->db->from(db_prefix() . 'dps_sofia_call_logs l');
        $this->db->join(db_prefix() . 'dps_sofia_campaigns c', 'c.id = l.campaign_id', 'left');
        $this->db->where_not_in('l.status', ['pending']);
        if ($campanha > 0) {
            $this->db->where('l.campaign_id', $campanha);
        }
        $this->db->order_by('l.started_at', 'DESC');
        $this->db->limit(500);

        $data['linhas']   = $this->db->get()->result_array();
        $data['contagem'] = $contagem;
        $data['total']    = $total;
        $data['campanha'] = $campanha;
        $data['title']    = 'Resultados das chamadas';

        $this->load->view('relatorio', $data);
    }

}
