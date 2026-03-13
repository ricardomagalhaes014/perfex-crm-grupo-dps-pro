
<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Property_request_send_to_client extends App_mail_template
{
    protected $for = 'customer';

    protected $request;

    protected $contact;

    public $slug = 'property-request-send-to-client';

    public $rel_type = 'realestate';

    public function __construct($request, $contact, $cc = '')
    {
        parent::__construct();

        $this->request = $request;
        $this->contact = $contact;
        $this->cc      = $cc;

    }

    public function build()
    {
        $this->to($this->contact->email)
        ->set_rel_id($this->request->id)
        ->set_merge_fields('client_merge_fields', $this->request->clientid, $this->contact->id)
        ->set_merge_fields('property_request_merge_fields', $this->request->id);
    }
}
