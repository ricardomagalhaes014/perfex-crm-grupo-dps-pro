<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Si_lead_filter_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * @param  integer (optional)
    * @return object
    * Get single lead filter
    */
    public function get($id = '')
    {
        $this->db->where('staff_id', get_staff_user_id());
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'si_lead_filter')->row();
        }
        return $this->db->get(db_prefix() . 'si_lead_filter')->result_array();
    }

    /**
    * get all filter templates of that staff
    */
    public function get_templates($staff_id)
    {
        if (is_numeric($staff_id)) {
            $this->db->where('staff_id', $staff_id);
            return $this->db->get(db_prefix() . 'si_lead_filter')->result_array();
        }
        return [];
    }

    /**
    * Add new lead filter
    * @param mixed $data All $_POST data
    * @return mixed
    */
    public function add($data)
    {
        $this->db->insert(db_prefix() . 'si_lead_filter', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Lead Filter Added [Name:' . $data['filter_name'] . ']');
            return $insert_id;
        }
        return false;
    }

    /**
    * Update lead filter
    * @param mixed $data All $_POST data
    * @return mixed
    */
    public function update($data, $filter_id)
    {
        $this->db->where('id', $filter_id);
        $update = $this->db->update(db_prefix() . 'si_lead_filter', $data);
        if ($update) {
            log_activity('Lead Filter Updated [Name:' . $data['filter_name'] . ']');
            return true;
        }
        return false;
    }

    /**
    * Delete lead filter
    * @param  mixed $id filter id
    * @return boolean
    */
    public function delete($id, $staff_id)
    {
        $this->db->where('id', $id);
        $this->db->where('staff_id', $staff_id);
        $this->db->delete(db_prefix() . 'si_lead_filter');
        if ($this->db->affected_rows() > 0) {
            log_activity('Lead Filter Deleted [ID:' . $id . ']');
            return true;
        }
        return false;
    }

    /**
    * get lead company list
    * @return array
    */
    public function get_leads_country_list()
    {
        $this->db->select(db_prefix() . 'leads.country as id,short_name as name');
        $this->db->where('country > ', 0);
        $this->db->join(
            db_prefix() . 'countries',
            db_prefix() . 'countries.country_id=' . db_prefix() . 'leads.country',
            'left'
        );
        $this->db->group_by(db_prefix() . 'leads.country');
        $result = $this->db->get(db_prefix() . 'leads');
        if ($result->num_rows() > 0) {
            return $result->result_array();
        }
        return [];
    }

    /**
    * Lista de filtros disponíveis (adiciona "Atribuído a")
    * @return array
    */
    public function get_available_lead_filters()
    {
        $filters = [];

        $filters[] = ['name' => 'País', 'id' => 'country'];
        $filters[] = ['name' => 'Cidade', 'id' => 'city'];
        $filters[] = ['name' => 'Estado', 'id' => 'state'];
        $filters[] = ['name' => 'Cód. Postal', 'id' => 'zip'];
        $filters[] = ['name' => 'Público', 'id' => 'public'];
        $filters[] = ['name' => 'Perdido', 'id' => 'lost'];
        $filters[] = ['name' => 'Lixo', 'id' => 'junk'];
        $filters[] = ['name' => 'Último Contato', 'id' => 'last_contact'];
        $filters[] = ['name' => 'Data de criação', 'id' => 'datecreated'];
        $filters[] = ['name' => 'Data de Atribuição', 'id' => 'assigned_date'];
        $filters[] = ['name' => 'Valor da Lead', 'id' => 'lead_value'];
        $filters[] = ['name' => 'Estado do Lead', 'id' => 'status'];
        $filters[] = ['name' => 'Fonte de Lead', 'id' => 'source'];
        $filters[] = ['name' => 'NIF, Localidade, Nome da Empresa, Atribuido', 'id' => 'custom'];
        $filters[] = ['name' => 'Whatsapp Enable', 'id' => 'whatsapp_enable'];

        // 🚀 Novo campo para selecionar staff
        $filters[] = [
            'name'   => 'Atribuído a',
            'id'     => 'assigned',
            'type'   => 'select',
            'source' => 'staff'
        ];

        return $filters;
    }
}