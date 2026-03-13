<?php

/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: WhatsApp Notification para Whaticket
Description: Envio de mensagens de notificação pelo Whatsapp. 
Author: MNS Meu Negócio Social -> BY Lucas Vasconcelos
Author URI: https://crmperfex.com/perfex
Version: 1.1.1
Requires at least: 2.9.4
*/



define('whatsapiv2_MODULE_NAME', 'whatsapiv2');
define('SMS_TRIGGER2_INVOICE_SEND_TO_CUSTOMER', 'invoice_send_to_customer2');

hooks()->add_filter('sms_gateways', 'whatsapiv2_sms_gateways');
hooks()->add_filter('sms_triggers', 'whatsapiv2_triggers');
hooks()->add_filter('sms_gateway_available_triggers', 'whatsapiv2_triggers');
hooks()->add_action('invoice_sent', 'invoice_to_customer2');

function whatsapiv2_sms_gateways($gateways)
{
    $gateways[] = 'whatsapiv2/sms_whatsapiv2';
    return $gateways;
}

function whatsapiv2_triggers($triggers)
{
    $invoice_fields = [
        '{contact_firstname}',
        '{contact_lastname}',
        '{client_company}',
        '{client_vat_number}',
        '{client_id}',
        '{invoice_link}',
        '{invoice_number}',
        '{invoice_duedate}',
        '{invoice_date}',
        '{invoice_status}',
        '{invoice_subtotal}',
        '{invoice_total}',
    ];

    $triggers[SMS_TRIGGER2_INVOICE_SEND_TO_CUSTOMER] = [
        'merge_fields' => $invoice_fields,
        'label' => 'Send Invoice to customer',
        'info' => 'Trigger when invoice is created/sent to customer contacts.',
    ];
    return $triggers;
}

function invoice_to_customer2($id)
{
    $CI = &get_instance();
    $CI->load->helper('sms_helper');

    $invoice = $CI->invoices_model->get($id);
    $where = ['active' => 1, 'invoice_emails' => 1];
    $contacts = $CI->clients_model->get_contacts($invoice->clientid, $where);

    foreach ($contacts as $contact) {
        $template = mail_template('invoice_overdue_notice', $invoice, $contact);
        $merge_fields = $template->get_merge_fields();
        if (is_sms_trigger_active(SMS_TRIGGER2_INVOICE_SEND_TO_CUSTOMER)) {
            $CI->app_sms->trigger(SMS_TRIGGER2_INVOICE_SEND_TO_CUSTOMER, $contact['phonenumber'], $merge_fields);
        }
    }
}

