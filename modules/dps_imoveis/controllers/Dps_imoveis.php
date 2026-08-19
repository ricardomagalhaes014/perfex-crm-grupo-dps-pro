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

    /**
     * Verifica se o utilizador actual pode aprovar/rejeitar imóveis.
     * Apenas Super Admin ou quem tiver a capability 'approve'.
     */
    private function _pode_aprovar()
    {
        return is_admin() || has_permission('dps_imoveis', '', 'approve');
    }

    // ---------------------------------------------------------------
    // DASHBOARD / LISTAGEM  — acessível a todos os staff autenticados
    // ---------------------------------------------------------------
    public function index()
    {
        $filters = [
            'status'    => $this->input->get('status'),
            'tipo'      => $this->input->get('tipo'),
            'distrito'  => $this->input->get('distrito'),
            'agente_id' => $this->input->get('agente_id'),
        ];

        $data['imoveis']      = $this->dps_imoveis_model->get_all($filters);
        $data['stats']        = $this->dps_imoveis_model->get_stats();
        $data['agentes']      = $this->_get_agentes();
        $data['filters']      = $filters;
        $data['pode_aprovar'] = $this->_pode_aprovar();
        $data['title']        = 'DPS Imóveis';
        $data['bodyclass']    = 'dps-imoveis-page';

        $this->load->view('dps_imoveis/imoveis/index', $data);
    }

    // ---------------------------------------------------------------
    // CRIAR IMÓVEL  — acessível a todos os staff autenticados
    // ---------------------------------------------------------------
    public function novo()
    {
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
    // EDITAR IMÓVEL  — qualquer staff pode editar; não-admin só edita os seus
    // ---------------------------------------------------------------
    public function editar($id)
    {
        $imovel = $this->dps_imoveis_model->get_by_id($id);
        if (!$imovel) {
            show_404();
        }

        if (!is_admin() && !$this->_pode_aprovar()) {
            if ($imovel['agente_id'] != get_staff_user_id()) {
                set_alert('danger', 'Só pode editar os seus próprios imóveis.');
                redirect(admin_url('dps_imoveis'));
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
    // DETALHE  — acessível a todos os staff autenticados
    // ---------------------------------------------------------------
    public function detalhe($id)
    {
        $imovel = $this->dps_imoveis_model->get_by_id($id);
        if (!$imovel) {
            show_404();
        }

        $data['imovel']       = $imovel;
        $data['is_admin']     = is_admin();
        $data['pode_aprovar'] = $this->_pode_aprovar();
        $data['title']        = $imovel['titulo'];
        $data['bodyclass']    = 'dps-imoveis-page';
        $this->load->view('dps_imoveis/imoveis/detalhe', $data);
    }

    // ---------------------------------------------------------------
    // APAGAR  — apenas admin ou aprovador
    // ---------------------------------------------------------------
    public function apagar($id)
    {
        if (!$this->_pode_aprovar()) {
            set_alert('danger', 'Sem permissão para apagar imóveis.');
            redirect(admin_url('dps_imoveis'));
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
    // APROVAÇÃO  — apenas Super Admin ou Responsável de Área (approve)
    // ---------------------------------------------------------------
    public function aprovar($id)
    {
        if (!$this->_pode_aprovar()) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['success' => false, 'message' => 'Sem permissão para aprovar imóveis.']);
                return;
            }
            set_alert('danger', 'Sem permissão para aprovar imóveis.');
            redirect(admin_url('dps_imoveis/detalhe/' . $id));
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
        if (!$this->_pode_aprovar()) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['success' => false, 'message' => 'Sem permissão para rejeitar imóveis.']);
                return;
            }
            set_alert('danger', 'Sem permissão para rejeitar imóveis.');
            redirect(admin_url('dps_imoveis/detalhe/' . $id));
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
        if (!$this->_pode_aprovar()) {
            set_alert('danger', 'Sem permissão para despublicar imóveis.');
            redirect(admin_url('dps_imoveis/detalhe/' . $id));
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
        $imovel = $this->dps_imoveis_model->get_by_id($id);
        if (!$imovel) {
            echo json_encode(['success' => false, 'message' => 'Imóvel não encontrado']);
            return;
        }

        if (!is_admin() && !$this->_pode_aprovar()) {
            if ($imovel['agente_id'] != get_staff_user_id()) {
                echo json_encode(['success' => false, 'message' => 'Sem permissão']);
                return;
            }
        }

        $foto = $this->input->post('foto');
        $ok = $this->dps_imoveis_model->remover_foto($id, $foto);
        echo json_encode(['success' => $ok]);
    }

    // ---------------------------------------------------------------
    // NECESSIDADES — aba interna, sem aprovação, sem publicação no site
    // ---------------------------------------------------------------

    /**
     * Listagem de necessidades
     */
    public function necessidades()
    {
        $data['necessidades'] = $this->dps_imoveis_model->get_necessidades();
        $data['title']        = 'Necessidades de Clientes';
        $data['bodyclass']    = 'dps-imoveis-page';
        $this->load->view('dps_imoveis/necessidades/index', $data);
    }

    /**
     * Formulário de nova necessidade
     */
    public function nova_necessidade()
    {
        if ($this->input->post()) {
            $post = $this->input->post();
            $id = $this->dps_imoveis_model->create_necessidade($post);
            if ($id) {
                set_alert('success', 'Necessidade registada com sucesso!');
                redirect(admin_url('dps_imoveis/necessidades'));
            } else {
                set_alert('danger', 'Erro ao registar a necessidade. Tente novamente.');
            }
        }

        /*
         * Vindo de uma lead (?lead=123), o formulário abre com o cliente já
         * escrito. Quem acabou de pôr a lead em NECESSIDADES não tem de voltar
         * a copiar o nome, o telefone e o email de uma ficha que acabou de ver.
         */
        $data['necessidade'] = null;
        $lead_id = (int) $this->input->get('lead');

        if ($lead_id > 0) {
            $lead = $this->db->select('name, phonenumber, email')
                             ->where('id', $lead_id)
                             ->get(db_prefix() . 'leads')->row_array();

            if ($lead) {
                $data['necessidade'] = [
                    'id'               => '',
                    'nome_cliente'     => $lead['name'] ?? '',
                    'contacto_cliente' => $lead['phonenumber'] ?? '',
                    'email_cliente'    => $lead['email'] ?? '',
                ];
                $data['lead_id'] = $lead_id;
            }
        }

        $data['title']       = 'Nova Necessidade';
        $data['bodyclass']   = 'dps-imoveis-page';
        $this->load->view('dps_imoveis/necessidades/form', $data);
    }

    /**
     * Formulário de edição de necessidade
     */
    public function editar_necessidade($id)
    {
        $necessidade = $this->dps_imoveis_model->get_necessidade_by_id($id);
        if (!$necessidade) {
            show_404();
        }

        // Não-admin só pode editar as suas próprias necessidades
        if (!is_admin() && !$this->_pode_aprovar()) {
            if ($necessidade['staff_id'] != get_staff_user_id()) {
                set_alert('danger', 'Só pode editar as suas próprias necessidades.');
                redirect(admin_url('dps_imoveis/necessidades'));
            }
        }

        if ($this->input->post()) {
            $post = $this->input->post();
            $ok = $this->dps_imoveis_model->update_necessidade($id, $post);
            if ($ok) {
                set_alert('success', 'Necessidade actualizada com sucesso!');
                redirect(admin_url('dps_imoveis/necessidades'));
            } else {
                set_alert('danger', 'Erro ao actualizar a necessidade.');
            }
        }

        $data['necessidade'] = $necessidade;
        $data['title']       = 'Editar Necessidade';
        $data['bodyclass']   = 'dps-imoveis-page';
        $this->load->view('dps_imoveis/necessidades/form', $data);
    }

    /**
     * Apagar necessidade — apenas admin
     */
    public function apagar_necessidade($id)
    {
        if (!is_admin()) {
            set_alert('danger', 'Apenas o administrador pode apagar necessidades.');
            redirect(admin_url('dps_imoveis/necessidades'));
        }

        $ok = $this->dps_imoveis_model->delete_necessidade($id);
        if ($ok) {
            set_alert('success', 'Necessidade apagada com sucesso.');
        } else {
            set_alert('danger', 'Erro ao apagar a necessidade.');
        }
        redirect(admin_url('dps_imoveis/necessidades'));
    }

    /**
     * Guardar necessidade (POST handler para novo e editar)
     */
    public function guardar_necessidade()
    {
        $id = $this->input->post('id');
        $post = $this->input->post();

        if ($id) {
            $necessidade = $this->dps_imoveis_model->get_necessidade_by_id($id);
            if (!$necessidade) { show_404(); }

            if (!is_admin() && !$this->_pode_aprovar()) {
                if ($necessidade['staff_id'] != get_staff_user_id()) {
                    set_alert('danger', 'Sem permissão.');
                    redirect(admin_url('dps_imoveis/necessidades'));
                }
            }

            $ok = $this->dps_imoveis_model->update_necessidade($id, $post);
            if ($ok) {
                set_alert('success', 'Necessidade actualizada com sucesso!');
            } else {
                set_alert('danger', 'Erro ao actualizar a necessidade.');
            }
        } else {
            $new_id = $this->dps_imoveis_model->create_necessidade($post);
            if ($new_id) {
                set_alert('success', 'Necessidade registada com sucesso!');
            } else {
                set_alert('danger', 'Erro ao registar a necessidade.');
            }
        }

        redirect(admin_url('dps_imoveis/necessidades'));
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

        foreach ($imoveis as &$i) {
            $base = base_url();
            if (!empty($i['foto_principal'])) {
                $i['foto_principal_url'] = $base . $i['foto_principal'];
            }
            if (!empty($i['fotos'])) {
                $fotos_arr = json_decode($i['fotos'], true) ?: [];
                $i['fotos_urls'] = array_map(function($f) use ($base) { return $base . $f; }, $fotos_arr);
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
