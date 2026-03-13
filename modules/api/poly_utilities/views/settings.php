<?php defined('BASEPATH') or exit('No direct script access allowed');

init_head();

echo '<script src="' . poly_utilities_common_helper::get_assets('modules/poly_utilities/dist/assets/js/lib/vuejs/3.4.27/vue.global.prod.js') . '"></script>';
echo '<link rel="stylesheet" href="' . poly_utilities_common_helper::get_assets('modules/poly_utilities/dist/assets/css/lib/select2/select2.min.css') . '">';

$defaults = [
  'is_active_logout_buttons' => 'false',
  'is_sticky' => 'false',
  'is_search_menu' => 'false',
  'is_data_table_filters_column' => 'true',
  'is_data_table_reorder_column' => 'true',
  'is_button_data_table_filters_column' => 'true',
  'is_quick_access_menu' => 'true',
  'is_quick_access_menu_icons' => 'true',
  'is_table_of_content' => 'false',
  'is_active_scripts' => 'true',
  'is_active_styles' => 'true',
  'is_note_confirm_delete' => 'true',
  'is_operation_functions' => 'true',
  'is_scroll_to_top' => 'false',
  'scroll_icon_button_right' => 10,
  'scroll_icon_button_bottom' => 30,
  'is_toggle_sidebar_menu' => 'false',
  'is_admin_breadcrumb' => 'true',
];

foreach ($defaults as $key => $default) {
  $$key = isset($poly_utilities_settings[$key]) ? $poly_utilities_settings[$key] : $default;
}

$is_edit = has_permission('poly_utilities_settings', '', 'edit');


