<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Manage Custom Statuses | Gerenciar Status Personalizados
Description: O módulo permitirá adicionar mais status a Projetos e Tarefas.
Author: Sejal Infotech | Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
Version: 1.0.0
Requires at least: 1.0.*
*/

define('SI_CUSTOM_STATUS_MODULE_NAME', 'si_custom_status');

$CI = &get_instance();

hooks()->add_action('admin_init','si_custom_status_hook_admin_init');
hooks()->add_filter('module_'.SI_CUSTOM_STATUS_MODULE_NAME.'_action_links', 'module_si_custom_status_action_links');
hooks()->add_filter('before_get_project_statuses','si_custom_status_hook_before_get_project_statuses');
hooks()->add_filter('before_get_task_statuses','si_custom_status_hook_before_get_task_statuses');

function module_si_custom_status_action_links($actions)
{
	$actions[] = '<a href="' . admin_url('si_custom_status/statuses/projects') . '">' . _l('settings') . '</a>';
	return $actions;
}
$CI->load->model(SI_CUSTOM_STATUS_MODULE_NAME . '/si_custom_status_model');
$CI->load->helper(SI_CUSTOM_STATUS_MODULE_NAME . '/si_custom_status');
register_activation_hook(SI_CUSTOM_STATUS_MODULE_NAME, 'si_custom_status_activation_hook');
function si_custom_status_activation_hook()
{
	$CI = &get_instance();
	require_once(__DIR__ . '/install.php');
}
register_language_files(SI_CUSTOM_STATUS_MODULE_NAME, [SI_CUSTOM_STATUS_MODULE_NAME]);
function si_custom_status_hook_admin_init()
{
	$CI = &get_instance();
	/*Add customer permissions */
	$capabilities = [];
	$capabilities['capabilities'] = [
		'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
		'create'	 => _l('permission_create'),
		'edit'	 => _l('permission_edit'),
		'delete'	=> _l('permission_delete'),
	];
	register_staff_capabilities('si_custom_status', $capabilities, _l('si_custom_status'));
	
	/** Add Menu for Custom Statuses in Setup**/
	if (is_admin() || has_permission('si_custom_status', '', 'view')) {
		$CI->app_menu->add_setup_menu_item('si_custom_status_setup_menu', [
			'collapse' => true,
			'name'     => _l('si_custom_status_setup_menu'),
			'position' => 35,
		]);
		$CI->app_menu->add_setup_children_item('si_custom_status_setup_menu', [
			'slug'     => 'si-custom-status-project',
			'name'     => _l('si_custom_status_project_statuses_menu'),
			'href'     => admin_url('si_custom_status/statuses/projects'),
			'position' => 10,
		]);
		$CI->app_menu->add_setup_children_item('si_custom_status_setup_menu', [
			'slug'     => 'si-custom-status-task',
			'name'     => _l('si_custom_status_task_statuses_menu'),
			'href'     => admin_url('si_custom_status/statuses/tasks'),
			'position' => 10,
		]);
	}
}
function si_custom_status_hook_before_get_project_statuses($statuses)
{
	$CI = &get_instance();
	$custom_statuses = si_cs_get_custom_statuses("projects");
	if(!empty($custom_statuses))
		$statuses = array_merge($statuses,$custom_statuses);    
	return $statuses;
}
function si_custom_status_hook_before_get_task_statuses($statuses)
{
	$CI = &get_instance();
	$custom_statuses = si_cs_get_custom_statuses("tasks");
	if(!empty($custom_statuses))
		$statuses = array_merge($statuses,$custom_statuses);     
	return $statuses;
}



