<?php

defined('BASEPATH') or exit('No direct script access allowed');

class poly_utilities_common_helper
{
    public static $transition_effects = ['fadeInOut', 'slide', 'flip', 'flipX', 'flipY', 'zoomInOut', 'jackInTheBox', 'rotateInOut'];
    public static $rels = ['follow', 'nofollow', 'alternate', 'author', 'bookmark', 'external', 'help', 'license', 'next', 'noreferrer', 'noopener', 'prev', 'search', 'tag'];
    public static $targets = ['_self', '_blank', '_parent', '_top'];
    public static $link_type;
    public static $alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    public static $numbers = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
    public static $aio_supports_type = ['link', 'email', 'mobile', 'facebook_messenger', 'viber', 'whatsapp', 'other'];
    public static $clients_menu_items = ['invoices', 'projects', 'contracts', 'estimates', 'proposals', 'subscriptions', 'support'];

    /**
     * List of table names and the identity of tables that support hooks from the Perfex core
     */
    public static $table_hooks = [
        ['key_table' => 'customers', 'key_reorder' => 'admin-clients'],
        ['key_table' => 'projects', 'key_reorder' => 'admin-projects'],
        ['key_table' => 'tasks', 'key_reorder' => 'admin-tasks'],
        ['key_table' => 'invoices', 'key_reorder' => 'admin-invoices'],
        ['key_table' => 'leads', 'key_reorder' => 'admin-leads'],
        ['key_table' => 'contracts', 'key_reorder' => 'admin-contracts'],
        ['key_table' => 'estimates', 'key_reorder' => 'admin-estimates'],
        ['key_table' => 'expenses', 'key_reorder' => 'admin-expenses'],
        ['key_table' => 'proposals', 'key_reorder' => 'admin-proposals']
    ];

    public static $language_code = ['bulgarian' => 'bg', 'francais_canada' => 'ca', 'dutch' => 'nl'/*Netherlands*/, 'czech' => 'cz'/*Czech Republic*/, 'catalan' => 'es-ct'/*Catalonia*/, 'vietnamese' => 'vn', 
    'japanese' => 'jp', 'german' => 'de', 'indonesia' => 'id', 'slovak' => 'sk', 'romanian' => 'ro', 'turkish' => 'tr', 
    'russian' => 'ru', 'italian' => 'it', 'english' => 'gb', 'greek' => 'gr', 'norwegian' => 'no'/*Norway*/, 'portuguese_br' => 'br', 
    'portuguese' => 'pt'/*Portuguese*/, 'finnish' => 'fi', 'polish' => 'pl', 'french' => 'fr', 'swedish' => 'se' /*Sweden*/, 'spanish' => 'es', 
    'chinese' => 'cn', 'persian' => 'ir', 'ukrainian' => 'ua'];

    public static function get_link_type()
    {
        if (self::$link_type === null) {
            self::$link_type = [
                ['default' => _l('poly_utilities_type_default_link')],
                ['none' => _l('poly_utilities_type_none_link')],
                ['iframe' => _l('poly_utilities_type_iframe_link')],
                ['popup' => _l('poly_utilities_type_popup_link')],
                ['divider' => _l('poly_utilities_type_divider_link')],
            ];
        }
        return self::$link_type;
    }

