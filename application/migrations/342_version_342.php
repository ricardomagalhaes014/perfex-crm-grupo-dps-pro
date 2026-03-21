<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração 342:
 * Garante que o utilizador ricardomagalhaes014@gmail.com tem admin = 1
 * e que não está bloqueado (active = 1).
 */
class Migration_Version_342 extends CI_Migration
{
    public function up(): void
    {
        $this->db->where('email', 'ricardomagalhaes014@gmail.com');
        $this->db->update(db_prefix() . 'staff', [
            'admin'  => 1,
            'active' => 1,
        ]);
    }
}
