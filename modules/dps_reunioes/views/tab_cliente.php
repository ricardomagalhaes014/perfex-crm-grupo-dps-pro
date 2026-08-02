<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/**
 * Separador "Reuniões" da ficha do cliente.
 *
 * Só prepara o contexto e entrega ao bloco partilhado: o mesmo bloco serve a
 * lead e o cliente, e assim há uma redacção só para manter.
 */
$CI = &get_instance();

$cliente_id = 0;
if (function_exists('get_client') && ($cl = get_client())) {
    $cliente_id = (int) $cl->userid;
}
if (!$cliente_id) {
    $cliente_id = (int) $CI->uri->segment(4);
}

$contacto = $CI->db->select('firstname, lastname, email, phonenumber')
                   ->where('userid', $cliente_id)->where('is_primary', 1)
                   ->get(db_prefix() . 'contacts')->row_array();
$ficha = $CI->db->select('company, phonenumber')->where('userid', $cliente_id)
                ->get(db_prefix() . 'clients')->row_array();

$rel_type  = 'customer';
$rel_id    = $cliente_id;
$pre_nome  = trim((string) ($ficha['company'] ?? ''))
             ?: trim(($contacto['firstname'] ?? '') . ' ' . ($contacto['lastname'] ?? ''));
$pre_email = $contacto['email'] ?? '';
$pre_tel   = $contacto['phonenumber'] ?? ($ficha['phonenumber'] ?? '');
?>
<div class="tab-pane" id="dps_reunioes">
    <?php $CI->load->view('dps_reunioes/bloco_ficha', compact('rel_type','rel_id','pre_nome','pre_email','pre_tel')); ?>
</div>
