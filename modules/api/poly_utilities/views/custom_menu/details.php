<?php
defined('BASEPATH') or exit('No direct script access allowed');
init_head();
echo '<script src="' . poly_utilities_common_helper::get_assets('modules/poly_utilities/dist/assets/js/lib/vuejs/3.4.27/vue.global.prod.js') . '"></script>';
echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/common.js') . '"></script>';
?>
<div id="wrapper">
    <div id="polyApp" class="content" style="display:contents" v-cloak>

        <div class="poly-loader" :class="{'hide': dataLoaded }">
            <div :class="{'poly-loading': !dataLoaded }">&nbsp;</div>
        </div>

        <div class="poly_utilities_settings poly-data-container" :class="{'disabled': !dataLoaded }">
            <iframe width="100%" height="100%" style="height:100vh" src="<?php echo html_escape($custom_menu['href']); ?>"></iframe>
        </div>
    </div>
</div>
<?php init_tail();

echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/custom_menu_details.js') . '"></script>';
