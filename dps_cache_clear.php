<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$results = [];

// Invalidar o imoveis-api.php especificamente
$file = __DIR__ . '/imoveis-api.php';
if (function_exists('opcache_invalidate')) {
    $results['opcache_invalidate_imoveis'] = opcache_invalidate($file, true) ? 'OK' : 'FAILED';
}
if (function_exists('opcache_reset')) {
    $results['opcache_reset'] = opcache_reset() ? 'OK' : 'FAILED';
}

// Verificar o conteúdo actual do imoveis-api.php (primeiras 500 chars)
$results['imoveis_api_preview'] = substr(file_get_contents($file), 0, 500);

// Verificar se a linha do LEFT JOIN ainda existe
$content = file_get_contents($file);
$results['has_left_join_customfields'] = strpos($content, 'customfieldsvalues') !== false ? 'SIM (versão antiga!)' : 'NÃO (versão nova OK)';
$results['has_landing_whatsapp'] = strpos($content, 'landing_whatsapp') !== false ? 'SIM (versão nova OK)' : 'NÃO';

$results['php_version'] = PHP_VERSION;
$results['file_mtime'] = date('Y-m-d H:i:s', filemtime($file));

// ===== STOP SOFIA CAMPAIGNS =====
if (isset($_GET['stop_sofia'])) {
    $db_config_file = __DIR__ . '/application/config/app.php';
    if (file_exists($db_config_file)) {
        $config_content = file_get_contents($db_config_file);
    }
    // Conectar à base de dados via ficheiro de configuração do Perfex
    $ci_config = __DIR__ . '/application/config/database.php';
    if (file_exists($ci_config)) {
        include_once($ci_config);
        $db_host = $db['default']['hostname'];
        $db_user = $db['default']['username'];
        $db_pass = $db['default']['password'];
        $db_name = $db['default']['database'];
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if (!$conn->connect_error) {
            $conn->query("UPDATE dps_sofia_campaigns SET status='stopped' WHERE status='active'");
            $results['sofia_stop'] = 'Todas as campanhas ativas paradas. Rows: ' . $conn->affected_rows;
            $conn->close();
        } else {
            $results['sofia_stop_error'] = $conn->connect_error;
        }
    }
}

// ===== FIX SOFIA MODULE FILES =====
if (isset($_GET['fix_sofia'])) {
    $module_dir = __DIR__ . '/modules/dps_sofia_calls';
    
    // Corrigir o controller
    $controller_content = '<?php defined("BASEPATH") or exit("No direct script access allowed");

class Dps_sofia_calls extends AdminController {
    public function __construct() {
        parent::__construct();
        $this->load->model("dps_sofia_calls_model");
    }

    public function index() {
        if (!is_admin()) { redirect(admin_url()); }
        $data["title"] = "Sofia Calls";
        $data["statuses"] = $this->db->get("leads_status")->result_array();
        $data["campaigns"] = $this->dps_sofia_calls_model->get_campaigns();
        $this->load->view("sofia_calls/index", $data);
    }

    public function create_campaign() {
        if (!$this->input->is_ajax_request()) { show_404(); }
        $name = $this->input->post("name");
        $status_id = $this->input->post("status_id");
        $focus = $this->input->post("focus");
        $staff_id = get_staff_user_id();
        if (!$name || !$status_id) {
            echo json_encode(["success" => false, "message" => "Campos obrigatórios em falta"]);
            return;
        }
        $result = $this->dps_sofia_calls_model->create_campaign($name, $status_id, $focus, $staff_id);
        echo json_encode($result);
    }

    public function stop_campaign($id) {
        if (!$this->input->is_ajax_request()) { show_404(); }
        $this->db->where("id", $id)->update("dps_sofia_campaigns", ["status" => "stopped"]);
        echo json_encode(["success" => true]);
    }

    public function pause_campaign($id) {
        if (!$this->input->is_ajax_request()) { show_404(); }
        $this->db->where("id", $id)->update("dps_sofia_campaigns", ["status" => "paused"]);
        echo json_encode(["success" => true]);
    }

    public function resume_campaign($id) {
        if (!$this->input->is_ajax_request()) { show_404(); }
        $this->db->where("id", $id)->update("dps_sofia_campaigns", ["status" => "active"]);
        echo json_encode(["success" => true]);
    }

    public function process_calls() {
        $this->dps_sofia_calls_model->process_pending_calls();
        echo json_encode(["success" => true]);
    }
}';
    file_put_contents($module_dir . '/controllers/Dps_sofia_calls.php', $controller_content);
    $results['fix_controller'] = 'OK';
    
    // Limpar opcache
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($module_dir . '/controllers/Dps_sofia_calls.php', true);
        opcache_invalidate($module_dir . '/models/Dps_sofia_calls_model.php', true);
        opcache_invalidate($module_dir . '/views/sofia_calls/index.php', true);
    }
    $results['fix_opcache'] = 'OK';
}

echo json_encode($results, JSON_PRETTY_PRINT);