    public static function render_language()
    {
        $module_lang = [
            'module_action_activate'                      => _l('poly_utilities_module_action_activate'),
            'module_action_deactivate'                      => _l('poly_utilities_module_action_deactivate'),
            'module_action_apply'                      => _l('poly_utilities_module_action_apply'),
            'module_action_select_text' => _l('poly_utilities_module_action_select_text'),
            'message_confirm_action_default' => _l('poly_utilities_message_confirm_action_default'),
            'message_confirm_action_reset_reorder_columns' => _l('poly_utilities_message_confirm_action_reset_reorder_columns'),
            'message_confirm_action_reset_all_reorder_columns' => _l('poly_utilities_message_confirm_action_reset_all_reorder_columns'),
            'message_confirm_reset_position_button' => _l('poly_utilities_message_confirm_reset_position_button'),
            'projects' => [
                'button' => [
                    'add' => _l('poly_utilities_projects_button_add_pattern'),
                    'add_estimate' => _l('poly_utilities_projects_button_add_estimate'),
                    'add_contract' => _l('poly_utilities_projects_button_add_contract'),
                    'add_proposal' => _l('poly_utilities_projects_button_add_proposal'),
                    'add_estimate_tooltip' => _l('poly_utilities_projects_button_add_estimate_tooltip'),
                    'add_contract_tooltip' => _l('poly_utilities_projects_button_add_contract_tooltip'),
                    'add_proposal_tooltip' => _l('poly_utilities_projects_button_add_proposal_tooltip'),
                ],
                'validate' => [
                    'name' => _l('poly_utilities_projects_field_name_validate')
                ]
            ]

        ];

        return json_encode($module_lang, JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public static function display_message_help($content, $is_icon = true)
    {
        $icon = '<i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1"></i>';
        return '<div class="poly-help-message-small tw-mb-2 tw-mt-2">' . ($is_icon ? $icon : '') . $content . '</div>';
    }

    public static function core_version()
    {
        $CI = &get_instance();
        return $CI->app_css->core_version();
    }

    public static function get_assets($path, $is_version = true, $is_date = false)
    {
        $url = base_url($path);
        if ($is_version) {
            $url = self::add_or_update_url_param($url, array('c' => self::core_version(), 'v' => POLY_UTILITIES_VERSION));
        }
        if ($is_date) {
            $url = self::add_or_update_url_param($url, array('d' => time()));
        }
        return $url;
    }

    public static function get_assets_minified($path, $is_version = true, $is_date = false)
    {
        $url = base_url($path);
        if ($is_version) {
            $url = self::add_or_update_url_param($url, array('c' => self::core_version(), 'v' => POLY_UTILITIES_VERSION));
        }
        if ($is_date) {
            $url = self::add_or_update_url_param($url, array('d' => time()));
        }
        return self::convert_to_minified_url($url);
    }

    public static function convert_to_minified_url($url, $isMinified = false)
    {
        $isMinified = POLYUTILS_ISMINIFIED ?? $isMinified;
        $isProduction = (ENVIRONMENT === 'production');

        $mode = ($isProduction || $isMinified) ? 'min.' : '';

        $parts = parse_url($url);
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? $_SERVER['HTTP_HOST'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'];

        if (preg_match('/\.(css|js)$/', $path, $matches)) {
            $extension = $matches[1];
            $path = substr($path, 0, -strlen($extension) - 1) . '.' . $mode . $extension;
        }

        if (!$isProduction && !$isMinified) {
            $path = str_replace('dist/', '', $path);
        }

        $absoluteUrl = $scheme . '://' . $host . $port . $path . $query;

        return $absoluteUrl;
    }

    public static function remove_url_param($url, $paramToRemove)
    {
        $parsedUrl = parse_url($url);
        $query = array();

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }

        unset($query[$paramToRemove]);

        $newQueryString = http_build_query($query);

        $finalUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        if (isset($parsedUrl['path'])) {
            $finalUrl .= $parsedUrl['path'];
        }
        if ($newQueryString) {
            $finalUrl .= '?' . $newQueryString;
        }

        return $finalUrl;
    }


    public static function add_or_update_url_param($url, $newParams)
    {
        $parsedUrl = parse_url($url);
        $query = array();
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }
        $query = array_merge($query, $newParams);
        $newQueryString = http_build_query($query);
        $finalUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        if (isset($parsedUrl['port'])) {
            $finalUrl .= ':' . $parsedUrl['port'];
        }
        if (isset($parsedUrl['path'])) {
            $finalUrl .= $parsedUrl['path'];
        }
        if ($newQueryString) {
            $finalUrl .= '?' . $newQueryString;
        }

        return $finalUrl;
    }

    /**
     * Converts an array to a list of objects with specified key-value pairs.
     *
     * This function filters out any elements whose keys are present in the $exclude array
     * before mapping the remaining elements to objects with the specified key and value names.
     *
     * @param array $arr_input The input array to be converted.
     * @param array $exclude An array of keys to be excluded from the input array. Default is an empty array.
     * @param string $key_name The name to be used for the key in the resulting objects. Default is 'id'.
     * @param string $value_name The name to be used for the value in the resulting objects. Default is 'text'.
     * @return array An array of objects with the specified key-value pairs.
     */
    public static function array_map_to_objects_key_value($arr_input, $exclude = [], $key_name = 'id', $value_name = 'text')
    {
        $arr = [];

        // Filter out elements with keys present in the exclude array
        if (!empty($exclude)) {
            $arr_input = array_filter($arr_input, function ($item) use ($exclude) {
                foreach ($item as $key => $value) {
                    if (in_array($key, $exclude)) {
                        return false;
                    }
                }
                return true;
            });
        }

        // Map the remaining elements to objects with specified key-value pairs
        foreach ($arr_input as $item) {
            foreach ($item as $key => $value) {
                $arr[] = array(
                    $key_name => $key,
                    $value_name => $value
                );
            }
        }

        return $arr;
    }

    public static function isExisted($arr, $field, $content)
    {
        if (count($arr) == 0) return false;
        if (is_array($arr)) {
            foreach ($arr as $itm) {
                if (isset($content) && $itm[$field] === $content) {
                    return true;
                    break;
                }
            }
        }
        return false;
    }

    public static function getResourceObject($arr, $field, $content)
    {
        if (is_array($arr)) {
            foreach ($arr as $itm) {
                if (isset($content) && $itm[$field] === $content) {
                    return $itm;
                }
            }
        }
        return null;
    }

     /**
     * Removes an object from an array based on a specific field and its value.
     *
     * @param array $arr The input array of objects.
     * @param string $field The field used to identify the object.
     * @param string|int $content The value of the field to match for removal.
     * 
     * @return array The updated array with the specified object removed.
     */
    public static function removeDataByField($arr, $field, $content)
    {
        if (empty($arr)) {
            return $arr;
        }

        if (is_array($arr)) {
            foreach ($arr as $key => $itm) {
                if (isset($itm[$field]) && $itm[$field] === $content) {
                    unset($arr[$key]);
                    break;
                }
            }
        }
        return array_values($arr);
    }

    /**
     * Removes objects from an array where a specific field contains the given content.
     *
     * @param array $arr The input array of objects.
     * @param string $field The field used to identify the object.
     * @param string $content The partial content to match within the field's value for removal.
     * 
     * @return array The updated array with matching objects removed.
     */
    public static function removeDataByFieldContains($arr, $field, $content)
    {
        if (empty($arr)) {
            return $arr;
        }

        if (is_array($arr)) {
            foreach ($arr as $key => $itm) {
                if (isset($itm[$field]) && strpos($itm[$field], $content) !== false) {
                    unset($arr[$key]);
                }
            }
        }
        return array_values($arr);
    }

    /**
     * Removes an item from the array and its sub-arrays based on the specified field and value.
     * This function recursively searches through the array and any sub-arrays defined by $sub_field to find and remove items where $field equals $content.
     * 
     * @param array &$arr The array to search through, passed by reference to allow modifications.
     * @param string $field The field name in the array items to compare against $content.
     * @param string $sub_field The field name in the array items that may contain sub-arrays to recursively search through.
     * @param mixed $content The value to compare against the $field value to determine if an item should be removed.
     * @return bool Returns true if at least one item was removed; otherwise, returns false.
     */
    public static function isRemoveWhenExisted(&$arr, $field, $sub_field, $content)
    {
        if (count($arr) == 0) return false;
        $removed = false;

        foreach ($arr as $key => &$itm) {
            if (isset($itm[$field]) && $itm[$field] === $content) {
                unset($arr[$key]);
                $removed = true;
            }

            if (isset($itm[$sub_field]) && is_array($itm[$sub_field])) {
                $subRemoved = self::isRemoveWhenExisted($itm[$sub_field], $field, $sub_field, $content);
                if ($subRemoved) {
                    $removed = true;
                    $itm[$sub_field] = array_values($itm[$sub_field]);
                }
            }
        }

        if ($removed) {
            $arr = array_values($arr);
        }

        return $removed;
    }

    public static function updateDataByField($arr, $field, $value, $obj)
    {
        foreach ($arr as $key => $item) {
            if ($item[$field] === $value) {
                $arr[$key] = array_merge($item, (array) $obj);
                break;
            }
        }
        return $arr;
    }

    public static function generateUniqueID()
    {
        $uniqueID = uniqid();
        $hashedID = md5($uniqueID);
        return $hashedID;
    }

    public static function random_password($min = 12, $max = 30)
    {
        $minLength = max($min, 12);
        $maxLength = min($max, 30);

        $numbers = '0123456789';
        $lowerLetters = 'abcdefghijklmnopqrstuvwxyz';
        $upperLetters = strtoupper($lowerLetters);
        $specialChars = '!@#%^&*()-_=+[]{};:,.?';

        $randomPassword = substr(str_shuffle($numbers), 0, 1)
            . substr(str_shuffle($lowerLetters), 0, 1)
            . substr(str_shuffle($upperLetters), 0, 1)
            . substr(str_shuffle($specialChars), 0, 1);

        $remainingLength = rand($minLength, $maxLength) - strlen($randomPassword);
        $allChars = $numbers . $lowerLetters . $upperLetters . $specialChars;
        $randomPassword .= substr(str_shuffle($allChars), 0, $remainingLength);

        return str_shuffle($randomPassword);
    }

    public static function render_select($id, $options, $value, $label = '', $group_class = '', $select_class = '', $input_attrs = [])
    {
        $rest = '<div class="' . $group_class . '">' . ((!empty($label)) ? "<label>{$label}</label>" : '');

        $_input_attrs     = '';
        foreach ($input_attrs as $key => $val) {
            if ($key == 'title') {
                $val = _l($val);
            }
            $_input_attrs .= $key . '=' . '"' . $val . '" ';
        }

        $_input_attrs = rtrim($_input_attrs);

        $rest .= "<select class='form-control " . $select_class . "' " . $_input_attrs . " id='{$id}' name='{$id}'>";
        foreach ($options as $key => $option) {
            if (is_array($option)) {
                $selected = ($option['id'] === $value) ? ' selected' : '';
                $rest .= '<option value="' . $option['id'] . '"' . $selected . '>' . $option['text'] . '</option>';
            } elseif (is_object($option)) {
                $selected = ($option->id === $value) ? ' selected' : '';
                $rest .= '<option value="' . $option->id . '"' . $selected . '>' . $option->text . '</option>';
            } elseif (is_string($option)) {
                $selected = ($option === $value) ? ' selected' : '';
                $rest .= "<option value='{$option}'{$selected}>{$option}</option>";
            }
        }
        $rest = $rest . "</select></div>";
        return $rest;
    }

    public static function render_textarea_vuejs($name, $label = '', $value = '', $textarea_attrs = [], $form_group_attr = [], $form_group_class = '', $textarea_class = '', $v_model = '')
    {
        $textarea         = '';
        $_form_group_attr = '';
        $_textarea_attrs  = '';
        if (!isset($textarea_attrs['rows'])) {
            $textarea_attrs['rows'] = 4;
        }

        if (isset($textarea_attrs['class'])) {
            $textarea_class .= ' ' . $textarea_attrs['class'];
            unset($textarea_attrs['class']);
        }

        foreach ($textarea_attrs as $key => $val) {
            // tooltips
            if ($key == 'title') {
                $val = _l($val);
            }
            $_textarea_attrs .= $key . '=' . '"' . $val . '" ';
        }

        $_textarea_attrs = rtrim($_textarea_attrs);

        $form_group_attr['app-field-wrapper'] = $name;

        foreach ($form_group_attr as $key => $val) {
            if ($key == 'title') {
                $val = _l($val);
            }
            $_form_group_attr .= $key . '=' . '"' . $val . '" ';
        }

        $_form_group_attr = rtrim($_form_group_attr);

        if (!empty($textarea_class)) {
            $textarea_class = trim($textarea_class);
            $textarea_class = ' ' . $textarea_class;
        }
        if (!empty($form_group_class)) {
            $form_group_class = ' ' . $form_group_class;
        }
        $textarea .= '<div class="form-group' . $form_group_class . '" ' . $_form_group_attr . '>';
        if ($label != '') {
            $textarea .= '<label for="' . $name . '" class="control-label">' . _l($label, '', false) . '</label>';
        }

        $v = clear_textarea_breaks($value);
        if (strpos($textarea_class, 'tinymce') !== false) {
            $v = html_purify($value);
        }
        if (!empty($v_model)) {
            $textarea .= '<textarea id="' . $name . '" name="' . $name . '" class="form-control' . $textarea_class . '" ' . $_textarea_attrs . ' v-model="' . $v_model . '">' . set_value($name, $v) . '</textarea>';
        } else {
            $textarea .= '<textarea id="' . $name . '" name="' . $name . '" class="form-control' . $textarea_class . '" ' . $_textarea_attrs . '>' . set_value($name, $v) . '</textarea>';
        }

        $textarea .= '</div>';

        return $textarea;
    }

    /**
     * Function that renders input for admin area based on passed arguments. Handle from render_input
     * @param  string $name             input name
     * @param  string $label            label name
     * @param  string $value            default value
     * @param  string $type             input type eq text,number
     * @param  array  $input_attrs      attributes on <input
     * @param  array  $form_group_attr  <div class="form-group"> html attributes
     * @param  string $form_group_class additional form group class
     * @param  string $input_class      additional class on input
     * @param  string $field_validation      additional field validation
     * @return string
     */
    public static function render_input_vuejs($name, $label = '', $value = '', $type = 'text', $input_attrs = [], $form_group_attr = [], $form_group_class = '', $input_class = '', $v_model = '', $field_validation = '')
    {
        $input            = '';
        $_form_group_attr = '';
        $_input_attrs     = '';

        if (is_array($input_attrs)) {
            $input_attrs = array_merge($input_attrs, array('v-model' => $v_model));
        }

        foreach ($input_attrs as $key => $val) {
            if ($key == 'title') {
                $val = _l($val);
            }
            $_input_attrs .= $key . '=' . '"' . $val . '" ';
        }

        $_input_attrs = rtrim($_input_attrs);

        $form_group_attr['app-field-wrapper'] = $name;

        foreach ($form_group_attr as $key => $val) {
            if ($key == 'title') {
                $val = _l($val);
            }
            $_form_group_attr .= $key . '=' . '"' . $val . '" ';
        }

        $_form_group_attr = rtrim($_form_group_attr);

        if (!empty($form_group_class)) {
            $form_group_class = ' ' . $form_group_class;
        }
        if (!empty($input_class)) {
            $input_class = ' ' . $input_class;
        }
        $input = '<div class="form-group' . $form_group_class . '" ' . $_form_group_attr;
        if (!empty($field_validation)) {
            $input .= ' :class="{\'has-error\': ' . $field_validation . ' && !' . $v_model . '}"';
        }
        $input .= '>';

        if ($label != '') {
            $input .= '<label for="' . $name . '" class="control-label">' . _l($label, '', false) . '</label>';
        }
        $input .= '<input type="' . $type . '" id="' . $name . '" name="' . $name . '" class="form-control' . $input_class . '" ' . $_input_attrs . ' value="' . set_value($name, $value) . '">';
        if (!empty($field_validation)) {
            $input .= '<p v-if="' . $field_validation . ' && !' . $v_model . ' " class="text-danger">{{ ' . $field_validation . ' }}</p>';
        }
        $input .= '</div>';

        return $input;
    }

    public static function render_file_upload($name, $label, $v_model, $accept = '', $no_file_message = 'No file input', $help_description = '')
    {
?>
        <div class="poly-utilities-file-input">
            <?php
            if (!empty($label)) {
            ?>
                <label for="<?php echo $name ?>">
                    <?php echo $label ?>
                </label>
            <?php
            }
            ?>
            <div class="poly-utilities-media-block">
                <label for="<?php echo $name ?>" class="poly-utilities-file-input__label">
                    <div class="media-preview" v-if="<?php echo $v_model ?>">
                        <div class="media-preview__wrap"><img class="media" :src="<?php echo $v_model ?>" /></div>
                    </div>

                    <div class="custom-file-upload">
                        <input type="file" id="<?php echo $name ?>" name="<?php echo $name ?>" accept="<?php echo $accept ?>" data-no-file-message="<?php echo $no_file_message ?>">
                        <span id="file-name" class="poly-utilities-file-input__file-name"><?php echo $no_file_message ?></span>
                    </div>
                </label>
                <p class="poly-help-message-small"><?php echo $help_description ?></p>
            </div>
    <?php
    }

    public static function render_toggle_vuejs($name, $label = '', $value = '', $input_attrs = [], $form_group_attr = [], $form_group_class = '', $input_class = '', $v_model = '')
    {
        $input            = '';
        $_form_group_attr = '';
        $_input_attrs     = '';

        if (is_array($input_attrs)) {
            $input_attrs = array_merge($input_attrs, array('v-model' => $v_model));
        }

        foreach ($input_attrs as $key => $val) {
            if ($key == 'title') {
                $val = _l($val);
            }
            $_input_attrs .= $key . '=' . '"' . $val . '" ';
        }

        $_input_attrs = rtrim($_input_attrs);

        $form_group_attr['app-field-wrapper'] = $name;

        foreach ($form_group_attr as $key => $val) {
            if ($key == 'title') {
                $val = _l($val);
            }
            $_form_group_attr .= $key . '=' . '"' . $val . '" ';
        }

        $_form_group_attr = rtrim($_form_group_attr);

        if (!empty($form_group_class)) {
            $form_group_class = ' ' . $form_group_class;
        }
        if (!empty($input_class)) {
            $input_class = ' ' . $input_class;
        }
        $input = '<div class="inline-flex' . $form_group_class . '" ' . $_form_group_attr . '>';
        $input .= '<span class="relative poly-utilities-onoffswitch" data-id="' . $name . '">';
        $input .= '<div class="onoffswitch"><input type="checkbox" id="' . $name . '" name="' . $name . '" class="relative onoffswitch-checkbox' . $input_class . '" ' . $_input_attrs . ' data-field-name="' . $name . '" :checked="(' . $v_model . ' && ' . $v_model . ' == 1)"><label class="onoffswitch-label" for="' . $name . '"></label></div></span>';
        if ($label != '') {
            $input .= '&nbsp;<label for="' . $name . '">' . _l($label, '', false) . '</label>';
        }
        $input .= '</div>';
        return $input;
    }

    public static function render_input($name, $label = '', $value = '', $type = 'text', $input_attrs = [], $form_group_attr = [], $form_group_class = '', $input_class = '', $help_message = '')
    {
        $input            = '';
        $_form_group_attr = '';
        $_input_attrs     = '';
        foreach ($input_attrs as $key => $val) {
            // tooltips
            if ($key == 'title') {
                $val = _l($val);
            }
            $_input_attrs .= $key . '=' . '"' . $val . '" ';
        }

        $_input_attrs = rtrim($_input_attrs);

        $form_group_attr['app-field-wrapper'] = $name;

        foreach ($form_group_attr as $key => $val) {
            // tooltips
            if ($key == 'title') {
                $val = _l($val);
            }
            $_form_group_attr .= $key . '=' . '"' . $val . '" ';
        }

        $_form_group_attr = rtrim($_form_group_attr);

        if (!empty($form_group_class)) {
            $form_group_class = ' ' . $form_group_class;
        }
        if (!empty($input_class)) {
            $input_class = ' ' . $input_class;
        }
        $input .= '<div class="form-group' . $form_group_class . '" ' . $_form_group_attr . '>';
        if ($label != '') {
            $input .= '<label for="' . $name . '" class="control-label">' . _l($label, '', false) . '</label>';
        }
        $input .= '<input type="' . $type . '" id="' . $name . '" name="' . $name . '" class="form-control' . $input_class . '" ' . $_input_attrs . ' value="' . set_value($name, $value) . '">';
        if (!empty($help_message)) {
            $input .= '<p class="poly-help-message"><i class="fa-regular fa-circle-question"></i>&nbsp;' . $help_message . '</p>';
        }
        $input .= '</div>';

        return $input;
    }

    public static function json_decode($json_string, $is_array = true)
    {
        if (is_array($json_string)) {
            return $json_string;
        }
        $arr = json_decode($json_string, $is_array);
        return is_array($arr) ? $arr : [];
    }

    /**
     * Retrieves or checks an item by $field corresponding to the test value $value
     * 
     * @param array $arr An array of items
     * @param string $field Name of the attribute to check.
     * @param string $value Value to test.
     * @param boolean $is_object returns an object if set to true. By default, it returns true if found and false if not found.
     * 
     * @return mixed Returns true/false when $is_object = true and object or null if false.
     */
    public static function get_item_by($arr, $field, $value, $is_object = false)
    {
        $value_check = strval($value);
        foreach ($arr as $item) {
            $currentValue = $is_object ? $item->$field : $item[$field];
            if ($currentValue === $value_check) {
                return $is_object ? true : $item;
            }
        }
        return $is_object ? null : false;
    }

    public static function domain($is_scheme = true)
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $currentDomain = ($is_scheme ? ($scheme . '://') : '') . $host;
        return $currentDomain;
    }

