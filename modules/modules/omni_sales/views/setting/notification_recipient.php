<?php echo form_open(site_url('omni_sales/save_setting/notification_recipient'),array('id'=>'invoice-form','class'=>'_transaction_form invoice-form')); ?>
<h4><?php echo _l('diary_sync'); ?></h4>
<div class="row">
    <div class="col-md-12">
      <a href="<?php echo admin_url('omni_sales/clear_diary_sync_data'); ?>" class="btn btn-info mbot15 _delete"><?php echo _l('clear_data'); ?></a> <label class="text-danger"><?php echo _l('clear_data_note'); ?></label>
  </div>
</div>
<?php echo render_input('number_of_days_to_save_diary_sync','number_of_days_to_save_diary_sync', $number_of_days_to_save_diary_sync, 'number'); ?>
<hr>
<?php echo render_select('staff',$staffs ,array('staffid', array('firstname','lastname')),'notification_recipients',$staff, array('multiple' => true, 'data-actions-box' => true),array(),'','',false);?>			 				
<hr>

<div class="form-group">
  <div class="checkbox checkbox-primary">
    <input type="checkbox" id="omni_sales_invoice_setting" name="omni_sales_invoice_setting" 
    <?php if($invoice_sync_configuration == 1){ 
    	echo 'checked="" value="0"';
    }else{
    	echo 'value="1"';
    } ?> >
    <label for="omni_sales_invoice_setting"><?php echo _l('enable_sync_auto') ?>
    </label>
  </div>
</div>		 				
<hr>

<button class="btn btn-primary pull-right"><?php echo _l('save'); ?></button>
<?php echo form_close(); ?>
