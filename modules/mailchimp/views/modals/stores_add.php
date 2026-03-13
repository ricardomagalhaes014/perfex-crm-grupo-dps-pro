<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Modal Contact -->
<div class="modal fade" id="add_store_model" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open(admin_url('mailchimp/add-store'),array('id'=>'store-model-form','autocomplete'=>'off')); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?= _l('add_audience')?><br /></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <!-- // For email exist check -->
                        <input type="hidden" name="id" id="id">
                        <input type="hidden" name="mailchimp_store_id" id="mailchimp_store_id">
                        <?php echo render_input( 'store_name', 'store_name',''); ?>
                        <?php
                            $default_audience_id = get_default_mailchimp_audience_has_id(); 
                            $selected =( isset($store) ? $store->mailchimp_audience_id : $default_audience_id);
                            echo render_select( 'store_audience',$audience_list,array( 'mailchimp_id',array( 'name')), 'store_audience_title',$selected,array('data-none-selected-text'=>_l('dropdown_non_selected_tex')));
                        ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-info stores-submit-btn" id="stores-submit-btn" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off" data-form="stores-model-form"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