    public static function domain_indentity($seperate = '-')
    {
        $host = $_SERVER['HTTP_HOST'];
        return poly_utilities_common_helper::create_slug($host, $seperate);
    }

    public static function create_slug($string, $seperate = '-')
    {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9]+/', $seperate, $string);
        $string = trim($string, $seperate);
        $string = str_replace(' ', $seperate, $string);
        return $string;
    }

    //#region debugs
    /**
     * Function to reset some values saved through define variables, options. Working on localhost
     */
    public static function debug_reset($check_localhost = true)
    {
        $check_localhost = $check_localhost ? poly_utilities_common_helper::is_localhost() : true;
        if (isset($_GET['reset']) && $check_localhost) {
            $remove_custom = true;
            poly_reset_custom_menu(POLYCUSTOMMENU::SIDEBAR, $remove_custom);
            poly_reset_custom_menu(POLYCUSTOMMENU::SETUP, $remove_custom);
            poly_reset_custom_menu(POLYCUSTOMMENU::CLIENTS, $remove_custom);
        }
    }

    public static function is_localhost()
    {
        return ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1');
    }

    public static function is_access_denied()
    {
        $currentUrl = self::get_current_url();
        if (strpos($currentUrl, 'admin/access_denied') !== false) {
            return true;
        }
        return false;
    }

    public static function get_current_url()
    {
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        return rtrim($url, '/');
    }

    public static function echo($str)
    {
        echo '<div style="margin-left:280px">' . $str . '</div>';
    }

    public static function debug_textarea($data = null, $pretty = false)
    {
        if (is_array($data) || is_object($data)) {
            if ($pretty === true) {
                echo '<textarea style="margin:0px auto;display:table">' . json_encode($data, JSON_PRETTY_PRINT) . '</textarea>';
            } else {
                echo '<textarea style="margin:0px auto;display:table">' . json_encode($data) . '</textarea>';
            }
        } elseif (is_string($data)) {
            echo '<textarea style="margin:0px auto;display:table">' . $data . '</textarea>';
        } else {
            echo '<textarea style="margin:0px auto;display:table">None</textarea>';
        }
    }
    //#endregion debugs

    //#region files

    /**
     * Requires one file into another file.
     * 
     * The function creates a require statement using a template and places it at a specified location in the target file. If no location is provided, it adds the statement to the file's end.
     * 
     * @param string $destPath      The path to the destination file.
     * @param string $requirePath   The path to the file to require.
     * @param boolean $force        Insert content regardless of whether it exists.
     * @param boolean $position     Location for inserting the require statement. If set to False, append it to the end of the file.
     * 
     * @return mixed
     */
    public static function require_in_file($destPath, $requirePath, $force = false, $position = false)
    {
        if (!file_exists($destPath)) {
            poly_utilities_common_helper::file_put_contents($destPath, "<?php defined('BASEPATH') or exit('No direct script access allowed');\n");
        }

        if (file_exists($destPath)) {
            $content = file_get_contents($destPath);
            $template = poly_utilities_common_helper::require_in_file_template($requirePath);

            $exist = preg_match(poly_utilities_common_helper::require_signature($requirePath), $content);
            if ($exist && !$force) {
                return;
            }
            $content = poly_utilities_common_helper::unrequire_in_file($destPath, $requirePath);

            if ($position !== false) {
                $content = substr_replace($content, $template . "\n", $position, 0);
            } else {
                $content = $content . $template;
            }

            poly_utilities_common_helper::file_put_contents($destPath, $content);
        }
    }

    /**
     * Removes a file's require statement from another file.
     * 
     * This function deletes a require statement, which was created using a template, from a specified position in the target file. If no specific position is provided, the function will search for and remove the require statement from the end of the file.
     * 
     * @param string $destPath      The path to the target file.
     * @param string $requirePath   The path to the file whose require statement needs to be removed.
     * 
     * @return string The modified content of the destination file.
     */
    public static function unrequire_in_file($destPath, $requirePath)
    {
        if (file_exists($destPath)) {
            $content = file_get_contents($destPath);
            $content = preg_replace(poly_utilities_common_helper::require_signature($requirePath), '', $content);
            poly_utilities_common_helper::file_put_contents($destPath, $content);
            return $content;
        }
    }

    public static function require_signature($file)
    {
        $basename = str_ireplace(['"', "'"], '', basename($file));
        return "#//" . POLY_UTILITIES_MODULE_NAME . ":start:" . $basename . "([\s\S]*)//" . POLY_UTILITIES_MODULE_NAME . ":end:" . $basename . "#";
    }

    public static function require_in_file_template($path)
    {
        $template = "\n//" . POLY_UTILITIES_MODULE_NAME . ":start:#filename\n//Do not delete or modify the code in this block\nif (file_exists(#path)) {require_once(#path);}\n//END: Do not delete or modify the code in this block\n//" . POLY_UTILITIES_MODULE_NAME . ":end:#filename";

        $template = str_ireplace('#filename', str_ireplace(['"', "'"], '', basename($path)), $template);
        $template = str_ireplace('#path', $path, $template);
        return $template;
    }

    public static function file_put_contents($path, $content)
    {
        @chmod($path, FILE_WRITE_MODE);
        if (!$fp = fopen($path, FOPEN_WRITE_CREATE_DESTRUCTIVE)) {
            return false;
        }
        flock($fp, LOCK_EX);
        fwrite($fp, $content, strlen($content));
        flock($fp, LOCK_UN);
        fclose($fp);
        @chmod($path, FILE_READ_MODE);
        return true;
    }

    public static function read_file($file_name, $directory)
    {
        if (file_exists($directory . '/' . $file_name)) {
            $content = file_get_contents($directory . '/' . $file_name);
            if ($content !== false) {
                return $content;
            }
        }
        return '';
    }

    public static function save_to_file($file_name, $directory, $content, $is_overwrite = false)
    {
        $file_path = $directory . '/' . $file_name;

        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            return 0;
        }

        if ($is_overwrite && file_exists($file_path) && !unlink($file_path)) {
            return 0;
        }

        if (!$is_overwrite && file_exists($file_path)) {
            return 0;
        }

        return file_put_contents($file_path, $content) !== false ? 1 : 0;
    }

    // Helper function to delete old files
    public static function deleteOldFiles($mediaFiles)
    {
        if (!is_array($mediaFiles)) {
            $mediaFiles = [$mediaFiles];
        }
        foreach ($mediaFiles as $fileUrl) {
            $filePath = str_replace(
                base_url(),
                FCPATH,
                $fileUrl
            );
            if (file_exists($filePath) && !unlink($filePath)) {
                return false; // Return false if unable to delete any file
            }
        }
        return true;
    }

    public static function getAllowedExtensions()
    {
        $allowed_extensions = explode(',', get_option('allowed_files'));
        $allowed_extensions = array_map('trim', $allowed_extensions);
        return $allowed_extensions;
    }
    //#endregion files

    //#region data
    /**
     * Sorts an array of associative arrays by a specified field in descending order.
     *
     * This method uses the usort function to sort the provided array by the values 
     * of a specified field. If the field does not exist in an associative array, 
     * a default value of 0 is used for the comparison.
     *
     * @param array  &$data       The array of associative arrays to be sorted. 
     *                            This array is passed by reference and will be modified.
     * @param string $field_name  The key name of the field to sort by. Default is 'created'.
     *
     * @return void This function does not return a value. It directly modifies the input array.
     */
    public static function sortByFieldName(&$data, $field_name = 'created')
    {
        usort($data, function ($a, $b) use ($field_name) {
            $fieldA = isset($a[$field_name]) ? $a[$field_name] : 0;
            $fieldB = isset($b[$field_name]) ? $b[$field_name] : 0;
            return $fieldB - $fieldA;
        });
    }

    //#endregion data

    #region xss
    public static function clean_xss_except($data, $excludedKeys = [])
    {
        $CI = &get_instance();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (!in_array($subKey, $excludedKeys)) {
                        $data[$key][$subKey] = $CI->security->xss_clean($subValue);
                    }
                }
            } elseif (!in_array($key, $excludedKeys)) {
                $data[$key] = $CI->security->xss_clean($value);
            }
        }
        return $data;
    }
    #endregion xss

    #region data table reorder
    /**
     * Resets column display settings for a table based on the `$keyReorder` and the `$option` type.
     * When columns are reordered, this function resets all user-defined column display and filter configurations.
     *
     * @param string $keyReorder The key that identifies the object to reset in the configuration array.
     * @param string $option Either `POLY_TABLE_FILTERS` or `POLY_TABLE_COLUMNS_REORDER`.
     *                        - `POLY_TABLE_FILTERS`: Represents column display settings.
     *                        - `POLY_TABLE_COLUMNS_REORDER`: Represents column reorder settings.
     * @param bool $is_contains Determines whether to match the field exactly (`false`) or partially (`true`).
     *
     * @return void
     */
    public static function reset_columns_display($keyReorder, $option, $is_contains = false)
    {
        $dataColumnsDisplay = get_option($option);
        $dataTableColumnsDisplay = !empty($dataColumnsDisplay)
            ? json_decode($dataColumnsDisplay, true)
            : [];

        // Use the appropriate removal method based on $is_contains
        if ($is_contains) {
            $updatedTableColumns = self::removeDataByFieldContains($dataTableColumnsDisplay, 'key', $keyReorder);
        } else {
            $updatedTableColumns = self::removeDataByField($dataTableColumnsDisplay, 'key', $keyReorder);
        }

        if (count($updatedTableColumns) !== count($dataTableColumnsDisplay)) {
            update_option($option, json_encode($updatedTableColumns));
        }
    }
    #endregion data table reorder
}
