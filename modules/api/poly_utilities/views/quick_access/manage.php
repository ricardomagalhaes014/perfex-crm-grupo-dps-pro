<?php defined('BASEPATH') or exit('No direct script access allowed');

init_head();

?>
<div id="wrapper">
    <div class="content">
        <div class="row poly_utilities_quick_access_menu_manage">
            <div class="col-md-12">
                <div class="tw-mb-2 sm:tw-mb-4">

                    <?php
                    if (has_permission('poly_utilities_shortcut_menu_extend', '', 'create')) {

                        echo form_open($this->uri->uri_string(), array('class' => 'quick_access-form')); ?>
                        <div class="panel_s">
                            <div class="panel-body">
                                <div class="row">

                                    <div class="col-md-1" data-toggle="popover" data-placement="bottom" data-content="">
                                        <label><i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_quick_access_icon_help') ?>"></i><?php echo _l('poly_utilities_quick_access_icon') ?></label>
                                        <input type="text" id="poly_utilities_quick_access_icon" name="poly_utilities_quick_access_icon" class="form-control poly-utilities-input-icon" value="fa-solid fa-shield-halved fa-fw">
                                        <span class="btn btn-default poly-utilities-icon-selected"><i class="poly-utilities-preview-icon-select fa-solid fa-shield-halved fa-fw"></i></span>
                                    </div>

                                    <div class="col-md-5">
                                        <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_quick_access_title_help') ?>"></i>
                                        <?php echo render_input('poly_utilities_quick_access_title', 'poly_utilities_quick_access_title', '', 'text', array('placeholder' => _l('poly_utilities_quick_access_title'))); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="row">
                                            <?php

                                            echo poly_utilities_common_helper::render_select('poly_utilities_quick_access_shortcut_key_pre', poly_utilities_common_helper::$alphabet, 'N', 'Shortcut key', 'col-md-3', 'poly-hotkey');

                                            echo poly_utilities_common_helper::render_select('poly_utilities_quick_access_shortcut_key_last', poly_utilities_common_helper::$numbers, 8, '&nbsp;', 'col-md-3', 'poly-hotkey');

                                            ?>
                                            <?php echo render_input('poly_utilities_quick_access_shortcut_key', 'poly_utilities_quick_access_shortcut_key', '', 'text', array('placeholder' => 'Shortcut key', 'readonly' => true), [], 'col-md-6', 'poly-bold'); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <?php echo render_input('poly_utilities_quick_access_link', 'poly_utilities_quick_access_link', '', 'text', array('placeholder' => 'https://...')); ?>
                                    </div>
                                    <?php echo poly_utilities_common_helper::render_select('poly_utilities_quick_access_link_target', poly_utilities_common_helper::$targets, '_self', 'Target', 'col-md-3'); ?>
                                    <?php echo poly_utilities_common_helper::render_select('poly_utilities_quick_access_link_rel', poly_utilities_common_helper::$rels, 'nofollow', 'Rel', 'col-md-3'); ?>
                                </div>

                            </div>

                            <div class="panel-footer">
                                <div class="tw-flex tw-justify-between tw-items-center">
                                    <span class="btn btn-primary btn-submit-poly-utilities"><?php echo _l('poly_utilities_quick_access_menu_add'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php echo form_close();
                    }
                    ?>

                </div>

                <div class="panel_s">
                    <?php $obj_storage = clear_textarea_breaks(get_option(POLY_QUICK_ACCESS_MENU));
                    $obj_old_data = [];
                    if (!empty($obj_storage)) {
                        $obj_old_data = json_decode($obj_storage);
                        if ($obj_old_data) {
                    ?>
                            <div class="panel-body panel-table-full panel-poly-quick-access-menu">
                                <div>&nbsp;<i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_quick_access_sortable_help') ?>">&nbsp;</i></div>
                                <div class="col-md-6">
                                    <div class="dd">
                                        <ol class="dd-list" id="myListItem">
                                            <?php
                                            foreach ($obj_old_data as $key => $value) {
                                            ?>
                                                <li class="dd-item dd3-item main dd-nochildren" data-icon="<?php echo "{$value->icon}"; ?>" data-title="<?php echo "{$value->title}"; ?>" data-index="<?php echo "{$value->index}"; ?>" data-link="<?php echo "{$value->link}"; ?>" data-shortcut_key="<?php echo "{$value->shortcut_key}"; ?>" data-target="<?php echo "{$value->target}"; ?>" data-rel="<?php echo "{$value->rel}"; ?>" data-id="<?php echo "mn_{$value->index}"; ?>">
                                                    <div class="dd-handle poly-handle dd3-handle"><i class="fa-solid fa-arrows-up-down-left-right"></i></div>
                                                    <div class="dd3-content">
                                                        <?php
                                                        $icon = $value->icon;
                                                        $icon_html = $icon ? "<span class='poly-utilities-preview-icon-selected {$icon}'>&nbsp;</span>&nbsp;" : '';
                                                        echo "{$icon_html}<a href='{$value->link}' target='_blank' rel='nofollow'>{$value->title}</a> <span class='poly-quick-access-shortcut-key'>{$value->shortcut_key}</span>";
                                                        ?>
                                                        <?php
                                                        if (has_permission('poly_utilities_shortcut_menu_extend', '', 'delete')) {
                                                        ?>
                                                            <span data-link="<?php echo "{$value->link}" ?>" class="poly-quick-access-menu-delete delete text-muted pull-right"><i class="fas fa-trash"></i></span>
                                                        <?php
                                                        }
                                                        ?><a href="#" class="tw-mr-1 text-muted toggle-menu-options main-item-options pull-right"><i class="fas fa-cog"></i></a>
                                                    </div>
                                                    <!-- Toggle -->
                                                    <div class="menu-options main-item-options poly-hide" data-menu-options="<?php echo "mn_{$value->index}" ?>">
                                                        <div class="row">
                                                            <?php
                                                            $arr = explode('+', $value->shortcut_key);
                                                            $hotkey = 'N';
                                                            $hotnumber = '8';
                                                            if (count($arr) >= 2) {
                                                                $hotkey = $arr[0];
                                                                $hotnumber = $arr[1];
                                                            }

                                                            echo poly_utilities_common_helper::render_select('poly_utilities_quick_access_shortcut_key_pre_' . $value->index, poly_utilities_common_helper::$alphabet, "{$hotkey}", 'Shortcut key', 'col-md-4', 'poly-hotkey', ['data-id' => $value->index]);

                                                            echo poly_utilities_common_helper::render_select('poly_utilities_quick_access_shortcut_key_last_' . $value->index, poly_utilities_common_helper::$numbers, "{$hotnumber}", 'Shortcut key', 'col-md-4', 'poly-hotkey', ['data-id' => $value->index]);

                                                            echo render_input('poly_utilities_quick_access_shortcut_key_' . $value->index, '&nbsp;', $hotkey . '+' . $hotnumber, 'text', array('placeholder' => 'Shortcut key', 'readonly' => true), [], 'col-md-4', 'poly-bold');
                                                            ?>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-2">
                                                                <label class="control-label"><?php echo _l('poly_utilities_quick_access_icon'); ?></label>
                                                                <input type="text" value="<?php if ($icon) {
                                                                                                echo $icon;
                                                                                            } ?>" class="poly-utilities-icons input_<?php echo $value->index ?> form-control" id="<?php echo "mn_icon-{$value->index}"; ?>">
                                                                <span data-id='<?php echo $value->index ?>' class="poly-utilities-icon-selected poly-utilities-preview-icon-<?php echo $value->index ?> btn btn-default"><?php echo $icon_html; ?></span>
                                                            </div>
                                                            <?php echo render_input("poly_utilities_quick_access_title_{$value->index}", 'poly_utilities_quick_access_title', $value->title, 'text', array('placeholder' => _l('poly_utilities_quick_access_title')), [], 'col-md-10'); ?>
                                                        </div>
                                                        <div class="row">
                                                            <?php echo render_input("poly_utilities_quick_access_link_{$value->index}", 'poly_utilities_quick_access_link', $value->link, 'text', array('placeholder' => 'https://...'), [], 'col-md-6'); ?>
                                                            <?php echo poly_utilities_common_helper::render_select("poly_utilities_quick_access_link_target_{$value->index}", poly_utilities_common_helper::$targets, $value->target, 'Target', 'col-md-3'); ?>
                                                            <?php echo poly_utilities_common_helper::render_select("poly_utilities_quick_access_link_rel_{$value->index}", poly_utilities_common_helper::$rels, $value->rel, 'Rel', 'col-md-3'); ?>
                                                        </div>
                                                    </div>
                                                    <!-- Toggle -->
                                                </li>
                                            <?php
                                            }
                                            ?>
                                        </ol>
                                    </div>
                                </div>
                                <div class="col-md-6 poly-utilities-list-icon">
                                    <div>
                                        <h4 class="poly-utilities-heading"><?php echo _l('poly_utilities_quick_access_shortcut_key_list_header') ?></h4>
                                    </div>
                                    <?php
                                    foreach ($obj_old_data as $key => $value) {
                                    ?>
                                        <div>
                                            <?php echo "<i class='{$value->icon} icon'></i><strong>{$value->shortcut_key}</strong>: <a href='{$value->link}' target='_blank' rel='nofollow' title='{$value->title}'>{$value->link}</a>" ?>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                            if (has_permission('poly_utilities_shortcut_menu_extend', '', 'edit')) {
                            ?>
                                <div class="panel-footer">
                                    <div class="tw-flex tw-justify-between tw-items-center">
                                        <span class="btn btn-primary btn-submit-manage-poly-utilities"><?php echo _l('submit'); ?></span>
                                    </div>
                                </div>
                            <?php
                            }
                            ?>
                    <?php
                        }
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
echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/quick_access.js') . '"></script>';
