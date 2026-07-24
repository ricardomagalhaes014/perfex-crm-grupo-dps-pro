<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Gestão das notícias/informações do rodapé (ticker).
 * Só admins gerem; o rodapé em si aparece a todos os utilizadores.
 */
class Dps_ticker extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_admin()) {
            access_denied('Notícias do Rodapé');
        }

        $this->ensure_table();
    }

    private function ensure_table()
    {
        $t = db_prefix() . 'dps_ticker';
        if (!$this->db->table_exists($t)) {
            $this->db->query("CREATE TABLE `{$t}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `mensagem` TEXT NOT NULL,
                `ativo` TINYINT(1) NOT NULL DEFAULT 1,
                `addedfrom` INT NULL,
                `dateadded` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function index()
    {
        if ($this->input->post()) {
            $mensagem = trim((string) $this->input->post('mensagem'));
            if ($mensagem !== '') {
                $this->db->insert(db_prefix() . 'dps_ticker', [
                    'mensagem'  => $mensagem,
                    'ativo'     => 1,
                    'addedfrom' => get_staff_user_id(),
                    'dateadded' => date('Y-m-d H:i:s'),
                ]);
                set_alert('success', 'Notícia adicionada ao rodapé.');
            }
            redirect(admin_url('dps_ticker'));
        }

        $data['mensagens'] = $this->db
            ->order_by('id', 'DESC')
            ->get(db_prefix() . 'dps_ticker')
            ->result_array();
        $data['title'] = 'Notícias do Rodapé';

        $this->load->view('admin/dps_ticker/manage', $data);
    }

    public function toggle($id)
    {
        $this->db->query(
            'UPDATE `' . db_prefix() . 'dps_ticker` SET ativo = 1 - ativo WHERE id = ?',
            [(int) $id]
        );
        redirect(admin_url('dps_ticker'));
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id)->delete(db_prefix() . 'dps_ticker');
        set_alert('success', 'Notícia removida.');
        redirect(admin_url('dps_ticker'));
    }
}
