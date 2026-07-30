<?php defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();
$CI->load->model('voipstudio_dps/voipstudio_dps_model');
$lead_id = isset($lead) ? $lead->id : (isset($GLOBALS['lead']) ? $GLOBALS['lead']->id : null);
$calls   = $lead_id ? $CI->voipstudio_dps_model->get_calls('lead', $lead_id) : [];
?>
<h4><i class="fa fa-phone"></i> Chamadas VoIPstudio</h4>
<hr/>
<table class="table table-striped">
  <thead><tr><th>Data</th><th>Direção</th><th>Número</th><th>Duração</th><th>Estado</th></tr></thead>
  <tbody>
  <?php if (empty($calls)) { ?>
    <tr><td colspan="5" class="text-muted">Sem chamadas registadas para esta lead.</td></tr>
  <?php } else { foreach ($calls as $c) { ?>
    <tr>
      <td><?php echo html_escape($c->calldate); ?></td>
      <td><?php echo $c->direction === 'inbound' ? 'Recebida' : 'Efetuada'; ?></td>
      <td><?php echo html_escape($c->direction === 'inbound' ? $c->src : $c->dst); ?></td>
      <td><?php echo (int) $c->duration; ?>s</td>
      <td><?php echo html_escape((string) $c->disposition); ?></td>
    </tr>
  <?php } } ?>
  </tbody>
</table>
