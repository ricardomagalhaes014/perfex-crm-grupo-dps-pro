<?php

defined('BASEPATH') or exit('No direct script access allowed');

class paystack_gateway extends App_gateway
{

    public function __construct()
    {
        $this->ci = &get_instance();
        /**
         * REQUIRED
         * Gateway unique id
         * The ID must be alpha/alphanumeric
         */
        $this->setId('paystack');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('paystack');

        /**
         * Add gateway settings
         */
        $this->setSettings(
            [
                [
                    'name' => 'paystack_epc',
                    'encrypted' => true,
                    'label' => 'Envato Purchase Code',
                ],
                [
                    'name' => 'paystack_public_key',
                    'encrypted' => true,
                    'label' => 'Public Key',
                ],
                [
                    'name' => 'paystack_Secret_key',
                    'encrypted' => true,
                    'label' => 'Secret key',
                ],
                [
                    'name' => 'paystack_test_Secret_key',
                    'encrypted' => true,
                    'label' => 'test Secret key',
                ],
                [
                    'name' => 'currencies',
                    'label' => 'settings_paymentmethod_currencies',
                    'default_value' => 'NGN',
                ],
                [
                    'name' => 'test_mode_enabled',
                    'type' => 'yes_no',
                    'default_value' => 1,
                    'label' => 'settings_paymentmethod_testing_mode',
                ],
            ]
        );
    }

    /**
     * REQUIRED FUNCTION
     * @param  array  $data
     * @return mixed
     */
    public function process_payment($data)
    {
        $prave = "https://boxvibe.com/pcode.php?epc=".$this->decryptSetting('paystack_epc');
        if (file_get_contents($prave) == 20024076921) {
            if ($this->getSetting('test_mode_enabled') == '1') {
                $paystacksecret = $this->decryptSetting('paystack_test_Secret_key');
            } else {
                $paystacksecret = $this->decryptSetting('paystack_Secret_key');
            }
            $paystack = new Yabacon\Paystack($paystacksecret);
            $refere = format_invoice_number($data['invoice']->id).'-'.time();
            $reference = str_replace('/', '', $refere);
            $calurl = site_url('paystack/verify?invoiceid='.$data['invoiceid'].'&hash='.$data['invoice']->hash);
            try {
                $email = null;
                $pamount = number_format($data['amount'], 2, '.', '');
                $koboamount = $pamount * 100;
                if (is_client_logged_in()) {
                    $contact = $this->ci->clients_model->get_contact(get_contact_user_id());
                    if ($contact->email) {
                        $email = $contact->email;
                    }
                } else {
                    $contacts = $this->ci->clients_model->get_contacts($data['invoice']->clientid);
                    if (count($contacts) == 1) {
                        $contact = $contacts[0];
                        if ($contact['email']) {
                            $email = $contact['email'];
                        }
                    }
                }
                $tranx = $paystack->transaction->initialize([
                    'amount' => $koboamount,       // in kobo
                    'email' => $email,
                    'reference' => $reference,
                    'callback_url' => $calurl,
                ]);
                header('Location: '.$tranx->data->authorization_url);
            } catch (\Yabacon\Paystack\Exception\ApiException $e) {
                $errors = $e->getResponseObject();
                set_alert('danger', _l($errors->message));
            }
        } else {
            set_alert('danger', _l('invalid purchase code'));
        }
    }
}
