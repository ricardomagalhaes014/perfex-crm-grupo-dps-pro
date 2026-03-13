<?php echo form_open(admin_url('poly_utilities/update_banners_settings'), ['id' => 'poly_utilities_banners_settings_form', '@submit.prevent' => 'bannersSettingsSubmit']); ?>
<div>
    <div class="col-md-6">
        <h4>Banners</h4>
        <div>
            <?php echo poly_utilities_common_helper::render_toggle_vuejs('active', _l('poly_utilities_banners_media_activate_all_positions'), '', ['@change' => 'bannersSettingsSubmit(handle_item_settings)'], [], '', '', 'handle_item_settings.active'); ?>
        </div>
        <div>
            <?php echo poly_utilities_common_helper::render_toggle_vuejs('is_autoplay', _l('poly_utilities_banners_media_autoplay'), '', ['@change' => 'bannersSettingsSubmit()'], [], '', '', 'handle_item_settings.is_autoplay'); ?>
        </div>
        <div>
            <?php echo poly_utilities_common_helper::render_toggle_vuejs('is_controls', _l('poly_utilities_banners_media_show_controls'), '', ['@change' => 'bannersSettingsSubmit()'], [], '', '', 'handle_item_settings.is_controls'); ?>
        </div>
        <div>
            <?php echo poly_utilities_common_helper::render_toggle_vuejs('is_thumbnails', _l('poly_utilities_banners_media_thumbnails'), '', ['@change' => 'bannersSettingsSubmit()'], [], '', '', 'handle_item_settings.is_thumbnails'); ?>
        </div>
        <div class="row">
            <?php echo poly_utilities_common_helper::render_select('transition_effect', poly_utilities_common_helper::$transition_effects, 'handle_item_settings.transition_effect', _l('poly_utilities_banners_media_transition_effects'), 'col-md-6', '', array('v-model' => 'handle_item_settings.transition_effect', '@change' => 'bannersSettingsSubmit()')); ?>
        </div>
    </div>
    <div class="col-md-6">
        <h4>Announcements</h4>
        <div>
            <?php echo poly_utilities_common_helper::render_toggle_vuejs('active_announcements', _l('poly_utilities_banners_media_activate_all_positions_announcements'), '', ['@change' => 'bannersSettingsSubmit(handle_item_settings)'], [], '', '', 'handle_item_settings.active_announcements'); ?>
        </div>
        <div>
            <?php echo poly_utilities_common_helper::render_toggle_vuejs('is_autoplay_announcements', _l('poly_utilities_banners_media_autoplay'), '', ['@change' => 'bannersSettingsSubmit()'], [], '', '', 'handle_item_settings.is_autoplay_announcements'); ?>
        </div>
        <div>
            <?php echo poly_utilities_common_helper::render_toggle_vuejs('is_controls_announcements', _l('poly_utilities_banners_media_show_controls'), '', ['@change' => 'bannersSettingsSubmit()'], [], '', '', 'handle_item_settings.is_controls_announcements'); ?>
        </div>
        <div class="row">
            <?php echo poly_utilities_common_helper::render_select('transition_effect_announcements', poly_utilities_common_helper::$transition_effects, 'handle_item_settings.transition_effect_announcements', _l('poly_utilities_banners_media_transition_effects'), 'col-md-6', '', array('v-model' => 'handle_item_settings.transition_effect_announcements', '@change' => 'bannersSettingsSubmit()')); ?>
        </div>
    </div>
</div>
<?php
echo form_close();
echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/banners_settings.js') . '"></script>';
