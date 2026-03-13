<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Testhelper extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Carrega o helper que tem a função list_folders()
        // Ajuste o nome caso seu arquivo seja "custom_files_helper.php"
        $this->load->helper('custom_files');
    }

    // Método de teste
    public function index()
    {
        // Teste a função list_folders()
        $folders = list_folders(FCPATH . 'uploads/');

        echo "<pre>";
        print_r($folders);
        echo "</pre>";
    }
}