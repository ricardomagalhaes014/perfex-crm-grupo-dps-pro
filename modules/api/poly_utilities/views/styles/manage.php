<?php defined('BASEPATH') or exit('No direct script access allowed');

init_head();

$obj_storage = clear_textarea_breaks(get_option(POLY_STYLES));
$obj_old_data = [];
if (!empty($obj_storage)) {
    $obj_old_data = json_decode($obj_storage);
}
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2 sm:tw-mb-4">
                    <?php
                    if (has_permission('poly_utilities_styles_extend', '', 'create')) {
                    ?>
                        <a href="<?php echo admin_url('poly_utilities/styles_add'); ?>">
                            <i class="far fa-plus-square tw-mr-1"></i>
                            <?php echo _l('new_poly_utilities_style'); ?>
                        </a>
                    <?php } ?>
                </div>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php
                        if (is_array($obj_old_data) && count($obj_old_data)) {
                            $idx = 0;
                            foreach ($obj_old_data as $key => $value) {
                                $idx++;
                                $is_lock = ($current_user_id != 1 && (isset($value->is_lock) && $value->is_lock == 'true')) ? 'true' : 'false';
                        ?>
                                <div class="<?php echo ($is_lock == 'true' ? 'disabled' : '') ?>" data-title="<?php echo "{$value->title}"; ?>" data-index="<?php echo "{$value->file}"; ?>" data-id="<?php echo "mn_{$idx}"; ?>">
                                    <span><?php echo $idx ?>. <a href="<?php echo base_url('modules/poly_utilities/uploads/css/' . $value->file) . '.css' ?>" target="_blank" rel="nofollow"><?php echo $value->title ?></a></span>
                                    <?php
                                    if (has_permission('poly_utilities_styles_extend', '', 'delete')) {
                                    ?>
                                        <span class="tw-mr-1 poly-resource-delete delete text-muted pull-right" data-id="<?php echo "{$value->file}" ?>"><i class="fas fa-trash"></i></span>
                                    <?php
                                    }
                                    ?>
                                    <span class="tw-mr-1 pull-right">
                                        <a rel="nofollow" target="_blank" download="<?php echo $value->file . '.css' ?>" href="<?php echo base_url('modules/poly_utilities/uploads/css/' . $value->file) . '.css' ?>">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                    </span>
                                    <span class="tw-mr-1 pull-right"><a href="#" class="text-muted toggle-menu-options main-item-options"><i class="fas fa-cog"></i></a></span>
                                    <?php
                                    if (has_permission('poly_utilities_styles_extend', '', 'edit')) {
                                    ?>
                                        <span class="tw-mr-1 pull-right">
                                            <a href="<?php echo admin_url('poly_utilities/styles_add?id=' . $value->file); ?>">
                                                <i class="fa-regular fa-pen-to-square poly-icon"></i>
                                            </a>
                                        </span>
                                    <?php
                                    }
                                    ?>
                                    <div id="poly_resource_status_<?php echo $value->file ?>">

                                        <!-- Is Lock? -->
                                        <div class="inline-block">
                                            <div class="checkbox checkbox-primary">
                                                <input type="checkbox" data-id="<?php echo $value->file ?>" name="poly_utilities_resource_is_lock_<?php echo $key ?>" id="poly_utilities_resource_is_lock_<?php echo $key ?>" class="poly_utilities_resource_status is_lock" <?php echo (isset($value->is_lock) ? (($value->is_lock == 'true') ? ' checked' : '') : '') ?><?php echo (($current_user_id != 1) ? ' disabled' : '') ?>>
                                                <label for="poly_utilities_resource_is_lock_<?php echo $key ?>"><?php echo _l('poly_utilities_resource_is_lock_short'); ?></label>
                                            </div>
                                        </div>
                                        <!-- Is Lock -->

                                        <!-- Is Admin? -->
                                        <div class="inline-block">
                                            <div class="checkbox checkbox-primary">
                                                <input type="checkbox" data-id="<?php echo $value->file ?>" name="poly_utilities_resource_is_admin_<?php echo $key ?>" id="poly_utilities_resource_is_admin_<?php echo $key ?>" class="poly_utilities_resource_status is_admin" <?php echo (($value->mode == 'admin_customers' || $value->mode == 'admin') ? ' checked' : '') ?>>
                                                <label for="poly_utilities_resource_is_admin_<?php echo $key ?>"><?php echo _l('poly_utilities_resource_is_admin_short'); ?></label>
                                            </div>
                                        </div>
                                        <!-- Is Admin -->

                                        <!-- Is Clients? -->
                                        <div class="inline-block tw-ml-4">
                                            <div class="checkbox checkbox-primary">
                                                <input type="checkbox" data-id="<?php echo $value->file ?>" name="poly_utilities_resource_is_customers_<?php echo $key ?>" id="poly_utilities_resource_is_customers_<?php echo $key ?>" class="poly_utilities_resource_status is_customer" <?php echo (($value->mode == 'admin_customers' || $value->mode == 'customers') ? ' checked' : '') ?>>
                                                <label for="poly_utilities_resource_is_customers_<?php echo $key ?>"><?php echo _l('poly_utilities_resource_is_customers_short'); ?></label>
                                            </div>
                                        </div>
                                        <!-- Is Clients? -->

                                    </div>

                                    <!-- Toggle -->
                                    <div class="menu-options main-item-options poly-hide" data-menu-options="<?php echo "mn_{$idx}" ?>">
                                        <div class="row">
                                            <?php echo render_input("poly_utilities_resource_name_{$value->file}", 'poly_utilities_resource_name', $value->title, 'text', array('readonly' => true), [], 'col-md-12'); ?>
                                            <?php echo render_input("poly_utilities_resource_file_{$value->file}", 'poly_utilities_file_name', base_url('modules/poly_utilities/uploads/css/' . $value->file) . '.css', 'text', array('readonly' => true), [], 'col-md-12'); ?>
                                        </div>
                                    </div>
                                    <hr />
                                    <!-- Toggle -->
                                </div>
                            <?php
                            }
                            ?>
                    </div>
                <?php
                        } else {
                            $this->load->view('poly_utilities/blank');
                        }
                ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php

init_tail();
echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/manage_styles.js') . '"></script>';
