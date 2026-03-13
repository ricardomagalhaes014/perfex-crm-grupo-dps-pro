    <?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
    <?php init_head(); ?>
    <div id="wrapper">
        <div class="content">
            <div class="row">
                <div class="col-md-6">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h4 class="no-margin">  <?php echo _l('vl_client_heading'); ?></h4>
                            <hr class="hr-panel-heading" />
                            <?php
                            $drive_id = get_option('vl_google_client_id');
                            $drive_secret = get_option('vl_google_client_secret');
                            $drive_url = get_option('vl_google_client_redirect_uri');
                            $drive_check = get_option('is_vl_google_drive');
                            echo form_open_multipart($this->uri->uri_string(), array('id' => 'upload_video_form'));
                            ?>
                            <div class="form-group">
                                <label for="upload_type" class="control-label clearfix">
                                    <?php echo _l('vl_ask_for_upload_gdrive'); ?> </label>
                                <div class="radio radio-primary radio-inline">
                                    <input type="radio" class="upload_type" id="upload-type-file" name="drivecheck" value="yes" <?php if ($drive_check == 'yes') : ?>checked<?php endif; ?>>
                                    <label for="upload-type-file">
                                        <?php  echo _l('vl_input_yes');?> </label>
                                </div>
                                <div class="radio radio-primary radio-inline">
                                    <input type="radio" id="upload-type-link" class="upload_type" name="drivecheck" value="no" <?php if ($drive_check == 'no') : ?>checked<?php endif; ?>>
                                    <label for="upload-type-link">
                                    <?php  echo _l('vl_input_no');?>  </label>
                                </div>
                            </div>
                            <?php
                            echo render_input('driveid', _l('vl_client_id'), $drive_id, '', ['placeholder' => _l('vl_client_id_placeholder')]);
                            echo render_input('drivesecret', _l('vl_client_secret'), $drive_secret, '', ['placeholder' => _l('vl_drivesecret_placeholder')]);
                            echo render_textarea('driveurl', _l('vl_client_uri'), $drive_url, ['placeholder' => _l('vl_driveurl_placeholder')],  [], '');
                            ?>
                            <button type="submit" class="btn btn-info pull-right save_vl_btn" data-><?php echo _l('submit'); ?></button>
                            <?php echo form_close(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php init_tail(); ?>
    <script>
        function validate_form() {
            <?php if (!isset($video) && empty($video)) { ?>
                appValidateForm($('#upload_video_form'), {
                    drivecheck: 'required',
                    driveid: 'required',
                    drivesecret: 'required',
                    driveurl: 'required'
                });
            <?php } else { ?>
                appValidateForm($('#upload_video_form'), {
                    drivecheck: 'required',
                    driveid: 'required',
                    drivesecret: 'required',
                    driveurl: 'required'
                });
            <?php } ?>
        }
        $(function() {
            $('body').on('click', 'button.save_vl_btn', function() {
                $('form#upload_video_form').submit();
            });
        });
    </script>
    </body>

    </html>