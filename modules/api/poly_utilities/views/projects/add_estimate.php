<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade poly_utilities_modal_add" id="poly_utilities_add_estimate" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog poly_utilities-setup-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span class="add-title"><?php echo _l('poly_utilities_modal_header_estimate') ?></span>
                </h4>
            </div>
            <div class="modal-body poly_utilities_modal_form">

                <?php
                echo form_open(admin_url('poly_utilities/add_estimate'), ['id' => 'poly_utilities_estimate_form', 'class' => '_transaction_form poly_utilities_estimate_form']);
                $data['customer_id'] = $customer_id = $project->clientid;
                $data['project_id'] = $project_id = $project->id;
                $data['estimate_statuses'] = $estimate_statuses;
                $this->load->view('admin/estimates/estimate_template', $data);
                echo form_close();

                $selected = (isset($customer_id) ? $customer_id : '');
                $rel_data = '';
                $rel_val = '';
                if ($selected != '') {
                    $rel_data = get_relation_data('customer', $selected);
                    $rel_val  = get_relation_values($rel_data, 'customer');
                }

                ?>
                <script>
                    $(function() {
                        $('#poly_utilities_estimate_form .f_client_id .ajax-search.ajax-remove-values-option').replaceWith(`
                        <input type="hidden" id="clientid" name="clientid" value="<?php echo $rel_val['id'] ?>"/>
                        <input type="text" id="client_name" value="<?php echo e($rel_val['name']) ?>" class="form-control" disabled>`);

                        $('#poly_utilities_add_estimate .projects-wrapper').append(`<input type="hidden" id="project_id" name="project_id" value="<?php echo $project_id ?>"/>
                    <input type="text" class="form-control" value="<?php echo '#'.$project_id.' - '.e(get_project_name_by_id($project_id)).' - '.$rel_val['name']?>" disabled/>`);

                        validate_estimate_form('#poly_utilities_estimate_form');
                        init_currency();
                        init_ajax_project_search_by_customer_id();
                        init_ajax_search('items', '#item_select.ajax-search', undefined, admin_url + 'items/search');
                    });
                </script>

            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>