<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: PrediX
Description: AI Integration for Perfex CRM. Empower staff and delight customers with predictive analytics, and personalized interactions. Maximize CRM potential, optimize strategies, and enhance customer satisfaction.
Version: 1.2.1
Author: LenzCreative
Author URI: https://codecanyon.net/user/lenzcreativee/portfolio
Requires at least: 1.0.*
*/

require(__DIR__ . '/libraries/OpenAI.php');

define('PREDIX_MODULE_NAME', 'predix');

hooks()->add_action('admin_init', 'predix_module_init_menu_items');
hooks()->add_action('admin_init', 'predix_permissions');
hooks()->add_action('ticket_created', 'predix_ticket_created_automated_reply');


/**
 * Load the module helper
 */
$CI = &get_instance();
$CI->load->helper(PREDIX_MODULE_NAME . '/predix'); //on module main file

function predix_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',

        'view_chat' => _l('permission_view') . ' - ' . _l('predix_chat'),
        'view_image_gen' => _l('permission_view') . ' - ' . _l('predix_image_gen'),
        'view_transcription' => _l('permission_view') . ' - ' . _l('predix_transcription'),
        'view_translation' => _l('permission_view') . ' - ' . _l('predix_translation'),
        'view_template_categories' => _l('permission_view') . ' - ' . _l('predix_template_categories'),
        'view_templates' => _l('permission_view') . ' - ' . _l('predix_templates'),

        'create_image_gen' => _l('permission_create') . ' - ' . _l('predix_image_gen'),
        'create_transcription' => _l('permission_create') . ' - ' . _l('predix_transcription'),
        'create_translation' => _l('permission_create') . ' - ' . _l('predix_translation'),
        'create_template_categories' => _l('permission_create') . ' - ' . _l('predix_template_categories'),
        'create_templates' => _l('permission_create') . ' - ' . _l('predix_templates'),

        'delete_chat' => _l('permission_delete') . ' - ' . _l('predix_chat'),
        'delete_image_gen' => _l('permission_delete') . ' - ' . _l('predix_image_gen'),
        'delete_transcription' => _l('permission_delete') . ' - ' . _l('predix_transcription'),
        'delete_translation' => _l('permission_delete') . ' - ' . _l('predix_translation'),
        'delete_template_categories' => _l('permission_delete') . ' - ' . _l('predix_template_categories'),
        'delete_templates' => _l('permission_delete') . ' - ' . _l('predix_templates'),

    ];

    register_staff_capabilities('predix', $capabilities, _l('predix'));
}

/**
 * Register activation module hook
 */
//register_activation_hook(PREDIX_MODULE_NAME, 'predix_module_activation_hook');

//function predix_module_activation_hook()
//{
//    $CI = &get_instance();
//    require_once(__DIR__ . '/install.php');
//}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(PREDIX_MODULE_NAME, [PREDIX_MODULE_NAME]);

/**
 * Init module menu items in setup in admin_init hook
 * @return null
 */
function predix_module_init_menu_items()
{
    $CI = &get_instance();

    if (has_permission('predix', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('predix', [
            'slug' => 'predix',
            'name' => _l('predix'),
            'position' => 6,
            'icon' => 'fa-solid fa-robot'
        ]);
    }

    if (has_permission('predix', '', 'view_chat')) {

        $CI->app_menu->add_sidebar_children_item('predix', [
            'slug' => 'predix-chat',
            'name' => _l('predix_chat'),
            'position' => 6,
            'icon' => 'fa-solid fa-comment',
            'href' => admin_url('predix/chat')
        ]);

    }

    if (has_permission('predix', '', 'view_image_gen')) {

        $CI->app_menu->add_sidebar_children_item('predix', [
            'slug' => 'predix-image-generator',
            'name' => _l('predix_image_gen'),
            'position' => 6,
            'icon' => 'fa-solid fa-image',
            'href' => admin_url('predix/image_generator')
        ]);

    }

    if (has_permission('predix', '', 'view_transcription')) {

        $CI->app_menu->add_sidebar_children_item('predix', [
            'slug' => 'predix-audio-transcription',
            'name' => _l('predix_transcription'),
            'position' => 6,
            'icon' => 'fa-solid fa-file-audio',
            'href' => admin_url('predix/audio_transcription')
        ]);

    }

    if (has_permission('predix', '', 'view_translation')) {

        $CI->app_menu->add_sidebar_children_item('predix', [
            'slug' => 'predix-audio-translation',
            'name' => _l('predix_translation'),
            'position' => 6,
            'icon' => 'fa-solid fa-language',
            'href' => admin_url('predix/audio_translation')
        ]);

    }

    $CI->app_menu->add_sidebar_children_item('predix', [
        'slug' => 'predix-documents',
        'name' => _l('predix_documents'),
        'position' => 6,
        'icon' => 'fa-solid fa-file-text',
        'href' => admin_url('predix/template_documents')
    ]);

    if (has_permission('predix', '', 'view_templates')) {
        $CI->app_menu->add_sidebar_children_item('predix', [
            'slug' => 'predix-templates',
            'name' => _l('predix_templates'),
            'position' => 6,
            'icon' => 'fa-solid fas fa-folder',
            'href' => admin_url('predix/templates')
        ]);
    }

    if (has_permission('predix', '', 'view_template_categories')) {
        $CI->app_menu->add_sidebar_children_item('predix', [
            'slug' => 'predix-template-categories',
            'name' => _l('predix_template_categories'),
            'position' => 6,
            'icon' => 'fa-solid fas fa-tag',
            'href' => admin_url('predix/template_categories')
        ]);
    }

    if (is_admin()) {

        $CI->app_menu->add_sidebar_children_item('predix', [
            'slug' => 'predix-settings',
            'name' => _l('predix_settings'),
            'position' => 6,
            'icon' => 'fa-solid fa-cog',
            'href' => admin_url('predix/settings')
        ]);

    }

}

function predix_ticket_created_automated_reply($arg1 = '', $arg2 = '')
{
    if (get_option('predix_ai_autoreply_on_opening_ticket') == '1'):

        $CI = &get_instance();
        $CI->load->model('tickets_model');

        $ticket_info = $CI->tickets_model->get_ticket_by_id($arg1);
        $message = $ticket_info->message;

        if (!is_null($ticket_info->admin)) {

            $apiKey = get_option('predix_openai_secret_key');
            $prompt = "I work as a " . get_option('predix_ai_reply_agent_title') . " and my name is " . get_option('predix_ai_reply_agent_name') . " and client name is " . get_option('companyname') . "  and client company name is " . get_option('companyname') . ". Write ticket reply about '" . strip_tags($message) . " ' that the client wrote, please do not refer anywhere the client name or any details about client and also please reply on the same language that client wrote the ticket";

            $openAi = new OpenAi($apiKey);

            $result = $openAi->completion([
                'model' => get_option('predix_chat_model'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 1.0,
                'max_tokens' => (int)get_option('predix_text_limit'),
                'frequency_penalty' => 0,
                'presence_penalty' => 0
            ]);

            $result = json_decode($result);

            $text = '';
            foreach ($result->choices as $choice):
                $text .= $choice->message->content;
            endforeach;

            $data['message'] = nl2br($text);
            $data['contactid'] = get_client_user_id();
            $data['userid'] = get_client_user_id();
            $data['status'] = 1;

            $CI->tickets_model->add_reply($data, $arg1, get_option('predix_ai_reply_agent_staff_id'));

            $CI->db->update(db_prefix() . 'tickets', array('adminread' => 0), array('ticketid' => $arg1));
        }
    endif;
}
