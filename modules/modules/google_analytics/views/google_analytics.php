<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <a href="#" onclick="save_google_analytics(); return false;" class="btn btn-info">Save Changes</a>
                        <a href="#" onclick="enable_google_analytics(); return false;" class="btn btn-info" <?php if (get_option('google_analytics') == 'enable') echo 'disabled';?>>Enable Google Analytics Support</a>
                        <a href="#" onclick="disable_google_analytics(); return false;" class="btn btn-info"<?php if (get_option('google_analytics') == 'disable') echo 'disabled';?>>Disable Google Analytics Support</a>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="bold" for="google_analytics_clients_and_admin_area">Google Analytics for both Admin & Clients area (frontend & backend) <i class="fa fa-question-circle" data-toggle="tooltip" data-title="If you paste your code here, your Google Analytics service will load in Admin area and Customers area aswell."></i></label>
                            <textarea name="google_analytics_clients_and_admin_area" id="google_analytics_clients_and_admin_area" rows="10" class="form-control"><?php echo clear_textarea_breaks(get_option('google_analytics_clients_and_admin_area')); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="bold" for="google_analytics_admin_area">Google Analytics for Admin area only (backend) <i class="fa fa-question-circle" data-toggle="tooltip" data-title="If you paste your code here, your Google Analytics service will load in Admin area only."></i></label>
                            <textarea name="google_analytics_admin_area" id="google_analytics_admin_area" rows="10" class="form-control"><?php echo clear_textarea_breaks(get_option('google_analytics_admin_area')); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="bold" for="google_analytics_clients_area">Google Analytics for Clients area only (frontend) <i class="fa fa-question-circle" data-toggle="tooltip" data-title="If you paste your code here, your Google Analytics service will load in Customers area only."></i></label>
                            <textarea name="google_analytics_clients_area" id="google_analytics_clients_area" rows="10" class="form-control"><?php echo clear_textarea_breaks(get_option('google_analytics_clients_area')); ?></textarea>
                        </div>
                        <br>
                        <br>
                        <span style="color:rgba(0, 0, 0, 0.5);"><i>Thank you for using our module!
                        <br>
                        If you face any issues, our team is always ready to help you at <a href="https://themesic.com/support" target="_blank"><b>Clients Area</b></a>
                        <br>
                        <br>
                        Rating our module with your honest feedback on CodeCanyon is appreciated in advance and will help us.</i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script type="text/javascript" src="<?php echo $this->config->base_url(); ?>modules/google_analytics/js/main.js"></script>
</body>
</html>