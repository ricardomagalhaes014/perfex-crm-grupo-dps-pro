<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="panel_s">
      <div class="panel-body">
        <div class="clearfix" style="margin-bottom:12px;">
          <h4 class="no-margin pull-left">Campanhas de reuniões</h4>
          <a href="<?= admin_url('dps_reunioes/propor'); ?>" class="btn btn-primary btn-sm pull-right">Nova campanha</a>
        </div>
        <hr>
        <?php if (empty($campanhas)) { ?>
        <div class="alert alert-info">Ainda não lançou nenhuma campanha.</div>
        <?php } else { ?>
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Estado das leads</th>
              <th>Comercial</th>
              <th>A partir de</th>
              <th>Canal</th>
              <th class="text-center">Propostas</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($campanhas as $c) { ?>
            <tr>
              <td><?= e($c['estado_nome'] ?: '—'); ?></td>
              <td><?= e($c['comercial']); ?></td>
              <td><?= _d($c['dia_inicio']); ?></td>
              <td><span class="text-muted" style="font-size:12px;"><?= e($c['canal']); ?></span></td>
              <td class="text-center"><?= (int) $c['total']; ?></td>
              <td class="text-right">
                <a href="<?= admin_url('dps_reunioes/campanha/' . (int) $c['id']); ?>" class="btn btn-default btn-xs">Ver</a>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
