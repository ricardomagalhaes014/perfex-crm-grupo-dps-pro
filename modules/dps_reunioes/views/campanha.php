<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="panel_s">
      <div class="panel-body">
        <h4 class="no-margin">Propostas da campanha</h4>
        <hr>
        <?php
        $cores = ['pendente' => 'warning', 'aceite' => 'success', 'recusada' => 'danger', 'expirada' => 'default'];
        ?>
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Horário proposto</th>
              <th>Cliente</th>
              <th>Contacto</th>
              <th>Enviado</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($propostas as $p) { ?>
            <tr>
              <td><?= dps_reunioes_quando_extenso($p['data_hora']); ?></td>
              <td>
                <a href="<?= admin_url('leads/index/' . (int) $p['lead_id']); ?>"><?= e($p['cliente_nome']); ?></a>
              </td>
              <td><span class="text-muted" style="font-size:12px;"><?= e($p['cliente_telefone'] ?: $p['cliente_email']); ?></span></td>
              <td>
                <?php if ($p['enviado_em']) { ?>
                  <span class="text-muted" style="font-size:12px;"><?= e($p['enviado_por']); ?></span>
                <?php } else { ?>
                  <span class="text-muted" style="font-size:12px;">na fila</span>
                <?php } ?>
                <?php if (!empty($p['erro_envio'])) { ?>
                  <div class="text-danger" style="font-size:11px;"><?= e($p['erro_envio']); ?></div>
                <?php } ?>
              </td>
              <td>
                <span class="label label-<?= $cores[$p['estado']] ?? 'default'; ?>"><?= e($p['estado']); ?></span>
                <?php if (!empty($p['reuniao_id'])) { ?>
                <a href="<?= admin_url('dps_reunioes/ver/' . (int) $p['reuniao_id']); ?>" class="btn btn-default btn-xs">Reunião</a>
                <?php } ?>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
