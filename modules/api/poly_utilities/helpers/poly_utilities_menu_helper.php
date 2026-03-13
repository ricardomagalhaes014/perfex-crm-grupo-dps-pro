<?php

defined('BASEPATH') or exit('No direct script access allowed');

class POLYCUSTOMMENU
{
    const SIDEBAR = 'sidebar';
    const SETUP = 'setup';
    const CLIENTS = 'clients';
}

/**
 * Helper function to convert the menu structure from a hierarchical tree into a flat array.
 *
 * @param array $menu_items The original list of menu items in a hierarchical structure.
 * @return array The list of menu items flattened into a single level array.
 */
function poly_flatten_menu_items($menu_items)
{
    $flat_menu = [];

    foreach ($menu_items as $item) {
        $flat_menu[] = $item;
        if (isset($item['children']) && !empty($item['children'])) {
            $children_flat = poly_flatten_menu_items($item['children']);
            $flat_menu = array_merge($flat_menu, $children_flat);
            unset($item['children']);
        }
    }

    return $flat_menu;
}

function poly_create_menu_item($custom_link)
{
    $id = 'i' . uniqid(); //slug

    $menu_item = $custom_link;

    /*if (isset($menu_item['icon']) && strpos($menu_item['icon'], 'svg') !== false) {
        $menu_item['svg'] = $menu_item['icon'];
        $menu_item['icon'] = 'menu-icon';
    }*/

    $menu_item['id'] = $id;
    $menu_item['slug'] = $id;
    $menu_item['disabled'] = 'true';/* Display is default */
    $menu_item['position'] = 0;

    return $menu_item;
}

function poly_utilities_is_user_access_module($user_id)
{
    $data = json_decode(poly_utilities_user_helper::get_users_access_modules(), true);
    if (!isset($data['users_access']) || !is_array($data['users_access'])) {
        return true;
    }

    foreach ($data['users_access'] as $user) {
        if (isset($user['id']) && $user['id'] == $user_id) {
            return true;
        }
    }

    return false;
}

function poly_utilities_is_user_access_custom_menu($user_id)
{
    $data = json_decode(poly_utilities_user_helper::get_users_access_modules(), true);
    if (!isset($data['users_custom_menu']) || !is_array($data['users_custom_menu'])) {
        return false;
    }

    foreach ($data['users_custom_menu'] as $user) {
        if (isset($user['id']) && $user['id'] == $user_id) {
            return true;
        }
    }

    return false;
}

function poly_custom_create_menu_item_array($item)
{
    $href = poly_utilities_normalize_url($item['href']);
    if (isset($item['type']) && $item['type']) {

        if (!isset($item['href_attributes'])) {
            $item['href_attributes'] = [];
        }

        if (!isset($item['href_attributes']['class'])) {
            $item['href_attributes']['class'] = '';
        }

        if (!isset($item['href_attributes']['popup'])) {
            $item['href_attributes']['popup'] = '';
        }

        switch ($item['type']) {
            case 'default': { // Accept empty
                    $href = $item['href'];
                    break;
                }
            case 'none':
                $href = '#';
                break;
            case 'iframe':
                $href = site_url(POLY_UTILITIES_CUSTOM_MENU_CLIENTS_SLUG . '/' . $item['slug']);
                $item['href_original'] = $item['href'];
                break;
            case 'popup':
                $href = '#';
                $class = $item['href_attributes']['class'];
                $arr_class = explode(' ', $class);
                array_push($arr_class, 'poly-menu-popup');
                $item['href_attributes']['class'] = implode(' ', $arr_class);
                $item['href_attributes']['popup'] = $item['slug'];
                break;
            default:
                $href = $item['href'];
                break;
        }
    }
    $item['href'] = $href;
    $menu_item = $item;
    $menu_item['href_attributes'] = [
        "target" => isset($item['target']) ? $item['target'] : '',
        "rel" => isset($item['rel']) ? $item['rel'] : ''
    ];

    if (isset($item['href_attributes']['data-children'])) {
        $menu_item['href_attributes']['data-children'] = $item['href_attributes']['data-children'] ?? '[]';
    }

    return $menu_item;
}

function app_admin_poly_custom_setup_menu_items($items, $exclude_disabled = false)
{
    $menu_items_arranged = poly_utilities_custom_sidebar_menu_items_pre_render($items, POLY_MENU_SETUP_CUSTOM_ACTIVE);

    $custom_menu_items_option = get_option(POLY_MENU_SETUP);
    $custom_menu_items = ($custom_menu_items_option != null) ? json_decode($custom_menu_items_option, TRUE) : [];

    $rest_menu_items = poly_utilities_custom_sidebar_menu_defined($menu_items_arranged, $custom_menu_items, $exclude_disabled);
    return $rest_menu_items;
}

