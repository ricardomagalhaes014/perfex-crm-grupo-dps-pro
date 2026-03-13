<?php

defined('BASEPATH') or exit('No direct script access allowed');

class paystack extends App_Controller
{
    public function verify()
    {
        $invoice_id = isset($_GET['invoiceid']) ? $_GET['invoiceid'] : '';
        if (!$invoice_id) {
            die('uknown transaction');
        }

        $invoice_hash = isset($_GET['hash']) ? $_GET['hash'] : '';
        if (!$invoice_hash) {
            die('No hash supplied');
        }

        check_invoice_restrictions($invoice_id, $invoice_hash);

        if ($this->paystack_gateway->getSetting('test_mode_enabled') == '1') {
            $paystacksecret = $this->paystack_gateway->decryptSetting('paystack_test_Secret_key');
        } else {
            $paystacksecret = $this->paystack_gateway->decryptSetting('paystack_Secret_key');
        }
        $reference = isset($_GET['reference']) ? $_GET['reference'] : '';
        if (!$reference) {
            die('No reference supplied');
        }
        $paystack = new Yabacon\Paystack($paystacksecret);
        try {
            $tranx = $paystack->transaction->verify([
                'reference' => $reference,
            ]);

            if ('success' === $tranx->data->status) {
                $success = $this->paystack_gateway->addPayment([
                    'amount' => $tranx->data->amount / 100,
                    'invoiceid' => $invoice_id,
                    'paymentmethod' => $tranx->data->channel,
                    'transactionid' => $reference,
                ]);
                set_alert($success ? 'success' : 'danger',
                    _l($success ? 'online_payment_recorded_success' : 'online_payment_recorded_success_fail_database'));
            } else {
                set_alert('danger', _l('invoice_payment_record_failed'));
            }
        } catch (\Yabacon\Paystack\Exception\ApiException $e) {
            $errors = $e->getResponseObject();
            set_alert('danger', _l($errors->message.' an error occured <br/> please try again'));
        }
        redirect(site_url('invoice/'.$invoice_id.'/'.$invoice_hash));
    }
}
