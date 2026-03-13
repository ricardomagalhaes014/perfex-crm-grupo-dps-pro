<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="contact_subscribe_modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="title"><?php echo _l('Mailchimp audience subscribed'); ?></h4>
            </div>
            <input type="hidden" name="contact_id" id="contact_id">
            <div class="modal-body">
                <div class="col-md-12 row" id="subscribed_unsubscribed_list_div">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default close_btn" data-dismiss="modal"><?php echo _l('close'); ?></button>
                    <button type="button" id="contact_subscribe_btn" class="btn btn-info"><?php echo _l('submit'); ?></button>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
