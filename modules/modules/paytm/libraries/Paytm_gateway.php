<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Paytm_gateway extends App_gateway
{
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setId('paytm');

        $this->setName('Paytm');
        
        $this->setSettings([
            [
                'name'      => 'PAYTM_MERCHANT_MID',
                'label'     => 'Merchant MID',
                'encrypted' => true,
                'type'      => 'input',
            ],
            [
                'name'  => 'PAYTM_MERCHANT_KEY',
                'label' => 'Merchant Key',
                'encrypted' => true,
                'type'  => 'input',
            ],
            [
                'name'          => 'test_mode_enabled',
                'type'          => 'yes_no',
                'default_value' => 0,
                'label'         => 'settings_paymentmethod_testing_mode',
            ],
            [
                'name'          => 'description_dashboard',
                'label'         => 'settings_paymentmethod_description',
                'type'          => 'textarea',
                'default_value' => 'Payment for Invoice {invoice_number}',
            ],
            [
                'name'          => 'currencies',
                'label'         => 'settings_paymentmethod_currencies',
                'default_value' => 'INR',
            ],
        ]);
    }

    public function process_payment($data)
    {
        $redirectGatewayURI = 'paytm/payment/' . $data['invoiceid'] . '/' . $data['invoice']->hash;
        $redirectPath = $redirectGatewayURI . '?total=' . $data['amount'];
        redirect(site_url($redirectPath));
    }

    public function recordPayment($paytm_order_id, $paytm_payment_id, $amount_received, $payment_mode, $invoice)
    {
        try {
            if (!empty($paytm_order_id) && !empty($paytm_payment_id) && !empty($amount_received)) {
                $this->updateInvoiceTokenData(null, $invoice->id);
                $patmentmethod = $this->paytm_payment_mode($payment_mode);
                $success = $this->addPayment([
                      'amount'        => $amount_received,
                      'invoiceid'     => $invoice->id,
                      'transactionid' => $paytm_payment_id,
                      'paymentmethod' => $patmentmethod,
                ]);
                $message = _l($success ? 'online_payment_recorded_success' : 'online_payment_recorded_success_fail_database');
                return [
                    'success' => $success,
                    'message' => $message,
                ];
            } else {
                return ['success' => false, 'error' => 'The payment is authorized but not captured, consult with administrator to capture the payment.'];
            }
            return ['success' => false, 'error' => $payment->error_description];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function paytm_payment_mode($payment_mode){
        if($payment_mode == 'NB'){
            return 'Net banking';
        }elseif($payment_mode == 'UPI'){
            return 'UPI';
        }elseif($payment_mode == 'NPUPI'){
            return 'Non Paytm UPI';
        }elseif($payment_mode == 'CC'){
            return 'Credit Card';
        }elseif($payment_mode == 'DC'){
            return 'Debit Card';
        }elseif($payment_mode == 'PPI'){
            return 'Paytm Wallet';
        }elseif($payment_mode == 'PAYTMCC'){
            return 'Postpaid';
        }else{
            return 'Unknown';
        }
    }

    public function updateInvoiceTokenData($data, $invoice_id)
    {
        $this->ci->db->where('id', $invoice_id);
        $this->ci->db->update(db_prefix() . 'invoices', [
            'token' => $data,
        ]);
    }
}