<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Modal Contact -->
<div class="modal fade" id="add_audience_model" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open(admin_url('mailchimp/add-audience'),array('id'=>'audience-model-form','autocomplete'=>'off')); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?= _l('add_audience')?><br /></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <!-- // For email exist check -->
                        <input type="hidden" name="id" id="id">
                        
                        <?php echo render_input( 'name', 'audience_name',''); ?>
                        <?php echo render_input( 'company', 'audience_company',''); ?>
                        <?php echo render_input( 'address', 'audience_address',''); ?>
                        <?php echo render_input( 'city', 'audience_city',''); ?>
                        <?php echo render_input( 'zip', 'audience_zip',''); ?>
                        <?php 
                            
                            $countries= get_all_countries();
                            $customer_default_country = get_option('customer_default_country');
                            $selected =( isset($client) ? $client->country : $customer_default_country);
                            echo render_select( 'country',$countries,array( 'iso2',array( 'short_name')), 'clients_country',$selected,array('data-none-selected-text'=>_l('dropdown_non_selected_tex')));
                        ?>
                        <?php echo render_input( 'state', 'audience_state',''); ?>
                        <?php echo render_input( 'phone', 'audience_phone','','text',array('autocomplete'=>'off')); ?>
                        <?php echo render_input( 'from_email', 'audience_from_email','', 'email'); ?>
                        <?php echo render_input( 'from_name', 'audience_from_name',''); ?>
                    
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-info audience-submit-btn" id="audience-submit-btn" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off" data-form="audience-model-form"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
