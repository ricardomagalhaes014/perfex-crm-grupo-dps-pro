<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Asaas_gateway extends App_gateway
{
    public function __construct()
    {
        parent::__construct();
        //	$this->load->helper('asaas');
        $this->setId('asaas');
        $this->setName('Asaas');
        $this->setSettings(array(
            array(
                'name' => 'api_key',
                'encrypted' => true,
                'label' => 'Api key Produção',
                'type' => 'input',
            ),
            array(
                'name' => 'api_key_sandbox',
                'encrypted' => true,
                'label' => 'Api key Sandbox',
                'type' => 'input',
            ),
            array(
                'name' => 'sandbox',
                'label' => 'Sandbox',
                'type' => 'yes_no',
                'default_value' => 1,
            ),
            array(
                'name' => 'debug',
                'label' => 'debug',
                'type' => 'yes_no',
                'default_value' => 0,
            ),
            array(
                'name' => 'currencies',
                'label' => 'settings_paymentmethod_currencies',
                'default_value' => 'BRL'
            ),
            array(
                'name' => 'description',
                'label' => 'settings_paymentmethod_description',
                'type' => 'textarea',
                'default_value' => 'Pagamento da Fatura {invoice_number}',
            ),
            array(
                'name' => 'interest_value',
                'label' => 'Valor juros',
                'type' => 'input',
                'default_value' => '0.00',
            ),
            array(
                'name' => 'fine_value',
                'label' => 'Valor multa',
                'type' => 'input',
                'default_value' => '0.00',
            ),
            array(
                'name' => 'discount_type',
                'label' => 'Tipo de desconto',
                'type' => 'yes_no',
                'default_value' => 1,
                //'field_attributes' => ['id' => 'discount_type_row'],
                //  'after'            => '<p class="mbot15">Statement descriptors are limited to 22 characters, cannot use the special characters <, >, \', ", or *, and must not consist solely of numbers.</p>',
            ),
            array(
                'name' => 'discount_value',
                'label' => 'Valor desconto',
                'type' => 'input',
                'default_value' => '0',
            ),
            array(
                'name' => 'discount_days',
                'label' => 'Dias para desconto',
                'type' => 'input',
                'default_value' => 0,
            ),
            array(
                'name' => 'installmentCount',
                'label' => 'Limite de parcelas',
                'type' => 'input',
                'default_value' => 1,
            ),
            array(
                'name' => 'billet_only',
                'label' => 'Habilitar boleto',
                'type' => 'yes_no',
                'default_value' => 1,
            ),
            array(
                'name' => 'card_only',
                'label' => 'Habilitar cartão de crédito',
                'type' => 'yes_no',
                'default_value' => 1,
            ),
            array(
                'name' => 'pix_only',
                'label' => 'Habilitar PIX',
                'type' => 'yes_no',
                'default_value' => 1,
            ),
            array(
                'name' => 'delete_charge',
                'label' => 'Deletar cobrança da fatura no Asaas',
                'type' => 'yes_no',
                'default_value' => 0,
            ),

            array(
                'name' => 'update_charge',
                'label' => 'Atualizar cobrança da fatura no Asaas',
                'type' => 'yes_no',
                'default_value' => 0,
            ),

            array(
                'name' => 'disable_charge_notification',
                'label' => 'Desativar notificações de cobrançaa',
                'type' => 'yes_no',
                'default_value' => 1,
            ),

        ));
    }

    public function process_payment($data)
    {
        if (empty($data)) {
            return;
        }
        $invoice = $data['invoice']->id;
        $ci = &get_instance();
        $ci->db->where('id', $invoice);
        $row = $ci->db->get(db_prefix() . 'invoices')->row_array();

        $sandbox = $this->getSetting('sandbox');
        $debug = $this->getSetting('debug');
        if ($sandbox == '0') {
            $api_key = $this->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }

        $billet_only = $this->getSetting('billet_only');
        $card_only = $this->getSetting('card_only');
        $pix_only = $this->getSetting('pix_only');
        $interest = $this->getSetting('interest_value');
        $fine = $this->getSetting('fine_value');
        $discount_value = $this->getSetting('discount_value');
        $dueDateLimitDays = $this->getSetting('discount_days');
        $discount_type = $this->getSetting('discount_type');
        $description = $this->getSetting('description');

        $search_charge = $this->search_charge($api_url, $api_key, $row["hash"]);

        if ($row['status'] == '4') {
            $row['total'] = $this->calculate_invoice($invoice, $row, $fine, $interest);

            if ($debug == '1') {
                echo $row['total'];
				 echo "<br>";
				 echo $ci->db->last_query();
				  echo "<br>";
				    echo "<pre>";
                var_dump($search_charge);
                echo "</pre>";
            }
        }
		
		  $ci->db->where('id', $invoice);
        $row = $ci->db->get(db_prefix() . 'invoices')->row_array();

        $disable_charge_notification = $this->getSetting('disable_charge_notification');

        if ($disable_charge_notification == '1') {
            $notificationDisabled = true;
        } else {
            $notificationDisabled = false;
        }

        $invoice_number = $row['prefix'] . str_pad($row['number'], 6, "0", STR_PAD_LEFT);
        $description = utf8_encode(str_replace("{invoice_number}", $invoice_number, $description));
        $email = $this->get_customer_customfields($clientid, 'customers', 'customers_email_do_cliente');
        $document = str_replace('/', '', str_replace('-', '', str_replace('.', '', $client['vat'])));
        $postalCode = str_replace('-', '', str_replace('.', '', $client['zip']));
        $address_number = $this->get_customer_customfields($clientid, 'customers', 'customers_numero_do_endere_o');
        $customer = $this->search_customer($api_url, $api_key, $document);
        if ($customer['totalCount'] == "0") {
            $post_data = json_encode([
                "name" => $client['company'],
                "email" => $email,
                "cpfCnpj" => $document,
                 "company" =>  $client['company'],
                "postalCode" => $postalCode,
                "address" => $client['address'],
                "addressNumber" => $address_number,
                "complement" => "",
                "phone" => $client['phonenumber'],
                "mobilePhone" => $client['phonenumber'],
                "externalReference" => $invoice,
                "notificationDisabled" => $notificationDisabled,
            ]);

            $cliente_create = $this->create_customer($api_url, $api_key, $post_data);
            $cliente_id = $cliente_create['id'];

            log_activity('Cliente cadastrado no Asaas [Cliente ID: ' . $cliente_id . ']');

            if ($debug == '1') {
                echo "Campos cadastro";
                echo "<br>";
                echo "<pre>";
                var_dump($post_data);
                echo "</pre>";
                echo "Cliente cadastrado no Asaas ID" . $cliente_id;
                echo "<hr>";
            }
        } else {
            // se existir recupera os dados para cobranca
            $cliente_id = $customer['data'][0]['id'];
            if ($debug == '1') {
                echo "Cliente já existente ID " . $cliente_id;
                echo "<hr>";

            }

        }

    
        $discount = NULL;
		
		     $sem_desconto = strpos($row['adminnote'], "{sem_desconto}", 0);

  if ($discount_type == 1) {

            $type = 'FIXED';

            $discount = [
                'type' => 'FIXED',
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];

        }

        if ($discount_type == 0) {

            $type = 'PERCENTAGE';

            $discount = [
                'type' => 'PERCENTAGE',
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];

        }

        if (is_bool($sem_desconto)) {
            $discount = [
                'type' => $type,
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];
        }

        if ($debug == '1') {
            echo "Tipo desconto config " . $discount_type;
            echo "<br>";
			  echo "Tipo desconto " . $type;
            echo "<br>";
            echo "Campos desconto";
            echo "<br>";
            echo "<pre>";
            var_dump($discount);
            echo "</pre>";
            echo "<hr>";
            echo "Sem desconto " .   var_dump($sem_desconto);
            echo "<br>";
    //      die();
        }

        $post_data = [
            "customer" => $cliente_id,
            "billingType" => "BOLETO",
            "dueDate" => $row['duedate'],
            "value" => $row['total'],
            "description" => $description,
            "externalReference" => $row['hash'],
            "discount" => $discount,
            "fine" => [
                "value" => $fine,
            ],
            "interest" => [
                "value" => $interest,
            ],
            "postalService" => false
        ];
		
		 if ($search_charge) {
	        unset($post_data["discount"]);
			 unset($post_data["fine"]);
			  unset($post_data["interest"]);
   }
		
		  $post_data = json_encode($post_data);

        if ($debug == '1') {

            echo "Campos cobranca asaas";
            echo "<br>";
            echo "<pre>";
            var_dump($post_data);
            echo "</pre>";
            echo "<hr>";
			
		//	die();
        }

        // n�o tem cobran�a no asaas
        if (!$search_charge) {
            $charge = $this->create_charge($api_url, $api_key, $post_data);

            log_activity('Cobrança Boleto/Pix Asaas [Fatura ID: ' . $invoice . ']');

        } else {
            $charge = $this->update_charge($search_charge->id, $post_data);

            log_activity('Cobrançaa atualizada Asaas [Fatura ID: ' . $invoice . ']');
        }


        //	echo "<hr>";

       //		die();

        if ($billet_only == 1 && $card_only == 0 && $pix_only == 0) {

            redirect(site_url('asaas/checkout/boleto/' . $row['hash']));

        }

        if ($billet_only == 0 && $card_only == 1 && $pix_only == 0) {

            redirect(site_url('asaas/checkout/cartao/' . $row['hash']));

        }

        if ($billet_only == 0 && $card_only == 0 && $pix_only == 1) {

            redirect(site_url('asaas/checkout/qrcode/' . $row['hash']));

        }
        redirect(site_url('asaas/checkout/index/' . $row['hash']));
    }


    public function calculate_invoice($invoice, $row, $fine, $interest)
    {
        $ci = &get_instance();

        $now = time();
        $duedate = strtotime($row["duedate"]);
        $datediff = $now - $duedate;

        $row["subtotal"] = $row["subtotal"] + $row["adjustment"];

        $row["subtotal"] = get_invoice_total_left_to_pay($row["id"], $row["subtotal"]);

        $overdue_days = round($datediff / (60 * 60 * 24));

        $overdue_days_interest = $interest * (int)$overdue_days;

        $overdue_interest = $row["subtotal"] * $overdue_days_interest;

        $overdue_fine = $row["subtotal"] * $fine;

        $updated_total_overdue = number_format($overdue_interest, 2) + $overdue_fine;
        $updated_total = $row["subtotal"] + number_format($updated_total_overdue / 100, 2);

        $adjustment = $row["adjustment"] + number_format($updated_total_overdue / 100, 2);

        $update_data = [
            'status' => 1,
            'adjustment' => $adjustment,
            'subtotal' => $updated_total,
            'total' => $updated_total,
            'duedate' => date('Y-m-d', strtotime("+03 day"))
        ];

        $ci->db->where('id', $invoice);
        $ci->db->update(db_prefix() . 'invoices', $update_data);


        return $updated_total;


    }

    public function get_charge($hash)
    {
        $sandbox = $this->getSetting('sandbox');
        $debug = $this->getSetting('debug');
        if ($sandbox == '0') {
            $api_key = $this->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }
        $charge = $this->search_charge($api_url, $api_key, $hash);
        return $charge;
    }

    public function get_charge2($hash)
    {
        $sandbox = $this->getSetting('sandbox');
        $debug = $this->getSetting('debug');
        if ($sandbox == '0') {
            $api_key = $this->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }
        $charge = $this->search_charge2($api_url, $api_key, $hash);
        return $charge;
    }

    public function search_charge($api_url, $api_key, $hash)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url . "/api/v3/payments",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "access_token: " . $api_key,
            ),
        ));
        $response = curl_exec($curl);
           if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
			return false;
        }
        curl_close($curl);
        $payments = json_decode($response);
        $charges = $payments->data;

        //	 return $charges;

        if ($charges) {
            foreach ($charges as $charge) {


                if ($charge->externalReference == $hash) {
                    return $charge;
                }
            }
        }
    }

    public function search_charge2($api_url, $api_key, $hash)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url . "/api/v3/payments",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "access_token: " . $api_key,
            ),
        ));
        $response = curl_exec($curl);
          if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
			return false;
        }
        curl_close($curl);
        $payments = json_decode($response);
        $charges = $payments->data;

        $response = [];

        if ($charges) {
            foreach ($charges as $charge) {
                if ($charge->externalReference == $hash) {
                    $response[] = $charge;
                }
            }
        }
        return $response;
    }

    public function charge_billet($data)
    {
        if (empty($data)) {
            return;
        }
        $invoice = $data['invoice']->id;
        $ci = &get_instance();
        $ci->db->where('id', $invoice);
        $row = $ci->db->get(db_prefix() . "invoices")->row_array();
        $clientid = $row['clientid'];
        $ci->db->where('userid', $clientid);
        $client = $ci->db->get(db_prefix() . "clients")->row_array();
        $sandbox = $this->getSetting('sandbox');
        $debug = $this->getSetting('debug');
        if ($sandbox == '0') {
            $api_key = $this->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }
        $description = $this->getSetting('description');
        $interest = $this->getSetting('interest_value');
        $fine = $this->getSetting('fine_value');
        $discount_value = $this->getSetting('discount_value');
        $dueDateLimitDays = $this->getSetting('discount_days');
        $billet_only = $this->getSetting('billet_only');

        $discount_type = $this->getSetting('discount_type');


        $disable_charge_notification = $this->getSetting('disable_charge_notification');

        if ($disable_charge_notification == '1') {
            $notificationDisabled = true;
        } else {
            $notificationDisabled = false;
        }


        $invoice_number = $row['prefix'] . str_pad($row['number'], 6, "0", STR_PAD_LEFT);
        $description = utf8_encode(str_replace("{invoice_number}", $invoice_number, $description));
        $email = $this->get_customer_customfields($clientid, 'customers', 'customers_email_do_cliente');
        $document = str_replace('/', '', str_replace('-', '', str_replace('.', '', $client['vat'])));
        $postalCode = str_replace('-', '', str_replace('.', '', $client['zip']));
        $address_number = $this->get_customer_customfields($clientid, 'customers', 'customers_numero_do_endere_o');
        $customer = $this->search_customer($api_url, $api_key, $document);
        if ($customer['totalCount'] == "0") {
            $post_data = json_encode([
            "name" => $client['company'],
                "email" => $email,
                "cpfCnpj" => $document,
                 "company" =>  $client['company'],
                "postalCode" => $postalCode,
                "address" => $client['address'],
                "addressNumber" => $address_number,
                "complement" => "",
                "phone" => $client['phonenumber'],
                "mobilePhone" => $client['phonenumber'],
                "externalReference" => $invoice,
                "notificationDisabled" => $notificationDisabled,
            ]);
            $cliente_create = $this->create_customer($api_url, $api_key, $post_data);
            $cliente_id = $cliente_create['id'];
            log_activity('Cliente cadastrado no Asaas [Cliente ID: ' . $cliente_id . ']');

            if ($debug == '1') {
                echo "Campos cadastro";
                echo "<br>";
                echo "<pre>";
                var_dump($post_data);
                echo "</pre>";
                echo "Cliente cadastrado no Asaas ID " . $cliente_id;
                echo "<hr>";
            }

        } else {
            // se existir recupera os dados para cobranca
            $cliente_id = $customer['data'][0]['id'];
            if ($debug == '1') {
                echo "Cliente já existente ID " . $cliente_id;
                echo "<hr>";

            }

        }

    
        $discount = NULL;

        $sem_desconto = strpos($row["adminnote"], "{sem_desconto}", 0);

        if ($discount_type == 1) {

            $type = 'FIXED';

            $discount = [
                'type' => 'FIXED',
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];

        }

        if ($discount_type == 0) {

            $type = 'PERCENTAGE';

            $discount = [
                'type' => 'PERCENTAGE',
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];

        }

        if (is_bool($sem_desconto)) {
            $discount = [
                'type' => $type,
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];
        }

        if ($debug == '1') {
            echo "Tipo desconto" . $discount_type;
            echo "<br>";
            echo "Campos desconto";
            echo "<br>";
            echo "<pre>";
            var_dump($discount);
            echo "</pre>";
            echo "<hr>";
            echo "Sem desconto" . $sem_desconto;
            echo "<br>";
            echo "<pre>";
            var_dump($discount);
            echo "</pre>";
            echo "<hr>";
        }

        $post_data = json_encode([
            "customer" => $cliente_id,
            "billingType" => "BOLETO",
            "dueDate" => $row['duedate'],
            "value" => $row['total'],
            "description" => $description,
            "externalReference" => $row['hash'],
            "discount" => $discount,
            "fine" => [
                "value" => $fine,
            ],
            "interest" => [
                "value" => $interest,
            ],
            "postalService" => false
        ]);


        $charge = $this->create_charge($api_url, $api_key, $post_data);
        $charge = json_decode($charge, TRUE);
		
		log_activity('Cobran�a Boleto Asaas [Fatura ID: ' . $invoice . ']');
  if ($debug == '1') {
           
            echo "Cobrança Boleto";
            echo "<br>";
            echo "<pre>";
            var_dump($charge);
            echo "</pre>";
            echo "<hr>";
  }
        

        return $charge;
    }

    public function charge_credit_card($data)
    {
        if (empty($data)) {
            return;
        }
        $invoice_id = $data['invoice']->id;
        $ci = &get_instance();
        $ci->db->where('id', $invoice_id);
        $invoice = $ci->db->get(db_prefix() . 'invoices')->row();
        $ci->db->where('userid', $invoice->clientid);
        $client = $ci->db->get(db_prefix() . 'clients')->row();

        $description = $this->getSetting('description');
        $interest = $this->getSetting('interest_value');
        $fine = $this->getSetting('fine_value');
        $discount_value = $this->getSetting('discount_value');
        $dueDateLimitDays = $this->getSetting('discount_days');
        $discount_type = $this->getSetting('discount_type');
        $sandbox = $this->getSetting('sandbox');
        $debug = $this->getSetting('debug');
        if ($sandbox == '0') {
            $api_key = $this->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }

        $disable_charge_notification = $this->getSetting('disable_charge_notification');

        if ($disable_charge_notification == '1') {
            $notificationDisabled = true;
        } else {
            $notificationDisabled = false;
        }

        $invoice_number = $invoice->prefix . str_pad($invoice->number, 6, "0", STR_PAD_LEFT);
        $description = utf8_encode(str_replace("{invoice_number}", $invoice_number, $description));
        $email = $this->get_customer_customfields($client->userid, 'customers', 'customers_email_do_cliente');
        $document = str_replace('/', '', str_replace('-', '', str_replace('.', '', $client->vat)));
        $postalCode = str_replace('-', '', str_replace('.', '', $client->zip));
        $address_number = $this->get_customer_customfields($client->userid, 'customers', 'customers_numero_do_endere_o');
        $customer = $this->search_customer($api_url, $api_key, $document);
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
                "externalReference" => $invoice,
                "notificationDisabled" => $notificationDisabled,
            ]);
            $cliente_create = $this->create_customer($api_url, $api_key, $post_data);
            $cliente_id = $cliente_create['id'];
            log_activity('Cliente cadastrado no Asaas [Cliente ID: ' . $cliente_id . ']');

            if ($debug == '1') {
                echo "Campos cadastro";
                echo "<br>";
                echo "<pre>";
                var_dump($post_data);
                echo "</pre>";
                echo "Cliente cadastrado no Asaas ID" . $cliente_id;
                echo "<hr>";
            }

        } else {
            // se existir recupera os dados para cobranca
            $cliente_id = $customer['data'][0]['id'];
            if ($debug == '1') {
                echo "Cliente já existente ID" . $cliente_id;
                echo "<hr>";

            }
        }

        if ($invoice->status == '4') {

            // calculate_invoice()

            $now = time();
            $duedate = strtotime($invoice->duedate);
            $datediff = $now - $duedate;

            $invoice->subtotal = $invoice->subtotal + $invoice->adjustment;

            $invoice->subtotal = get_invoice_total_left_to_pay($invoice->id, $invoice->subtotal);

            $overdue_days = round($datediff / (60 * 60 * 24));

            $overdue_days_interest = $interest * (int)$overdue_days;

            $overdue_interest = $invoice->subtotal * $overdue_days_interest;

            $overdue_fine = $invoice->subtotal * $fine;

            $updated_total_overdue = number_format($overdue_interest, 2) + $overdue_fine;
            $updated_total = $invoice->subtotal + number_format($updated_total_overdue / 100, 2);

            $adjustment = $invoice->adjustment + number_format($updated_total_overdue / 100, 2);

            $update_data = [
                'status' => 1,
                'adjustment' => $adjustment,
                'subtotal' => $updated_total,
                'total' => $updated_total,
                'duedate' => date('Y-m-d', strtotime("+03 day"))
            ];

            $ci->db->where('id', $invoice_id);
            $ci->db->update(db_prefix() . 'invoices', $update_data);

            $search_charge = $this->search_charge($api_url, $api_key, $invoice->hash);

            //

            $discount = NULL;

            $sem_desconto = strpos($invoice->adminnote, "{sem_desconto}", 0);

            if ($discount_type == 1) {

                $type = 'FIXED';

                $discount = [
                    'type' => 'FIXED',
                    "value" => $discount_value,
                    "dueDateLimitDays" => $dueDateLimitDays,
                ];

            }

            if ($discount_type == 0) {

                $type = 'PERCENTAGE';

                $discount = [
                    'type' => 'PERCENTAGE',
                    "value" => $discount_value,
                    "dueDateLimitDays" => $dueDateLimitDays,
                ];

            }

            if (is_bool($sem_desconto)) {
                $discount = [
                    'type' => $type,
                    "value" => $discount_value,
                    "dueDateLimitDays" => $dueDateLimitDays,
                ];
            }

            if ($debug == '1') {
                echo "Tipo desconto" . $discount_type;
                echo "<br>";
                echo "Campos desconto";
                echo "<br>";
                echo "<pre>";
                var_dump($discount);
                echo "</pre>";
                echo "<hr>";
                echo "Sem desconto" . $sem_desconto;
                echo "<br>";
                echo "<pre>";
                var_dump($discount);
                echo "</pre>";
                echo "<hr>";
            }

            $post_data = json_encode([
                "customer" => $search_charge->customer,
                "billingType" => $search_charge->billingType,
                "dueDate" => date('Y-m-d', strtotime("+03 day")),
                "value" => $updated_total,
                "description" => $search_charge->description,
                "externalReference" => $row['hash'],
                "discount" => $discount,
                "fine" => [
                    "value" => $fine,
                ],
                "interest" => [
                    "value" => $interest,
                ],
                "postalService" => false
            ]);

            $charge = $this->update_charge($search_charge->id, $post_data);

            log_activity('Cobrança atualizada Asaas [Fatura ID: ' . $search_charge->id . ']');

            return $charge;
        }


        $installmentValue = number_format($invoice->total / intval($data["card"]["installmentCount"]), 2);

        $discount = NULL;

        $sem_desconto = strpos($invoice->adminnote, "{sem_desconto}", 0);

        if ($discount_type == 1) {

            $type = 'FIXED';

            $discount = [
                'type' => 'FIXED',
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];

        }

        if ($discount_type == 0) {

            $type = 'PERCENTAGE';

            $discount = [
                'type' => 'PERCENTAGE',
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];

        }

        if (is_bool($sem_desconto)) {
            $discount = [
                'type' => $type,
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];
        }

        $invoice->total = get_invoice_total_left_to_pay($invoice->id, $invoice->subtotal);

        $post_data = json_encode([
            "customer" => $cliente_id,
            "billingType" => 'CREDIT_CARD',
            "dueDate" => $invoice->duedate,
            "value" => $invoice->total,
            "description" => $description,
            "externalReference" => $invoice->hash,
            "installmentCount" => $data["card"]["installmentCount"],
            "installmentValue" => $installmentValue,
            "creditCard" => [
                "holderName" => $data["card"]["holderName"],
                "number" => $data["card"]["number"],
                "expiryMonth" => $data["card"]["expiryMonth"],
                "expiryYear" => $data["card"]["expiryYear"],
                "ccv" => $data["card"]["cvv"]
            ],
            "creditCardHolderInfo" => [
                "name" => $client->company,
                "email" => $email,
                "cpfCnpj" => $document,
                "postalCode" => $postalCode,
                "addressNumber" => $address_number,
                "addressComplement" => "",
                "phone" => $client->phonenumber,
                "mobilePhone" => $client->phonenumber
            ],
            "discount" => $discount,
            "fine" => [
                "value" => $fine,
            ],
            "interest" => [
                "value" => $interest,
            ],
            "postalService" => false
        ]);
        $charge = $this->create_charge($api_url, $api_key, $post_data);


        log_activity('Cobrança cartão de credito Asaas [Fatura ID: ' . $invoice_id . ']');


        return $charge;
    }

    public function charge_pix($data)
    {
        if (empty($data)) {
            return;
        }
        $invoice = $data['invoice']->id;
        $ci = &get_instance();
        $ci->db->where('id', $invoice);
        $row = $ci->db->get(db_prefix() . "invoices")->row_array();
        $clientid = $row['clientid'];
        $ci->db->where('userid', $clientid);
        $client = $ci->db->get(db_prefix() . "clients")->row_array();
        $sandbox = $this->getSetting('sandbox');
        $debug = $this->getSetting('debug');
        if ($sandbox == '0') {
            $api_key = $this->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }
        $description = $this->getSetting('description');
        $interest = $this->getSetting('interest_value');
        $fine = $this->getSetting('fine_value');
        $discount_value = $this->getSetting('discount_value');
        $dueDateLimitDays = $this->getSetting('discount_days');
        $billet_only = $this->getSetting('billet_only');

        $discount_type = $this->getSetting('discount_type');

        $disable_charge_notification = $this->getSetting('disable_charge_notification');

        if ($disable_charge_notification == '1') {
            $notificationDisabled = false;
        } else {
            $notificationDisabled = true;
        }


        $invoice_number = $row['prefix'] . str_pad($row['number'], 6, "0", STR_PAD_LEFT);
        $description = utf8_encode(str_replace("{invoice_number}", $invoice_number, $description));
        // valida cliente
        $document = str_replace('/', '', str_replace('-', '', str_replace('.', '', $client['vat'])));
        $postalCode = str_replace('-', '', str_replace('.', '', $client->zip));
        $customer = $this->search_customer($api_url, $api_key, $document);
        if ($customer['totalCount'] == "0") {
            $address_number = $this->get_customer_customfields($clientid, 'customers', 'customers_numero_do_endere_o');
            $post_data = [
                 "name" => $client['company'],
                "email" => $email,
                "cpfCnpj" => $document,
                 "company" =>  $client['company'],
                "postalCode" => $postalCode,
                "address" => $client['address'],
                "addressNumber" => $address_number,
                "complement" => "",
                "phone" => $client['phonenumber'],
                "mobilePhone" => $client['phonenumber'],
                "externalReference" => $invoice,
                "notificationDisabled" => $notificationDisabled,
            ];
            $post_data = json_encode($post_data);
            $cliente_create = $this->create_customer($api_url, $api_key, $post_data);
            $cliente_id = $cliente_create['id'];
            log_activity('Cliente cadastrado no Asaas [Cliente ID: ' . $cliente_id . ']');

            if ($debug == '1') {
                echo "Campos cadastro";
                echo "<br>";
                echo "<pre>";
                var_dump($post_data);
                echo "</pre>";
                echo "Cliente cadastrado no Asaas ID" . $cliente_id;
                echo "<hr>";
            }
        } else {
            // se existir recupera os dados para cobranca
            $cliente_id = $customer['data'][0]['id'];
            if ($debug == '1') {
                echo "Cliente já existente ID" . $cliente_id;
                echo "<hr>";

            }
        }


        if ($row['status'] == '4') {

            // calculate_invoice()

            $now = time();
            $duedate = strtotime($row["duedate"]);
            $datediff = $now - $duedate;

            $row["subtotal"] = $row["subtotal"] + $row["adjustment"];

            $row["subtotal"] = get_invoice_total_left_to_pay($row["id"], $row["subtotal"]);

            $overdue_days = round($datediff / (60 * 60 * 24));

            $overdue_days_interest = $interest * (int)$overdue_days;

            $overdue_interest = $row["subtotal"] * $overdue_days_interest;

            $overdue_fine = $row["subtotal"] * $fine;

            $updated_total_overdue = number_format($overdue_interest, 2) + $overdue_fine;
            $updated_total = $row["subtotal"] + number_format($updated_total_overdue / 100, 2);
            $adjustment = $row["adjustment"] + number_format($updated_total_overdue / 100, 2);

            $update_data = [
                'status' => 1,
                'adjustment' => $adjustment,
                'subtotal' => $updated_total,
                'total' => $updated_total,
                'duedate' => date('Y-m-d', strtotime("+03 day"))
            ];

            $ci->db->where('id', $invoice);
            $ci->db->update(db_prefix() . 'invoices', $update_data);

            $search_charge = $this->search_charge($api_url, $api_key, $row["hash"]);

            //

            $discount = NULL;

            $sem_desconto = strpos($row["adminnote"], "{sem_desconto}", 0);

            if ($discount_type == 1) {

                $type = 'FIXED';

                $discount = [
                    'type' => 'FIXED',
                    "value" => $discount_value,
                    "dueDateLimitDays" => $dueDateLimitDays,
                ];

            }

            if ($discount_type == 0) {

                $type = 'PERCENTAGE';

                $discount = [
                    'type' => 'PERCENTAGE',
                    "value" => $discount_value,
                    "dueDateLimitDays" => $dueDateLimitDays,
                ];

            }

            if (is_bool($sem_desconto)) {
                $discount = [
                    'type' => $type,
                    "value" => $discount_value,
                    "dueDateLimitDays" => $dueDateLimitDays,
                ];
            }

            $post_data = json_encode([
                "customer" => $search_charge->customer,
                "billingType" => $search_charge->billingType,
                "dueDate" => date('Y-m-d', strtotime("+03 day")),
                "value" => $updated_total,
                "description" => $search_charge->description,
                "externalReference" => $row['hash'],
                "discount" => $discount,
                "fine" => [
                    "value" => $fine,
                ],
                "interest" => [
                    "value" => $interest,
                ],
                "postalService" => false
            ]);

            $charge = $this->update_charge($search_charge->id, $post_data);

            return $charge;
        }

        $discount = NULL;

        $sem_desconto = strpos($row["adminnote"], "{sem_desconto}", 0);

        if ($discount_type == 1) {

            $type = 'FIXED';

            $discount = [
                'type' => 'FIXED',
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];

        }

        if ($discount_type == 0) {

            $type = 'PERCENTAGE';

            $discount = [
                'type' => 'PERCENTAGE',
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];

        }

        if (is_bool($sem_desconto)) {
            $discount = [
                'type' => $type,
                "value" => $discount_value,
                "dueDateLimitDays" => $dueDateLimitDays,
            ];
        }

        $row["total"] = get_invoice_total_left_to_pay($row["id"], $row["subtotal"]);
        $post_data = json_encode([
            "customer" => $cliente_id,
            "billingType" => "PIX",
            "dueDate" => $row['duedate'],
            "value" => $row['total'],
            "description" => $description,
            "externalReference" => $row['hash'],
            "discount" => $discount,
            "fine" => [
                "value" => $fine,
            ],
            "interest" => [
                "value" => $interest,
            ],
            "postalService" => false
        ]); 

        $charge = $this->create_charge($api_url, $api_key, $post_data);
        $charge = json_decode($charge, TRUE);

        log_activity('Cobrançaa PIX Asaas [Fatura ID: ' . $invoice . ']');

        return $charge;
    }

    public function create_charge($api_url, $api_key, $post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url . "/api/v3/payments");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "access_token: " . $api_key,
        ));
        $response = curl_exec($ch);
           if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
			return false;
        }
        return $response;
    }

    public function create_qrcode($payment_id)
    {
        $sandbox = $this->getSetting('sandbox');
        $debug = $this->getSetting('debug');
        if ($sandbox == '0') {
            $api_key = $this->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url . "/api/v3/payments/" . $payment_id . "/pixQrCode");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "access_token: " . $api_key
        ));
        $response = curl_exec($ch);
         if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
			return false;
        }
        return $response;
    }

    public function get_customer($cpfCnpj)
    {
        $sandbox = $this->getSetting('sandbox');
        $debug = $this->getSetting('debug');
        if ($sandbox == '0') {
            $api_key = $this->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }
        $customer = $this->search_customer($api_url, $api_key, $cpfCnpj);
        return $customer;
    }

    public function search_customer($api_url, $api_key, $cpfCnpj)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url . "/api/v3/customers?cpfCnpj=" . $cpfCnpj,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "access_token: " . $api_key,
            ),
        ));
        $response = curl_exec($curl);
          if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
			return false;
        }
        curl_close($curl);
        $customer = json_decode($response, TRUE);
        return $customer;
    }

    public function update_charge($charge_id, $post_data)
    {
        $sandbox = $this->getSetting('sandbox');
        $debug = $this->getSetting('debug');
        if ($sandbox == '0') {
            $api_key = $this->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url . "/api/v3/payments/" . $charge_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",

            CURLOPT_POSTFIELDS => $post_data,

            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "access_token: " . $api_key,
            ),
        ));
        $response = curl_exec($curl);
          if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
			return false;
        }
        curl_close($curl);
        $response = json_decode($response, TRUE);
        return $response;
    }

    //https://www.asaas.com/api/v3/payments/id
    public function delete_charge($charge_id)
    {
        $sandbox = $this->getSetting('sandbox');
        $debug = $this->getSetting('debug');
        if ($sandbox == '0') {
            $api_key = $this->decryptSetting('api_key');
            $api_url = "https://www.asaas.com";
        } else {
            $api_key = $this->decryptSetting('api_key_sandbox');
            $api_url = "https://sandbox.asaas.com";
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url . "/api/v3/payments/" . $charge_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => array(
                "access_token: " . $api_key,
            ),
        ));
        $response = curl_exec($curl);
         if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
			return false;
        }
        curl_close($curl);
        $response = json_decode($response, TRUE);
        return $response;
    }

    public function create_customer($api_url, $api_key, $post_data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url . "/api/v3/customers",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "access_token: " . $api_key,
            ),
        ));
        $response = curl_exec($curl);
          if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
			return false;
        }
        curl_close($curl);
        $customer = json_decode($response, TRUE);
        return $customer;
    }

    public function get_webhook($api_key, $api_url)
    {
        $ch = curl_init();
// ?description=
        curl_setopt($ch, CURLOPT_URL, $api_url . "/api/v3/webhook");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "access_token: " . $api_key
        ));
        $response = curl_exec($ch);
  if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
			return false;
        }
        curl_close($ch);
        return json_decode($response, TRUE);
    }

    public function create_webhook($api_key, $api_url, $post_data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $api_url . "/api/v3/webhook");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "access_token: " . $api_key
        ));
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
			return false;
        }
        curl_close($ch);
        return json_decode($response, TRUE);
    }

    public function get_webhook_invoice($api_key, $api_url)
    {
        $ch = curl_init();
// ?description=
        curl_setopt($ch, CURLOPT_URL, $api_url . "/api/v3/webhook/invoice");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "access_token: " . $api_key
        ));
        $response = curl_exec($ch);
          if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
			return false;
        }
        
        curl_close($ch);
        return json_decode($response, TRUE);
    }

    public function create_webhook_invoice($api_key, $api_url, $post_data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $api_url . "/api/v3/webhook/invoice");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "access_token: " . $api_key
        ));
        $response = curl_exec($ch);
          if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
			return false;
        }
       
        curl_close($ch);
        return json_decode($response, TRUE);
    }

    public function get_webhook_transfer($api_key, $api_url)
    {
        $ch = curl_init();
// ?description=
        curl_setopt($ch, CURLOPT_URL, $api_url . "/api/v3/webhook/transfer");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "access_token: " . $api_key
        ));
        $response = curl_exec($ch);
       if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
			return false;
        }
        curl_close($ch);
        return $response;
    }

    public function create_webhook_transfer($api_key, $api_url, $post_data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $api_url . "/api/v3/webhook/transfer");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "access_token: " . $api_key
        ));
        $response = curl_exec($ch);
          if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
			return false;
        }
        curl_close($ch);
        return json_decode($response, TRUE);
    }

    function get_state_abbr()
    {
        $estadosBrasileiros = [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins'
        ];
        return $estadosBrasileiros;
    }

    public function get_customer_customfields($id, $fieldto, $slug)
    {
        $ci = &get_instance();
        $ci->db->where('fieldto', $fieldto);
        $ci->db->where('slug', $slug);
        $customfields = $ci->db->get(db_prefix() . 'customfields')->result();
        foreach ($customfields as $row) {
            $ci->db->where('fieldto', $fieldto);
            $ci->db->where('relid', $id);
            $ci->db->where('fieldid', $row->id);
            $customfieldsvalues = $ci->db->get(db_prefix() . 'customfieldsvalues')->row();
        }
        if (isset($customfieldsvalues)) {
            return $customfieldsvalues->value;
        } else {
            return NULL;
        }
    }
}
