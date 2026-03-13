<?php defined('BASEPATH') or exit('No direct script access allowed');

init_head();
echo '<script src="' . poly_utilities_common_helper::get_assets('modules/poly_utilities/dist/assets/js/lib/vuejs/3.4.27/vue.global.prod.js') . '"></script>';
echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/default.js') . '"></script>';
echo '<link rel="stylesheet" href="' . poly_utilities_common_helper::get_assets('modules/poly_utilities/dist/assets/css/lib/select2/select2.min.css') . '">';
?>

<div id="polyApp" v-cloak>
    <div id="wrapper">

        <div class="poly-loader" :class="{'hide': !isProccessing }">
            <div :class="{'poly-loading': isProccessing }">&nbsp;</div>
        </div>

        <div class="content" :class="{ 'disabled': isProccessing }">
            <div class="row poly_utilities_settings poly-data-container" v-if="dataLoaded">
                <div class="col-md-12">
                    <div class="tw-mb-2 sm:tw-mb-4">
                        <!-- Add Custom Link -->
                        <?php
                        if (has_permission('poly_utilities_context_menu', '', 'create')) {
                            echo form_open(admin_url('poly_utilities/update_context_menu'), ['id' => 'poly_utilities_add_context_menu_form', '@submit.prevent' => 'handleSubmit']);
                        ?>
                            <div class="panel_s">
                                <div class="panel-body tw-pb-0">
                                    <div class="row">
                                        <div class="col-md-1">
                                            <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1 hidden-xs" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_quick_access_icon_help') ?>"></i><?php echo _l('poly_utilities_quick_access_icon') ?>
                                            <div class="input-group" id="poly_field_aio_supports_button">
                                                <span class="remove-icon poly-cursor" @click="removeIcon(item_edit_object)"><i class="fa-solid fa-circle-xmark fa-fw hidden-xs"></i></span>
                                                <textarea name="icon" class="form-control poly_aio_supports_icon_button poly_aio_supports_icon hide">{{(item_edit_object.svg ? decodeHtml(item_edit_object.svg) :item_edit_object.icon) ||''}}</textarea>

                                                <span v-if="item_edit_object.svg" class="btn btn-default poly-utilities-aio-icon-select" data-id="poly_field_aio_supports_button" v-html="decodeHtml(item_edit_object.svg)"></span>

                                                <span v-if="!item_edit_object.svg" class="btn btn-default poly-utilities-aio-icon-select" data-id="poly_field_aio_supports_button">
                                                    <i :class="item_edit_object.icon || ''"></i>
                                                </span>

                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1 hidden-xs" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_custom_menu_badge_name_icon_help') ?>"></i><?php echo _l('poly_utilities_custom_menu_badge_name_label') ?>
                                            <?php echo render_input('badge[value]', '', '', 'text', array('placeholder' => _l('poly_utilities_custom_menu_badge_name_placeholder'), 'v-model' => 'item_edit_object.badge.value')); ?>

                                        </div>
                                        <div class="col-md-2">
                                            <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1 hidden-xs" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_custom_menu_badge_color_icon_help') ?>"></i><?php echo _l('poly_utilities_custom_menu_badge_color_label') ?>
                                            <div class="input-group colorpicker-input colorpicker-element">
                                                <input type="text" id="badge[color]" name="badge[color]" class="poly-colorpicker-input-value form-control" data-fieldto="badge[color]">
                                                <span class="input-group-addon cursor" :style="'background-color:'+item_edit_object.badge.color">&nbsp;</span>
                                            </div>
                                        </div>

                                        <?php echo poly_utilities_common_helper::render_input_vuejs('name', _l('poly_utilities_custom_menu_title'), '', 'text', array('placeholder' => _l('poly_utilities_custom_menu_title')), [], 'col-md-5', '', 'item_edit_object.name', 'validation_fields.name'); ?>

                                        <div class="col-md-2">
                                            <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1 hidden-xs" data-toggle="tooltip" data-title="<?php echo _l('poly_utilities_custom_menu_item_css_icon_help') ?>"></i><?php echo poly_utilities_common_helper::render_input_vuejs('css', _l('poly_utilities_custom_menu_css'), '', 'text', array('placeholder' => _l('poly_utilities_custom_menu_css')), [], '', '', 'item_edit_object.css'); ?>
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div v-if="roles && roles.length" class="form-group col-md-5">
                                            <label style="width: 100%" for="roles"><?php echo _l('poly_utilities_custom_menu_specific_roles_label') ?>
                                                <select style="width: 100%" id="roles" class="select2 roles form-control" name="roles[]" multiple="multiple">
                                                    <option v-for="role in roles" :key="role.roleid" :value="role.roleid">{{role.name}}</option>
                                                </select></label>
                                        </div>
                                        <div class="form-group col-md-7 poly-utilities-specific-users poly-utilities-users-search">
                                            <label style="width: 100%" for="users"><?php echo _l('poly_utilities_custom_menu_specific_users_label') ?>
                                                <select id="users" style="width: 100%" class="select2 users form-control" name="users[]" multiple="multiple">
                                                </select></label>
                                        </div>
                                    </div>

                                    <div class="row">

                                        <div class="col-md-2">
                                            <label for="parent_slug"><?php echo _l('poly_utilities_custom_menu_parent_label') ?></label>
                                            <select name="parent_slug" id="parent_slug" class="form-control" v-model="item_edit_object.parent_slug">
                                                <option v-for="item in flattenedMenuItems" :key="item.slug" :value="item.slug">
                                                    {{ item.prefix + extractTextFromHtml(item.name) }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label for="type"><?php echo _l('poly_utilities_custom_menu_type_label') ?></label>
                                            <select name="type" id="type" class="form-control" v-model="item_edit_object.type" @change="handleChangeLinkType(item_edit_object)">
                                                <option v-for="item in filteredTypes" :key="Object.keys(item)[0]" :value="Object.keys(item)[0]">
                                                    {{ Object.values(item)[0] }}
                                                </option>
                                            </select>
                                        </div>

                                        <?php echo poly_utilities_common_helper::render_input_vuejs('href', _l('poly_utilities_custom_menu_href_label'), '', 'text', array('placeholder' => 'https://...'), [], 'col-md-4', '', 'item_edit_object.href', 'validation_fields.href'); ?>

                                        <div class="col-md-2">
                                            <label for="target">Target</label>
                                            <select name="target" id="target" class="form-control" v-model="item_edit_object.target">
                                                <option v-for="target in default_settings.target" :key="target" :value="target">
                                                    {{target}}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label for="rel">Rel</label>
                                            <select name="rel" id="rel" class="form-control" v-model="item_edit_object.rel">
                                                <option v-for="rel in default_settings.rels" :key="rel" :value="rel">
                                                    {{rel}}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-12" v-show="item_edit_object.type == 'popup'">
                                            <div class="form-group">
                                                <?php
                                                echo poly_utilities_common_helper::render_textarea_vuejs('popup_description', '', '', array('placeholder' => 'Content popup'), [], '', 'tinymce tinymce-manual', 'item_edit_object.popup_description');
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel-footer">
                                    <div class="tw-flex tw-items-center">
                                        <button type="submit" class="btn btn-primary" @click="isEdit(false)"><?php echo _l('poly_utilities_custom_menu_button_save'); ?></button>
                                        &nbsp;<button type="submit" v-if="is_edit" class="btn btn-success" @click="isEdit(true)"><?php echo _l('poly_utilities_custom_menu_button_update'); ?></button>
                                    </div>
                                </div>
                            </div>
                        <?php echo form_close();
                        }
                        ?>

                        <!-- END Add Custom Link -->

                        <!-- Menu items -->
                        <div class="panel_s">
                            <div class="panel-body tw-pb-0">
                                <div id="shared-lists" class="row poly-sidebar-menu">
                                    <div class="col-md-6 poly-menu">
                                        <h4 class="col-12">Configure Active Context Menu</h4>
                                        <div id="poly-active-menu" class="list-group col nested-sortable">
                                            <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1 hidden-xs"></i><?php echo _l('poly_utilities_custom_menu_arange_help') ?>
                                            <template v-for="(item, parent_index) in menu_items" :key="item.id">

                                                <div :style="handleDividerStyles(item)" v-if="item.slug && !item.slug.includes('root')" :class="['list-group-item', `nested-${parent_index}`]" :data-id="item.slug" :data-icon="item.svg ? item.svg : item.icon" :data-badge="JSON.stringify(item.badge)" :data-css="item.css" :data-href_original="item.href_original" :data-href="item.href" :data-target="item.target" :data-rel="item.rel" :data-type="item.type" :data-roles="item.roles" :data-users="item.users" :data-name="handleHtmlContent(item.name)" :data-slug="item.slug" :data-parent_slug="item.parent_slug" :data-disabled="item.disabled" :data-level="0" :data-popup_description="item.popup_description">

                                                    <span class="poly-menu-block">
                                                        <span class="poly-menu-icon" v-if="item.svg" v-html="decodeHtml(item.svg)"></span>
                                                        <i v-if="!item.svg" :class="item.icon || ''"></i>&nbsp;
                                                        <span>
                                                            <a class="custom-menu-text" v-if="item.type!=='divider'" :href="item.href" :slug="item.href" v-html="handleHtmlContent(item.name)" :style="item.css"></a>
                                                            <span v-if="item.type==='divider'" v-html="handleHtmlContent(item.name)"></span>
                                                            <span v-if="item.badge" :style="'background-color:' + ((item.badge.color !== 'transparent' && item.badge.color !== '') ? item.badge.color : '#8c8c8c')" class="tw-ml-2 badge bg-info">{{item.badge.value}}</span>
                                                        </span>
                                                    </span>
                                                    <span class="poly-cursor poly-menu-item-disabled relative pull-right" :data-id="item.slug">
                                                        <div class="onoffswitch">
                                                            <input type="checkbox" :id="'parent_checkbox-' + parent_index" class="onoffswitch-checkbox" :checked="(item.disabled && item.disabled=='true')">
                                                            <label class="onoffswitch-label" @click.stop="handleDisabled($event, item)" :for="'parent_checkbox-' + parent_index"></label>
                                                        </div>
                                                    </span>

                                                    <a v-if="item.children && item.children.length" href="#" class="tw-mr-1 text-muted toggle-widgets widget-item-blocks pull-right"><i class="fa-solid fa-caret-up"></i></a><span @click.stop="handleDelete(item)" :data-id="item.slug" class="poly-cursor tw-mr-1 text-muted pull-right"><i class="fas fa-trash"></i></span><span @click.stop="handleEdit(item)" class="poly-cursor poly-menu-item-edit tw-mr-1 text-muted pull-right"><i class="fas fa-pencil"></i></span>

                                                    <span @click.stop="handleClone(item)" class="poly-cursor poly-menu-item-clone relative pull-right"><i class="fa-solid fa-clone fa-fw"></i></span>

                                                    <!-- Submenu container area -->
                                                    <div v-if="item.children && item.children.length" :class="['tw-mt-2 list-group nested-sortable poly-hide']">
                                                        <template v-for="(item_child, sub_index) in item.children" :key="item_child.id">
                                                            <div :style="handleDividerStyles(item_child)" v-if="item_child.slug && !item_child.slug.includes('_add')" :class="['list-group-item sub',`nested-${sub_index}`]" :data-id="item_child.slug" :data-type="item_child.type" :data-roles="item_child.roles" :data-users="item_child.users" :data-name="handleHtmlContent(item_child.name)" :data-href_original="item_child.href_original" :data-css="item_child.css" :data-href="item_child.href" :data-target="item_child.target" :data-rel="item_child.rel" :data-icon="item_child.svg ? item_child.svg : item_child.icon" :data-badge="JSON.stringify(item_child.badge)" :data-slug="item_child.slug" :data-parent_slug="item_child.parent_slug" :data-disabled="item_child.disabled" :data-level="1" :data-popup_description="item_child.popup_description">

                                                                <span class="poly-menu-block">
                                                                    <span class="poly-menu-icon" v-if="item_child.svg" v-html="decodeHtml(item_child.svg)"></span>
                                                                    <i v-if="!item_child.svg" :class="item_child.icon || ''"></i>&nbsp;
                                                                    <span><a class="custom-menu-text" :href="item_child.href" :slug="item_child.href" v-html="handleHtmlContent(item_child.name)" :style="item_child.css"></a>
                                                                        <span v-if="item_child.href==''">&nbsp;(<span>Root</span>)</span><span v-if="item_child.badge" :style="'background-color:'+item_child.badge.color" class="tw-ml-2 badge bg-info">{{item_child.badge.value}}</span></span>
                                                                </span>

                                                                <span class="poly-cursor poly-menu-item-disabled relative pull-right" :data-id="item_child.slug">
                                                                    <div class="onoffswitch">
                                                                        <input type="checkbox" :id="'children_checkbox-'+ parent_index + '_' + sub_index" class="onoffswitch-checkbox" :checked="(item_child.disabled && item_child.disabled == 'true')">
                                                                        <label class="onoffswitch-label" @click.stop="handleDisabled($event, item_child)" :for="'children_checkbox-'+ parent_index + '_' + sub_index"></label>
                                                                    </div>
                                                                </span>

                                                                <a v-if="item_child.children && item_child.children.length" href="#" class="tw-mr-1 text-muted toggle-widgets widget-item-blocks pull-right"><i class="fa-solid fa-caret-up"></i></a><span @click.stop="handleDelete(item_child)" :data-id="item.slug" class="poly-cursor tw-mr-1 text-muted pull-right"><i class="fas fa-trash"></i></span><span @click.stop="handleEdit(item_child)" class="poly-cursor poly-menu-item-edit tw-mr-1 text-muted pull-right"><i class="fas fa-pencil"></i></span>

                                                                <span @click.stop="handleClone(item_child)" class="poly-cursor poly-menu-item-clone relative pull-right"><i class="fa-solid fa-clone fa-fw"></i></span>

                                                                <!-- Submenu 3 container area -->
                                                                <div v-if="item_child.children && item_child.children.length" :class="['tw-mt-2 list-group sub2 nested-sortable poly-hide']">
                                                                    <template v-for="(item_child2, sub2_index) in item_child.children" :key="item_child2.id">
                                                                        <div :style="handleDividerStyles(item_child2)" v-if="item_child2.slug && !item_child2.slug.includes('_add')" :class="['list-group-item sub sub2',`nested-${sub2_index}`]" :data-id="item_child2.slug" :data-type="item_child2.type" :data-roles="item_child2.roles" :data-users="item_child2.users" :data-name="handleHtmlContent(item_child2.name)" :data-href_original="item_child2.href_original" :data-css="item_child2.css" :data-href="item_child2.href" :data-target="item_child2.target" :data-rel="item_child2.rel" :data-icon="item_child2.svg ? item_child2.svg : item_child2.icon" :data-badge="JSON.stringify(item_child2.badge)" :data-slug="item_child2.slug" :data-parent_slug="item_child2.parent_slug" :data-disabled="item_child2.disabled" :data-level="2" :data-popup_description="item_child2.popup_description">

                                                                            <span class="poly-menu-block">
                                                                                <span class="poly-menu-icon" v-if="item_child2.svg" v-html="decodeHtml(item_child2.svg)"></span>
                                                                                <i v-if="!item_child2.svg" :class="item_child2.icon || ''"></i>&nbsp;
                                                                                <span><a class="custom-menu-text" :href="item_child2.href" :slug="item_child2.href" v-html="handleHtmlContent(item_child2.name)" :style="item_child2.css"></a><span v-if="item_child2.href==''">&nbsp;(<span>Root</span>)</span><span v-if="item_child2.badge" :style="'background-color:'+item_child2.badge.color" class="tw-ml-2 badge bg-info">{{item_child2.badge.value}}</span></span>
                                                                            </span>

                                                                            <span class="poly-cursor poly-menu-item-disabled relative pull-right" :data-id="item_child2.slug">
                                                                                <div class="onoffswitch">
                                                                                    <input type="checkbox" :id="'children_checkbox-'+ parent_index + '_' + sub_index + ' ' + sub2_index" class="onoffswitch-checkbox" :checked="(item_child2.disabled && item_child2.disabled == 'true')">
                                                                                    <label class="onoffswitch-label" @click.stop="handleDisabled($event, item_child2)" :for="'children_checkbox-'+ parent_index + '_' + sub_index + ' '+ sub2_index"></label>
                                                                                </div>
                                                                            </span>

                                                                            <span @click.stop="handleDelete(item_child2)" :data-id="item_child.slug" class="poly-cursor tw-mr-1 text-muted pull-right"><i class="fas fa-trash"></i></span>
                                                                            <span @click.stop="handleEdit(item_child2)" class="poly-cursor poly-menu-item-edit tw-mr-1 text-muted pull-right"><i class="fas fa-pencil"></i></span>

                                                                            <span @click.stop="handleClone(item_child2)" class="poly-cursor poly-menu-item-clone relative pull-right"><i class="fa-solid fa-clone fa-fw"></i></span>

                                                                        </div>
                                                                    </template>
                                                                </div>
                                                                <!-- END: Submenu 3 container area -->
                                                                <!-- Empty submenu 3 container area -->
                                                                <div v-if="(!item_child.type || item_child.type !== 'divider')" class="tw-mt-2 list-group sub3 nested-sortable">
                                                                    <div :class="['list-group-item sub empty', `nested-${sub_index}`]"></div>
                                                                </div>
                                                                <!-- END: Empty submenu 3 container area -->

                                                            </div>
                                                        </template>
                                                    </div>
                                                    <!-- END: Submenu container area -->
                                                    <!-- Empty submenu container area -->
                                                    <div v-if="(!item.type || item.type !== 'divider')" class="tw-mt-2 list-group nested-sortable">
                                                        <div :class="['list-group-item sub empty', `nested-${parent_index}`]"></div>
                                                    </div>

                                                    <!-- END: Empty submenu container area -->
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h4 class="col-12">Context Menu Item List</h4>
                                        <div id="poly-custom-menu" class="list-group col">

                                            <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1 hidden-xs"></i><?php echo _l('poly_utilities_custom_menu_list_help') ?>

                                            <template v-for="item in custom_menu_items">

                                                <div class="list-group-item" v-if="!item.slug.includes('root')">

                                                    <div style="display:table">
                                                        <span class="poly-menu-block">
                                                            <span class="poly-menu-icon menu-icon" v-html="handleIcon(item)"></span>
                                                            <a class="custom-menu-text" v-if="item.type!=='divider'" :href="item.href" :parent="item.parent_slug" :slug="item.slug" :data-type="item.type" target="_blank" rel="nofollow" :style="item.css">{{item.name}} <span :style="'background-color:'+item.badge.color" class="tw-ml-2 badge pull-right bg-info">{{item.badge.value}}</span></a>
                                                            <span v-if="item.type==='divider'" :parent="item.parent_slug" :slug="item.slug" :data-type="item.type">{{item.name}} <span :style="'background-color:'+item.badge.color" class="tw-ml-2 badge pull-right bg-info">{{item.badge.value}}</span></span>
                                                        </span>

                                                    </div>
                                                    <div><i class="fa-solid fa-list fa-fw"></i> Type: {{item.type}}<span @click.stop="handleDelete(item)" :data-id="item.slug" class="poly-cursor poly-menu-item-delete tw-mr-1 text-muted pull-right"><i class="fas fa-trash"></i></span><span @click.stop="handleEdit(item)" class="poly-cursor poly-menu-item-edit tw-mr-1 text-muted pull-right"><i class="fas fa-pencil"></i></span>
                                                    </div>
                                                    <div class="tw-mt-1"><i class="fa-solid fa-unlock fa-fw"></i> Roles: <span class="poly-label label label-danger tw-ml-1 tw-mr-1" v-if="item.aroles && item.aroles.length==0"><?php echo _l('poly_utilities_custom_menu_admin_allow_all_access') ?></span><span v-for="role in item.aroles"><span class="poly-label label label-danger tw-ml-1 tw-mr-1" @click.stop="handleRoleInfo(role)">{{role.text}}</span></span></div>
                                                    <div class="tw-mt-1"><i class="fa-solid fa-unlock fa-fw"></i> Users: <span class="poly-label label label-info tw-ml-1 tw-mr-1" v-if="item.ausers && item.ausers.length==0"><?php echo _l('poly_utilities_custom_menu_admin_allow_all_access') ?></span>
                                                        <span v-for="user in item.ausers"><span class="poly-label label label-info tw-ml-1 tw-mr-1 poly-block-users" @click.stop="handleStaffInfo(user)"><img class="avatar-user" :src="user.avatar" />{{user.text}}</span></span>
                                                    </div>

                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END Menu items -->

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
init_tail();
echo '<script src="' . poly_utilities_common_helper::get_assets('modules/poly_utilities/dist/assets/js/lib/sortable/1.15.0/sortable.min.js') . '"></script>';
echo '<script src="' . poly_utilities_common_helper::get_assets('modules/poly_utilities/dist/assets/js/lib/select2/select2.min.js') . '"></script>';
echo '<script src="' . poly_utilities_common_helper::get_assets_minified('modules/poly_utilities/dist/assets/js/admin/context_menu.js') . '"></script>';
