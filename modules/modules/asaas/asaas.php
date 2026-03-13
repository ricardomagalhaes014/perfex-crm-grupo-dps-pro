<?php

/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Asaas - Módulo de Pagamento
Description: Integração com Sistema Financeiro Asaas com a função de recebimento via catão de crédito, Boleto e Pix.
Author: MNS -> Lucas Vasconcelos
Version: 1.2.8
Requires at least: 2.8.*
Author URI: http://www.crmperfex.com
*/


$module = "asaas";


register_activation_hook($module, 'asaas_activation_hook');

function asaas_activation_hook()
{

}


register_deactivation_hook($module, 'asaas_deactivation_hook');

function asaas_deactivation_hook()
{

}

register_uninstall_hook($module, 'asaas_uninstall_hook');

function asaas_uninstall_hook()
{

}

hooks()->add_action('before_render_payment_gateway_settings', 'asaas_before_render_payment_gateway_settings');


function asaas_before_render_payment_gateway_settings($gateway)
{
    $CI = &get_instance();

//	var_dump($gateway);


    if ($gateway["id"] == 'asaas') {

        //	var_dump($settings = $gateway['instance']->getSettings());
    }

    return $gateway;

}

hooks()->add_action('app_admin_footer', 'asaas_settings_tab_footer');

function asaas_settings_tab_footer()
{
    $CI = &get_instance();

    ?>
    <script>
        $(document).ready(function () {

      //      console.log(asaas);

            $(".form-control datepicker").attr("required", "true");

            function validate_invoice_form(e) {
                e = void 0 === e ? "#invoice-form" : e,
                    appValidateForm($(e), {
                        clientid: {
                            required: {
                                depends: function () {
                                    return !$("select#clientid").hasClass("customer-removed")
                                }
                            }
                        },
                        date: "required",
                        currency: "required",
                        repeat_every_custom: {
                            min: 1
                        },
                        number: {
                            required: !0
                        }
                    }), $("body").find('input[name="number"]').rules("add", {
                    remote: {
                        url: admin_url + "invoices/validate_invoice_number",
                        type: "post",
                        data: {
                            number: function () {
                                return $('input[name="number"]').val()
                            },
                            isedit: function () {
                                return $('input[name="number"]').data("isedit")
                            },
                            original_number: function () {
                                return $('input[name="number"]').data("original-number")
                            },
                            date: function () {
                                return $('input[name="date"]').val()
                            }
                        }
                    },
                    messages: {
                        remote: app.lang.invoice_number_exists
                    }
                })
            }
        });


        $("#online_payments_asaas_tab > div:nth-child(13) > div:nth-child(2) > label").html("Valor fixo");		
		
        $("#online_payments_asaas_tab > div:nth-child(13) > div:nth-child(3) > label").html("Porcentagem");


        $("#y_opt_1_Tipo\\ de\\ desconto").change(function () {

            // console.log( asaas);
            $("#online_payments_asaas_tab > div:nth-child(13) > label").empty();
            $("#online_payments_asaas_tab > div:nth-child(13) > label").html("Valor desconto ");

        });

        $("#y_opt_2_Tipo\\ de\\ desconto").change(function () {

            // console.log( asaas);
            $("#online_payments_asaas_tab > div:nth-child(13) > label").empty();
            $("#online_payments_asaas_tab > div:nth-child(13) > label").html("Valor desconto (Informar porcentagem)");

        });
    </script>
    <?php
}

register_payment_gateway('asaas_gateway', 'asaas');

//hooks()->add_action('admin_init', 'asaas_register_menu_items');

function asaas_register_menu_items()
{
    $CI = &get_instance();


    $CI->app_menu->add_sidebar_menu_item('asaas', [
        'name' => 'Asaas PIX',
        'href' => admin_url('asaas'),
        'position' => 10,
        'icon' => 'fa fa-money',
    ]);


}

/**/

hooks()->add_action('after_invoice_added', 'asaas_after_invoice_added');

function asaas_after_invoice_added($insert_id)
{
    $CI = &get_instance();
    $CI->load->library('asaas_gateway');
    $CI->load->model('invoices_model');
    $invoice = $CI->invoices_model->get($insert_id);

    if ($invoice) {
        if ($invoice->duedate) {
            $allowed_payment_modes = unserialize($invoice->allowed_payment_modes);

            $data['invoice'] = $invoice;

            if (in_array('asaas', $allowed_payment_modes)) {
                $billet = $CI->asaas_gateway->charge_billet($data);
            }
        }

    }
    return $insert_id;
}


hooks()->add_action('after_invoice_updated', 'asaas_after_invoice_updated');

