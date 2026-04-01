<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <h4 class="mbot15"><?php echo _l('dps_calendar_sync_settings'); ?></h4>
        <div class="panel_s">
          <div class="panel-body">
            <?php echo form_open(admin_url('dps_calendar_sync/settings')); ?>
            <?php echo render_input('client_id','dps_calendar_sync_client_id',$client_id); ?>
            <?php echo render_input('client_secret','dps_calendar_sync_client_secret',$client_secret); ?>
            <?php echo render_input('redirect_uri','dps_calendar_sync_redirect_uri',$redirect_uri,'text', ['placeholder' => admin_url('dps_calendar_sync/oauth_callback')]); ?>
            <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
