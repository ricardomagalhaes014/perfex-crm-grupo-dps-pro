<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Callback extends APP_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('asaas_gateway');
    }

    public function index()
    {			
        if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') == 0) {

            $sandbox = $this->asaas_gateway->getSetting('sandbox');
            if ($sandbox == '0') {
                $api_key = $this->asaas_gateway->decryptSetting('api_key');
                $api_url = "https://www.asaas.com";
            } else {
                $api_key = $this->asaas_gateway->decryptSetting('api_key_sandbox');
                $api_url = "https://sandbox.asaas.com";
            }

            $response = trim(file_get_contents("php://input"));
            $content = json_decode($response);

            $externalReference = $content->payment->externalReference;
            $status = $content->payment->status;
            $billingType = $content->payment->billingType;

            $this->db->where('hash', $externalReference);
            $invoice = $this->db->get('tblinvoices')->row();
			
            // check_invoice_restrictions($invoice->id, $invoiceid->hash);
            if ($invoice) {

                if ($invoice->status !== "2") {

                    if ($status == "RECEIVED" || $status == "CONFIRMED" || $status == "RECEIVED_IN_CASH" ) {
                        $this->asaas_gateway->addPayment([
                            'amount' => $invoice->total,
                            'invoiceid' => $invoice->id,
                            'paymentmode' => 'Asaas',
                            'paymentmethod' => $content->payment->billingType,
                            'transactionid' => $content->payment->id,
                        ]);

                        logActivity('Asaas: Confirmação de pagamento para a fatura ' . $invoice->id . ', com o ID: ' . $externalReference);
                        echo 'Asaas: Confirmação de pagamento para a fatura ' . $invoice->id . ', com o ID: ' . $externalReference;
                    } 
					else {
                        logActivity('Asaas: Estado do pagamento da fatura ' . $invoice->id . ', com o ID: ' . $externalReference . ', Status: ' . $status);
                        echo 'Asaas: Estado do pagamento da fatura ' . $invoice->id . ', com o ID: ' . $externalReference . ', Status: ' . $status;
                    }
                } else {
                    logActivity('Asaas: Falha ao receber callback para a fatura ' . $invoice->id . ', com o ID: ' . $externalReference . ' ');
                    echo 'Asaas: Falha ao receber callback para a fatura ' . $invoice->id . ', com o ID: ' . $externalReference;
                }
            }
        }
    }


}