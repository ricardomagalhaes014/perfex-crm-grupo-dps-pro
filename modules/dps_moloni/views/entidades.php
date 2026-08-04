<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-5">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><i class="fa fa-users"></i> <?php echo _l('dps_moloni_entidades'); ?></h4>
            <p class="text-muted"><?php echo _l('dps_moloni_entities_intro'); ?></p>

            <?php echo form_open(admin_url('dps_moloni/sincronizar_entidade')); ?>
              <?php echo render_input('vat', 'dps_moloni_vat', ''); ?>
              <?php echo render_input('name', 'dps_moloni_entity', ''); ?>
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-search"></i> <?php echo _l('dps_moloni_find_and_link'); ?>
              </button>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>

      <div class="col-md-5">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><i class="fa fa-building-o"></i> <?php echo _l('dps_moloni_promoters'); ?></h4>
            <p class="text-muted"><?php echo _l('dps_moloni_promoters_intro'); ?></p>

            <?php echo form_open(admin_url('dps_moloni/guardar_promotor')); ?>
              <div class="form-group">
                <label for="project"><?php echo _l('dps_moloni_col_project'); ?></label>
                <select name="project" id="project" class="form-control selectpicker" data-live-search="true">
                  <?php foreach ($projects as $project) { ?>
                    <option value="<?php echo html_escape($project); ?>"><?php echo html_escape($project); ?></option>
                  <?php } ?>
                </select>
              </div>
              <?php echo render_input('promoter_vat', 'dps_moloni_promoter_vat', ''); ?>
              <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            <?php echo form_close(); ?>

            <?php if (!empty($promoters)) { ?>
              <hr>
              <table class="table table-condensed">
                <tbody>
                  <?php foreach ($promoters as $promoter) { ?>
                    <tr>
                      <td><?php echo html_escape($promoter['name']); ?></td>
                      <td class="text-right"><code><?php echo html_escape($promoter['vat']); ?></code></td>
                      <td width="20" class="text-right">
                        <a href="<?php echo admin_url('dps_moloni/remover_entidade/' . (int) $promoter['id']); ?>"
                           class="text-danger"
                           onclick="return confirm('<?php echo _l('dps_moloni_unlink_confirm'); ?>');">
                          <i class="fa fa-times"></i>
                        </a>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            <?php } ?>
          </div>
        </div>
      </div>

      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-mtop bold">
              <?php echo _l('dps_moloni_mapped_entities'); ?>
              <span class="badge"><?php echo count($entities); ?></span>
            </h5>

            <?php if (empty($entities)) { ?>
              <p class="text-muted"><?php echo _l('dps_moloni_no_entities'); ?></p>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th><?php echo _l('dps_moloni_vat'); ?></th>
                      <th><?php echo _l('dps_moloni_entity'); ?></th>
                      <th>Moloni ID</th>
                      <th><?php echo _l('dps_moloni_synced'); ?></th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($entities as $entity) { ?>
                      <tr>
                        <td><?php echo html_escape($entity['vat']); ?></td>
                        <td><?php echo html_escape($entity['name']); ?></td>
                        <td>#<?php echo (int) $entity['customer_id']; ?></td>
                        <td><small class="text-muted"><?php echo html_escape($entity['date_sync']); ?></small></td>
                        <td class="text-right">
                          <a href="<?php echo admin_url('dps_moloni/remover_entidade/' . (int) $entity['id']); ?>"
                             class="text-danger"
                             onclick="return confirm('<?php echo _l('dps_moloni_unlink_confirm'); ?>');">
                            <i class="fa fa-times"></i>
                          </a>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
