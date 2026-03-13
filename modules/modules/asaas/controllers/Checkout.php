<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Checkout extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('asaas_gateway');
        $this->load->helper('general');
    }

    public function index($hash)
    {
		   $this->db->where('hash', $hash);
        $invoice = $this->db->get(db_prefix() . 'invoices')->row();

          
        $data = [		
		    'billet_only' => $this->asaas_gateway->getSetting('billet_only'),
	    	'card_only' => $this->asaas_gateway->getSetting('card_only'),
	    	'pix_only' => $this->asaas_gateway->getSetting('pix_only'),
			       'invoice' => $invoice,
            'hash' => $hash,
            'title' => 'Asaas'
        ];
        $this->disableNavigation();
        $this->disableSubMenu();
        $this->data($data);
        $this->view('asaas/payment');
        $this->layout();
    }

    public function boleto($hash)
    {
        $this->db->where('hash', $hash);
        $invoice = $this->db->get(db_prefix() . 'invoices')->row();
	
        $data = [];
        $data['invoice'] = $invoice;
		
		
        $charge = $this->asaas_gateway->get_charge($hash);
	
		if($charge) {		
        redirect($charge->bankSlipUrl);
		
		} else {
		// redirect(site_url('checkout/error/' . $invoice->hash), 'refresh');
		  set_alert('warning', 'Não foi possível gerar a cobrança, Fale com o financeiro');
		  
	//$this->session->set_flashdata('error', 'Não foi possível gerar a cobrança, Fale com o financeiro');
		  
		redirect(site_url('invoice/' . $invoice->id .'/' . $invoice->hash), 'refresh');
		}
    }

    public function cartao($hash)
    {
        if ($this->input->post()) {
            $this->db->where('hash', $hash);
            $invoice = $this->db->get(db_prefix() . 'invoices')->row();
            $data = [];
            $data['invoice'] = $invoice;
            $post_data = $this->input->post(NULL, TRUE);
            $data['card'] = [
                'holderName' => $this->input->post('holderName', TRUE),
                'number' => $this->input->post('cardNumber', TRUE),
                'expiryMonth' => $this->input->post('expirationMonth', TRUE),
                'expiryYear' => $this->input->post('expirationYear', TRUE),
                'cvv' => $this->input->post('securityCode', TRUE),
                'installmentCount' => $this->input->post('installmentCount', TRUE),
            ];
            $charge = $this->asaas_gateway->charge_credit_card($data);

            $charge = json_decode($charge, TRUE);

            if (isset($charge["errors"])) {
                $charge["errors"][0]["description"];
                set_alert('warning', $charge["errors"][0]["description"]);
                redirect(site_url('asaas/checkout/cartao/' . $invoice->hash), 'refresh');
                die();
            }
			
           // $redirect_url = $this->asaas_gateway->getSetting('asaas_redirect');
			
          //  redirect($redirect_url, 'refresh');
		  
		  redirect(site_url('invoice/' . $invoice->id .'/' . $invoice->hash), 'refresh');
        }

        $installmentCount = $this->asaas_gateway->getSetting('installmentCount');

        $this->db->where('hash', $hash);
        $invoice = $this->db->get(db_prefix() . 'invoices')->row();
		
		   $invoice->total_left_to_pay = get_invoice_total_left_to_pay($invoice->id, $invoice->total);

        $data = [
            'hash' => $hash,
            'invoice' => $invoice,
            'installmentCount' => $installmentCount,
            'title' => 'Asaas'
        ];
        $this->disableNavigation();
        $this->disableSubMenu();
        $this->data($data);
        $this->view('asaas/cartao');
        $this->layout();
    }

    public function qrcode($hash)
    {
        $this->db->where('hash', $hash);
        $invoice = $this->db->get(db_prefix() . 'invoices')->row();
		
		   $invoice->total_left_to_pay = get_invoice_total_left_to_pay($invoice->id, $invoice->total);
		
        $data = [];
        $data['invoice'] = $invoice;
      
        $billet = $this->asaas_gateway->get_charge($hash);
	
        $qrcode = $this->asaas_gateway->create_qrcode($billet->id);
			
        $data = [
            'title' => 'Asaas',
            'invoice' => $invoice,
            'response' => json_decode($qrcode),
        ];
        $this->disableNavigation();
        $this->disableSubMenu();
        $this->data($data);
        $this->view('asaas/qrcode');
        $this->layout();
    }
	
	  public function error($hash)
    {
		
		    $data = [
            'title' => 'Asaas',
         
        ];
        $this->disableNavigation();
        $this->disableSubMenu();
        $this->data($data);
        $this->view('asaas/error');
        $this->layout();
	}
}