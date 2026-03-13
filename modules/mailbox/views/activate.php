<?php defined('BASEPATH') || exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-6 col-md-offset-3">
            <div class="panel_s">
               <div class="panel-body">
                  <h4>Module activation</h4>
                  <hr class="hr-panel-heading">
                  Your module has been successfully activated!
               </div>
               <div class="panel-footer"><?php echo "Version " . $this->app_modules->get($module_name)['headers']['version'] ?? "" ?></div>
            </div>
         </div>
         <div class="col-md-3">
         </div>
      </div>
   </div>
</div>
<?php init_tail(); ?>
