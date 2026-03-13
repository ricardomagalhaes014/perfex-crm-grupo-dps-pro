<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Email_send_to_customer extends Lead_manager_mail_template
{
    protected $for = 'customer';
    protected $email_data;
    public $slug = 'lead-manager-send-email-to-customer';
    public $rel_type = 'lead_manager_mailbox';

    public function __construct($data)
    {
        parent::__construct();
        $this->email_data = $data;
    }
    public function build()
    {
        $this->to($this->email_data['to_email'])
            ->set_rel_id($this->email_data['to_id'])
            ->set_lead_manager_merge_fields('lead_manager_mailbox_merge_fields', $this->email_data['to_id'], $this->email_data);
    }
}
