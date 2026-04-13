<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Promotores extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('promotores/promotores_model');
    }

    public function index()
    {
        if (!staff_can('view', 'promotores') && !staff_can('view_own', 'promotores') && !is_admin()) {
            access_denied('promotores');
        }

        $data['title'] = _l('promotores');
        $data['promotores'] = $this->promotores_model->get();
        $data['contact_phases'] = promotores_contact_phases();
        $data['districts'] = $this->promotores_model->get_districts();
        $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
        $this->load->view('promotores/manage', $data);
    }

    public function save($id = '')
    {
        if ($this->input->post()) {
            if ($id == '') {
                if (!staff_can('create', 'promotores') && !is_admin()) {
                    access_denied('promotores');
                }
                $insertId = $this->promotores_model->add($this->input->post());
                if ($insertId) {
                    set_alert('success', 'Promotor criado com sucesso.');
                }
            } else {
                if (!staff_can('edit', 'promotores') && !is_admin()) {
                    access_denied('promotores');
                }
                $updated = $this->promotores_model->update($this->input->post(), $id);
                if ($updated) {
                    set_alert('success', 'Promotor atualizado com sucesso.');
                }
            }
        }

        redirect(admin_url('promotores'));
    }

    public function delete($id)
    {
        if (!staff_can('delete', 'promotores') && !is_admin()) {
            access_denied('promotores');
        }

        $success = $this->promotores_model->delete($id);
        if ($success) {
            set_alert('success', 'Promotor removido com sucesso.');
        }

        redirect(admin_url('promotores'));
    }

    public function import()
    {
        if (!staff_can('create', 'promotores') && !staff_can('import', 'promotores') && !is_admin()) {
            access_denied('promotores');
        }

        if (!isset($_FILES['csv_file']) || empty($_FILES['csv_file']['tmp_name'])) {
            set_alert('warning', 'Selecione um ficheiro CSV.');
            redirect(admin_url('promotores'));
        }

        $result = $this->promotores_model->import_csv($_FILES['csv_file']['tmp_name']);
        set_alert('success', 'Importação concluída. Registos inseridos: ' . (int) $result['inserted']);

        if (!empty($result['errors'])) {
            set_alert('warning', implode(' | ', $result['errors']));
        }

        redirect(admin_url('promotores'));
    }

    public function seed()
    {
        if (!staff_can('create', 'promotores') && !staff_can('import', 'promotores') && !is_admin()) {
            access_denied('promotores');
        }

        $result = $this->promotores_model->import_csv(module_dir_path(PROMOTORES_MODULE_NAME, 'assets/data/promotores_seed.csv'), true);
        set_alert('success', 'Base inicial carregada. Novos registos inseridos: ' . (int) $result['inserted']);
        if (!empty($result['errors'])) {
            set_alert('warning', implode(' | ', $result['errors']));
        }
        redirect(admin_url('promotores'));
    }
}
