<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin"><i class="fa fa-bar-chart"></i> VoIPstudio — Relatório de Chamadas</h4>
            <hr/>
            <form method="get" class="form-inline" style="margin-bottom:15px;">
              <label>De</label>
              <input type="date" name="from" class="form-control" value="<?php echo html_escape($from); ?>" style="margin:0 10px 0 5px;" />
              <label>Até</label>
              <input type="date" name="to" class="form-control" value="<?php echo html_escape($to); ?>" style="margin:0 10px 0 5px;" />
              <?php if ($is_admin) { ?>
                <label>Comercial</label>
                <select name="staff" class="form-control" style="margin:0 10px 0 5px;">
                  <option value="">Todos</option>
                  <?php foreach ($staff_members as $s) { ?>
                    <option value="<?php echo (int) $s->staffid; ?>" <?php echo $staff == $s->staffid ? 'selected' : ''; ?>>
                      <?php echo html_escape($s->firstname . ' ' . $s->lastname); ?>
                    </option>
                  <?php } ?>
                </select>
              <?php } ?>
              <button type="submit" class="btn btn-primary">Filtrar</button>
              <a href="<?php echo admin_url('voipstudio_dps'); ?>" class="btn btn-default">Definições</a>
              <a href="<?php echo admin_url('voipstudio_dps/sync'); ?>" class="btn btn-default">Sincronizar agora</a>
            </form>

            <?php $t = $totals; ?>
            <div class="row" style="margin-bottom:15px;">
              <div class="col-md-3"><div class="panel_s"><div class="panel-body text-center">
                <h3 class="no-margin"><?php echo (int) ($t->total ?? 0); ?></h3><span class="text-muted">Chamadas</span>
              </div></div></div>
              <div class="col-md-3"><div class="panel_s"><div class="panel-body text-center">
                <h3 class="no-margin text-success"><?php echo (int) ($t->atendidas ?? 0); ?></h3><span class="text-muted">Atendidas</span>
              </div></div></div>
              <div class="col-md-3"><div class="panel_s"><div class="panel-body text-center">
                <h3 class="no-margin"><?php echo gmdate('H:i:s', (int) ($t->dur_total ?? 0)); ?></h3><span class="text-muted">Tempo total</span>
              </div></div></div>
              <div class="col-md-3"><div class="panel_s"><div class="panel-body text-center">
                <h3 class="no-margin"><?php echo (int) ($t->dur_media ?? 0); ?>s</h3><span class="text-muted">Duração média (atendidas)</span>
              </div></div></div>
            </div>

            <?php if ($is_admin) { ?>
            <h4>Por comercial</h4>
            <table class="table table-striped">
              <thead><tr>
                <th>Comercial</th><th>Total</th><th>Efetuadas</th><th>Recebidas</th>
                <th>Atendidas</th><th>Não atendidas</th><th>% Sucesso</th><th>Tempo total</th><th>Média</th>
              </tr></thead>
              <tbody>
              <?php if (empty($stats)) { ?>
                <tr><td colspan="9" class="text-muted">Sem dados no período.</td></tr>
              <?php } else { foreach ($stats as $r) { ?>
                <tr>
                  <td><?php echo trim($r->staff_name) !== '' ? html_escape($r->staff_name) : '<span class="text-muted">— (sem comercial associado)</span>'; ?></td>
                  <td><?php echo (int) $r->total; ?></td>
                  <td><?php echo (int) $r->efetuadas; ?></td>
                  <td><?php echo (int) $r->recebidas; ?></td>
                  <td class="text-success"><?php echo (int) $r->atendidas; ?></td>
                  <td class="text-danger"><?php echo (int) $r->nao_atendidas; ?></td>
                  <td><?php echo $r->total > 0 ? round(100 * $r->atendidas / $r->total) : 0; ?>%</td>
                  <td><?php echo gmdate('H:i:s', (int) $r->dur_total); ?></td>
                  <td><?php echo (int) $r->dur_media; ?>s</td>
                </tr>
              <?php } } ?>
              </tbody>
            </table>
            <?php } ?>

            <h4>Chamadas no período</h4>
            <table class="table table-striped">
              <thead><tr><th>Data</th><th>Comercial</th><th>Direção</th><th>De</th><th>Para</th><th>Duração</th><th>Estado</th><th>Associada a</th></tr></thead>
              <tbody>
              <?php if (empty($calls)) { ?>
                <tr><td colspan="8" class="text-muted">Sem chamadas no período.</td></tr>
              <?php } else {
                  $staff_map = [];
                  foreach ($staff_members as $s) { $staff_map[$s->staffid] = $s->firstname . ' ' . $s->lastname; }
                  foreach ($calls as $c) { ?>
                <tr>
                  <td><?php echo html_escape($c->calldate); ?></td>
                  <td><?php echo isset($staff_map[$c->staff_id]) ? html_escape($staff_map[$c->staff_id]) : '—'; ?></td>
                  <td><?php echo $c->direction === 'inbound' ? '<span class="label label-info">Recebida</span>' : '<span class="label label-success">Efetuada</span>'; ?></td>
                  <td><?php echo html_escape($c->src); ?></td>
                  <td><?php echo html_escape($c->dst); ?></td>
                  <td><?php echo (int) $c->duration; ?>s</td>
                  <td><?php echo html_escape((string) $c->disposition); ?></td>
                  <td>
                    <?php if ($c->rel_type === 'lead' && $c->rel_id) { ?>
                      <a href="<?php echo admin_url('leads/index/' . $c->rel_id); ?>">Lead #<?php echo (int) $c->rel_id; ?></a>
                    <?php } elseif ($c->rel_type === 'customer' && $c->rel_id) { ?>
                      <a href="<?php echo admin_url('clients/client/' . $c->rel_id); ?>">Cliente #<?php echo (int) $c->rel_id; ?></a>
                    <?php } else { echo '—'; } ?>
                  </td>
                </tr>
              <?php } } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