$current_user_id = get_staff_user_id();
?>
<div id="wrapper">
  <div class="content">
    <div class="row poly_utilities_settings">
      <div class="col-md-12">
        <div class="tw-mb-2 sm:tw-mb-4">
          <?php echo form_open($this->uri->uri_string(), array('class' => 'quick_access-form', 'id' => 'poly_utilities_settings_form')); ?>
          <div class="panel_s">
            <div class="panel-body">

              <div class="row">
                <!-- Tab Navigation (Vertical Tabs) -->
                <div class="col-md-3">
                  <h4><?php echo _l('poly_utilities_settings_heading') ?></h4>
                  <ul class="nav nav-pills" id="settingsTabs" role="tablist">
                    <li class="nav-item active">
                      <a class="nav-link" id="ui-tab" data-toggle="pill" href="#uiSettings" role="tab" aria-controls="uiSettings" aria-selected="true">
                        <i class="fa-solid fa-palette fa-fw"></i> <?php echo _l('poly_utilities_settings_tab_ui_display') ?>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="quick-access-tab" data-toggle="pill" href="#quickAccessSettings" role="tab" aria-controls="quickAccessSettings" aria-selected="false">
                        <i class="fa-solid fa-list-check"></i> <?php echo _l('poly_utilities_settings_tab_quick_access') ?>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="advanced-tab" data-toggle="pill" href="#advancedSettings" role="tab" aria-controls="advancedSettings" aria-selected="false">
                        <i class="fa fa-cog menu-icon"></i> <?php echo _l('poly_utilities_settings_tab_advanced_options') ?>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="data-table-tab" data-toggle="pill" href="#dataTableSettings" role="tab" aria-controls="dataTableSettings" aria-selected="false">
                        <i class="fa-solid fa-table fa-fw"></i> <?php echo _l('poly_utilities_settings_tab_datatable') ?>
                      </a>
                    </li>
                  </ul>
                </div>

                <!-- Tab Content -->
                <div class="col-md-9">
                  <div class="tab-content" id="settingsTabsContent">

                    <!-- Blank Content for UI & Display -->
                    <div class="tab-pane fade active in" id="uiSettings" role="tabpanel" aria-labelledby="ui-tab">
                      <h4><?php echo _l('poly_utilities_settings_tab_ui_display') ?></h4>

                      <!-- Is toggle sidebar menu? -->
                      <?php
                      $favicon = get_option('favicon');
                      $favicon_path = (!empty($favicon)) ? base_url('uploads/company/' . $favicon) : '';
                      ?>
                      <div class="form-group tw-mb-0 relative poly-inline-flex">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_is_toggle_sidebar_menu" id="poly_utilities_is_toggle_sidebar_menu" <?php echo (($is_toggle_sidebar_menu == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_is_toggle_sidebar_menu"><?php echo _l('poly_utilities_is_toggle_sidebar_menu_icon_help'); ?></label>
                        </div>
                        <div class="poly-favicon"><a href="<?php echo base_url('admin/settings?group=general') ?>" target="_blank"><i class="fa fa-edit"></i></a><img class="poly-favicon-thumb" src="<?php echo $favicon_path ?>" /></div>
                      </div>
                      <!-- Is toggle sidebar menu? -->

                      <!-- Is active logout button? -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_is_active_logout_buttons" id="poly_utilities_is_active_logout_buttons" <?php echo (($is_active_logout_buttons == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_is_active_logout_buttons"><?php echo _l('poly_utilities_is_active_logout_buttons'); ?></label>
                        </div>
                      </div>
                      <!-- Is active logout button? -->

                      <!-- Is search menu? -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_is_search_menu" id="poly_utilities_is_search_menu" <?php echo (($is_search_menu == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_is_search_menu"><?php echo _l('poly_utilities_is_search_menu'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_is_search_menu_icon_message') ?>">&nbsp;</i>
                        </div>
                      </div>
                      <!-- Is search menu? -->

                      <!-- Is sticky menu? -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_topbar_is_sticky" id="poly_utilities_topbar_is_sticky" <?php echo (($is_sticky == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_topbar_is_sticky"><?php echo _l('poly_utilities_topbar_is_sticky'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_is_sticky_menu_topbar_icon_message') ?>">&nbsp;</i>
                        </div>
                      </div>
                      <!-- Is sticky menu? -->

                      <!-- Is breadcrumb? -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_is_admin_breadcrumb" id="poly_utilities_is_admin_breadcrumb" <?php echo (($is_admin_breadcrumb == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_is_admin_breadcrumb"><?php echo _l('poly_utilities_is_admin_breadcrumb'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_is_admin_breadcrumb_icon_message') ?>">&nbsp;</i>
                        </div>
                      </div>
                      <!-- Is breadcrumb? -->

                      <!-- Active scroll to top -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">

                          <input type="hidden" value="<?php echo $scroll_icon_button_right ?>" name="poly_utilities_scroll_icon_right" id="poly_utilities_scroll_icon_right" class="form-control poly_utilities_scroll_icon_right">
                          <input type="hidden" value="<?php echo $scroll_icon_button_bottom ?>" name="poly_utilities_scroll_icon_bottom" id="poly_utilities_scroll_icon_bottom" class="form-control poly_utilities_scroll_icon_bottom">

                          <input type="checkbox" name="poly_utilities_enable_scroll_to_top" id="poly_utilities_enable_scroll_to_top" <?php echo (($is_scroll_to_top == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_enable_scroll_to_top"><?php echo _l('poly_utilities_enable_scroll_to_top'); ?></label>

                          <div class="inline-block">
                            <div class="input-group">
                              <span class="btn btn-default poly-utilities-scroll-to-top-icon-select-position btn-reset">
                                <i class="fa-solid fa-arrows-to-circle fa-fw"></i>
                              </span>&nbsp;
                              <span class="btn btn-default poly-utilities-scroll-to-top-icon-select-position-reset btn-reset">
                                <i class="fa-solid fa-refresh fa-fw"></i>
                              </span>
                            </div>
                          </div>

                        </div>
                      </div>
                      <!-- Active scroll to top -->
                    </div>
                    <!-- Blank Content for Quick Access -->
                    <div class="tab-pane fade" id="quickAccessSettings" role="tabpanel" aria-labelledby="quick-access-tab">
                      <h4><?php echo _l('poly_utilities_settings_tab_quick_access') ?></h4>
                      <!-- Enable Quick Access Menu? -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_is_quick_access_menu" id="poly_utilities_is_quick_access_menu" <?php echo (($is_quick_access_menu == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_is_quick_access_menu"><?php echo _l('poly_utilities_is_quick_access_menu'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_is_enable_quick_access_menu_icon_message') ?>">&nbsp;</i>
                        </div>
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_is_quick_access_menu_icons" id="poly_utilities_is_quick_access_menu_icons" <?php echo (($is_quick_access_menu_icons == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_is_quick_access_menu_icons"><?php echo _l('poly_utilities_is_quick_access_menu_icons'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_is_quick_access_menu_icon_message') ?>">&nbsp;</i>
                        </div>
                      </div>
                      <!-- Enable Quick Access Menu? -->
                      <!-- Is Table of content? -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_is_table_of_content" id="poly_utilities_is_table_of_content" <?php echo (($is_table_of_content == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_is_table_of_content"><?php echo _l('poly_utilities_is_table_of_content'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_is_table_of_content_icon_message') ?>">&nbsp;</i>
                        </div>
                      </div>
                      <!-- Is Table of content? -->
                    </div>
                    <!-- Blank Content for Advanced Customization -->
                    <div class="tab-pane fade" id="advancedSettings" role="tabpanel" aria-labelledby="advanced-tab">
                      <h4><?php echo _l('poly_utilities_settings_tab_advanced_options') ?></h4>
                      <!-- Enable custom JS -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_enable_scripts" id="poly_utilities_enable_scripts" <?php echo (($is_active_scripts == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_enable_scripts"><?php echo _l('poly_utilities_enable_scripts'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_enable_scripts_icon_message') ?>">&nbsp;</i>
                        </div>
                      </div>
                      <!-- Enable cusom JS -->

                      <!-- Enable custom CSS -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_enable_styles" id="poly_utilities_enable_styles" <?php echo (($is_active_styles == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_enable_styles"><?php echo _l('poly_utilities_enable_styles'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_enable_styles_icon_message') ?>">&nbsp;</i>
                        </div>
                      </div>
                      <!-- Enable custom CSS -->
                      <!-- Active confirm delete note -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_enable_note_confirm_delete" id="poly_utilities_enable_note_confirm_delete" <?php echo (($is_note_confirm_delete == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_enable_note_confirm_delete"><?php echo _l('poly_utilities_enable_note_confirm_delete'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_enable_note_confirm_delete_icon_message') ?>">&nbsp;</i>
                        </div>
                      </div>
                      <!-- Active confirm delete note -->

                      <!-- Active operation actions -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_enable_operation_functions" id="poly_utilities_enable_operation_functions" <?php echo (($is_operation_functions == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_enable_operation_functions"><?php echo _l('poly_utilities_enable_operation_functions'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_enable_operation_functions_icon_message') ?>">&nbsp;</i>
                        </div>
                      </div>
                      <!-- Active operation actions -->
                    </div>
                    <!-- Blank Content for Data Tables -->
                    <div class="tab-pane fade" id="dataTableSettings" role="tabpanel" aria-labelledby="data-table-tab">
                      <h4><?php echo _l('poly_utilities_settings_tab_datatable') ?></h4>
                      <!-- Data Table filters -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_enable_data_table_filters_column" id="poly_utilities_enable_data_table_filters_column" <?php echo (($is_data_table_filters_column == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_enable_data_table_filters_column"><?php echo _l('poly_utilities_enable_data_table_filters_column'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_enable_data_table_filters_column_icon_message') ?>">&nbsp;</i>
                        </div>
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_enable_button_data_table_filters_column" id="poly_utilities_enable_button_data_table_filters_column" <?php echo (($is_button_data_table_filters_column == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_enable_button_data_table_filters_column"><?php echo _l('poly_utilities_enable_button_data_table_filters_column'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_enable_button_data_table_filters_column_icon_message') ?>">&nbsp;</i>
                        </div>
                      </div>
                      <!-- Data Table filters -->

                      <!-- Data Table Reorder Columns -->
                      <div class="form-group tw-mb-0">
                        <div class="checkbox checkbox-primary">
                          <input type="checkbox" name="poly_utilities_enable_data_table_reorder_column" id="poly_utilities_enable_data_table_reorder_column" <?php echo (($is_data_table_reorder_column == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                          <label for="poly_utilities_enable_data_table_reorder_column"><?php echo _l('poly_utilities_enable_data_table_reorder_column'); ?></label> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_enable_data_table_reorder_column_icon_message') ?>">&nbsp;</i>

                          <div class="inline-block">
                            <div class="input-group">
                              <span class="btn btn-default btn-reset poly-utilities-all-reorder-columns-reset">
                                <i class="fa-solid fa-refresh fa-fw"></i>
                              </span>
                            </div>
                          </div>

                        </div>
                      </div>
                      <!-- Data Table Reorder Columns -->
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
      </div>

      <!-- Roles -->
      <div id="polyApp" v-cloak>

        <!-- Export/ import -->
        <div class="col-md-12">
          <div class="panel_s">
            <div class="panel-body">
              <h4><?php echo _l('poly_utilities_settings_export_import_heading') ?></h4>
              <div style="display: inline-flex; gap: 4px; align-items: center">
                <button class="btn btn-primary btn-sm" @click.stop="handleExportSettings">
                  <?php echo _l('poly_utilities_button_export_menu_list') ?>
                </button> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_button_export_menu_list_icon_message') ?>">
                  &nbsp;
                </i>

                <label for="importFile" class="btn btn-primary btn-sm">
                  <?php echo _l('poly_utilities_button_import_menu_list') ?>
                </label>
                <input type="file" id="importFile" accept=".json" @change="handleImportSettings" class="btn btn-primary btn-sm hide"> <i class="fa-regular fa-circle-question cursor" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_button_import_menu_list_icon_message'); ?>">
                  &nbsp;
                </i>
              </div>

            </div>
          </div>
        </div>
        <!-- Export/ import -->

        <div class="col-md-12<?php echo ($current_user_id != 1 ?  ' disabled' : '') ?>">
          <div class="tw-mb-2 sm:tw-mb-4">
            <?php echo form_open($this->uri->uri_string(), array('class' => 'poly_utilities_roles-form', 'id' => 'poly_utilities_roles-form', '@submit.prevent' => 'handleSubmit')); ?>
            <div class="panel_s">
              <div class="panel-body">
                <h4><?php echo _l('poly_utilities_permission_access_module_heading'); ?></h4>
                <div class="wrap">
                  <div class="left">
                    <h5 class="col-12"><?php echo _l('poly_utilities_users_can_access_modules'); ?></h5>
                    <div class="form-group poly-utilities-users-search">
                      <select id="users" style="width: 100%" class="select2 users form-control" name="users[]" multiple="multiple"></select>
                    </div>
                    <div class="poly-help-message">
                      <?php echo _l('poly_utilities_users_can_access_modules_message'); ?>
                    </div>
                  </div>
                  <div class="right">
                    <h5 class="col-12"><?php echo _l('poly_utilities_users_can_access_custom_menu'); ?></h5>
                    <div class="form-group poly-utilities-users-search">
                      <select id="users_custom_menu" style="width: 100%" class="select2 users form-control" name="users[]" multiple="multiple"></select>
                    </div>
                    <div class="poly-help-message">
                      <?php echo _l('poly_utilities_users_can_access_custom_menu_message'); ?>
                    </div>
                  </div>
                </div>
              </div>
              <?php if ($current_user_id == 1) { ?>
                <div class="panel-footer">
                  <div class="tw-flex tw-items-center">
                    <button type="submit" class="btn btn-primary"><?php echo _l('poly_utilities_custom_menu_button_save'); ?></button>
                  </div>
                </div>
              <?php
              }
              ?>
            </div>

            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
      <!-- //Roles -->

    </div>
  </div>
</div>
<?php init_tail(); ?>
<?php
echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/settings.js') . '"></script>';
echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/settings_vue.js') . '"></script>';
echo '<script src="' . poly_utilities_common_helper::get_assets('modules/poly_utilities/dist/assets/js/lib/select2/select2.min.js') . '"></script>';
