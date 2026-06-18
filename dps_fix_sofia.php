<?php
// Script de correção imediata do módulo dps_sofia_calls
// Corrige o erro 500 causado pela variável $agents não definida

$base = __DIR__ . '/modules/dps_sofia_calls';
$results = [];

// ── 1. CORRIGIR O CONTROLLER ─────────────────────────────────────────────────
$controller_file = $base . '/controllers/Dps_sofia_calls.php';
$controller_new = <<<'PHPEOF'
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
        $data['campaigns']     = $this->Dps_sofia_calls_model->get_campaigns(20);
        $this->load->view('dps_sofia_calls/sofia_calls/index', $data);
    }
    public function create_campaign()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $data = [
            'name'           => $this->input->post('name'),
            'lead_status_id' => (int) $this->input->post('lead_status_id'),
            'focus_text'     => $this->input->post('focus_text'),
        ];
        if (empty($data['name']) || empty($data['lead_status_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nome e estado obrigatórios']);
            exit;
        }
        $campaign_id = $this->Dps_sofia_calls_model->create_campaign($data);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'campaign_id' => $campaign_id]);
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
            echo json_encode(['success' => false]);
            exit;
        }
        $ok = $this->Dps_sofia_calls_model->update_campaign_status($id, $action);
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
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
}
PHPEOF;

if (file_put_contents($controller_file, $controller_new) !== false) {
    $results['controller'] = 'CORRIGIDO';
    if (function_exists('opcache_invalidate')) opcache_invalidate($controller_file, true);
} else {
    $results['controller'] = 'ERRO - sem permissão';
}

// ── 2. CORRIGIR A VIEW ───────────────────────────────────────────────────────
$view_file = $base . '/views/sofia_calls/index.php';
if (file_exists($view_file)) {
    $view = file_get_contents($view_file);
    if (strpos($view, 'foreach ($agents') !== false) {
        // Remover o bloco inteiro do select de agentes
        $view = preg_replace(
            '/<div class="form-group">\s*<label>Agente Sofia<\/label>.*?<\/div>\s*(?=<div class="form-group">)/s',
            '<input type="hidden" name="agent_id" value="agent_9901kv1pvewveh9s9ebs1rys274k">' . "\n",
            $view
        );
        if (file_put_contents($view_file, $view) !== false) {
            $results['view'] = 'CORRIGIDO - agents removido';
            if (function_exists('opcache_invalidate')) opcache_invalidate($view_file, true);
        } else {
            $results['view'] = 'ERRO - sem permissão';
        }
    } else {
        $results['view'] = 'OK - agents já não existe';
    }
} else {
    $results['view'] = 'ERRO - ficheiro não encontrado: ' . $view_file;
}

// ── 3. LIMPAR OPCACHE ────────────────────────────────────────────────────────
if (function_exists('opcache_reset')) {
    opcache_reset();
    $results['opcache'] = 'reset OK';
}

$results['module_dir_exists'] = is_dir($base) ? 'SIM' : 'NÃO - ' . $base;
$results['controller_exists'] = file_exists($controller_file) ? 'SIM' : 'NÃO';
$results['view_exists'] = file_exists($view_file) ? 'SIM' : 'NÃO';

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
