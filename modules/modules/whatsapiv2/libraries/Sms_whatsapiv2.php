<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Sms_whatsapiv2 extends App_sms
{
    // Account SID from 
    private $sid;
    
    public function __construct()
    {
        parent::__construct();
      

        $this->add_gateway('whatsapiv2', [
            'name'    => 'whatsapiv2',
            'info'    => '<p>OS CONTACTOS devem estar no formato internacional sem o +</p><hr class="hr-10" />',
            'options' => [

                [
                    'name'  => 'endpoint',
                    'label' => 'URL da API',
                ],    
				
				 [
                    'name'  => 'authorized',
                    'label' => 'API Key',
                ]
            ],
        ]);
    }
	
	
    public function send($number, $message)
    {
		
		
		$endpoint   = $this->get_option('whatsapiv2', 'endpoint');
		
		//$whatsid   = $this->get_option('whatsapiv2', 'whatsid');
		   
		$authorized   = $this->get_option('whatsapiv2', 'authorized');

        $number= preg_replace('/[^0-9,.]+/', '', $number);
		
    	$send = $this->send_to_api($endpoint, $authorized, $number,  $message);

		
        if($send){
        log_activity('Notificação enviada via Whatsapp para: ' . $number . ', mensagem: ' . $message);
             return true;
        }else{
           $this->set_error('O envio falhou : <BR> Erro: '. $send->message);
             return false;
        }
    }
	
	
    public function send_to_api($endpoint, $authorized, $number, $text)
    {
		$apiData= [ 
           // "whatsappId" => $whatsid,
            "number" => $number,
            "body" => $text      
        ];
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $endpoint,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => json_encode($apiData),
          CURLOPT_HTTPHEADER => array(
		   'Content-Type: application/json',
            'Authorization: Bearer '.$authorized
          )
        ));  
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
			return false;
        }
        curl_close($curl);
		
		
		return $response;
    }
}
