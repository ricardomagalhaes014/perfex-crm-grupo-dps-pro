<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">

    <?php if (!$mapping_ok) { ?>
      <div class="alert alert-warning">
        <?php echo _l('dps_moloni_mapping_missing'); ?>
        <a href="<?php echo admin_url('dps_moloni/mapeamento'); ?>" class="bold"><?php echo _l('dps_moloni_mapeamento'); ?></a>
      </div>
    <?php } ?>

    <div class="row">
      <div class="col-lg-3 col-md-6">
        <div class="panel_s dps-kpi">
          <div class="panel-body">
            <span class="text-muted"><?php echo _l('dps_moloni_kpi_sales'); ?></span>
            <h3 class="bold no-mbot"><?php echo dps_moloni_money($totals['sale_value']); ?></h3>
            <small class="text-muted"><?php echo (int) $totals['count']; ?> <?php echo _l('dps_moloni_kpi_lines'); ?></small>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="panel_s dps-kpi">
          <div class="panel-body">
            <span class="text-muted"><?php echo _l('dps_moloni_kpi_received'); ?></span>
            <h3 class="bold no-mbot text-success"><?php echo dps_moloni_money($totals['commission_recv']); ?></h3>
            <small class="text-muted">
              <?php echo _l('dps_moloni_kpi_from_promoter'); ?>
              <?php if ($totals['invoiced_open'] > 0) { ?>
                <br><span class="text-warning">
                  + <?php echo dps_moloni_money($totals['invoiced_open']); ?>
                  <?php echo _l('dps_moloni_kpi_invoiced_open'); ?>
                </span>
              <?php } ?>
            </small>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="panel_s dps-kpi">
          <div class="panel-body">
            <span class="text-muted"><?php echo _l('dps_moloni_kpi_due'); ?></span>
            <h3 class="bold no-mbot text-danger"><?php echo dps_moloni_money($totals['commission_due']); ?></h3>
            <small class="text-muted">
              <?php echo _l('dps_moloni_kpi_to_agents'); ?>
              <?php if (!empty($totals['overrides'])) { ?>
                <br><?php echo _l('dps_moloni_kpi_includes_overrides'); ?>
                <?php echo dps_moloni_money($totals['overrides']); ?>
              <?php } ?>
            </small>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="panel_s dps-kpi">
          <div class="panel-body">
            <span class="text-muted"><?php echo _l('dps_moloni_kpi_result'); ?></span>
            <h3 class="bold no-mbot <?php echo $totals['result'] >= 0 ? 'text-success' : 'text-danger'; ?>">
              <?php echo dps_moloni_money($totals['result']); ?>
            </h3>
            <small class="text-muted">
              <?php echo (int) $totals['with_document']; ?>/<?php echo (int) $totals['count']; ?>
              <?php echo _l('dps_moloni_kpi_with_docs'); ?>
              <?php if ($totals['draft_documents'] > 0) { ?>
                · <span class="text-warning"><?php echo (int) $totals['draft_documents']; ?> <?php echo _l('dps_moloni_draft'); ?></span>
              <?php } ?>
            </small>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-mtop bold"><?php echo _l('dps_moloni_by_project'); ?></h5>
            <div class="table-responsive">
              <table class="table table-condensed">
                <thead>
                  <tr>
                    <th><?php echo _l('dps_moloni_col_project'); ?></th>
                    <th class="text-center">#</th>
                    <th class="text-right"><?php echo _l('dps_moloni_kpi_received'); ?></th>
                    <th class="text-right"><?php echo _l('dps_moloni_kpi_due'); ?></th>
                    <th class="text-right"><?php echo _l('dps_moloni_kpi_result'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($by_project as $group) { ?>
                    <tr>
                      <td><?php echo html_escape($group['label']); ?></td>
                      <td class="text-center"><?php echo (int) $group['count']; ?></td>
                      <td class="text-right"><?php echo dps_moloni_money($group['received']); ?></td>
                      <td class="text-right"><?php echo dps_moloni_money($group['commission']); ?></td>
                      <td class="text-right <?php echo $group['result'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo dps_moloni_money($group['result']); ?>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-mtop bold"><?php echo _l('dps_moloni_by_agent'); ?></h5>
            <div class="table-responsive">
              <table class="table table-condensed">
                <thead>
                  <tr>
                    <th><?php echo _l('dps_moloni_col_commercial'); ?></th>
                    <th class="text-center">#</th>
                    <th class="text-right"><?php echo _l('dps_moloni_col_sale_value'); ?></th>
                    <th class="text-right"><?php echo _l('dps_moloni_kpi_due'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $override_by_name = [];
                  foreach ($overrides as $o) {
                      $override_by_name[dps_moloni_norm_key($o['beneficiary'])] = $o;
                  }

                  foreach ($by_agent as $group) {
                      $extra = isset($override_by_name[dps_moloni_norm_key($group['label'])])
                          ? $override_by_name[dps_moloni_norm_key($group['label'])]
                          : null;
                  ?>
                    <tr>
                      <td>
                        <?php echo html_escape($group['label']); ?>
                        <?php if ($extra) { ?>
                          <br><small class="text-muted">
                            + <?php echo rtrim(rtrim(number_format($extra['rate'], 4, ',', ''), '0'), ','); ?>%
                            <?php echo _l('dps_moloni_override_short'); ?>
                          </small>
                        <?php } ?>
                      </td>
                      <td class="text-center"><?php echo (int) $group['count']; ?></td>
                      <td class="text-right"><?php echo dps_moloni_money($group['sale_value']); ?></td>
                      <td class="text-right">
                        <?php echo dps_moloni_money($group['commission'] + ($extra ? $extra['amount'] : 0)); ?>
                        <?php if ($extra) { ?>
                          <br><small class="text-muted">
                            <?php echo dps_moloni_money($group['commission']); ?>
                            + <?php echo dps_moloni_money($extra['amount']); ?>
                          </small>
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
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-mtop bold">
              <?php echo _l('dps_moloni_operations'); ?>
              <a href="<?php echo admin_url('dps_moloni/recalcular'); ?>"
                 class="btn btn-default btn-sm pull-right"
                 title="<?php echo _l('dps_moloni_recalc_hint'); ?>">
                <i class="fa fa-refresh"></i> <?php echo _l('dps_moloni_recalc'); ?>
              </a>
            </h5>
            <div class="table-responsive">
              <table class="table table-striped dt-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th><?php echo _l('dps_moloni_col_project'); ?></th>
                    <th><?php echo _l('dps_moloni_col_client'); ?></th>
                    <th><?php echo _l('dps_moloni_col_commercial'); ?></th>
                    <th class="text-right"><?php echo _l('dps_moloni_col_commission'); ?></th>
                    <th class="text-right"><?php echo _l('dps_moloni_col_received'); ?></th>
                    <th><?php echo _l('dps_moloni_documents'); ?></th>
                    <th class="text-right"><?php echo _l('dps_moloni_actions'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($sales as $sale) {
                      $sale_links = isset($links[(int) $sale['id']]) ? $links[(int) $sale['id']] : [];
                  ?>
                    <tr>
                      <td><?php echo html_escape($sale['id']); ?></td>
                      <td>
                        <?php echo html_escape($sale['project']); ?>
                        <small class="text-muted"><?php echo html_escape($sale['unit']); ?></small>
                      </td>
                      <td><?php echo html_escape($sale['client']); ?></td>
                      <td><?php echo html_escape($sale['commercial']); ?></td>
                      <td class="text-right"><?php echo dps_moloni_money($sale['commission']); ?></td>
                      <td class="text-right"><?php echo dps_moloni_money($sale['received']); ?></td>
                      <td>
                        <?php if (empty($sale_links)) { ?>
                          <span class="text-muted">—</span>
                        <?php } else { ?>
                          <?php foreach ($sale_links as $link) { ?>
                            <div class="mbot5">
                              <a href="<?php echo admin_url('dps_moloni/pdf/' . (int) $link['document_id']); ?>" target="_blank">
                                <?php echo html_escape($link['number'] ?: ('#' . $link['document_id'])); ?>
                              </a>
                              <?php if ((int) $link['status'] === 0) { ?>
                                <span class="label label-warning"><?php echo _l('dps_moloni_draft'); ?></span>
                              <?php } ?>
                              <?php if ($link['kind'] === 'invoice') { ?>
                                <?php if ((int) $link['is_paid'] === 1) { ?>
                                  <span class="label label-success"><?php echo _l('dps_moloni_paid'); ?></span>
                                <?php } else { ?>
                                  <span class="label label-default"><?php echo _l('dps_moloni_unpaid'); ?></span>
                                <?php } ?>
                              <?php } ?>
                              <span class="text-muted small">
                                <?php echo dps_moloni_money($link['total_value']); ?>
                              </span>
                              <a href="<?php echo admin_url('dps_moloni/desligar/' . (int) $link['id']); ?>"
                                 class="text-danger mleft5" title="<?php echo _l('dps_moloni_unlink'); ?>"
                                 onclick="return confirm('<?php echo _l('dps_moloni_unlink_confirm'); ?>');">
                                <i class="fa fa-times"></i>
                              </a>
                            </div>
                          <?php } ?>
                        <?php } ?>
                      </td>
                      <td class="text-right">
                        <a href="<?php echo admin_url('dps_moloni/emitir/receipt/' . (int) $sale['id']); ?>"
                           class="btn btn-default btn-xs">
                          <?php echo _l('dps_moloni_issue_receipt_short'); ?>
                        </a>
                        <a href="<?php echo admin_url('dps_moloni/emitir/invoice/' . (int) $sale['id']); ?>"
                           class="btn btn-default btn-xs">
                          <?php echo _l('dps_moloni_issue_invoice_short'); ?>
                        </a>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
