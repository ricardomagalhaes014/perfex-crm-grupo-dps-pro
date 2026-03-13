<?php defined('BASEPATH') or exit('No direct script access allowed');

init_head();

if (!empty($poly_utilities_aio_supports)) {
  $is_admin = $poly_utilities_aio_supports['is_admin'] ?? 'false';
  $is_clients = $poly_utilities_aio_supports['is_clients'] ?? 'false';
  $is_messages = $poly_utilities_aio_supports['is_messages'] ?? 'false';
  $is_messages_mobile = $poly_utilities_aio_supports['is_messages_mobile'] ?? 'false';
  $icon_button = $poly_utilities_aio_supports['icon_button'] ?? 'fa-solid fa-headset';
  $icon_button_color = $poly_utilities_aio_supports['icon_button_color'] ?? '#000';

  $icon_button_right = $poly_utilities_aio_supports['icon_right'] ?? 10;
  $icon_button_bottom = $poly_utilities_aio_supports['icon_bottom'] ?? 60;

  $messages = $poly_utilities_aio_supports['messages'] ?? '';
  $supports = $poly_utilities_aio_supports['supports'] ?? '';
  $supports = ($supports) ? json_decode($supports) : [];
  $is_edit = (is_admin() || has_permission('poly_utilities_supports', '', 'edit'));
}
$messages_value = $messages ? implode("\n", $messages) : '';
?>
<div id="wrapper">
  <div class="content">
    <div class="row poly_utilities_aio_supports_manage">
      <div class="col-md-12">
        <div class="tw-mb-2 sm:tw-mb-4">

          <?php
          echo form_open($this->uri->uri_string(), array('class' => 'poly_aio_supports-form')); ?>
          <div class="panel_s" :class="{ 'disabled': isProccessing, 'loading': isProccessing }">
            <div class="panel-body">
              <!-- All in one Supports -->
                <!-- Active in Admin? -->
                <div class="form-group inline-block">
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" class="poly_aio_is_supports" name="poly_aio_support_is_admin" id="poly_aio_support_is_admin" <?php echo (($is_admin == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                    <label for="poly_aio_support_is_admin"><?php echo _l('poly_aio_support_is_admin'); ?></label>
                  </div>
                </div>
                <!-- Active in Admin? -->

                <!-- Active in Clients? -->
                <div class="form-group inline-block tw-ml-4">
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" class="poly_aio_is_supports" name="poly_aio_support_is_clients" id="poly_aio_support_is_clients" <?php echo (($is_clients == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                    <label for="poly_aio_support_is_clients"><?php echo _l('poly_aio_support_is_clients'); ?></label>
                  </div>
                </div>
                <!-- Active in Clients? -->

                <!-- Messages -->
                <?php
                echo render_textarea('poly_aio_supports_messages', _l('poly_aio_supports_messages'), $messages_value, [], [], 'form-group');
                ?>
                <p class="poly-help-message"><?php echo _l('poly_aio_supports_messages_notice'); ?></p>
                <!-- Active? -->
                <div class="form-group inline-block">
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" class="poly_aio_is_supports_messages poly_aio_is_supports" name="poly_aio_is_supports_messages" id="poly_aio_is_supports_messages" <?php echo (($is_messages == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                    <label for="poly_aio_is_supports_messages"><?php echo _l('poly_aio_is_supports_messages'); ?></label>
                  </div>
                </div>
                <!-- Active? -->
                <!-- Active on Mobile? -->
                <div class="form-group inline-block tw-ml-4">
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" class="poly_aio_is_supports_messages_mobile poly_aio_is_supports" name="poly_aio_is_supports_messages_mobile" id="poly_aio_is_supports_messages_mobile" <?php echo (($is_messages_mobile == 'true') ? ' checked' : '') . (!$is_edit ? ' disabled' : '') ?>>
                    <label for="poly_aio_is_supports_messages_mobile"><?php echo _l('poly_aio_is_supports_messages_mobile'); ?></label>
                  </div>
                </div>
                <!-- Active on Mobile? -->
                <!-- Messages -->
                <hr />
                <!-- All-in-one Contact button -->
                <h3 class="text-center">
                  <?php echo _l('poly_aio_supports_button') ?>
                </h3>

                <input type="hidden" value="<?php echo $icon_button_right?>" name="poly_aio_supports_icon_right" id="poly_aio_supports_icon_right" class="form-control poly_aio_supports_icon_right">
                <input type="hidden" value="<?php echo $icon_button_bottom?>" name="poly_aio_supports_icon_bottom" id="poly_aio_supports_icon_bottom" class="form-control poly_aio_supports_icon_bottom">

                <div class="poly-field-template row text-center tw-flex" id="poly_field_aio_supports_button">
                  <div class="col-xs-12">
                    <div class="inline-block">
                      <div class="input-group">
                        <textarea name="poly_aio_supports_icon_button" class="form-control poly_aio_supports_icon_button poly_aio_supports_icon hide"><?php echo $icon_button ?></textarea>
                        <span class="btn btn-default poly-utilities-aio-icon-select" data-id="poly_field_aio_supports_button">
                          <i class="<?php echo $icon_button ?>"></i>
                        </span>
                      </div>
                    </div>
                    <div class="inline-block">
                      <div class="input-group colorpicker-input colorpicker-element">
                        <input type="text" value="<?php echo $icon_button_color ?? '#000' ?>" name="poly_aio_supports_icon_button_color" class="form-control poly_aio_supports_icon_button_color" data-fieldto="poly_aio_supports_icon_button_color" data-fieldid="">
                        <span class="input-group-addon cursor">&nbsp;</span>
                      </div>
                    </div>
                    <div class="inline-block">
                      <div class="input-group">
                        <span class="btn btn-default poly-utilities-aio-icon-select-position">
                          <i class="fa-solid fa-arrows-to-circle fa-fw"></i>
                        </span>
                      </div>
                    </div>
                    <div class="inline-block">
                      <div class="input-group">
                        <span class="btn btn-default poly-utilities-aio-icon-select-position-reset">
                          <i class="fa-solid fa-refresh fa-fw"></i>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- All-in-one Contact button -->
                <hr />
                <h3>
                  <?php echo _l('poly_aio_supports_list_head'); ?>
                </h3>
                <p class="poly-help-message"><?php echo _l('poly_aio_supports_list_notice'); ?></p>
                <!-- Supports -->
                <div id="repeater">
                  <div id="repeater-fields">
                    <!-- Data fields -->
                    <?php
                    if ($supports) {
                      foreach ($supports as $key => $support) {
                        $icon = $support->icon ?? 'fa-solid fa-shield-halved fa-fw';
                        $icon = (strpos($icon, '<') !== false) ? $icon : '<i class="' . $icon . '"></i>';
                    ?>
                        <div class="poly-field-template row active" id="poly_field_<?php echo $key ?>">
                          <?php echo poly_utilities_common_helper::render_select('poly_aio_supports_types_' . $key, poly_utilities_common_helper::$aio_supports_type, $support->type, _l('poly_aio_supports_list_item_type'), 'col-md-2 col-xs-5', 'poly_aio_supports_types'); ?>
                          <div class="col-md-1 col-xs-1">
                            <label><i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1 hidden-xs" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_quick_access_icon_help') ?>"></i><?php echo _l('poly_utilities_quick_access_icon') ?></label>
                            <textarea id="poly_aio_supports_icon_<?php echo $key ?>" name="poly_aio_supports_icon" class="form-control poly_aio_supports_icon hide"><?php echo $support->icon ?></textarea>
                            <span class="btn btn-default poly-utilities-aio-icon-select" data-id="poly_field_<?php echo $key ?>"><?php echo $icon ?></span>
                          </div>
                          <div class="col-md-2 col-xs-6">
                            <label><i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1 hidden-xs" data-toggle="tooltip" data-title="<?php echo _l('poly_aio_supports_list_item_icon_color_message') ?>"></i><?php echo _l('poly_aio_supports_list_item_icon_color') ?></label>
                            <div class="input-group colorpicker-input colorpicker-element">
                              <input type="text" value="<?php echo $support->icon_color ?>" name="poly_aio_supports_icon_color" id="poly_aio_supports_icon_color_<?php echo $key ?>" class="form-control poly_aio_supports_icon_color" data-fieldto="poly_aio_supports_icon_color_<?php echo $key ?>" data-fieldid="<?php echo $key ?>">
                              <span class="input-group-addon cursor">&nbsp;</span>
                            </div>
                          </div>
                          <div class="col-md-3 col-xs-5"><label class="row col-md-12"><?php echo _l('poly_aio_supports_list_item_title') ?></label><input type="text" class="form-control poly_aio_supports_title" placeholder="<?php echo _l('poly_aio_supports_list_item_title') ?>" value="<?php echo $support->title ?>"></div>
                          <div class="col-md-3 col-xs-4"><label class="row col-md-12"><?php echo _l('poly_aio_supports_list_item_content') ?></label><input type="text" class="form-control poly_aio_supports_content" placeholder="<?php echo _l('poly_aio_supports_list_item_content') ?>" value="<?php echo $support->content ?>"></div>
                          <div class="col-md-1 col-xs-3">
                            <div class="cursor poly-aio-handle-sortable"><i class="fa-solid fa-arrows-up-down-left-right"></i></div>
                            <?php
                            if (is_admin() || has_permission('poly_utilities_supports', '', 'delete')) {
                            ?>
                              <div class="cursor poly-aio-handle-delete" data-id="poly_field_<?php echo $key ?>"><i class="fa fa-trash"></i></div>
                            <?php
                            }
                            ?>
                          </div>
                        </div>
                    <?php
                      }
                    }
                    ?>
                    <!-- Data fields -->
                    <!-- Template for fields -->
                    <div class="poly-fields-template hide">
                      <div class="poly-field-template row">
                        <?php echo poly_utilities_common_helper::render_select('poly_aio_supports_types', poly_utilities_common_helper::$aio_supports_type, 'link', _l('poly_aio_supports_list_item_type'), 'col-md-2 col-xs-5', 'poly_aio_supports_types'); ?>
                        <div class="col-md-1 col-xs-1">
                          <label><i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1 hidden-xs" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_quick_access_icon_help') ?>"></i><?php echo _l('poly_utilities_quick_access_icon') ?></label>
                          <textarea name="poly_aio_supports_icon" class="form-control poly_aio_supports_icon hide">fa-solid fa-shield-halved fa-fw</textarea>
                          <span class="btn btn-default poly-utilities-aio-icon-select">
                            <i class="fa-solid fa-shield-halved fa-fw"></i>
                          </span>
                        </div>
                        <div class="col-md-2 col-xs-6">
                          <label><i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1 hidden-xs" data-toggle="tooltip" data-title="<?php echo _l('poly_aio_supports_list_item_icon_color_message') ?>"></i><?php echo _l('poly_aio_supports_list_item_icon_color') ?></label>
                          <div class="input-group colorpicker-input colorpicker-element">
                            <input type="text" value="" name="poly_aio_supports_icon_color" class="form-control poly_aio_supports_icon_color" data-fieldto="poly_aio_supports_icon_color" data-fieldid="">
                            <span class="input-group-addon cursor">&nbsp;</span>
                          </div>
                        </div>
                        <div class="col-md-3 col-xs-5"><label class="row col-md-12"><?php echo _l('poly_aio_supports_list_item_title') ?></label><input type="text" class="form-control poly_aio_supports_title" placeholder="<?php echo _l('poly_aio_supports_list_item_title') ?>"></div>
                        <div class="col-md-3 col-xs-4"><label class="row col-md-12"><?php echo _l('poly_aio_supports_list_item_content') ?></label><input type="text" class="form-control poly_aio_supports_content" placeholder="<?php echo _l('poly_aio_supports_list_item_content') ?>"></div>
                        <div class="col-md-1 col-xs-3">
                          <div class="cursor poly-aio-handle-sortable"><i class="fa-solid fa-arrows-up-down-left-right"></i></div>
                          <?php
                          if (is_admin() || has_permission('poly_utilities_supports', '', 'delete')) {
                          ?>
                            <div class="cursor poly-aio-handle-delete"><i class="fa fa-trash"></i></div>
                          <?php
                          }
                          ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <?php
                  if (is_admin() || has_permission('poly_utilities_supports', '', 'create')) {
                  ?>
                    <div class="btn" id="add-field"><i class="far fa-plus-square tw-mr-1"></i>&nbsp;<?php echo _l('poly_aio_supports_add_new_contact'); ?></div>
                  <?php
                  }
                  ?>
                  <!-- Template for fields -->
                </div>

                <!-- Supports -->
              <!-- All in one Supports -->

            </div>
            <?php echo form_close();
            if (is_admin() || has_permission('poly_utilities_supports', '', 'edit')) {
            ?>
              <div class="panel-footer poly-aio-panel-footer">
                <div class="row">
                  <div class="btn-bottom-toolbar tw-flex tw-justify-between tw-items-center">
                    <span class="btn btn-primary btn-submit-poly-aio-supports"><?php echo _l('poly_aio_supports_save') ?></span>
                  </div>
                </div>
              </div>
            <?php
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php
  init_tail();
  echo '<script src="' . poly_utilities_common_helper::get_assets('modules/poly_utilities/dist/assets/js/lib/sortable/1.15.0/sortable.min.js') . '"></script>';
  echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/support.js') . '"></script>';
