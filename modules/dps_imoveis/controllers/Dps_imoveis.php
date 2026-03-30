<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_imoveis extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_imoveis/dps_imoveis_model');
        $this->lang->load('dps_imoveis', 'portuguese');
    }

    // ---------------------------------------------------------------
    // DASHBOARD / LISTAGEM
    // ---------------------------------------------------------------
    public function index()
    {
        if (!has_permission('dps_imoveis', '', 'view') && !is_admin()) {
            access_denied('dps_imoveis');
        }

        $filters = [
            'status'    => $this->input->get('status'),
            'tipo'      => $this->input->get('tipo'),
            'distrito'  => $this->input->get('distrito'),
            'agente_id' => $this->input->get('agente_id'),
        ];

        $data['imoveis']    = $this->dps_imoveis_model->get_all($filters);
        $data['stats']      = $this->dps_imoveis_model->get_stats();
        $data['agentes']    = $this->_get_agentes();
        $data['filters']    = $filters;
        $data['title']      = 'DPS Imóveis';
        $data['bodyclass']  = 'dps-imoveis-page';

        $this->load->view('dps_imoveis/imoveis/index', $data);
    }

    // ---------------------------------------------------------------
    // CRIAR IMÓVEL
    // ---------------------------------------------------------------
    public function novo()
    {
        if (!has_permission('dps_imoveis', '', 'create') && !is_admin()) {
            access_denied('dps_imoveis');
        }

        if ($this->input->post()) {
            $post = $this->input->post();
            $id = $this->dps_imoveis_model->create($post);
            if ($id) {
                set_alert('success', 'Imóvel registado com sucesso! Aguarda aprovação do gestor.');
                redirect(admin_url('dps_imoveis/detalhe/' . $id));
            } else {
                set_alert('danger', 'Erro ao registar o imóvel. Tente novamente.');
            }
        }

        $data['agentes']   = $this->_get_agentes();
        $data['title']     = 'Novo Imóvel';
        $data['bodyclass'] = 'dps-imoveis-page';
        $this->load->view('dps_imoveis/imoveis/form', $data);
    }

    // ---------------------------------------------------------------
    // EDITAR IMÓVEL
    // ---------------------------------------------------------------
    public function editar($id)
    {
        if (!has_permission('dps_imoveis', '', 'edit') && !is_admin()) {
            access_denied('dps_imoveis');
        }

        $imovel = $this->dps_imoveis_model->get_by_id($id);
        if (!$imovel) {
            show_404();
        }

        // Comercial só edita os seus
        if (!is_admin() && !has_permission('dps_imoveis', '', 'view_all')) {
            if ($imovel['agente_id'] != get_staff_user_id()) {
                access_denied('dps_imoveis');
            }
        }

        if ($this->input->post()) {
            $post = $this->input->post();
            $ok = $this->dps_imoveis_model->update($id, $post);
            if ($ok) {
                set_alert('success', 'Imóvel actualizado com sucesso!');
                redirect(admin_url('dps_imoveis/detalhe/' . $id));
            } else {
                set_alert('danger', 'Erro ao actualizar o imóvel.');
            }
        }

        $data['imovel']    = $imovel;
        $data['agentes']   = $this->_get_agentes();
        $data['title']     = 'Editar Imóvel: ' . $imovel['titulo'];
        $data['bodyclass'] = 'dps-imoveis-page';
        $this->load->view('dps_imoveis/imoveis/form', $data);
    }

    // ---------------------------------------------------------------
    // DETALHE
    // ---------------------------------------------------------------
    public function detalhe($id)
    {
        if (!has_permission('dps_imoveis', '', 'view') && !is_admin()) {
            access_denied('dps_imoveis');
        }

        $imovel = $this->dps_imoveis_model->get_by_id($id);
        if (!$imovel) {
            show_404();
        }

        $data['imovel']    = $imovel;
        $data['is_admin']  = is_admin();
        $data['pode_aprovar'] = is_admin() || has_permission('dps_imoveis', '', 'edit');
        $data['title']     = $imovel['titulo'];
        $data['bodyclass'] = 'dps-imoveis-page';
        $this->load->view('dps_imoveis/imoveis/detalhe', $data);
    }

    // ---------------------------------------------------------------
    // APAGAR
    // ---------------------------------------------------------------
    public function apagar($id)
    {
        if (!has_permission('dps_imoveis', '', 'delete') && !is_admin()) {
            access_denied('dps_imoveis');
        }

        $ok = $this->dps_imoveis_model->delete($id);
        if ($ok) {
            set_alert('success', 'Imóvel apagado com sucesso.');
        } else {
            set_alert('danger', 'Erro ao apagar o imóvel.');
        }
        redirect(admin_url('dps_imoveis'));
    }

    // ---------------------------------------------------------------
    // APROVAÇÃO
    // ---------------------------------------------------------------
    public function aprovar($id)
    {
        if (!is_admin() && !has_permission('dps_imoveis', '', 'edit')) {
            access_denied('dps_imoveis');
        }

        $notas = $this->input->post('notas_aprovacao') ?: '';
        $ok = $this->dps_imoveis_model->aprovar($id, $notas);

        if ($this->input->is_ajax_request()) {
            echo json_encode(['success' => $ok]);
            return;
        }

        if ($ok) {
            set_alert('success', 'Imóvel aprovado e publicado no site!');
        } else {
            set_alert('danger', 'Erro ao aprovar o imóvel.');
        }
        redirect(admin_url('dps_imoveis/detalhe/' . $id));
    }

    public function rejeitar($id)
    {
        if (!is_admin() && !has_permission('dps_imoveis', '', 'edit')) {
            access_denied('dps_imoveis');
        }

        $notas = $this->input->post('notas_aprovacao') ?: '';
        $ok = $this->dps_imoveis_model->rejeitar($id, $notas);

        if ($this->input->is_ajax_request()) {
            echo json_encode(['success' => $ok]);
            return;
        }

        if ($ok) {
            set_alert('warning', 'Imóvel rejeitado.');
        } else {
            set_alert('danger', 'Erro ao rejeitar o imóvel.');
        }
        redirect(admin_url('dps_imoveis/detalhe/' . $id));
    }

    public function despublicar($id)
    {
        if (!is_admin() && !has_permission('dps_imoveis', '', 'edit')) {
            access_denied('dps_imoveis');
        }

        $ok = $this->dps_imoveis_model->despublicar($id);
        if ($ok) {
            set_alert('warning', 'Imóvel retirado do site.');
        }
        redirect(admin_url('dps_imoveis/detalhe/' . $id));
    }

    // ---------------------------------------------------------------
    // REMOVER FOTO (AJAX)
    // ---------------------------------------------------------------
    public function remover_foto($id)
    {
        if (!has_permission('dps_imoveis', '', 'edit') && !is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return;
        }

        $foto = $this->input->post('foto');
        $ok = $this->dps_imoveis_model->remover_foto($id, $foto);
        echo json_encode(['success' => $ok]);
    }

    // ---------------------------------------------------------------
    // API INTERNA (para imoveis-api.php)
    // ---------------------------------------------------------------
    public function api_imoveis()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $filters = [
            'tipo'      => $this->input->get('tipo'),
            'distrito'  => $this->input->get('distrito'),
            'tipologia' => $this->input->get('tipologia'),
        ];

        $imoveis = $this->dps_imoveis_model->get_for_api($filters);

        // Formatar fotos
        foreach ($imoveis as &$i) {
            $base = base_url();
            if (!empty($i['foto_principal'])) {
                $i['foto_principal_url'] = $base . $i['foto_principal'];
            }
            if (!empty($i['fotos'])) {
                $fotos_arr = json_decode($i['fotos'], true) ?: [];
                $i['fotos_urls'] = array_map(fn($f) => $base . $f, $fotos_arr);
            } else {
                $i['fotos_urls'] = [];
            }
            if (!empty($i['agente_foto'])) {
                $i['agente_foto_url'] = $base . 'uploads/staff_profile_images/' . $i['agente_foto'];
            }
            unset($i['fotos']);
        }

        echo json_encode(['success' => true, 'data' => $imoveis, 'total' => count($imoveis)]);
    }

    // ---------------------------------------------------------------
    // HELPER PRIVADO
    // ---------------------------------------------------------------
    private function _get_agentes()
    {
        $this->db->select('staffid, firstname, lastname, email, phonenumber');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'ASC');
        return $this->db->get(db_prefix() . 'staff')->result_array();
    }
}
