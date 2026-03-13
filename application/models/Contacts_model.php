<?php defined('BASEPATH') or exit('No direct script access allowed');

class Contacts_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obter todos os contactos ou um contacto específico por ID
     *
     * @param int|null $id
     * @return array|object
     */
    public function get($id = null)
    {
        if ($id !== null) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'contacts')->row();
        }

        return $this->db->get(db_prefix() . 'contacts')->result_array();
    }

    /**
     * Adicionar um novo contacto
     *
     * @param array $data
     * @return int|bool
     */
    public function add($data)
    {
        $this->db->insert(db_prefix() . 'contacts', $data);
        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }

        return false;
    }

    /**
     * Atualizar contacto
     *
     * @param array $data
     * @param int $id
     * @return bool
     */
    public function update($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'contacts', $data);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Eliminar contacto
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'contacts');

        return $this->db->affected_rows() > 0;
    }
}