<?php defined('BASEPATH') or exit('No direct script access allowed');

init_head();
$is_edit = (is_admin() || has_permission('poly_utilities_widgets_extend', '', 'edit'));
?>
<div id="wrapper">
    <div class="content">
        <div class="row poly_utilities_quick_access_menu_manage">
            <div class="col-md-12 tw-p-1">
                <?php
                $is_disabled = (is_admin() || has_permission('poly_utilities_widgets_extend', '', 'edit') ? '' : ' disabled');
                poly_utilities_widget_helper::display_avaible_widgets($is_disabled);
                poly_utilities_widget_helper::display_widgets_area($is_disabled);
                ?>
            </div>
        </div>
    </div>
</div>

<?php
init_tail();
echo '<script src="' . poly_utilities_common_helper::get_assets('modules/poly_utilities/dist/assets/js/lib/sortable/1.15.0/sortable.min.js') . '"></script>';
echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/widgets.js') . '"></script>';