function asaas_after_invoice_updated($id)
{
    $CI = &get_instance();

    $CI->load->library('asaas_gateway');
    $CI->load->model('invoices_model');
    $sandbox = $CI->asaas_gateway->getSetting('sandbox');
        $debug = $CI->asaas_gateway->getSetting('debug');
        if ($sandbox == '0') {
            $api_key = $CI->asaas_gateway->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $CI->asaas_gateway->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }
    $description = $CI->asaas_gateway->getSetting('description');
    $interest = $CI->asaas_gateway->getSetting('interest_value');
    $fine = $CI->asaas_gateway->getSetting('fine_value');
    $discount_value = $CI->asaas_gateway->getSetting('discount_value');
    $dueDateLimitDays = $CI->asaas_gateway->getSetting('discount_days');
    $billet_only = $CI->asaas_gateway->getSetting('billet_only');
    $billet_check = $CI->asaas_gateway->getSetting('billet_check');
    $discount_type = $CI->asaas_gateway->getSetting('discount_type');
	
	  $update_charge = $CI->asaas_gateway->getSetting('update_charge');
	  
	      $disable_charge_notification = $CI->asaas_gateway->getSetting('disable_charge_notification');

        if ($disable_charge_notification == '1') {
            $notificationDisabled = true;
        } else {
            $notificationDisabled = false;
        }
	
	
	 if ($update_charge == 1) {
		 
		     $invoice = $CI->invoices_model->get($id);

    if ($invoice) {

    $discount = NULL;
	
	$sem_desconto = strpos($invoice->adminnote, "{sem_desconto}", 0);
	
	
    if ($discount_type == 1) {

        $discount = [
            'type' => 'FIXED',
            "value" => $discount_value,
            "dueDateLimitDays" => $dueDateLimitDays,
        ];
    }

    if ($discount_type == 2) {

        $discount = [
            'type' => 'PERCENTAGE',
            "value" => $discount_value,
            "dueDateLimitDays" => $dueDateLimitDays,
        ];
    }


		 if(is_bool($sem_desconto)) {
				$discount =  [
		    	'type' => 'PERCENTAGE',
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ]; 
		 }

        $invoice_number = $invoice->prefix . str_pad($invoice->number, 6, "0", STR_PAD_LEFT);
        $description = utf8_encode(str_replace("{invoice_number}", $invoice_number, $description));

        if ($invoice->duedate) {

            $clientid = $invoice->clientid;
            $CI->db->where('userid', $invoice->clientid);
            $client = $CI->db->get(db_prefix() . "clients")->row();

            $document = str_replace('/', '', str_replace('-', '', str_replace('.', '', $client->vat)));
			
         //   $customer = $CI->asaas_gateway->get_customer($document);
			
		   $customer = $CI->asaas_gateway->search_customer($api_url, $api_key, $document);
		   
        if ($customer['totalCount'] == "0") {
            $post_data = json_encode([
                "name" => $client->company,
                "email" => $email,
                "cpfCnpj" => $document,
                "company" => $client->company,
                "postalCode" => $postalCode,
                "address" => $client->address,
                "addressNumber" => $address_number,
                "complement" => "",
                "city"=> $client->city,
                "state"=> $client->state,
                "phone" => $client->phonenumber,
                "mobilePhone" => $client->phonenumber,
                "externalReference" => $client->userid,
                "notificationDisabled" => $notificationDisabled,
            ]);
            $cliente_create = $CI->asaas_gateway->create_customer($api_url, $api_key, $post_data);
            $cliente_id = $cliente_create['id'];
            log_activity('Cliente cadastrado no Asaas [Cliente ID: ' . $cliente_id . ']');
  

        } else {
            // se existir recupera os dados para cobranca
            $cliente_id = $customer['data'][0]['id'];
			
			
		
		}
        
            $charges = $CI->asaas_gateway->get_charge2($invoice->hash);
			
			
			   $post_data = json_encode([           
                        "customer" => $cliente_id,
						 "billingType" => $charge->billingType,
                        "dueDate" => $invoice->duedate,
                        "value" => $invoice->total,
                        "description" => $description,
                     /*
					    "discount" => $discount,
                        "fine" => [
                            "value" => $fine,
                        ],
                        "interest" => [
                            "value" => $interest,
                        ],
						*/
                        "postalService" => false
                    ]);

            if ($charges) {
                foreach ($charges as $charge) {
                 
                   if (in_array('asaas', $allowed_payment_modes)) {
                    $update_charge = $CI->asaas_gateway->update_charge($charge->id, $post_data);
				   }
                }
            }			
			else {
				
				
            $allowed_payment_modes = unserialize($invoice->allowed_payment_modes);

            $data['invoice'] = $invoice;
            if (in_array('asaas', $allowed_payment_modes)) {
                // $billet = $CI->asaas_gateway->charge_billet($data);		
				
				 $charge = $this->create_charge($api_url, $api_key, $post_data);
											
            }
			
			
        }
        }
    }	 
	
}
	 return $id;
}

hooks()->add_action('before_invoice_deleted', 'asaas_before_invoice_deleted');

function asaas_before_invoice_deleted($id)
{
    $CI = &get_instance();
    $CI->load->library('asaas_gateway');
    $CI->load->model('invoices_model');
	
	$delete_charge = $CI->asaas_gateway->getSetting('delete_charge');
	
	 $debug = $CI->asaas_gateway->getSetting('debug');
	 
    if ($delete_charge == 1) {
	
    $invoice = $CI->invoices_model->get($id);

    $charges = $CI->asaas_gateway->get_charge2($invoice->hash);

if($charges) {
    foreach ($charges as $charge) {
     
        $response = $CI->asaas_gateway->delete_charge($charge->id);
   
    log_activity('Cobrança removida Asaas [Fatura ID: ' . $charge->id . ']');
    }

}
	}
	 return $id;
}