function app_admin_poly_custom_sidebar_menu_items($items, $exclude_disabled = false)
{
    $menu_items_arranged = poly_utilities_custom_sidebar_menu_items_pre_render($items, POLY_MENU_SIDEBAR_CUSTOM_ACTIVE);// Exist full system menu

    $custom_menu_items_option = get_option(POLY_MENU_SIDEBAR);
    $custom_menu_items = ($custom_menu_items_option != null) ? json_decode($custom_menu_items_option, TRUE) : [];

    $rest_menu_items = poly_utilities_custom_sidebar_menu_defined($menu_items_arranged, $custom_menu_items, $exclude_disabled);
    return $rest_menu_items;
}
function app_admin_poly_custom_clients_menu_items()
{
    // Override the previous hook function:
    // application/helpers/themes_helper.php => add_default_theme_menu_items function that adds the list of default links: knowledge-base, login, register into the client's menu first.

    $menu_items_custom = get_option(POLY_MENU_CLIENTS);
    if ($menu_items_custom === '[]' || $menu_items_custom === '') {
        $menu_items_custom = get_option(POLY_MENU_CLIENTS_CUSTOM_ACTIVE);
    }

    $custom_clients_menu_items = poly_utilities_common_helper::json_decode($menu_items_custom, TRUE);
    $current_client_id = get_client_user_id();

    // First: remove menu items that the current client does not have permission to access.
    if (is_client_logged_in()) {
        $flat_menu_items = poly_flatten_menu_items($custom_clients_menu_items);
        poly_process_menu_items($flat_menu_items, $custom_clients_menu_items);
    } else {
        // Remove all clients item logged
        poly_remove_menu_items_logged($custom_clients_menu_items);

        // Remove register
        if (get_option('allow_registration') != 1) {
            poly_remove_menu_item_by_slug($custom_clients_menu_items, 'register');
        }
    }

    foreach ($custom_clients_menu_items as $key => &$item) {
        // SVG
        if (isset($item['icon']) && strpos($item['icon'], 'svg') !== false) {
            $item['svg'] = $item['icon'];
            $item['icon'] = 'menu-icon';
        }

        // Submenu level 3
        if (!empty($item['children'])) {
            $item['href_attributes']['data-children'] = htmlspecialchars(json_encode($item['children']), ENT_QUOTES, 'UTF-8');
        }

        if (is_client_logged_in() && in_array($item['slug'], ['register', 'login'])) {
            continue;
        }

        if (is_admin() || !isset($item['require_login']) || $item['require_login'] !== 'on') {
            $menu_item = poly_custom_create_menu_item_array($item);
            add_theme_menu_item($menu_item['slug'], $menu_item);
            continue;
        }

        if ($item['require_login'] === 'on' && !is_client_logged_in()) {
            continue;
        }

        if (!empty($item['clients']) && $item['clients'] != '[]') {
            $clients = poly_utilities_common_helper::json_decode($item['clients'], true);
            $client_can_access = poly_utilities_common_helper::get_item_by($clients, 'id', $current_client_id);

            if (!$client_can_access) {
                continue;
            }
        }

        // Remove: 'knowledge-base', 'register', 'login'
        if (in_array($item['slug'], ['knowledge-base', 'register', 'login'])) {
            continue;
        }

        $menu_item = poly_custom_create_menu_item_array($item);
        add_theme_menu_item($menu_item['slug'], $menu_item);
    }

    unset($item);
}

/**
 * Render the main sidebar list with custom attributes, categorizing menus: iframe, popup, blank,...
 */
function poly_utilities_custom_sidebar_menu_items_pre_render($items, $MENU_DEFINE)
{
    $menu_items_merged = $items;
    $menu_items_custom = get_option($MENU_DEFINE);
    if (!empty($menu_items_custom)) {
        $tmp = poly_utilities_init_custom_sidebar_menu_items($menu_items_custom);
        $menu_items_merged = array_merge($items, $tmp);
    }
    $menu_items_merged = poly_utilities_init_custom_sidebar_menu_items($menu_items_merged);

    foreach ($menu_items_merged as $key => &$value) {
        if (!isset($value['position'])) {
            $value['position'] = 0;
        }
    }
    unset($value);

    usort($menu_items_merged, function ($a, $b) {
        return $a['position'] <=> $b['position'];
    });

    // Reset root position
    $maxPositionParent = 0;
    foreach ($menu_items_merged as $key => &$value) {
        $value['position'] = $maxPositionParent++;
    }
    unset($value);

    foreach ($menu_items_merged as $key => &$value) {
        if (!empty($value['children']) && is_array($value['children'])) {
            $positions = array_column($value['children'], 'position');
            $maxPosition = (!empty($positions)) ? max($positions) : 0;

            foreach ($value['children'] as &$children_item) {
                if (!isset($children_item['position'])) {
                    $maxPosition++;
                    $children_item['position'] = $maxPosition;

                    // Level 3
                    if (isset($children_item['children']) && is_array($children_item['children'])) {
                        $positions_level3 = array_column($children_item['children'], 'position');
                        $maxPositionLevel3 = (!empty($positions_level3)) ? max($positions_level3) : 0;
                        foreach ($children_item['children'] as &$children_item3) {
                            if (!isset($children_item3['position'])) {
                                $maxPositionLevel3++;
                                $children_item3['position'] = $maxPositionLevel3;
                            }
                        }
                        unset($children_item3);

                        usort($children_item['children'], function ($a, $b) {
                            return $a['position'] <=> $b['position'];
                        });
                    }
                    // Level 3
                }
            }
            unset($children_item);

            usort($value['children'], function ($a, $b) {
                return $a['position'] <=> $b['position'];
            });
        }
    }
    return $menu_items_merged;
}

/**
 * Function to rearrange the order of menu items based on parent_slug and children.
 * @param array $custom_menu_items List of menus to be sorted.
 * @return array Array of sorted menu items.
 */
function poly_utilities_init_custom_sidebar_menu_items($custom_menu_items)
{
    $menu_items = $custom_menu_items;
    if (is_string($custom_menu_items)) {
        $menu_items = json_decode($custom_menu_items, true);
    }
    $result = [];
    $temp = [];
    foreach ($menu_items as &$item) {
        $temp[$item['slug']] = $item + ['children' => []];
    }
    unset($item);

    foreach ($temp as $key => &$itm) {
        if (!empty($itm['parent_slug']) && isset($temp[$itm['parent_slug']])) {
            $temp[$itm['parent_slug']]['children'][] = &$itm;
        } else {
            $result[$itm['slug']] = &$itm;
        }
    }
    unset($itm);
    return $result;
}

