<?php

defined('BASEPATH') or exit('No direct script access allowed');

class poly_utilities_widget_helper
{
    private static $CI;
    /**
     * Display permission information for widgets.
     */
    public static $roles = array(
        array('name' => 'active', 'type' => 'checkbox', 'label' => 'Active', 'value' => false),
        array('name' => 'active_admin', 'type' => 'checkbox', 'label' => 'Active Admin', 'value' => false),
        array('name' => 'active_staff', 'type' => 'checkbox', 'label' => 'Active Staff', 'value' => false),
        array('name' => 'active_client', 'type' => 'checkbox', 'label' => 'Active Client', 'value' => false)
    );
    public static $widget_blocks;
    public static $avaible_widgets;
    public static $dynamic_widgets;

    public static function init_ci()
    {
        if (!self::$CI) {
            self::$CI = &get_instance();
        }
        return self::$CI;
    }
    /**
     * Set up default widget information.
     */
    public static function init()
    {
        self::init_ci();

        self::$dynamic_widgets = json_decode(clear_textarea_breaks(get_option(POLY_WIDGETS)));
        // Widget active
        $filteredRoles = array_filter(self::$roles, function ($role) {
            return $role['name'] == 'active';
        });
        // Reset keys
        self::$roles = array_values($filteredRoles);
        self::$widget_blocks = array(
            array(
                'id' => 'poly-area-after-main-menu',
                'name' => _l('poly_utilities_widget_after_topbar_menu_header'),
                'description' => _l('poly_utilities_widget_after_topbar_menu_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-right-avatar',
                'name' => _l('poly_utilities_widget_right_avatar_header'),
                'description' => _l('poly_utilities_widget_right_avatar_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-after-avatar',
                'name' => _l('poly_utilities_widget_after_avatar_header'),
                'description' => _l('poly_utilities_widget_after_avatar_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-before-sidebar-menu',
                'name' =>  _l('poly_utilities_widget_before_sidebar_menu_header'),
                'description' => _l('poly_utilities_widget_before_sidebar_menu_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-after-sidebar-menu',
                'name' =>  _l('poly_utilities_widget_after_sidebar_menu_header'),
                'description' => _l('poly_utilities_widget_after_sidebar_menu_description'),
                'default' => 'true'
            ),

            array(
                'id' => 'poly-area-before-setup-menu',
                'name' =>  _l('poly_utilities_widget_before_setup_menu_header'),
                'description' => _l('poly_utilities_widget_before_setup_menu_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-after-setup-menu',
                'name' =>  _l('poly_utilities_widget_after_setup_menu_header'),
                'description' => _l('poly_utilities_widget_after_setup_menu_description'),
                'default' => 'true'
            ),

            array(
                'id' => 'poly-area-before-dashboard',
                'name' =>  _l('poly_utilities_widget_before_dashboard_header'),
                'description' => _l('poly_utilities_widget_before_dashboard_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-before-article-details',
                'name' =>  _l('poly_utilities_widget_before_article_details_header'),
                'description' => _l('poly_utilities_widget_before_article_details_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-between-article-details',
                'name' =>  _l('poly_utilities_widget_between_article_details_header'),
                'description' => _l('poly_utilities_widget_between_article_details_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-after-article-details',
                'name' =>  _l('poly_utilities_widget_after_article_details_header'),
                'description' => _l('poly_utilities_widget_after_article_details_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-footer',
                'name' =>  _l('poly_utilities_widget_admin_footer_header'),
                'description' => _l('poly_utilities_widget_admin_footer_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-customers-toppage',
                'name' =>  _l('poly_utilities_widget_customer_toppage_header'),
                'description' => _l('poly_utilities_widget_customer_toppage_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-customers-footer',
                'name' =>  _l('poly_utilities_widget_customer_footer_header'),
                'description' => _l('poly_utilities_widget_customer_footer_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-customers-before-login',
                'name' =>  _l('poly_utilities_widget_customers_before_login_header'),
                'description' => _l('poly_utilities_widget_customers_before_login_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-customers-after-login',
                'name' =>  _l('poly_utilities_widget_customers_after_login_header'),
                'description' => _l('poly_utilities_widget_customers_after_login_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-login-top-page',
                'name' =>  _l('poly_utilities_widget_login_top_page_header'),
                'description' => _l('poly_utilities_widget_login_top_page_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-login-bottom-page',
                'name' =>  _l('poly_utilities_widget_login_bottom_page_header'),
                'description' => _l('poly_utilities_widget_login_bottom_page_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-admin-before-login',
                'name' =>  _l('poly_utilities_widget_admin_before_login_header'),
                'description' => _l('poly_utilities_widget_admin_before_login_description'),
                'default' => 'true'
            ),
            array(
                'id' => 'poly-area-admin-after-login',
                'name' =>  _l('poly_utilities_widget_admin_after_login_header'),
                'description' => _l('poly_utilities_widget_admin_after_login_description'),
                'default' => 'true'
            )
        );

        $languages = '';

        if (!is_language_disabled()) {
            $languages = self::$CI->app->get_available_languages();
        }

        self::$avaible_widgets = array(
            array(
                'name' => 'Text', 'type' => 'text', 'fields' => array(
                    ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'value' => ''],
                    ['name' => 'description', 'type' => 'textarea', 'label' => 'Description', 'value' => '']
                ),
                'roles' => self::$roles,
                'active' => true
            ),

            array(
                'name' => 'HTML', 'type' => 'html',
                'fields' => array(
                    ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'value' => ''],
                    ['name' => 'description', 'type' => 'textarea', 'label' => 'HTML', 'value' => '']
                ),
                'roles' => self::$roles,
                'active' => true
            ),

            array(
                'name' => 'Language', 'type' => 'language',
                'fields' => array(
                    ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'value' => ''],
                    ['name' => 'language', 'type' => 'language', 'label' => 'Language', 'value' => $languages]
                ),
                'roles' => self::$roles,
                'active' => true
            ),

            array(
                'name' => 'Image', 'type' => 'image',
                'fields' => array(
                    ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'value' => ''],
                    ['name' => 'image', 'type' => 'image', 'label' => 'Add Image', 'value' => '']
                ),
                'roles' => self::$roles,
                'active' => false
            ),

            array(
                'name' => 'Pinned Project', 'type' => 'pinned_project',
                'fields' => array(
                    ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'value' => ''],
                    ['name' => 'template', 'type' => 'textarea', 'label' => 'Template display', 'value' => '']
                ),
                'roles' => self::$roles,
                'active' => false
            ),

            array(
                'name' => 'Human Resources\' birthday', 'type' => 'birthday',
                'fields' => array(
                    ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'value' => ''],
                    ['name' => 'description', 'type' => 'textarea', 'label' => 'Description', 'value' => '']
                ),
                'roles' => self::$roles,
                'active' => false
            )
        );
    }

    /**
     * Checks if a $widget_area has widgets to display.
     * @param string $widget_area The registered widget ID.
     * @return bool true if the widget_area has widgets to display, otherwise false.
     */
    public static function is_active_widget($widget_area)
    {
        foreach (self::$dynamic_widgets as $obj) {
            if (isset($obj->id) && $obj->id === $widget_area && !empty($obj->widgets)) {
                return count($obj->widgets) > 0;
            }
        }
        return false;
    }

    /**
     * Set the content display position for the widget based on $widget_area
     * @param string $widget_area The registered widget position ID.
     * @return string HTML content code for the widget_area content display position
     */
    public static function dynamic_widget($widget_area)
    {
        echo '<span id="widget_' . $widget_area . '"></span>';
    }

    public static function get_widget_by_id($widget_data, $id)
    {
        foreach ($widget_data as $widget_area) {
            if (isset($widget_area->id) && $widget_area->id === $id) {
                return $widget_area;
            }
        }
        return null;
    }

    public static function get_widgets_area($widget_data, $area)
    {
        if (empty($widget_data)) return '';

        $widget_object = self::get_widget_by_id($widget_data, $area);
        if (isset($widget_object->widgets) && $widget_object->widgets) {
            $widgets = $widget_object->widgets;
            if ($widgets) {
                $widgets_rest = [];
                foreach ($widgets as $key => $widget) {
                    $widget_object = array();
                    $widget_object['name'] = $widget->name;
                    $widget_object['type'] = $widget->type;
                    $obj_fields = $widget->fields;
                    $obj_roles = $widget->roles;
                    $roles = [];
                    $fields = [];

                    foreach ($obj_fields as $field) {
                        $fields[] = array(
                            'name' => $field->name ?? '',
                            'type' => $field->type ?? '',
                            'label' => $field->label ?? '',
                            'value' => $field->value
                        );
                    }
                    $widget_object['fields'] = $fields;

                    foreach ($obj_roles as $role) {
                        if ($role->name == 'active') {
                            $roles[] = array(
                                'name' => $role->name,
                                'type' => $role->type,
                                'label' => $role->label ?? '',
                                'value' => $role->value
                            );
                        }
                    }
                    $widget_object['roles'] = $roles;

                    $widgets_rest[] = $widget_object;
                }
                return $widgets_rest;
            }
        }
        return '';
    }

    public static function render_widgets_area($widget_areas, $widget_data, $is_disabled = false)
    {
        foreach ($widget_areas as $widget_block) {
            $widgets = self::get_widgets_area($widget_data, $widget_block['id']);
            $is_default = ($widget_block['default'] === 'true') ? 'true' : 'false';
?>
            <ul class="poly-widgets-area">
                <li class="block" data-block-id="<?php echo $widget_block['id'] ?>" id="<?php echo $widget_block['id'] ?>" default="<?php echo $is_default ?>">
                    <div class="widget cursor"><span class="header"><?php echo $widget_block['name'] ?></span><a href="#" class="tw-mr-1 text-muted toggle-widgets widget-item-blocks pull-right">
                            <i class="fa-solid fa-caret-up"></i></a>
                    </div>
                    <div class="widget-block poly-hide tw-mt-2.5" block-target="<?php echo $widget_block['id'] ?>">
                        <p class="poly-widget-description"><?php echo $widget_block['description'] ?></p>
                        <ul id="poly-widget-list-active" class="poly-widget-list active">
                            <?php
                            if (!empty($widgets)) {
                                foreach ($widgets as $key => $widget) {
                                    self::render_widget($widget, $is_disabled);
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </li>
            </ul>
        <?php
        }
    }

    public static function avaible_widgets($is_disabled = false)
    {
        foreach (poly_utilities_widget_helper::$avaible_widgets as $current_widget) {
            if ($current_widget['active'] === true) {
                self::render_widget($current_widget, $is_disabled);
            }
        }
    }

    public static function widgets_generate_content($is_default = false)
{
    // Decodificar widgets
    $widget_objects = json_decode(clear_textarea_breaks(get_option(POLY_WIDGETS)), false);

    // Garantir que é um array
    if (!is_array($widget_objects)) {
        $widget_objects = [];
    }

    $result = [];
    // Filtrar por padrão ou não
    foreach ($widget_objects as $value) {
        if (isset($value->default) && (($is_default && $value->default === 'true') || (!$is_default && $value->default === 'false'))) {
            $result[] = $value;
        }
    }

    $objs = [];
    foreach ($result as $value) {
        $content = [];
        if (isset($value->widgets) && is_array($value->widgets)) {
            foreach ($value->widgets as $item) {
                // Verificar roles[0] antes de acessar
                if (isset($item->roles[0]) && isset($item->roles[0]->name, $item->roles[0]->value) && $item->roles[0]->name === 'active' && $item->roles[0]->value === 'true') {
                    foreach ($item->fields as $item2) {
                        if ($item2->type === 'language') {
                            $languages = isset($item2->value) && is_array($item2->value)
                                ? json_decode(json_encode($item2->value), true)
                                : [];

                            if (is_array($languages)) {
                                $checkedLanguages = array_filter($languages, function ($language) {
                                    return isset($language['checked']) && $language['checked'] === 'true';
                                });

                                $html = '';
                                foreach ($checkedLanguages as $lang) {
                                    if (array_key_exists($lang['language'], poly_utilities_common_helper::$language_code)) {
                                        $language_code = poly_utilities_common_helper::$language_code[$lang['language']];
                                        $html .= '<a href="' . admin_url('staff/change_language/' . $lang['language']) . '" title="' . e(ucfirst($lang['language'])) . '"><span class="flag-icon flag-icon-' . $language_code . '"></span></a> ';
                                    } else {
                                        $html .= '<a href="' . admin_url('staff/change_language/' . $lang['language']) . '" title="' . e(ucfirst($lang['language'])) . '">' . $lang['language'] . '</a> ';
                                    }
                                }

                                if (!empty($html)) {
                                    $content[] = '<span class="poly_utilities--widgets text-center language">' . $html . '</span>';
                                }
                            }
                        } else {
                            if ($item2->name === 'description') {
                                $content[] = $item2->value;
                            }
                        }
                    }
                }
            }
            $objs[$value->id] = $content;
        }
    }
    return json_encode($objs, true);
}

    public static function render_widget($current_widget, $is_disabled = false)
    {
        ?>
        <li class="ui-widget-default" data-type="<?php echo $current_widget['type'] ?>" data-id="zzzz" data-name="<?php echo $current_widget['name'] ?>">
            <!-- Text widget -->
            <div class="widget"><span><?php echo $current_widget['name'] ?></span><a href="#" class="tw-mr-1 text-muted toggle-widgets widget-item-blocks pull-right"><i class="fa-solid fa-caret-up">&nbsp;</i></a>
                <?php
                if (is_admin() || has_permission('poly_utilities_widgets_extend', '', 'delete')) {
                ?>
                    <a href="#" class="widget-delete text-muted pull-right"><i class="fas fa-trash">&nbsp;</i></a>
                    <a href="#" class="widget-clone text-muted pull-right"><i class="fa-solid fa-clone fa-fw">&nbsp;</i></a>
                <?php
                    if (!empty($current_widget['roles'])) {
                        foreach ($current_widget['roles'] as $role) {
                            echo '<label class="item-roles-label pull-right"><input class="item-roles-property" label="' . $role['label'] . '" type="' . $role['type'] . '" field="' . $role['name'] . '" ' . (($role['value'] === 'true') ? 'checked' : '') . $is_disabled . '/>&nbsp;</label>';
                        }
                    }
                }
                ?>
            </div>
            <div class="widget-item-block poly-hide tw-mt-2.5" widget-target="zzzz">
                <div class="row<?php echo (($is_disabled == true) ? ' disabled' : '') ?>">
                    <?php
                    $fields = $current_widget['fields'];
                    foreach ($fields as $field) {
                        switch ($field['type']) {
                            case 'text': {
                                    echo  render_input('', $field['label'], $field['value'], 'text', array('placeholder' => 'Title', 'field' => $field['name'], 'label' => $field['label']), [], 'col-md-12', 'item-property');
                                    break;
                                }
                            case 'textarea': {
                                    echo render_textarea('', $field['label'], $field['value'], ['field' => $field['name'], 'type' => 'textarea', 'label' => $field['label'], 'id' => 'item-html-content'], [], 'col-md-12', 'item-property item-html-content');
                                    break;
                                }
                            case 'checkbox': {
                                    echo '<div class="col-md-12"><input class="item-property" label="' . $field['label'] . '" type="checkbox" field="' . $field['name'] . '" ' . (($field['value'] == true) ? 'checked' : '') . $is_disabled . '/> ' . $field['label'] . '</div>';
                                    break;
                                }
                            case 'image': {
                                    echo '<div class="col-md-12 tw-mb-2.5">
                                        <input class="item-property poly-hide" type="image" label="' . $field['label'] . '" field="' . $field['name'] . '" value="' . $field['value'] . '"/>
                                        <div class="poly-widget-image-add">' . $field['label'] . '</div>
                                    </div>
                                    <div class="col-md-12 tw-mb-2.5">Replace</div>';
                                    break;
                                }
                            case 'language': {
                                
                                if (!is_language_disabled()) {
                                    ?>
                                    <div class="col-md-12">
                                        <label><?php echo $field['label'] ?></label>
                                        <div class="item-property" type="language" field="<?= $field['name'] ?>">
                                            <?php
                                            $languages = isset($field['value']) && is_array($field['value']) 
                                                ? json_decode(json_encode($field['value']), true) 
                                                : [];
                                            foreach (self::$CI->app->get_available_languages() as $user_lang) {
                                                $isChecked = false;
                                                if (!empty($languages)) {
                                                    foreach ($languages as $lang) {
                                                        if (
                                                            isset($lang['language']) && 
                                                            $lang['language'] === $user_lang && 
                                                            isset($lang['checked']) && 
                                                            filter_var($lang['checked'], FILTER_VALIDATE_BOOLEAN)
                                                        ) {
                                                            $isChecked = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <div>
                                                    <input type="checkbox" 
                                                           data-language="<?= $user_lang ?>" 
                                                           <?= $isChecked ? 'checked' : '' ?> />&nbsp;
                                                    <a href="<?= admin_url('staff/change_language/' . $user_lang); ?>">
                                                        <?= e(ucfirst($user_lang)); ?>
                                                    </a>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                                    
                                    break;
                                }
                        }
                    }
                    ?>
                </div>
                <?php
                if (is_admin() || has_permission('poly_utilities_widgets_extend', '', 'delete')) {
                ?>
                    <div class="row">
                        <div class="col-md-12 poly_utilities_widgets_extend--action">
                            <a href="#" class="widget-delete"><?php echo _l('poly_utilities_widget_button_action_delete') ?></a> | <a href="#" class="widget-clone"><?php echo _l('poly_utilities_widget_button_action_clone') ?></a> | <a href="#" class="widget-close"><?php echo _l('poly_utilities_widget_button_action_done') ?></a>
                        </div>
                    </div>
                <?php
                }
                if (is_admin() || has_permission('poly_utilities_widgets_extend', '', 'edit')) {
                ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="btn btn-primary pull-right poly-widgets-submit"><?php echo _l('poly_utilities_widget_button_action_save') ?></div>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
            <!-- END Text widget -->
        </li>
    <?php
    }
    public static function display_avaible_widgets($is_disabled = false)
    {
    ?>
        <div id="left-column" class="poly-avaible-widgets col-md-3 tw-p-1">
            <h2 class="header"><?php echo _l('poly_utilities_widgets_avaible_header') ?></h2>
            <p class="poly-widget-description"><?php echo _l('poly_utilities_widgets_avaible_description') ?></p>
            <ul id="poly-widget-list" class="poly-widget-list<?php echo (($is_disabled == true) ? ' disabled' : '') ?>">
                <?php self::avaible_widgets($is_disabled); ?>
            </ul>
        </div>
    <?php
    }
    public static function display_widgets_area($is_disabled = false)
    {
        $widget_objects = json_decode(clear_textarea_breaks(get_option(POLY_WIDGETS)));
        $widget_blocks = hooks()->apply_filters('poly_utilities_widgets_init', poly_utilities_widget_helper::$widget_blocks);
        if (empty($widget_blocks)) return '';

        $array_length = count($widget_blocks);
        $part_size = ceil($array_length / 3);
        $first_column = array_slice($widget_blocks, 0, $part_size);
        $second_column = array_slice($widget_blocks, $part_size, $part_size);
        $third_column = array_slice($widget_blocks, $part_size * 2);

    ?>
        <div id="right-column" class="col-md-9 tw-p-1">
            <div class="col-md-4 tw-p-1">
                <?php
                self::render_widgets_area($first_column, $widget_objects, $is_disabled);
                ?>
            </div>
            <div class="col-md-4 tw-p-1">
                <?php
                self::render_widgets_area($second_column, $widget_objects, $is_disabled);
                ?>
            </div>
            <div class="col-md-4 tw-p-1">
                <?php
                self::render_widgets_area($third_column, $widget_objects, $is_disabled);
                ?>
            </div>
        </div>
<?php
    }
}
