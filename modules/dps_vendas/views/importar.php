<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
  <div class="row"><div class="col-md-12"><div class="panel_s"><div class="panel-body">

    <h4 class="no-margin">Importar vendas</h4>
    <hr>

    <?php if (empty($linhas) && empty($erros)) { ?>
      <p>
        Descarregue o modelo, preencha uma linha por venda, e volte aqui.
        <strong>Nada é gravado sem lhe ser mostrado primeiro.</strong>
      </p>
      <p>
        <a href="<?php echo admin_url('dps_vendas/importar_modelo/Belo_Horizonte'); ?>" class="btn btn-default">
          <i class="fa fa-download"></i> Descarregar modelo (CSV)
        </a>
      </p>
      <p class="text-muted" style="font-size:13px;">
        Obrigatórias: <strong>unidade</strong>, <strong>cliente</strong> e <strong>valor</strong> — o resto pode ficar vazio.<br>
        O valor aceita <code>374900</code> ou <code>374.900,00</code>. A data aceita
        <code>25/07/2026</code> ou <code>2026-07-25</code>.<br>
        O comercial aceita o nome (<code>Breno Gil</code>) ou o número.<br>
        O estado, a taxa e os meses de CPCV e escritura <strong>não vão no ficheiro</strong> —
        vêm da regra do empreendimento.
      </p>
      <hr>
    <?php } ?>

    <?php echo form_open_multipart(admin_url('dps_vendas/importar'), ['class' => 'form-inline']); ?>
      <label class="control-label">Empreendimento</label>
      <input type="text" name="empreendimento" class="form-control" list="emps"
             value="<?php echo html_escape($empreendimento); ?>" style="width:220px;">
      <datalist id="emps">
        <?php foreach ($empreendimentos as $e) { ?><option value="<?php echo html_escape($e); ?>"></option><?php } ?>
      </datalist>

      <?php if (empty($linhas) && empty($erros)) { ?>
        <input type="file" name="ficheiro" class="form-control" accept=".csv,text/csv" required
               style="display:inline-block;width:260px;">
        <button type="submit" class="btn btn-info">Ler ficheiro</button>
      <?php } else { ?>
        <input type="hidden" name="csv_bruto" value="<?php echo html_escape($csv_bruto); ?>">
      <?php } ?>

      <?php if (!empty($erros)) { ?>
        <hr>
        <div class="alert alert-danger">
          <strong>O ficheiro tem problemas — não foi gravado nada.</strong>
          <ul style="margin:8px 0 0 18px;">
            <?php foreach ($erros as $e) { ?><li><?php echo html_escape($e); ?></li><?php } ?>
          </ul>
          <a href="<?php echo admin_url('dps_vendas/importar'); ?>" class="btn btn-default btn-sm mtop15">
            Corrigir e recomeçar
          </a>
        </div>
      <?php } ?>

      <?php if (!empty($linhas)) { ?>
        <hr>
        <h5>
          <?php echo count($linhas); ?> venda(s) a importar para
          <strong><?php echo html_escape($empreendimento); ?></strong>,
          todas como <span class="label label-warning">reservado</span>
        </h5>
        <div class="table-responsive">
          <table class="table table-striped table-condensed">
            <thead><tr>
              <th>Unidade</th><th>Cliente</th><th class="text-right">Valor</th>
              <th>Data</th><th>Comercial</th><th>Contactos</th>
            </tr></thead>
            <tbody>
            <?php
            $CI = &get_instance();
            $CI->load->model('staff_model');
            $nomes = [];
            foreach ($CI->staff_model->get('', ['active' => 1]) as $st) {
                $nomes[(int) $st['staffid']] = trim($st['firstname'] . ' ' . $st['lastname']);
            }
            $total = 0;
            foreach ($linhas as $l) {
                $total += (float) $l['valor']; ?>
              <tr>
                <td><strong><?php echo html_escape($l['unidade']); ?></strong></td>
                <td><?php echo html_escape($l['cliente']); ?></td>
                <td class="text-right"><?php echo number_format((float) $l['valor'], 2, ',', '.'); ?> €</td>
                <td><?php echo $l['data_venda'] ? _d($l['data_venda']) : '—'; ?></td>
                <td><?php echo html_escape($nomes[(int) $l['staff_id']] ?? ('#' . $l['staff_id'])); ?></td>
                <td style="font-size:12px;">
                  <?php echo html_escape($l['cliente_email']); ?>
                  <?php echo $l['cliente_telefone'] ? '<br>' . html_escape($l['cliente_telefone']) : ''; ?>
                </td>
              </tr>
            <?php } ?>
            </tbody>
            <tfoot><tr>
              <th colspan="2">Total</th>
              <th class="text-right"><?php echo number_format($total, 2, ',', '.'); ?> €</th>
              <th colspan="3"></th>
            </tr></tfoot>
          </table>
        </div>

        <?php if (empty($erros)) { ?>
          <div class="alert alert-info">
            Ao confirmar, cada unidade fica marcada no simulador do
            <?php echo html_escape($empreendimento); ?>. No Belo Horizonte,
            uma reserva conta como <strong>DPS</strong> na montra.
          </div>
          <button type="submit" class="btn btn-info btn-lg">
            <i class="fa fa-check"></i> Confirmar e importar <?php echo count($linhas); ?> vendas
          </button>
          <a href="<?php echo admin_url('dps_vendas/importar'); ?>" class="btn btn-default">Cancelar</a>
        <?php } ?>
      <?php } ?>
    <?php echo form_close(); ?>

  </div></div></div></div>
</div></div>
<?php init_tail(); ?>
</body></html>