/**
 * Searches for a menu item by its slug within a list of menu items.
 * 
 * @param array $custom_menu_items An array of menu item objects, where each item is expected
 * to be an associative array with at least a 'slug' key.
 * @param string $menu_item_slug The slug string to search for within the 'slug' attribute of each menu item array.
 * 
 * @return mixed Returns the found menu item array if a match is found, or null if no match is found.
 */
function poly_utilities_find_menu_item_by_slug($custom_menu_items, $menu_item_slug, $is_object = false)
{
    foreach ($custom_menu_items as $item) {
        if ($item['slug'] === $menu_item_slug) {
            return $is_object ? $item : true;
        }
        if (isset($item['children']) && is_array($item['children'])) {
            if (poly_utilities_find_menu_item_by_slug($item['children'], $menu_item_slug)) {
                return $is_object ? $item : true;
            }
        }
    }
    return $is_object ? null : false;
}

/**
 * Reorders the full menu list to maintain the custom sort order of the custom menu.
 * @param array &$custom_menu_items The custom sorted menu list. This array may be modified to include items from $menu_items that are not present.
 * @param array $menu_items The full list of menu items, including those in $custom_menu_items but not sorted.
 */
function poly_utilities_menu_sidebar_merged(&$custom_menu_items, $menu_items)
{
    //TODO: $item exist in $menu_items but not in $custom_menu_items => add it
    foreach ($menu_items as &$item) {
        $current_object = poly_utilities_find_menu_item_by_slug($custom_menu_items, $item['slug']);
        if (!$current_object) {
            $custom_menu_items[] = $item;
        }
    }
    unset($item);
    
    //TODO: $item exist in $custom_menu_items but not in $menu_item => remove it
    $menu_items_mapped = [];
    poly_utilities_map_slug_arr_sidebar_menu($menu_items, $menu_items_mapped);
    poly_utilities_menu_sidebar_merged_mapped($custom_menu_items, $menu_items_mapped);

    // Add update menu items: synchronize the menu items of the core modules with changes in the item list.
    poly_utilities_synchronize_menu_items($menu_items_mapped, $custom_menu_items);
}

function poly_utilities_synchronize_menu_items(&$menuItemsMapped, &$customMenuItems) {
    foreach ($customMenuItems as &$customItem) {
        if (isset($menuItemsMapped[$customItem['slug']])) {
            $mappedItem = $menuItemsMapped[$customItem['slug']];

            if (!empty($mappedItem['children']) && is_array($mappedItem['children'])) {
                if (!isset($customItem['children']) || !is_array($customItem['children'])) {
                    $customItem['children'] = [];
                }

                $childrenUpdated = false;
                foreach ($mappedItem['children'] as $mappedChild) {
                    $childExists = false;

                    foreach ($customItem['children'] as &$existingChild) {
                        if ($existingChild['slug'] === $mappedChild['slug']) {
                            $childExists = true;
                            break;
                        }
                    }

                    if (!$childExists) {
                        $customItem['children'][] = $mappedChild;
                        $childrenUpdated = true;
                    }
                }

                if ($childrenUpdated) {
                    usort($customItem['children'], function ($a, $b) {
                        return intval($a['position']) <=> intval($b['position']);
                    });
                }
                poly_utilities_synchronize_menu_items($menuItemsMapped, $customItem['children']);
            }
        }
    }
}

/**
 * Function to remove all custom elements in $custom_menu_items if they do not exist in the main menu $menu_items (mapped by slug).
 */
function poly_utilities_menu_sidebar_merged_mapped(&$custom_menu_items, $menu_items_mapped)
{
    foreach ($custom_menu_items as $key => &$custom_item) {
        $exists = false;
        foreach ($menu_items_mapped as $item) {
            if ($item['slug'] === $custom_item['slug']) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            unset($custom_menu_items[$key]);
        } else {
            if (isset($custom_item['children']) && is_array($custom_item['children'])) {
                poly_utilities_menu_sidebar_merged_mapped($custom_item['children'], $menu_items_mapped);
            }
        }
    }
}

/**
 * Check permission to view the custom menu feature
 */
function staff_can_poly_utilities_custom_menu()
{
    $staff_id = get_staff_user_id();
    /**
     * TODO features: Handle the case where granting admin rights then removes permissions to install modules, backup
     */
    if ((is_admin() && ($staff_id != 1 && !poly_utilities_is_user_access_custom_menu($staff_id))) || !staff_can('view', 'poly_utilities_custom_menu_extend')) {
        access_denied();
    }
}

/**
 * Check permission to view the polyutilities feature
 */
function staff_can_poly_utilities()
{
    $staff_id = get_staff_user_id();
    /**
     * TODO features: Handle the case where granting admin rights then removes permissions to install modules, backup
     */
    if ((is_admin() && ($staff_id != 1 && !poly_utilities_is_user_access_module($staff_id)))) {
        access_denied();
    }
}

/**
 * Prevents access to a specific custom menu by removing it from the $menu_items list.
 * The function iterates over $menu_items, checking for a specific 'slug'. If the 'slug' matches
 * the predefined value and the staff does not have the 'view' permission for this menu, it is removed.
 * It also recursively checks and applies the same logic to any children menus.
 *
 * @param array &$menu_items An array representing the list of menu items. Each item is an associative array that may include 'slug' and 'children' keys.
 */
