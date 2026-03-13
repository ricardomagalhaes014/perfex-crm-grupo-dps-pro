<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Asaas extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('asaas/asaas_gateway');

    }


    public function index()
    {

        $this->db->where('active', 1);
        $clients = $this->db->get(db_prefix() . 'clients')->result();

        $i = 1;

        foreach ($clients as $client) {

            $get_customer = $this->asaas_gateway->get_customer($client->vat);
            echo $i;
            echo "<hr>";
            var_dump($client->userid);
            echo "<hr>";
            var_dump($client->company);
            echo "<hr>";
            var_dump($client->vat);
            echo "<hr>";
            var_dump(str_replace('/', '', str_replace('-', '', str_replace('.', '', $client->vat))));

            echo "<hr>";
            var_dump($get_customer);
            echo "<hr>";

            $i++;
        }

    }
	
	   public function get_invoice_data($invoice_hash)
    {
        $this->db->where('hash', $invoice_hash);
        $invoice = $this->db->get('tblinvoices')->row();

        if ($invoice->status == 2) {
            echo 1;
        } else {
            echo 0;
        }
    }


    public function charges()
    {
        $sandbox = $this->asaas_gateway->getSetting('sandbox');
        if ($sandbox == '0') {
            $api_key = $this->asaas_gateway->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->asaas_gateway->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }


        $response = $this->asaas_gateway->charges($api_key, $api_url);


        //echo "<pre>";

        //  var_dump($response);

        //echo "</pre>";


        $response = json_decode($response, TRUE);

        natsort($response["data"]);

        $data = [
            "response" => $response ? $response["data"] : NULL,
        ];

        $this->load->view('asaas/charges', $data);


        ?>
        <?php
    }

    public function customers()
    {
        $sandbox = $this->asaas_gateway->getSetting('sandbox');
        if ($sandbox == '0') {
            $api_key = $this->asaas_gateway->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->asaas_gateway->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }


        $response = $this->asaas_gateway->get_customers($api_key, $api_url);

        //		echo "<pre>";

//	  var_dump($get_customers);

//	echo "</pre>";

//	 $response = json_decode($response, TRUE);

        natsort($response["data"]);


        $data = [
            "response" => $response ? $response["data"] : NULL,
        ];


        $this->load->view('asaas/customers', $data);

    }

    public function merge()
    {
        $sandbox = $this->asaas_gateway->getSetting('sandbox');
        if ($sandbox == '0') {
            $api_key = $this->asaas_gateway->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->asaas_gateway->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }

        $charges = $this->asaas_gateway->charges($api_key, $api_url);


        echo "<pre>";

        //  var_dump($response);

        echo "</pre>";


        $charges = json_decode($charges, TRUE);

        natsort($charges["data"]);


        $response = $this->asaas_gateway->get_customers($api_key, $api_url);


        natsort($response["data"]);

        $i = 0;
        $new_array = [];

        foreach ($charges["data"] as $row) {
            foreach ($response["data"] as $customer) {
                if ($row["customer"] = $customer["id"]) {
                    $new_array[$i]["name"] = $customer["name"];
                    $new_array[$i]["cpfCnpj"] = $customer["cpfCnpj"];
                    $new_array[$i]["id"] = $row["id"];
                    $new_array[$i]["dateCreated"] = $row["dateCreated"];
                    $new_array[$i]["customer"] = $row["customer"];
                    $new_array[$i]["value"] = $row["value"];
                    $new_array[$i]["description"] = $row["description"];
                    $new_array[$i]["billingType"] = $row["billingType"];
                    $new_array[$i]["status"] = $row["status"];
                    $new_array[$i]["dueDate"] = $row["dueDate"];
                    $new_array[$i]["paymentDate"] = $row["paymentDate"];
                    $new_array[$i]["installmentNumber"] = $row["installmentNumber"];
                    $new_array[$i]["invoiceUrl"] = $row["invoiceUrl"];
                    $new_array[$i]["invoiceNumber"] = $row["invoiceNumber"];
                    $new_array[$i]["externalReference"] = $row["externalReference"];
                }
                $i++;
            }


            echo "<pre>";

            var_dump($new_array);

            echo "</pre>";

            die();

        }
        $data = [
            "new_array" => $new_array ? $new_array : NULL,
        ];


        $this->load->view('asaas/customers', $data);


    }

    public function services()
    {
        $sandbox = $this->asaas_gateway->getSetting('sandbox');
        if ($sandbox == '0') {
            $api_key = $this->asaas_gateway->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->asaas_gateway->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }

        $response = $this->invoice->services($api_key, $api_url);


    }

    public function setup_webhook()
    {
        $sandbox = $this->asaas_gateway->getSetting('sandbox');
        if ($sandbox == '0') {
            $api_key = $this->asaas_gateway->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->asaas_gateway->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }

        $email = "lucas@meunegociosocial.com";

        $webhook = $this->asaas_gateway->get_webhook($api_key, $api_url);
        echo "<pre>";
        var_dump($webhook);
        echo "</pre>";
        echo "<hr>";
        $set_webhook = $this->set_webhook($api_key, $api_url, $email);
        echo "<pre>";
        var_dump($set_webhook);

        echo "</pre>";
        echo "<hr>";

        $set_webhook_invoice = $this->set_webhook_invoice($api_key, $api_url, $email);
        echo "<pre>";
        var_dump($set_webhook_invoice);
        echo "</pre>";
        echo "<hr>";
        //  $set_webhook_transfer = $this->set_webhook_transfer($api_key, $api_url, $email);
        //   var_dump($set_webhook_transfer);
    }

    public function set_webhook($api_key, $api_url, $email)
    {
        $webhook = $this->asaas_gateway->get_webhook($api_key, $api_url);
        $webhook = json_decode($webhook, TRUE);
        if ($webhook["url"] !== site_url('asaas/gateways/callback/index')) {
            $post_data = json_encode([
                "url" => site_url('asaas/gateways/callback/index'),
                "email" => $email,
                "interrupted" => false,
                "enabled" => true,
                "apiVersion" => 3
            ]);
            $create_webhook = $this->asaas_gateway->create_webhook($api_key, $api_url, $post_data);
            return $create_webhook;
        }
    }

    public function set_webhook_invoice($api_key, $api_url, $email)
    {
        $webhook = $this->asaas_gateway->get_webhook_invoice($api_key, $api_url);
        if ($webhook["url"] !== site_url('asaas_invoice/gateways/callback/index')) {
            $post_data = json_encode([
                "url" => site_url('asaas_invoice/gateways/callback'),
                "email" => $email,
                "interrupted" => false,
                "enabled" => true,
                "apiVersion" => 3

            ]);
            $create_webhook = $this->asaas_gateway->create_webhook_invoice($api_key, $api_url, $post_data);
            return $create_webhook;
        }
    }

    public function set_webhook_transfer($api_key, $api_url, $email)
    {
        $webhook = $this->asaas_gateway->get_webhook_transfer($api_key, $api_url);
        $webhook = json_decode($webhook, TRUE);
        if ($webhook["url"] !== site_url('asaas/gateways/callback/invoices')) {
            $post_data = json_encode([
                "url" => site_url('asaas/gateways/callback'),
                "email" => $email,
                "interrupted" => false,
                "enabled" => true,
                "apiVersion" => 3

            ]);
            $create_webhook = $this->asaas_gateway->create_webhook_transfer($api_key, $api_url, $post_data);
            return $create_webhook;
        }
    }
}

   
   
  