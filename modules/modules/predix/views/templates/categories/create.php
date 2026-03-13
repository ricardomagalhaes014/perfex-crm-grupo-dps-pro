<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <?php

            if (isset($category_data)) {
                $requestUrl = 'predix/create_template_category/'.$category_data->id;
            } else {
                $requestUrl = 'predix/create_template_category';
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
                            <?php echo render_input('category_name', 'predix_category_name', $category_data->category_name ?? ''); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_input('category_description', 'predix_category_description', $category_data->category_description ?? ''); ?>
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