function poly_utilities_denie_access_custom_menu(&$menu_items)
{
    foreach ($menu_items as $key => &$item) {
        if (isset($item['slug']) && $item['slug'] === 'poly_utilities_custom_menu_extend' && !staff_can('view', $item['slug'])) {
            unset($menu_items[$key]);
            continue;
        }

        if (array_key_exists('children', $item) && is_array($item['children'])) {
            poly_utilities_denie_access_custom_menu($item['children']);
        }
    }
    unset($item);
    $menu_items = array_values($menu_items);
}

/**
 * Sorts the main menu items $menu_items according to the order specified in the list of custom menu items.
 * This function adjusts the order of $menu_items based on their positions in the $custom_menu_items list,
 * ensuring that the final order of menu items reflects the custom order defined.
 *
 * @param array $menu_items An array of the main menu items. Each item in this array is expected to be
 * an associative array that could represent a menu item.
 * @param array $custom_menu_items An array of custom menu items specifying the desired order. Each item
 * in this array should correspond to or be identifiable with items in $menu_items, dictating the order
 * the items in $menu_items should be arranged in.
 */
function poly_utilities_custom_sidebar_menu_defined($menu_items, $custom_menu_items, $exclude_disabled = false)
{
    if ($custom_menu_items != null) {
        foreach ($custom_menu_items as $key => &$item) {
            if (!array_key_exists('children', $item)) {
                $item['children'] = [];
            }
            if (!empty($item['children'])) {
                poly_utilities_denie_access_custom_menu($item['children']);
            }
        }
        unset($item);

        $menu_sidebar_slug_map_items = [];

        poly_utilities_map_slug_arr_sidebar_menu($menu_items, $menu_sidebar_slug_map_items);
        poly_utilities_menu_sidebar_language($custom_menu_items, $menu_sidebar_slug_map_items);

        poly_utilities_menu_sidebar_merged($custom_menu_items, $menu_items);
        
        $menu_items = $custom_menu_items;
    }

    poly_utilities_menu_sidebar_define_by_type($menu_items);

    //ROLES & Permissions
    $staff_id = get_staff_user_id();
    poly_utilities_menu_sidebar_users_access($menu_items, $staff_id, $exclude_disabled);
    //ROLES & Permissions

    return $menu_items;
}

function poly_utilities_menu_sidebar_users_access(&$menu_items, $staff_id, $exclude_disabled = false)
{
    foreach ($menu_items as $key => &$item) {

        if (!isset($item['disabled'])) {
            $item['disabled'] = 'true'; //Display all;
        }

        if (isset($item['icon']) && strpos($item['icon'], 'svg') !== false) {
            $item['svg'] = $item['icon'];
            $item['icon'] = 'menu-icon';
        }

        if ($item['disabled'] && $item['disabled'] === 'false') {
            $arr_class = [];
            if (isset($item['href_attributes']['class'])) {
                $class = $item['href_attributes']['class'];
                $arr_class = explode(' ', $class);
            }
            array_push($arr_class, 'poly-remove-menu-items');
            array_push($arr_class, 'hide');
            $item['href_attributes']['class'] = implode(' ', $arr_class);

            $item['name'] = '<span class="poly-hidehide hide">' . $item['name'] . '</span>';
        }
        //Disabled

        //Badge
        if (isset($item['is_custom']) && $item['is_custom']) {
            if ($item['is_custom'] === 'true') {
                $color = '#fff';
                $height = '1px';
                if (isset($item['badge']) && empty($item['badge']['value']) && ((isset($item['type']) && $item['type'] !== 'divider'))) {
                    $item['badge']['value'] = '';
                    $item['badge']['color'] = 'transparent';
                } else {
                    if (isset($item['badge']['value'])) {
                        $item['badge']['value'] = $item['badge']['value'];
                        $height = !empty($item['badge']['value']) ? $item['badge']['value'] : $height;
                        $color = empty($item['badge']['color']) ? 'transparent' : $item['badge']['color'];
                    }
                }

                if (isset($item['type']) && $item['type'] === 'divider') {
                    if (isset($item['icon'])) {
                        $item['icon'] = '';
                    }
                    $item['name'] = '<span class=\'poly-dividivi hide\' title=\'background-color:' . $color . ';height:' . $height . '\'>' . $item['name'] . '</span>';
                }
            }
        }
        //Badge

        //Roles
        $user_can_access = false;
        $role_can_access = false;

        if (!empty($item['roles'])) {
            $role_by_staffid = poly_utilities_user_helper::get_user_role($staff_id);
            if ($role_by_staffid !== null) {
                $roleid_by_user = $role_by_staffid->role;
                $roles_access = poly_utilities_common_helper::json_decode($item['roles'], true);
                $role_can_access = poly_utilities_common_helper::get_item_by($roles_access, 'id', $roleid_by_user);
            }
        } else {
            $role_can_access = true;
        }

        //Users
        if (!empty($item['users'])) {
            $users = poly_utilities_common_helper::json_decode($item['users'], true);
            $user_can_access = poly_utilities_common_helper::get_item_by($users, 'id', $staff_id);
        } else {
            $user_can_access = true;
        }

        //Remove menu items from the list if the account or group does not have access permission.
        if (!$role_can_access && !$user_can_access && ($staff_id != 1 && !poly_utilities_is_user_access_module($staff_id))) {
            unset($menu_items[$key]);
        } elseif (!empty($item['children'])) {
            poly_utilities_menu_sidebar_users_access($item['children'], $staff_id, $exclude_disabled);
        }
    }
    unset($item);
}

/**
 * Handle custom item by type: none, iframe, popup,...
 */
