<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('qb_income_account_helper'); ?>"></i>
<div class="form-group inc_acc">
    <label class="control-label" for="parent_id"><small class="req text-danger">* </small><?php echo _l('qb_income_account'); ?></label>
    <?php echo form_dropdown('qb_incomeAccountRef', $qb_income_accounts, $selected, array('id' => 'qb_incomeAccountRef', 'class' => 'form-control selectpicker', 'data-live-search' => 'true', 'data-width' => '100%', 'required' => 'required')); ?>
</div>