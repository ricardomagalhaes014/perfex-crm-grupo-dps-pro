<?php
// Script de correção do módulo dps_sofia_calls
// Remove a dependência de $agents que causava o erro 500

$base = __DIR__ . '/modules/dps_sofia_calls';

// 1. Corrigir o controller - remover get_agents()
$controller = $base . '/controllers/Dps_sofia_calls.php';
$ctrl_content = '<?php
defined(\'BASEPATH\') or exit(\'No direct script access allowed\');
class Dps_sofia_calls extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(\'dps_sofia_calls/Dps_sofia_calls_model\');
    }
    public function index()
    {
        $data[\'title\']         = \'Sofia Calls\';
        $data[\'lead_statuses\'] = $this->Dps_sofia_calls_model->get_lead_statuses();
        $data[\'campaigns\']     = $this->Dps_sofia_calls_model->get_campaigns(20);
        $this->load->view(\'dps_sofia_calls/sofia_calls/index\', $data);
    }
    public function create_campaign()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $data = [
            \'name\'           => $this->input->post(\'name\'),
            \'lead_status_id\' => (int) $this->input->post(\'lead_status_id\'),
            \'focus_text\'     => $this->input->post(\'focus_text\'),
        ];
        if (empty($data[\'name\']) || empty($data[\'lead_status_id\'])) {
            header(\'Content-Type: application/json\');
            echo json_encode([\'success\' => false, \'message\' => \'Nome e estado obrigatórios\']);
            exit;
        }
        $campaign_id = $this->Dps_sofia_calls_model->create_campaign($data);
        header(\'Content-Type: application/json\');
        echo json_encode([\'success\' => true, \'campaign_id\' => $campaign_id]);
        exit;
    }
    public function campaign_action()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $id     = (int) $this->input->post(\'id\');
        $action = $this->input->post(\'action\');
        $allowed = [\'active\', \'paused\', \'stopped\'];
        if (!in_array($action, $allowed)) {
            header(\'Content-Type: application/json\');
            echo json_encode([\'success\' => false]);
            exit;
        }
        $ok = $this->Dps_sofia_calls_model->update_campaign_status($id, $action);
        header(\'Content-Type: application/json\');
        echo json_encode([\'success\' => $ok]);
        exit;
    }
    public function make_call()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $campaign_id = (int) $this->input->post(\'campaign_id\');
        $result      = $this->Dps_sofia_calls_model->make_immediate_call($campaign_id);
        header(\'Content-Type: application/json\');
        echo json_encode($result);
        exit;
    }
    public function campaign_detail()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $id    = (int) $this->input->post(\'id\');
        $stats = $this->Dps_sofia_calls_model->get_campaign_stats($id);
        $logs  = $this->Dps_sofia_calls_model->get_call_logs($id, 50);
        header(\'Content-Type: application/json\');
        echo json_encode([\'stats\' => $stats, \'logs\' => $logs]);
        exit;
    }
}
';

// 2. Corrigir a view - remover o foreach($agents)
$view_file = $base . '/views/sofia_calls/index.php';
$view_content = file_get_contents($view_file);

// Remover o bloco do select de agentes
$old_block = '                    <div class="form-group">
                        <label>Agente Sofia</label>
                        <select name="agent_id" class="form-control">
                            <?php foreach ($agents as $a): ?>
                            <option value="<?php echo $a[\'agent_id\']; ?>" <?php echo strpos($a[\'name\'], \'Outbound\') !== false ? \'selected\' : \'\'; ?>>
                                <?php echo htmlspecialchars($a[\'name\']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>';
$new_block = '                    <input type="hidden" name="agent_id" value="agent_9901kv1pvewveh9s9ebs1rys274k">';

$results = [];

// Escrever controller
if (file_put_contents($controller, $ctrl_content)) {
    $results['controller'] = 'OK';
    if (function_exists('opcache_invalidate')) opcache_invalidate($controller, true);
} else {
    $results['controller'] = 'ERRO: sem permissão de escrita';
}

// Corrigir view
if (strpos($view_content, 'foreach ($agents') !== false) {
    $new_view = str_replace($old_block, $new_block, $view_content);
    if (file_put_contents($view_file, $new_view)) {
        $results['view'] = 'OK - agents removido';
        if (function_exists('opcache_invalidate')) opcache_invalidate($view_file, true);
    } else {
        $results['view'] = 'ERRO: sem permissão de escrita';
    }
} else {
    $results['view'] = 'agents já não existe na view';
}

// Limpar opcache geral
if (function_exists('opcache_reset')) {
    opcache_reset();
    $results['opcache'] = 'reset OK';
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
