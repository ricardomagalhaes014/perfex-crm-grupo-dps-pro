<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
  <div class="row"><div class="col-md-9 col-md-offset-1"><div class="panel_s"><div class="panel-body">
    <?php
    /*
     * Reaproveita o mesmo bloco da ficha: a tabela das reuniões já marcadas e
     * o formulário. Uma redacção só, três sítios a usá-la.
     */
    $CI = &get_instance();
    $CI->load->view('dps_reunioes/bloco_ficha',
        compact('rel_type', 'rel_id', 'pre_nome', 'pre_email', 'pre_tel'));
    ?>
    <hr>
    <a href="<?php echo $rel_type === 'lead'
        ? admin_url('leads')
        : admin_url('clients/client/' . (int) $rel_id); ?>" class="btn btn-default">
        &larr; Voltar
    </a>
  </div></div></div></div>
</div></div>
<?php init_tail(); ?>
<script>
// A janela abre logo: quem carregou em "Marcar reunião" quer marcar, não ver a lista.
$(function () { $('#dps-marcar-reuniao').modal('show'); });
</script>
</body></html>
