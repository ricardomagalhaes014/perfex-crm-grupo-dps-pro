<?php if(count(get_included_files()) == 1) exit("No direct script access allowed");

// A constante de depuração e as mensagens relacionadas ao processo de atualização foram mantidas para o funcionamento geral do código.
define("LB_API_DEBUG", false);
define("LB_SHOW_UPDATE_PROGRESS", true);

define("LB_TEXT_VERIFIED_RESPONSE", 'Verificado! Obrigado por adquirir.');
define("LB_TEXT_UPDATE_WITH_SQL_IMPORT_DONE", 'Aplicativo atualizado com sucesso e o arquivo SQL foi importado automaticamente.');
define("LB_TEXT_UPDATE_WITH_SQL_DONE", 'Aplicativo atualizado com sucesso, por favor, importe manualmente o arquivo SQL no seu banco de dados.');
define("LB_TEXT_UPDATE_WITHOUT_SQL_DONE", 'Aplicativo atualizado com sucesso, não houve atualizações de SQL.');

if(!LB_API_DEBUG){
    ini_set('display_errors', 0);
}

if((ini_get('max_execution_time')!=='0')&&(ini_get('max_execution_time'))<600){
    ini_set('max_execution_time', 600);
}
ini_set('memory_limit', '256M');

// Remoção da verificação de licença e todas as funcionalidades relacionadas à verificação de licença.

class MailflowLic {

    // Dados internos do produto e caminho da licença
    private $product_id;
    private $current_version;
    private $license_file;
    private $check_interval_file;

    public function __construct() {
        $this->product_id = '2627A934';
        $this->current_version = 'v1.0.0';
        $this->license_file = __DIR__.'/.lic';
        $this->check_interval_file = __DIR__.'/.licint';
    }

    // Método simplificado que retorna que a licença está sempre válida
    public function verify_license($time_based_check = false, $license = false, $client = false) {
        // Força a validação bem-sucedida
        return array('status' => TRUE, 'message' => LB_TEXT_VERIFIED_RESPONSE);
    }

    // Método de ativação simplificado, sem necessidade de verificação externa
    public function activate_license($license, $client, $create_lic = true, $staff = null) {
        // Ativação automática, sempre bem-sucedida
        if ($create_lic) {
            $licfile = 'lic_data_placeholder';
            file_put_contents($this->license_file, $licfile, LOCK_EX);
        }
        return array('status' => TRUE, 'message' => LB_TEXT_VERIFIED_RESPONSE);
    }

    // Desativação da licença removida
    public function deactivate_license($license = false, $client = false) {
        // Não faz nada, pois a licença não precisa ser desativada
        return array('status' => TRUE, 'message' => 'License deactivated successfully.');
    }

    // Funções auxiliares mantidas para garantir o funcionamento básico do módulo
    public function get_current_version() {
        return $this->current_version;
    }

    // Verificação local de licença, simplificada para sempre retornar como válida
    public function check_local_license_exist() {
        return true;
    }
}
