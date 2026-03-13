<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="tw-max-w-md tw-mx-auto tw-pt-24 authentication-form-wrapper tw-relative tw-z-20">
  <div class="text-center forgot-password-heading">
    <h1 class="text-uppercase mbot20"><?php echo _l('customer_forgot_password_heading'); ?></h1>
  </div>
  <div class="">
    <div class="panel_s">
      <div class="panel-body">
        <?php echo form_open($this->uri->uri_string(),['id'=>'forgot-password-form']); ?>
        <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>
        <?php if($this->session->flashdata('message-danger')){ ?>
          <div class="alert alert-danger">
            <?php $this->session->flashdata('message-danger'); ?>
          </div>
        <?php } ?>
        <?php echo render_input('email','customer_forgot_password_email','','email'); ?>
        <div class="form-group">
          <button type="submit" class="btn btn-info btn-block"><?php echo _l('customer_forgot_password_submit'); ?></button>
        </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