function poly_utilities_menu_sidebar_define_by_type(&$finally_sidebar_menu_items)
{
    foreach ($finally_sidebar_menu_items as $key => &$item) {
        $href = poly_utilities_normalize_url($item['href']);
        if (isset($item['type']) && $item['type']) {

            if (!isset($item['href_attributes'])) {
                $item['href_attributes'] = [];
            }

            if (!isset($item['href_attributes']['class'])) {
                $item['href_attributes']['class'] = '';
            }

            if (!isset($item['href_attributes']['popup'])) {
                $item['href_attributes']['popup'] = '';
            }

            switch ($item['type']) {
                case 'none':
                    $href = '#';
                    break;
                case 'iframe':
                    $href = admin_url('poly_utilities/details/' . $item['slug']);
                    $item['href_original'] = $item['href'];

                    break;
                case 'popup':
                    $href = '#';
                    $class = $item['href_attributes']['class'];
                    $arr_class = explode(' ', $class);
                    array_push($arr_class, 'poly-menu-popup');
                    $item['href_attributes']['class'] = implode(' ', $arr_class);
                    $item['href_attributes']['popup'] = $item['slug'];

                    $item['href_attributes']['data-popup'] = isset($item['popup_description']) 
                    ? htmlspecialchars(json_encode($item['popup_description'], JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') 
                    : '';

                    break;
                case 'divider':
                    $href = '#';
                    $class = $item['href_attributes']['class'];
                    $arr_class = explode(' ', $class);
                    array_push($arr_class, 'poly-menu-divider');
                    $item['href_attributes']['class'] = implode(' ', $arr_class);
                    break;
                default:
                    $href = $item['href'];
                    break;
            }

            $item['href'] = $href;

            if (isset($item['target'])) {
                $item['href_attributes']['target'] = $item['target'];
            }
            if (isset($item['rel'])) {
                $item['href_attributes']['rel'] = $item['rel'];
            }
            if (isset($item['data-type'])) {
                $item['href_attributes']['data-type'] = $item['type'];
            }
        }

        foreach ($item['children'] as &$child_item) {
            $child_href = poly_utilities_normalize_url($child_item['href']);
            if (isset($child_item['type']) && $child_item['type']) {

                if (!isset($child_item['href_attributes'])) {
                    $child_item['href_attributes'] = [];
                }

                if (!isset($child_item['href_attributes']['class'])) {
                    $child_item['href_attributes']['class'] = '';
                }

                if (!isset($child_item['href_attributes']['popup'])) {
                    $child_item['href_attributes']['popup'] = '';
                }

                switch ($child_item['type']) {
                    case 'none':
                        $child_href = '#';
                        break;
                    case 'iframe':
                        $child_href = admin_url('poly_utilities/details/' . $child_item['slug']);
                        $child_item['href_original'] = $child_item['href'];
                        break;
                    case 'popup': // Display popup
                        $child_href = '#';
                        $class = $child_item['href_attributes']['class'];
                        $arr_class = explode(' ', $class);
                        array_push($arr_class, 'poly-menu-popup');
                        $child_item['href_attributes']['class'] = implode(' ', $arr_class);
                        $child_item['href_attributes']['popup'] = $child_item['slug'];

                        $child_item['href_attributes']['data-popup'] = isset($child_item['popup_description']) 
                        ? htmlspecialchars(json_encode($child_item['popup_description'], JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') 
                        : '';

                        break;
                    case 'divider':
                        $child_href = '#';
                        $class = $child_item['href_attributes']['class'];
                        $arr_class = explode(' ', $class);
                        array_push($arr_class, 'poly-menu-divider');
                        $child_item['href_attributes']['class'] = implode(' ', $arr_class);
                        break;
                    default:
                        $child_href = $child_item['href'];
                        break;
                }
            }
            if (($child_item['href'] == '') && (isset($child_item['type']) && $child_item['type'] != 'popup') && ((isset($item['type']) && $item['type'] !== 'divider'))) {
                $child_item['href_attributes']['class'] = 'hide';
            }

            $child_item['href'] = $child_href;

            // Submenu level 3
            if (!empty($child_item['children'])) {

                foreach ($child_item['children'] as &$child_item_sub) {

                    if (!isset($child_item_sub['href_attributes'])) {
                        $child_item_sub['href_attributes'] = [];
                    }
    
                    if (!isset($child_item_sub['href_attributes']['class'])) {
                        $child_item_sub['href_attributes']['class'] = '';
                    }
    
                    if (!isset($child_item_sub['href_attributes']['popup'])) {
                        $child_item_sub['href_attributes']['popup'] = '';
                    }

                    if (isset($child_item_sub['type']) && $child_item_sub['type']) {
                        switch ($child_item_sub['type']) {
                            case 'popup': // Display popup
                                $child_sub_href = '#';
                                $class = (isset($child_item_sub['href_attributes']) && $child_item_sub['href_attributes']['class']) ? $child_item_sub['href_attributes']['class'] : '';
                                $arr_class = explode(' ', $class);
                                array_push($arr_class, 'poly-menu-popup');
                                $child_item_sub['href_attributes']['class'] = implode(' ', $arr_class);
                                $child_item_sub['href_attributes']['popup'] = $child_item_sub['slug'];
        
                                $child_item_sub['href_attributes']['data-popup'] = isset($child_item_sub['popup_description']) 
                                ? htmlspecialchars(json_encode($child_item_sub['popup_description'], JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') 
                                : '';
                                $child_item_sub['href'] = $child_sub_href;
                                break;
                        }
                    }
                }
                unset($child_item_sub);

                $child_item['href_attributes']['data-children'] = htmlspecialchars(json_encode($child_item['children']), ENT_QUOTES, 'UTF-8');
            }
            // Submenu level 3

            if (isset($child_item['target'])) {
                $child_item['href_attributes']['target'] = $child_item['target'];
            }
            if (isset($child_item['rel'])) {
                $child_item['href_attributes']['rel'] = $child_item['rel'];
            }
            if (isset($child_item['type'])) {
                $child_item['href_attributes']['data-type'] = $child_item['type'];
            }
        }
        unset($child_item);
    }
    unset($item);
}

/**
 * Updates the sidebar menu items by merging updates for a specific item. 
 * If child items are being processed, the item is removed and re-appended under its parent.
 * If the item or its parent is not found, the item is added to the top-level menu.
 * 
 * @param array &$menu_items      Reference to the array of menu items to be updated.
 * @param array $menu_item_update The updated menu item data, including the 'id' and 'parent_slug' to identify the item.
 * @param bool  $isChildrenProcess Flag to indicate whether to process child items. Default is false.
 */
function poly_utilities_menu_sidebar_update(&$menu_items, $menu_item_update, $isChildrenProcess = false)
{
    if (!$isChildrenProcess) {
        $found = false;
        foreach ($menu_items as &$item) {
            if (isset($item['id']) && $item['id'] === $menu_item_update['id']) {
                $item = array_merge($item, $menu_item_update);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $menu_items[] = $menu_item_update;
        }
    } else {
        $found_item_result = poly_remove_item_recursive($menu_items, $menu_item_update);
        if ($found_item_result !== null) {
            if ($found_item_result['status'] === 'updated') {
                return;
            } elseif ($found_item_result['status'] === 'removed') {
                $found_item = array_merge($found_item_result['data'], $menu_item_update);
                $parent_found = poly_find_and_append($menu_items, $found_item);

                if (!$parent_found) {
                    $menu_items[] = $found_item;
                }
            }
        } else {
            if (isset($menu_item_update['parent_slug'])) {
                $parent_found = poly_find_and_append($menu_items, $menu_item_update);
                if (!$parent_found) {
                    $menu_items[] = $menu_item_update;
                }
            } else {
                $menu_items[] = $menu_item_update;
            }
        }
    }
}

/**
 * Recursively removes a menu item by its ID from the menu items array.
 * If the item's parent_slug matches, it updates the item instead of removing it.
 * 
 * @param array  &$menu_items        Reference to the array of menu items.
 * @param object $menu_item_update   The menu item object containing the 'id' and 'parent_slug' to identify the item.
 * 
 * @return array|null                The removed or updated menu item if found, or null if not found.
 */
function poly_remove_item_recursive(&$menu_items, $menu_item_update)
{
    foreach ($menu_items as $index => &$item) {
        if (isset($item['id']) && $item['id'] === $menu_item_update['id']) {
            if (isset($item['parent_slug']) && $item['parent_slug'] === $menu_item_update['parent_slug']) {
                $item = array_merge($item, $menu_item_update);
                return [
                    'status' => 'updated',
                    'data' => $item
                ];
            } else {
                $removed_item = $item;
                unset($menu_items[$index]);
                return [
                    'status' => 'removed',
                    'data' => $removed_item
                ];
            }
        }

        if (isset($item['children']) && !empty($item['children'])) {
            $removed_item = poly_remove_item_recursive($item['children'], $menu_item_update);
            if ($removed_item !== null) {
                return $removed_item;
            }
        }
    }
    return null;
}

/**
 * Recursively finds the parent item by its slug and appends the child item to it.
 * 
 * @param array &$menu_items      Reference to the array of menu items.
 * @param array $child_item       The child menu item to append, which includes a 'parent_slug' to identify the parent.
 * 
 * @return bool                   True if the parent item was found and the child item was appended, false otherwise.
 */
function poly_find_and_append(&$menu_items, $child_item)
{
    foreach ($menu_items as &$item) {
        if (isset($item['id']) && $item['id'] === $child_item['parent_slug']) {
            if (!isset($item['children'])) {
                $item['children'] = [];
            }
            $item['children'][] = $child_item;
            return true;
        }
        if (isset($item['children']) && !empty($item['children'])) {
            $found_in_children = poly_find_and_append($item['children'], $child_item);
            if ($found_in_children) {
                return true;
            }
        }
    }
    return false;
}

function poly_utilities_normalize_url($url)
{
    $parsedUrl = parse_url($url);
    if (isset($parsedUrl['path']) && $parsedUrl['path']) {
        $path = $parsedUrl['path'];
        $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';

        $parts = explode('/', $path);

        $adminKey = array_search('admin', $parts);
        if ($adminKey === false) {
            return $url;
        }
        $afterAdminParts = array_slice($parts, $adminKey + 1);
        $afterAdmin = implode('/', $afterAdminParts) . $query;

        return admin_url($afterAdmin);
    } else {
        return '';
    }
}

/**
 * Handles multilingual display
 */
function poly_utilities_menu_sidebar_language(&$menu_items, $menu_items_map)
{
    foreach ($menu_items as $key => &$item) {

        if (isset($item['slug']) && isset($menu_items_map[$item['slug']])) {
            $item['name'] = $menu_items_map[$item['slug']]['name'];
        } else {
            //Error menu item => remove;
            unset($menu_items[$key]);
            continue;
        }

        if (!empty($item['children'])) {
            poly_utilities_menu_sidebar_language($item['children'], $menu_items_map);
        }
    }
    unset($item);
}

function poly_utilities_map_slug_arr_sidebar_menu($menu_items, &$slugMap)
{
    foreach ($menu_items as $item) {
        $slugMap[$item['slug']] = $item;
        if (!empty($item['children'])) {
            poly_utilities_map_slug_arr_sidebar_menu($item['children'], $slugMap);
        }
    }
}

/**
 * Delete a custom sidebar menu item by id in POLY_MENU_SIDEBAR & POLY_MENU_SIDEBAR_CUSTOM_ACTIVE.
 */
function poly_utilities_delete_custom_sidebar_menu_by_id($id, $storage)
{
    $obj_storage = get_option($storage);
    if (!empty($obj_storage)) {
        $obj_old_data = json_decode($obj_storage, true);

        poly_utilities_common_helper::isRemoveWhenExisted($obj_old_data, 'id', 'children', $id);

        update_option($storage, json_encode($obj_old_data));
        return true;
    }
    return false;
}

function poly_utilities_custom_menu_items($MENU_DEFINE = POLY_MENU_SIDEBAR_CUSTOM_ACTIVE)
{
    $menu_items_custom = get_option($MENU_DEFINE);
    $menu_items_custom = $menu_items_custom ? json_decode($menu_items_custom, TRUE) : [];
    $result = [];
    $flattenMenu = function ($menuItems) use (&$result, &$flattenMenu) {
        foreach ($menuItems as $item) {
            $result[$item['slug']] = $item;
            if (isset($item['children']) && is_array($item['children'])) {
                $flattenMenu($item['children']);
            }
        }
    };
    $flattenMenu($menu_items_custom);

    return $result;
}

function poly_utilities_custom_menu_slim($menu_type)
{
    $menu = poly_utilities_custom_menu_items($menu_type);
    $slim_menu = array_map(function ($item) {
        return [
            'id' => $item['id'],
            'css' => $item['css'] ?? '',
            'icon' => isset($item['svg']) ? htmlspecialchars($item['svg']) : $item['icon']
        ];
    }, $menu);
    return !empty($slim_menu) ? $slim_menu : [];
}

function poly_get_clients_menu_items()
{
    $CI = &get_instance();
    $menu_items = $CI->app_menu->get_theme_items();
    return $menu_items;
}

function poly_clone_menu_items($menu_item, $poly_menu_active, $poly_menu)
{
    $menu_items_custom = get_option($poly_menu_active);
    $menu_items_custom = poly_utilities_common_helper::json_decode($menu_items_custom, TRUE);

    $menu_object = poly_create_menu_item($menu_item);

    if (isset($menu_object['children'])) {
        foreach ($menu_object['children'] as &$item_child) {
            $item_child['is_custom'] = 'true';
            $item_child['parent_slug'] = $menu_object['id'];
            $sub_menu_object = poly_create_menu_item($item_child);
            $item_child = $sub_menu_object;
        }
        unset($item_child);
    }

    $menu_items_custom[] = $menu_object;

    update_option($poly_menu_active, json_encode($menu_items_custom));

    $custom_items_position = get_option($poly_menu);

    if (!empty($custom_items_position)) {
        $custom_items_position = poly_utilities_common_helper::json_decode($custom_items_position, TRUE);
        if ($menu_object['parent_slug'] === 'root') {
            array_unshift($custom_items_position, $menu_object);
            update_option($poly_menu, json_encode($custom_items_position));
        } else {
            foreach ($custom_items_position as &$item) {
                if ($item['slug'] === $menu_object['parent_slug']) {
                    if (empty($item['children'])) {
                        $item['children'] = [];
                    }
                    array_push($item['children'], $menu_object);
                    break;
                }

                if (!empty($item['children'])) {
                    foreach ($item['children'] as &$sub_item) {
                        if ($sub_item['slug'] === $menu_object['parent_slug']) {
                            if (empty($sub_item['children'])) {
                                $sub_item['children'] = [];
                            }
                            array_push($sub_item['children'], $menu_object);
                            break 2;
                        }
                    }
                    unset($sub_item);
                }
            }
            unset($item);
            update_option($poly_menu, json_encode($custom_items_position));
        }
    }
}

function poly_remove_menu_item_by_slug(&$menu_items, $slug)
{
    foreach ($menu_items as $key => $item) {
        if ($item['slug'] === $slug) {
            unset($menu_items[$key]);
            break;
        }
        if (isset($item['children']) && is_array($item['children'])) {
            poly_remove_menu_item_by_slug($item['children'], $slug);
        }
    }
}

/**
 * This function removes menu items that the current client ID does not have permission to access.
 * 
 * The function processes an array of slugs representing the menu items available to clients and checks if 
 * the logged-in contact has the required permissions to view each menu item. If the client does not have 
 * permission for a specific item, that item is removed from the custom client's menu.
 * 
 * Special condition: For the "subscriptions" menu item, it checks a different permission function 
 * (can_logged_in_contact_view_subscriptions()) to determine access.
 *
 * @param array $flat_menu_items - List of available menu items.
 * @param array $custom_clients_menu_items - Custom client menu items to be modified based on permission checks.
 */
function poly_process_menu_items($flat_menu_items, &$custom_clients_menu_items)
{
    $arr_slug = poly_utilities_common_helper::$clients_menu_items ?? [];
    if (!empty($arr_slug)) {
        foreach ($arr_slug as $slug) {
            $current_object = poly_utilities_find_menu_item_by_slug($flat_menu_items, $slug);
            if ($current_object) {
                // Check permission for 'subscriptions' or general permission for other items
                if (($slug === 'subscriptions' && !can_logged_in_contact_view_subscriptions()) ||
                    (!has_contact_permission($slug))
                ) {
                    poly_remove_menu_item_by_slug($custom_clients_menu_items, $slug);
                }
            }
        }
    }
}

function poly_client_logged_in_can_access()
{
    $arr_slug = poly_utilities_common_helper::$clients_menu_items ?? [];

    if (get_option('allow_registration') !== 1) {
        array_push($arr_slug,'register');
    }

    $access = [];
    if (!empty($arr_slug)) {
        $access = $arr_slug;
        if (is_client_logged_in()) {
            foreach ($arr_slug as $key => $slug) {
                if (($slug === 'subscriptions' && !can_logged_in_contact_view_subscriptions()) ||
                    (!has_contact_permission($slug))
                ) {
                    unset($arr_slug[$key]);
                }
            }
        }
    }
    return array('can_access' => array_values($arr_slug), 'access' => array_values($access));
}

function poly_remove_menu_items_logged(&$custom_clients_menu_items)
{
    $arr_slug = poly_utilities_common_helper::$clients_menu_items ?? [];
    if (!empty($arr_slug)) {
        foreach ($arr_slug as $slug) {
            poly_remove_menu_item_by_slug($custom_clients_menu_items, $slug);
        }
    }
}

function poly_add_default_menu_items($flat_menu_items, &$menu_items_custom)
{
    $arr_slug = [
        ['slug' => 'projects', 'name' => _l('clients_nav_projects'), 'href' => site_url('clients/projects'), 'position' => 10],
        ['slug' => 'invoices', 'name' => _l('clients_nav_invoices'), 'href' => site_url('clients/invoices'), 'position' => 15],
        ['slug' => 'contracts', 'name' => _l('clients_nav_contracts'), 'href' => site_url('clients/contracts'), 'position' => 20],
        ['slug' => 'estimates', 'name' => _l('clients_nav_estimates'), 'href' => site_url('clients/estimates'), 'position' => 25],
        ['slug' => 'proposals', 'name' => _l('clients_nav_proposals'), 'href' => site_url('clients/proposals'), 'position' => 30],
        ['slug' => 'subscriptions', 'name' => _l('subscriptions'), 'href' => site_url('clients/subscriptions'), 'position' => 40],
        ['slug' => 'support', 'name' => _l('clients_nav_support'), 'href' => site_url('clients/tickets'), 'position' => 45]
    ];

    foreach ($arr_slug as $item) {
        $current_object = poly_utilities_find_menu_item_by_slug($flat_menu_items, $item['slug']);
        if (!$current_object) {
            array_push($menu_items_custom, [
                'name' => $item['name'],
                'slug' => $item['slug'],
                'href' => $item['href'],
                'position' => $item['position']
            ]);
        }
    }
}

/**
 * Reset the custom menu list: sidebar, setup, clients.
 * @param array $menu The menu to reset, with values being sidebar, setup, clients.
 */
function poly_reset_custom_menu($menu, $remove_custom = false)
{
    if (empty($menu)) return false;

    $menus = [
        'all' => [
            ['key' => POLY_MENU_SIDEBAR, 'custom' => POLY_MENU_SIDEBAR_CUSTOM_ACTIVE],
            ['key' => POLY_MENU_SETUP, 'custom' => POLY_MENU_SETUP_CUSTOM_ACTIVE],
            ['key' => POLY_MENU_CLIENTS, 'custom' => POLY_MENU_CLIENTS_CUSTOM_ACTIVE],
        ],
        POLYCUSTOMMENU::SIDEBAR => [
            ['key' => POLY_MENU_SIDEBAR, 'custom' => POLY_MENU_SIDEBAR_CUSTOM_ACTIVE],
        ],
        POLYCUSTOMMENU::SETUP => [
            ['key' => POLY_MENU_SETUP, 'custom' => POLY_MENU_SETUP_CUSTOM_ACTIVE],
        ],
        POLYCUSTOMMENU::CLIENTS => [
            ['key' => POLY_MENU_CLIENTS, 'custom' => POLY_MENU_CLIENTS_CUSTOM_ACTIVE],
        ],
    ];

    if (!isset($menus[$menu])) {
        return false;
    }

    $result = true;

    foreach ($menus[$menu] as $item) {
        if ($remove_custom) {
            $result = $result && update_option($item['custom'], '[]');
        }
        $result = $result && update_option($item['key'], '[]');
    }

    return $result;
}

/**
 * Delete the custom menu list: sidebar, setup, clients.
 * @param array $menu The menu to delete, with values being sidebar, setup, clients.
 */
function poly_delete_custom_menu($menu)
{
    if (empty($menu)) return false;

    $menus = [
        'all' => [
            ['key' => POLY_MENU_SIDEBAR, 'custom' => POLY_MENU_SIDEBAR_CUSTOM_ACTIVE],
            ['key' => POLY_MENU_SETUP, 'custom' => POLY_MENU_SETUP_CUSTOM_ACTIVE],
            ['key' => POLY_MENU_CLIENTS, 'custom' => POLY_MENU_CLIENTS_CUSTOM_ACTIVE],
        ],
        POLYCUSTOMMENU::SIDEBAR => [
            ['key' => POLY_MENU_SIDEBAR, 'custom' => POLY_MENU_SIDEBAR_CUSTOM_ACTIVE],
        ],
        POLYCUSTOMMENU::SETUP => [
            ['key' => POLY_MENU_SETUP, 'custom' => POLY_MENU_SETUP_CUSTOM_ACTIVE],
        ],
        POLYCUSTOMMENU::CLIENTS => [
            ['key' => POLY_MENU_CLIENTS, 'custom' => POLY_MENU_CLIENTS_CUSTOM_ACTIVE],
        ],
    ];

    if (!isset($menus[$menu])) {
        return false;
    }

    foreach ($menus[$menu] as $item) {
        delete_option($item['custom']);
        delete_option($item['key']);
    }

    return true;
}
