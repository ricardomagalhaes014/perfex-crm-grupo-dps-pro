<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
  <div class="row"><div class="col-md-12">
    <?php $this->load->view('_barra', ['atalho' => '']); ?>
  </div></div>
  <div class="row"><div class="col-md-12"><div class="panel_s"><div class="panel-body">
    <h4 class="no-margin">Reuniões online</h4>
    <hr>
    <?php if (empty($reunioes)) { ?>
      <p class="text-muted" style="margin-bottom:6px;">Ainda não há reuniões marcadas.</p>
      <p class="text-muted" style="font-size:13px;">
        Marque uma a partir da ficha de uma lead ou de um cliente. Para deixar que os
        colegas marquem consigo sem perguntar, publique os seus horários em
        <a href="<?php echo admin_url('dps_reunioes/disponibilidade'); ?>"><strong>A minha disponibilidade</strong></a>.
      </p>
    <?php } else { ?>
    <div class="table-responsive"><table class="table table-striped">
      <thead><tr><th>Data</th><th>Hora</th><th>Cliente</th><th>Comercial</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php
      $cores = ['agendada'=>'label-info','realizada'=>'label-success','nao_compareceu'=>'label-danger','cancelada'=>'label-default'];
      $nomes = ['agendada'=>'Agendada','realizada'=>'Realizada','nao_compareceu'=>'Não compareceu','cancelada'=>'Cancelada'];
      foreach ($reunioes as $r) { ?>
        <tr>
          <td><?php echo date('d/m/Y', strtotime($r['data_hora'])); ?></td>
          <td><?php echo date('H:i', strtotime($r['data_hora'])); ?></td>
          <td><?php echo html_escape((string) $r['cliente_nome']); ?></td>
          <td><?php echo html_escape((string) $r['comercial']); ?></td>
          <td><span class="label <?php echo $cores[$r['estado']] ?? 'label-default'; ?>"><?php echo $nomes[$r['estado']] ?? $r['estado']; ?></span></td>
          <td class="text-right">
            <a href="<?php echo html_escape($r['link']); ?>" target="_blank" class="btn btn-success btn-xs">Entrar</a>
            <a href="<?php echo admin_url('dps_reunioes/ver/' . (int) $r['id']); ?>" class="btn btn-default btn-xs">Abrir</a>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table></div>
    <?php } ?>
  </div></div></div></div>
</div></div>
<?php init_tail(); ?>
</body></html>
