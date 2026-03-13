<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Video Library Module | Módulo da Biblioteca de Vídeo
Description: Biblioteca de vídeos para treinamento
Version: 1.0.1
Author: Zonvoir | Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
Requires at least: 2.3.*
*/
if (!defined('MODULE_VIDEO_LIBRARY')) {
    define('MODULE_VIDEO_LIBRARY', basename(__DIR__));
}
define('VIDEO_LIBRARY_UPLOADS_FOLDER', FCPATH . 'uploads/video_library' . '/');
define('VIDEO_LIBRARY_DISCUSSIONS_ATTACHMENT_FOLDER', FCPATH . 'uploads/video_library/discussions/attachment' . '/');
// Google API configuration 
$drive_id = get_option('vl_google_client_id');
$drive_secret = get_option('vl_google_client_secret');
$drive_url = get_option('vl_google_client_redirect_uri');
define('GOOGLE_CLIENT_ID', $drive_id);
define('GOOGLE_CLIENT_SECRET', $drive_secret);
define('GOOGLE_OAUTH_SCOPE', 'https://www.googleapis.com/auth/drive');
define('REDIRECT_URI', $drive_url);
$CI = &get_instance();
$CI->load->helper(MODULE_VIDEO_LIBRARY . '/video_library');
/**
 * Register activation module hook
 */
register_activation_hook(MODULE_VIDEO_LIBRARY, 'video_library_module_activation_hook');

function video_library_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}
register_language_files(MODULE_VIDEO_LIBRARY, [MODULE_VIDEO_LIBRARY]);
/**
 * Register uninstall module hook
 */
register_uninstall_hook(MODULE_VIDEO_LIBRARY, 'video_library_module_uninstall_hook');

function video_library_module_uninstall_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/uninstall.php');
}
hooks()->add_action('admin_init', 'video_library_module_init_menu_items');
function video_library_module_init_menu_items()
{
    $CI = &get_instance();
    $CI->app->add_quick_actions_link([
        'name'       => _l('vl_video_library'),
        'url'        => 'video_library',
        'permission' => 'video_library',
        'position'   => 52,
    ]);
    if (is_admin()) {
        // The first paremeter is the parent menu ID/Slug
        $CI->app_menu->add_setup_menu_item('Video_lib_setup', [
            'collapse' => true,
            'name' => _l('Video Library'),
            'position' => 10,
        ]);
        $CI->app_menu->add_setup_children_item('Video_lib_setup', [
            'slug' => 'Video_lib_setup-groups',
            'name' => _l('Google Drive'),
            'href' => admin_url('video_library/video_drive_setup'),
            'position' => 5,
        ]);
    }
    if (has_permission('video_library', '', 'view_own') || has_permission('video_library', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('video_library', [
            'slug'     => 'video_library',
            'name'     => _l('vl_menu'),
            'position' => 10,
            'icon'     => 'fa fa-video-camera'
        ]);
        $CI->app_menu->add_sidebar_children_item('video_library', [
            'slug'     => 'video_library_dashboard',
            'name'     => _l('vl_videos_submenu'),
            'href'     => admin_url('video_library/index'),
            'position' => 1,
        ]);
        $CI->app_menu->add_sidebar_children_item('video_library', [
            'slug'     => 'video_library_categeories',
            'name'     => _l('vl_categories_submenu'),
            'href'     => admin_url('video_library/categeory'),
            'position' => 2,
        ]);
        $CI->app_tabs->add_project_tab('video_library', [
            'name'                      => _l('vl_video_library'),
            'icon'                      => 'fa fa-video-camera',
            'view'                      => 'video_library/admin/libraries/project_videos',
            'position'                  => 40,
        ]);
    }
    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete')
    ];
    register_staff_capabilities(MODULE_VIDEO_LIBRARY, $capabilities, _l('vl_video_library'));
}
hooks()->add_filter('get_upload_path_by_type', 'video_library_get_upload_path_by_type', 10, 2);
function video_library_get_upload_path_by_type($path, $type)
{
    if ($type == 'video_library') {
        $path = VIDEO_LIBRARY_UPLOADS_FOLDER;
    }
    return $path;
}
hooks()->add_action('app_customers_head', 'video_library_customer_project_tabs');
function video_library_customer_project_tabs()
{
    $CI = &get_instance();
    if ($CI->uri->segment(2) == 'project') {
        $project_id = $CI->uri->segment(3); ?>
        <script type="text/javascript">
            $(document).ready(function() {
                var node = '<li role="presentation" class="project_tab_video_library"><a data-group="project_video_library" href="<?php echo site_url('admin/video_library/client/project/' . $project_id); ?>?group=video_library" role="tab"><i class="fa fa-video-camera" aria-hidden="true"></i> <?php echo _l('vl_video_library'); ?> </a></li>';
                $('.nav-tabs').append(node);
            });
        </script>
    <?php }
    ?>
<?php
}
