<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Paytm extends App_Controller
{
    private $sandbox_endpoint_payment = 'https://securegw-stage.paytm.in/theia/processTransaction';
    private $production_endpoint_payment = 'https://securegw.paytm.in/theia/processTransaction';

    public function payment($id, $hash)
    {
        check_invoice_restrictions($id, $hash);

        $this->load->model('invoices_model');

        $data['invoice'] = $this->invoices_model->get($id);

        $this->sent_to_payment($data, $id, $hash);
    }

    public function sent_to_payment($data, $id, $hash){
        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");
        require_once(APPPATH . "../modules/paytm/libraries/encdec_paytm.php");

        if($this->paytm_gateway->getSetting('test_mode_enabled') == '1'){
            $endpoint = $this->sandbox_endpoint_payment;
            $website = "WEBSTAGING";
        }else{
            $endpoint = $this->production_endpoint_payment;
            $website = "DEFAULT";
        }
        
        $checkSum = "";
        $paramList = array();

        $ORDER_ID = "ORDS".$data['invoice']->number.rand(10000, 999999);;
        $CUST_ID  = "CUST".$data['invoice']->client->userid."_". rand(10000, 999999);;
        $INDUSTRY_TYPE_ID = 'Retail';
        $CHANNEL_ID = 'WEB';

        $paramList["MID"] = $this->paytm_gateway->decryptSetting('PAYTM_MERCHANT_MID');
        $paramList["ORDER_ID"] = $ORDER_ID;
        $paramList["CUST_ID"] = $CUST_ID;
        $paramList["INDUSTRY_TYPE_ID"] = $INDUSTRY_TYPE_ID;
        $paramList["CHANNEL_ID"] = $CHANNEL_ID;
        $paramList["TXN_AMOUNT"] = $this->input->get('total');;
        $paramList["WEBSITE"] = $website;
        $paramList["CALLBACK_URL"] = site_url("paytm/success/" . $id ."/". $hash);

        $checkSum = getChecksumFromArray($paramList, $this->paytm_gateway->decryptSetting('PAYTM_MERCHANT_KEY'));

        $page_data['paramList'] = $paramList;
        $page_data['checkSum'] = $checkSum;
        $page_data['PAYTM_TXN_URL'] = $endpoint;

        $this->load->view('send_to_payment', $page_data);
    }

    public function success($id, $hash)
    {
        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");

        check_invoice_restrictions($id, $hash);
        $this->load->model('invoices_model');
        $invoice  = $this->invoices_model->get($id);
        $language = load_client_language($invoice->clientid);

        require_once(APPPATH . "../modules/paytm/libraries/encdec_paytm.php");

        $paytmChecksum = "";
        $paramList = array();
        $isValidChecksum = "FALSE";
        $paramList = $_POST;
        $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : "";
        $isValidChecksum = verifychecksum_e($paramList, $this->paytm_gateway->decryptSetting('PAYTM_MERCHANT_KEY'), $paytmChecksum);
        $amount_paid = $this->input->post('amount_to_pay');

        if ($isValidChecksum == "TRUE") {
            if ($_POST["STATUS"] == "TXN_SUCCESS") {
                $payment_mode = $this->input->post('PAYMENTMODE');
                if(isset($_POST['BANKNAME']) && !empty($this->input->post('BANKNAME'))){
                    $payment_id = $this->input->post('BANKNAME').'-'.$this->input->post('BANKTXNID');
                }else{
                    $payment_id = $this->input->post('BANKTXNID');
                }
                $order_id   = $this->input->post('ORDERID');
                $amount_received = $this->input->post('TXNAMOUNT');
                $result = $this->paytm_gateway->recordPayment($order_id, $payment_id, $amount_received, $payment_mode, $invoice);
                if ($result['success'] === false) {
                    set_alert('warning', $result['error']);
                } else {
                    set_alert('success', $result['message']);
                }
            } else {
                $error = 'Trnasaction Failed, Try again.';
              set_alert('warning', $error);
            }
        }else{
            $error = 'Checksum Error..! Retry, if error repeats contact info@delgon.co';
            set_alert('warning', $error);
        }

        redirect(site_url('invoice/' . $id . '/' . $hash));
    }

}