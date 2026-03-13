<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: QR code Manager | Gerenciador de código QR
Description: Complemento de código QR para PerfexCRM
Version: 1.0.1
Requires at least: 2.9.3
Author: Techy4m | Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
*/

define('QRCODE_MODULE_NAME', 'qrcode');

register_activation_hook(QRCODE_MODULE_NAME, 'setup_qrcode_module');
register_language_files(QRCODE_MODULE_NAME, [QRCODE_MODULE_NAME]);

hooks()->add_action('admin_init', 'qrcode_init_settings');
hooks()->add_filter('module_qrcode_action_links', 'qrcode_module_action_links');
hooks()->add_action('pdf_close', 'qrcode_pdf_footer');

function qrcode_init_settings()
{
    $CI = &get_instance();
    $CI->app_tabs->add_settings_tab('qrcode', [
        'name' => _l('qrcode'),
        'view' => QRCODE_MODULE_NAME . '/settings',
        'position' => 66,
    ]);
}

/**
 * adds help module button on modules page
 * @param  array  $actions  current actions
 * @return array
 */
function qrcode_module_action_links(array $actions): array
{
    $actions[] = "<a href='" . admin_url("settings?group=qrcode") . "'>" . _l("settings") . "</a>";
    $actions[] = "<a href='https://www.boxvibe.com/support?envato_item_id=' target='_blank'>" . _l('support') . "</a>";
    return $actions;
}

function qrcode_pdf_footer($data)
{
    $ci = &get_instance();
    $ci->load->library(QRCODE_MODULE_NAME . '/qrcode_module');
    $ci->qrcode_module->render($data['pdf_instance'], $data['type']);
}

function setup_qrcode_module()
{
    add_option('qr_code_width', '50');
    add_option('qr_code_height', '50');

    add_option('invoice_qr_code_x_position', '');
    add_option('invoice_qr_code_y_position', '');

    add_option('payment_qr_code_x_position', '');
    add_option('payment_qr_code_y_position', '');

    add_option('credit_note_qr_code_x_position', '');
    add_option('credit_note_qr_code_y_position', '');

    add_option('proposal_qr_code_x_position', '');
    add_option('proposal_qr_code_y_position', '');

    add_option('estimate_qr_code_x_position', '');
    add_option('estimate_qr_code_y_position', '');

    add_option('invoice_qr_code_x_position', '');
    add_option('invoice_qr_code_y_position', '');

    add_option('show_invoice_qr_code', '1');
    add_option('show_payment_qr_code', '0');
    add_option('show_credit_note_qr_code', '0');
    add_option('show_estimate_qr_code', '1');
    add_option('show_proposal_qr_code', '1');

    add_option('invoice_qr_code_info', '{invoice_link}');
    add_option('payment_qr_code_info', '{invoice_link}');
    add_option('credit_note_qr_code_info', '');
    add_option('estimate_qr_code_info', '{estimate_link}');
    add_option('proposal_qr_code_info', '{proposal_link}');
}
