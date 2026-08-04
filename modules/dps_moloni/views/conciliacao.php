<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><i class="fa fa-link"></i> <?php echo _l('dps_moloni_conciliacao'); ?></h4>
            <p class="text-muted"><?php echo _l('dps_moloni_reconcile_intro'); ?></p>

            <?php if (!$mapping_ok) { ?>
              <div class="alert alert-warning">
                <?php echo _l('dps_moloni_mapping_missing'); ?>
                <a href="<?php echo admin_url('dps_moloni/mapeamento'); ?>"><?php echo _l('dps_moloni_mapeamento'); ?></a>
              </div>
            <?php } ?>

            <?php if ($api_error) { ?>
              <div class="alert alert-danger"><?php echo html_escape($api_error); ?></div>
            <?php } ?>

            <form method="get" action="<?php echo admin_url('dps_moloni/conciliacao'); ?>" class="form-inline mbot15">
              <div class="form-group">
                <label class="mright5"><?php echo _l('dps_moloni_from'); ?></label>
                <input type="date" name="from" value="<?php echo html_escape($from); ?>" class="form-control">
              </div>
              <div class="form-group mleft10">
                <label class="mright5"><?php echo _l('dps_moloni_to'); ?></label>
                <input type="date" name="to" value="<?php echo html_escape($to); ?>" class="form-control">
              </div>
              <button type="submit" class="btn btn-default mleft10"><?php echo _l('dps_moloni_load'); ?></button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <?php if (!empty($matches)) { ?>
      <?php echo form_open(admin_url('dps_moloni/conciliar_aplicar')); ?>
      <div class="row">
        <div class="col-md-12">
          <div class="panel_s">
            <div class="panel-body">
              <h5 class="no-mtop bold">
                <?php echo _l('dps_moloni_suggestions'); ?>
                <span class="badge"><?php echo count($matches); ?></span>
              </h5>

              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th width="30">
                        <input type="checkbox" id="dps-check-all">
                      </th>
                      <th><?php echo _l('dps_moloni_confidence'); ?></th>
                      <th><?php echo _l('dps_moloni_sale'); ?></th>
                      <th><?php echo _l('dps_moloni_document'); ?></th>
                      <th><?php echo _l('dps_moloni_entity'); ?></th>
                      <th class="text-right"><?php echo _l('dps_moloni_doc_value'); ?></th>
                      <th class="text-right"><?php echo _l('dps_moloni_col_commission'); ?></th>
                      <th><?php echo _l('dps_moloni_reason'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($matches as $match) {
                        $confidence = dps_moloni_confidence_label($match['confidence']);
                        $doc        = $match['document'];
                        $sale       = $match['sale'];
                    ?>
                      <tr>
                        <td>
                          <input type="checkbox" class="dps-apply" name="apply[]"
                                 value="<?php echo html_escape($match['payload']); ?>"
                                 <?php echo $match['confidence'] === 'certeza' ? 'checked' : ''; ?>>
                        </td>
                        <td>
                          <span class="label label-<?php echo $confidence['class']; ?>">
                            <?php echo $confidence['label']; ?>
                          </span>
                        </td>
                        <td>
                          <strong>#<?php echo html_escape($sale['id']); ?></strong><br>
                          <small class="text-muted">
                            <?php echo html_escape($sale['project']); ?>
                            <?php echo $sale['unit'] ? ' · ' . html_escape($sale['unit']) : ''; ?>
                          </small>
                        </td>
                        <td>
                          <?php echo html_escape(dps_moloni_doc_number($doc)); ?><br>
                          <small class="text-muted">
                            <?php echo html_escape(isset($doc['date']) ? substr($doc['date'], 0, 10) : ''); ?>
                            <?php if (isset($doc['status']) && (int) $doc['status'] === 0) { ?>
                              · <span class="text-warning"><?php echo _l('dps_moloni_draft'); ?></span>
                            <?php } ?>
                          </small>
                        </td>
                        <td>
                          <?php echo html_escape($doc['entity_name'] ?? ''); ?><br>
                          <small class="text-muted"><?php echo html_escape($doc['entity_vat'] ?? ''); ?></small>
                        </td>
                        <td class="text-right"><?php echo dps_moloni_money(dps_moloni_doc_value($doc)); ?></td>
                        <td class="text-right"><?php echo dps_moloni_money($sale['commission']); ?></td>
                        <td><small class="text-muted"><?php echo html_escape($match['reason']); ?></small></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>

              <div class="checkbox checkbox-primary">
                <input type="checkbox" name="write_back" id="write_back" value="1" checked>
                <label for="write_back"><?php echo _l('dps_moloni_write_back'); ?></label>
              </div>

              <button type="submit" class="btn btn-primary">
                <i class="fa fa-check"></i> <?php echo _l('dps_moloni_apply'); ?>
              </button>
            </div>
          </div>
        </div>
      </div>
      <?php echo form_close(); ?>
    <?php } elseif (!$api_error) { ?>
      <div class="row">
        <div class="col-md-12">
          <div class="panel_s">
            <div class="panel-body text-center text-muted">
              <?php echo _l('dps_moloni_no_suggestions'); ?>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>

    <?php if (!empty($documents)) { ?>
      <div class="row">
        <div class="col-md-12">
          <div class="panel_s">
            <div class="panel-body">
              <h5 class="no-mtop bold">
                <?php echo _l('dps_moloni_all_documents'); ?>
                <span class="badge"><?php echo count($documents); ?></span>
              </h5>
              <div class="table-responsive">
                <table class="table table-striped dt-table" data-order-col="0" data-order-type="desc">
                  <thead>
                    <tr>
                      <th><?php echo _l('dps_moloni_date'); ?></th>
                      <th><?php echo _l('dps_moloni_document'); ?></th>
                      <th><?php echo _l('dps_moloni_entity'); ?></th>
                      <th><?php echo _l('dps_moloni_vat'); ?></th>
                      <th class="text-right"><?php echo _l('dps_moloni_net'); ?></th>
                      <th class="text-right"><?php echo _l('dps_moloni_gross'); ?></th>
                      <th><?php echo _l('dps_moloni_status'); ?></th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($documents as $doc) { ?>
                      <tr>
                        <td><?php echo html_escape(substr($doc['date'] ?? '', 0, 10)); ?></td>
                        <td><?php echo html_escape(dps_moloni_doc_number($doc)); ?></td>
                        <td><?php echo html_escape($doc['entity_name'] ?? ''); ?></td>
                        <td><?php echo html_escape($doc['entity_vat'] ?? ''); ?></td>
                        <td class="text-right"><?php echo dps_moloni_money(dps_moloni_doc_value($doc)); ?></td>
                        <td class="text-right"><?php echo dps_moloni_money(dps_moloni_doc_total($doc)); ?></td>
                        <td>
                          <?php if ((int) ($doc['status'] ?? 0) === 1) { ?>
                            <span class="label label-success"><?php echo _l('dps_moloni_closed'); ?></span>
                          <?php } else { ?>
                            <span class="label label-warning"><?php echo _l('dps_moloni_draft'); ?></span>
                          <?php } ?>
                        </td>
                        <td>
                          <a href="<?php echo admin_url('dps_moloni/pdf/' . (int) $doc['document_id']); ?>"
                             target="_blank" class="btn btn-default btn-xs">PDF</a>
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
    <?php } ?>
  </div>
</div>
<?php init_tail(); ?>
