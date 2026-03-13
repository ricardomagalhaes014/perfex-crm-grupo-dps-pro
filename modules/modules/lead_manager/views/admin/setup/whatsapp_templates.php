<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_filters _hidden_inputs hidden">
                </div>
                <div class="panel_s mbot5">
                    <div class="panel-body">
                        <div class="_buttons">
                            <div class="row mtop0">
                                <!-- <div class="col-lg-6">
                                    <h4 class="pull-left display-block"><?php echo _l('lm_manage_whatsapp_templates'); ?></h4>
                                </div> -->
                                <div class="col-lg-12">
                                    <a class="btn btn-primary" href="#" data-toggle="modal" data-target="#template-modal"><?php echo _l('lm_manage_whatsapp_templates_add'); ?></a>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />
                        <div class="tab-content">
                            <div class="row">
                                <div class="col-md-12">
                                    <?php
                                    $table_data = array();
                                    $_table_data[] =   array(
                                        'name' => _l('lm_wh_temp_id'),
                                        'th_attrs' => array('class' => 'toggleable', 'id' => 'th-id')
                                    );
                                    $_table_data[] = array(
                                        'name' => _l('lm_wh_temp_name'),
                                        'th_attrs' => array('class' => 'toggleable', 'id' => 'th-name')
                                    );
                                    $_table_data[] =  array(
                                        'name' => _l('lm_wh_temp_language'),
                                        'th_attrs' => array('class' => 'toggleable', 'id' => 'th-language')
                                    );
                                    $_table_data[] = array(
                                        'name' => _l('lm_wh_temp_status'),
                                        'th_attrs' => array('class' => 'toggleable', 'id' => 'th-status')
                                    );
                                    $_table_data[] = array(
                                        'name' => _l('lm_wh_temp_action'),
                                        'th_attrs' => array('class' => 'toggleable', 'id' => 'th-action')
                                    );
                                    foreach ($_table_data as $_t) {
                                        array_push($table_data, $_t);
                                    }
                                    render_datatable(
                                        $table_data,
                                        'whatsapp_templates'
                                    ); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="template-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('lm_add_template_modal_header'); ?></h4>
            </div>
            <div class="modal-body">
                <?php echo form_open(current_url(), ['id' => 'template-form']);
                echo render_input('template_id', _l('lm_wh_temp_id'));
                echo render_input('template_name', _l('lm_wh_temp_name')); ?>
                <?php if (!is_language_disabled()) { ?>
                    <div class="form-group select-placeholder">
                        <select name="language" id="language" class="form-control selectpicker" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" data-live-search="true">
                            <?php $selected = $staff->default_language ?? ''; ?>
                            <?php foreach ($languages as $availableLanguage) {
                            ?>
                                <option value="<?php echo $availableLanguage; ?>" <?php echo ($availableLanguage == $selected) ? 'selected' : '' ?>>
                                    <?php echo ucfirst($availableLanguage); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                <?php } ?>
                <?php
                echo render_select('status', [['id' => 1, 'name' => 'Active'], ['id' => 0, 'name' => 'Inactive']], ['id', 'name'], _l('lm_wh_temp_status'));
                ?>
                <button type="submit" class="btn btn-success "><?php echo _l('submit'); ?></button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>