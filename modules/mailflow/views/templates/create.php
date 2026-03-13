<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <?php

            if (isset($template_data)) {
                $requestUrl = 'mailflow/create_template/'.$template_data->id;
            } else {
                $requestUrl = 'mailflow/create_template';
            }

            echo form_open(admin_url($requestUrl));
            ?>
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo $title; ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body">

                        <div class="col-md-6">
                            <?php echo render_input('template_name', 'mailflow_template_name', $template_data->template_name ?? ''); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_input('template_subject', 'mailflow_template_subject', $template_data->template_subject ?? ''); ?>
                        </div>

                        <div class="col-md-12">
                            <?php echo render_textarea('template_content', '', $template_data->template_content ?? '', ['rows' => 10], [], '', 'tinymce'); ?>
                        </div>

                        <div class="col-md-12">
                            <strong><?php echo _l('mailflow_available_merge_fields') ?> :</strong>
                            <a href="#">{{unsubscribe_link}}</a>
                        </div>

                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-primary"><?php echo _l('save'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>

