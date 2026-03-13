<?php defined('BASEPATH') or exit('No direct script access allowed');

init_head();

?>
<div id="wrapper">
  <div class="content">
    <div class="row poly_utilities_settings">
      <div class="col-md-12">
        <div class="tw-mb-2 sm:tw-mb-4">
          <?php echo form_open($this->uri->uri_string(), array('class' => 'custom_menu-form')); ?>
          <div class="panel_s">
            <div class="panel-body">
              <div class="row">
              </div>
            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<?php
echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/create_custom_menu.js') . '"></script>';
