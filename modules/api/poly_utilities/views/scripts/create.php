<?php defined('BASEPATH') or exit('No direct script access allowed');

init_head();

$this->load->view('poly_utilities/code_editor_js');

$editResource = '';
$contents = '';

if (isset($_GET['id'])) {
    if (!has_permission('poly_utilities_scripts_extend', '', 'edit')) {
        access_denied();
    }
    $editResource = $_GET['id'];

    $obj_storage = clear_textarea_breaks(get_option(POLY_SCRIPTS));
    $obj_old_data = [];
    $resourceEdit = null;
    if (!empty($obj_storage)) {
        $obj_old_data = json_decode($obj_storage);

        foreach ($obj_old_data as $resource) {
            if ($resource->file === $editResource) {
                $resourceEdit = $resource;
                break;
            }
        }

        if (isset($resourceEdit)) {
            $fileResourceContent = poly_utilities_common_helper::read_file($resourceEdit->file . '.js', POLY_UTILITIES_MODULE_UPLOAD_FOLDER . '/js');
            $contents = $fileResourceContent;
        }
    }
} else {
    if (!has_permission('poly_utilities_scripts_extend', '', 'create')) {
        access_denied();
    }
}

if (isset($resourceEdit) && (isset($resourceEdit->is_lock) && $resourceEdit->is_lock === 'true') && $current_user_id!=1) {
    access_denied();
}

$fileNameAttr = array('placeholder' => 'poly-utilities-script');
$fileNameAttr = (!empty($editResource)) ? array('placeholder' => 'poly-utilities-script', 'readonly' => true) : $fileNameAttr;
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <a href="<?php echo admin_url('poly_utilities/scripts'); ?>" class="poly-list">
                    <i class="fas fa-arrow-left tw-mr-1"></i>
                    <?php echo _l('poly_utilities_scripts'); ?>
                </a>
                <?php
                if (has_permission('poly_utilities_scripts_extend', '', 'create')) {
                ?>
                    <a href="<?php echo admin_url('poly_utilities/scripts_add'); ?>">
                        <i class="far fa-plus-square tw-mr-1"></i>
                        <?php echo _l('new_poly_utilities_script'); ?>
                    </a>
                <?php } ?>
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo $title; ?>
                </h4>

                <?php echo form_open($this->uri->uri_string()); ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <?php echo poly_utilities_common_helper::render_input("poly_utilities_resource_name", 'poly_utilities_resource_name', isset($resourceEdit->title) ? $resourceEdit->title : '', 'text', array('placeholder' => _l('poly_utilities_resource_name_placeholder')), [], 'col-md-12'); ?>

                            <?php echo poly_utilities_common_helper::render_input("poly_utilities_file_name", 'poly_utilities_file_name', isset($resourceEdit->file) ? $resourceEdit->file : '', 'text', $fileNameAttr, [], 'col-md-12', 'poly-resource-name', _l('poly_utilities_file_name_help')); ?>
                        </div>

                        <!-- Is Lock? -->
                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" name="poly_utilities_resource_is_lock" id="poly_utilities_resource_is_lock" <?php echo (isset($resourceEdit->is_lock) ? (($resourceEdit->is_lock == 'true' ) ? ' checked' : '') : '') ?><?php echo (($current_user_id != 1)?' disabled':'')?>>
                                <label for="poly_utilities_resource_is_lock"><?php echo _l('poly_utilities_resource_is_lock'); ?></label>
                            </div>
                        </div>
                        <!-- Is Lock -->

                        <!-- Is Admin? -->
                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" name="poly_utilities_resource_is_admin" id="poly_utilities_resource_is_admin" <?php echo (isset($resourceEdit->mode) ? (($resourceEdit->mode == 'admin_customers' || $resourceEdit->mode == 'admin') ? ' checked' : '') : '') ?>>
                                <label for="poly_utilities_resource_is_admin"><?php echo _l('poly_utilities_resource_is_admin'); ?></label>
                            </div>
                        </div>
                        <!-- Is Admin -->

                        <!-- Is Clients? -->
                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" name="poly_utilities_resource_is_customers" id="poly_utilities_resource_is_customers" <?php echo (isset($resourceEdit->mode) ? (($resourceEdit->mode == 'admin_customers' || $resourceEdit->mode == 'customers') ? ' checked' : '') : '') ?>>
                                <label for="poly_utilities_resource_is_customers"><?php echo _l('poly_utilities_resource_is_customers'); ?></label>
                            </div>
                        </div>
                        <!-- Is Clients? -->

                        <!-- Is embed? -->
                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" name="poly_utilities_is_embed" id="poly_utilities_is_embed" <?php echo (isset($resourceEdit->is_embed) ? (($resourceEdit->is_embed == 'true') ? ' checked' : '') : '') ?>>
                                <label for="poly_utilities_is_embed"><?php echo _l('poly_utilities_is_embed'); ?></label>
                            </div>
                        </div>
                        <!-- Is embed? -->

                        <!-- Is embed position? -->
                        <div class="form-group">
                            <select class="form-control" id="poly_utilities_is_embed_position" name="poly_utilities_is_embed_position">
                                <option value="header" <?php echo (isset($resourceEdit->is_embed_position) && ($resourceEdit->is_embed_position === 'header' || !$resourceEdit->is_embed_position) ? ' selected' : '') ?>>Header - <?php echo _l('poly_utilities_is_embed_position_header_message') ?></option>
                                <option value="footer" <?php echo ((isset($resourceEdit->is_embed_position) && $resourceEdit->is_embed_position === 'footer' || (isset($resourceEdit->is_embed_position) && !$resourceEdit->is_embed_position)) ? ' selected' : '') ?>>Footer - <?php echo _l('poly_utilities_is_embed_position_footer_message') ?></option>
                            </select>
                            <p class="poly-help-message"><i class="fa-regular fa-circle-question"></i>&nbsp;<?php echo _l('poly_utilities_is_embed_position'); ?></p>
                            <div><i class="fa-solid fa-code fa-fw"></i>&nbsp;<?php echo _l('poly_utilities_scripts_message') ?></div>
                            <pre><code class="pxg-copy" style="white-space:pre-line">window.addEventListener('DOMContentLoaded', function() {
                                            //TODO: <?php echo _l('poly_utilities_scripts_message_code') ?>;
                                      });</code></pre>
                        </div>
                        <!-- Is embed position? -->

                        <?php
                        $data['contents'] = $contents;
                        $this->load->view('poly_utilities/code_editor', $data);
                        ?>
                    </div>
                    <div class="panel-footer">
                        <div class="btn-bottom-toolbar text-right tw-flex tw-justify-between tw-items-center">
                            <a href="#" class="btn btn-primary btn-submit-poly-utilities-add-resource" data-state="<?php echo isset($resourceEdit) ? true : false ?>"><?php echo _l('submit'); ?></a>
                        </div>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php

init_tail();
echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/create_script.js') . '"></script>';
