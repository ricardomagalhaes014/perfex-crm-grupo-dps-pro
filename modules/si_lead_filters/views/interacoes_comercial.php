<?php init_head(); ?>
<link href="<?php echo module_dir_url('si_lead_filters','assets/css/si_lead_filters_style.css'); ?>" rel="stylesheet" />
<style>
.ic-filter-bar { background:#f8f9fa; border:1px solid #e0e0e0; border-radius:6px; padding:16px 20px; margin-bottom:18px; }
.ic-filter-bar .form-group { margin-bottom:0; }
.ic-period-btn { display:inline-block; margin:3px 4px 3px 0; padding:5px 14px; border-radius:20px; border:1px solid #bfcbd9; background:#fff; color:#555; font-size:12.5px; cursor:pointer; transition:all .15s; text-decoration:none; }
.ic-period-btn:hover, .ic-period-btn.active { background:#1a73e8; border-color:#1a73e8; color:#fff; text-decoration:none; }
.ic-table-wrap { overflow-x:auto; }
.ic-table { width:100%; border-collapse:collapse; font-size:13.5px; }
.ic-table thead th { background:#f0f4f8; border-bottom:2px solid #dde3ea; padding:10px 14px; text-align:left; font-weight:600; white-space:nowrap; }
.ic-table tbody tr { border-bottom:1px solid #eef0f3; transition:background .1s; }
.ic-table tbody tr:hover { background:#f5f8ff; }
.ic-table tbody td { padding:9px 14px; vertical-align:middle; }
.ic-badge { display:inline-block; min-width:32px; padding:3px 10px; border-radius:12px; font-weight:700; font-size:13px; text-align:center; }
.ic-badge-0 { background:#e9ecef; color:#888; }
.ic-badge-pos { background:#d4edda; color:#155724; }
.ic-rank { font-weight:700; color:#888; font-size:13px; }
.ic-detail-btn { padding:4px 14px; font-size:12px; border-radius:4px; }
.ic-detail-panel { display:none; background:#fff; border:1px solid #dde3ea; border-radius:6px; margin-top:8px; }
.ic-detail-panel .ic-detail-inner { padding:14px 18px; }
.ic-detail-panel table { font-size:12.5px; }
.ic-summary-row { background:#f0f7ff; font-weight:600; }
.ic-empty { color:#aaa; font-style:italic; padding:18px 0; text-align:center; }
</style>

<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">

        <!-- Cabeçalho -->
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="pull-left"><i class="fa fa-bar-chart text-info"></i> &nbsp;Interacções por Comercial</h4>
            <div class="clearfix"></div>
            <hr style="margin-top:10px;margin-bottom:14px;" />

            <!-- Filtros -->
            <?php echo form_open($this->uri->uri_string(), 'id=ic_form method=GET'); ?>
            <div class="ic-filter-bar">
              <div class="row">
                <!-- Período -->
                <div class="col-md-8">
                  <label class="control-label" style="display:block;margin-bottom:6px;font-weight:600;">Período</label>
                  <div>
                    <?php
                    $periods = [
                      'today'     => 'Hoje',
                      'today_yesterday' => 'Hoje e Ontem',
                      'last_7'    => 'Últimos 7 dias',
                      'last_15'   => 'Últimos 15 dias',
                      'last_30'   => 'Últimos 30 dias',
                      'last_3m'   => 'Últimos 3 meses',
                    ];
                    foreach ($periods as $val => $label):
                      $active = ($periodo === $val) ? 'active' : '';
                    ?>
                    <a href="javascript:void(0)" class="ic-period-btn <?php echo $active; ?>" data-period="<?php echo $val; ?>"><?php echo $label; ?></a>
                    <?php endforeach; ?>
                    <input type="hidden" name="periodo" id="ic_periodo" value="<?php echo htmlspecialchars($periodo); ?>" />
                  </div>
                </div>
                <!-- Status do Lead -->
                <div class="col-md-3">
                  <label class="control-label" style="font-weight:600;">Status do Lead</label>
                  <select name="status_id" class="selectpicker form-control" data-width="100%" data-none-selected-text="Todos os Status">
                    <option value="">Todos os Status</option>
                    <?php foreach ($lead_statuses as $st): ?>
                    <option value="<?php echo $st['id']; ?>" <?php echo ($status_id == $st['id'] ? 'selected' : ''); ?>>
                      <?php echo htmlspecialchars($st['name']); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <!-- Botão filtrar -->
                <div class="col-md-1" style="padding-top:22px;">
                  <button type="submit" class="btn btn-info btn-block"><i class="fa fa-search"></i></button>
                </div>
              </div>
            </div>
            <?php echo form_close(); ?>

            <!-- Período activo label -->
            <p class="text-muted" style="font-size:12px;margin-bottom:10px;">
              <i class="fa fa-calendar"></i>&nbsp;
              <?php
              $period_labels = [
                'today'           => 'Hoje (' . date('d/m/Y') . ')',
                'today_yesterday' => 'Hoje e Ontem (' . date('d/m/Y', strtotime('-1 day')) . ' – ' . date('d/m/Y') . ')',
                'last_7'          => 'Últimos 7 dias (' . date('d/m/Y', strtotime('-6 days')) . ' – ' . date('d/m/Y') . ')',
                'last_15'         => 'Últimos 15 dias (' . date('d/m/Y', strtotime('-14 days')) . ' – ' . date('d/m/Y') . ')',
                'last_30'         => 'Últimos 30 dias (' . date('d/m/Y', strtotime('-29 days')) . ' – ' . date('d/m/Y') . ')',
                'last_3m'         => 'Últimos 3 meses (' . date('d/m/Y', strtotime('-3 months')) . ' – ' . date('d/m/Y') . ')',
              ];
              echo isset($period_labels[$periodo]) ? $period_labels[$periodo] : 'Período seleccionado';
              if ($status_id) {
                foreach ($lead_statuses as $st) {
                  if ($st['id'] == $status_id) {
                    echo ' &nbsp;·&nbsp; Status: <strong>' . htmlspecialchars($st['name']) . '</strong>';
                    break;
                  }
                }
              }
              ?>
            </p>
          </div>
        </div>

        <!-- Tabela de resultados -->
        <div class="panel_s">
          <div class="panel-body">
            <?php if (empty($comerciais)): ?>
              <p class="ic-empty">Nenhum dado encontrado para o período seleccionado.</p>
            <?php else: ?>
            <div class="ic-table-wrap">
              <table class="ic-table" id="ic_main_table">
                <thead>
                  <tr>
                    <th style="width:40px;">#</th>
                    <th>Comercial</th>
                    <th style="width:160px;">Total de Interacções</th>
                    <th style="width:100px;">Acções</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $total_geral = 0;
                  $rank = 1;
                  foreach ($comerciais as $com):
                    $total_geral += $com['total_interacoes'];
                    $badge_class = $com['total_interacoes'] > 0 ? 'ic-badge-pos' : 'ic-badge-0';
                  ?>
                  <tr>
                    <td class="ic-rank"><?php echo $rank++; ?></td>
                    <td>
                      <strong><?php echo htmlspecialchars($com['nome']); ?></strong>
                    </td>
                    <td>
                      <span class="ic-badge <?php echo $badge_class; ?>"><?php echo $com['total_interacoes']; ?></span>
                    </td>
                    <td>
                      <?php if ($com['total_interacoes'] > 0): ?>
                      <button type="button" class="btn btn-default btn-sm ic-detail-btn"
                        data-staff="<?php echo $com['staff_id']; ?>"
                        data-nome="<?php echo htmlspecialchars($com['nome']); ?>"
                        onclick="icToggleDetail(this)">
                        <i class="fa fa-list"></i> Detalhe
                      </button>
                      <?php else: ?>
                      <span class="text-muted" style="font-size:12px;">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <!-- Painel de detalhe inline -->
                  <?php if ($com['total_interacoes'] > 0): ?>
                  <tr class="ic-detail-row" id="ic_detail_<?php echo $com['staff_id']; ?>" style="display:none;">
                    <td colspan="4" style="padding:0 10px 12px 40px;">
                      <div class="ic-detail-panel" id="ic_panel_<?php echo $com['staff_id']; ?>">
                        <div class="ic-detail-inner">
                          <h5 style="margin-top:0;margin-bottom:10px;"><i class="fa fa-user text-info"></i> &nbsp;Leads com interacção — <strong><?php echo htmlspecialchars($com['nome']); ?></strong></h5>
                          <?php if (!empty($com['leads'])): ?>
                          <table class="table table-condensed table-hover" style="margin-bottom:0;">
                            <thead>
                              <tr>
                                <th>#</th>
                                <th>Nome do Lead</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Status</th>
                                <th>Interacções</th>
                                <th></th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php $li = 1; foreach ($com['leads'] as $lead): ?>
                              <tr>
                                <td><?php echo $li++; ?></td>
                                <td>
                                  <a href="<?php echo admin_url('leads/index/' . $lead['id']); ?>" onclick="init_lead(<?php echo $lead['id']; ?>); return false;">
                                    <?php echo htmlspecialchars($lead['name']); ?>
                                  </a>
                                </td>
                                <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                <td><?php echo htmlspecialchars($lead['phonenumber']); ?></td>
                                <td><?php echo si_format_lead_status($lead['status']); ?></td>
                                <td>
                                  <span class="label label-success"><?php echo $lead['interactions']; ?></span>
                                </td>
                                <td>
                                  <a href="<?php echo admin_url('leads/index/' . $lead['id']); ?>" onclick="init_lead(<?php echo $lead['id']; ?>); return false;" class="btn btn-xs btn-default">
                                    <i class="fa fa-eye"></i>
                                  </a>
                                </td>
                              </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                          <?php else: ?>
                          <p class="text-muted" style="margin:0;">Nenhuma lead encontrada.</p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </td>
                  </tr>
                  <?php endif; ?>
                  <?php endforeach; ?>
                  <!-- Linha de totais -->
                  <tr class="ic-summary-row">
                    <td colspan="2" style="text-align:right;padding-right:20px;">Total Geral</td>
                    <td><span class="ic-badge ic-badge-pos"><?php echo $total_geral; ?></span></td>
                    <td></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
(function($) {
"use strict";

// Botões de período
$('.ic-period-btn').on('click', function() {
  $('.ic-period-btn').removeClass('active');
  $(this).addClass('active');
  $('#ic_periodo').val($(this).data('period'));
});

// Toggle detalhe inline
window.icToggleDetail = function(btn) {
  var staffId = $(btn).data('staff');
  var $row = $('#ic_detail_' + staffId);
  var $panel = $('#ic_panel_' + staffId);

  if ($row.is(':visible')) {
    $row.hide();
    $panel.hide();
    $(btn).html('<i class="fa fa-list"></i> Detalhe');
    $(btn).removeClass('btn-primary').addClass('btn-default');
  } else {
    $row.show();
    $panel.show();
    $(btn).html('<i class="fa fa-times"></i> Fechar');
    $(btn).removeClass('btn-default').addClass('btn-primary');
  }
};

// Selectpicker init
if ($.fn.selectpicker) {
  $('select.selectpicker').selectpicker('refresh');
}

})(jQuery);
</script>
</html>
