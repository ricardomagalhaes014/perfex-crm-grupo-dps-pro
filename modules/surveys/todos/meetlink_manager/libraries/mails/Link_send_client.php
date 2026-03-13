<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Link_send_client extends App_mail_template
{
    public $slug     = 'meetlink-to-client';
    public $rel_type = 'meetlink';
    protected $for   = 'staff';
    protected $meeting_data;

    public function __construct($meeting_data)
    {
        parent::__construct();
        $this->meeting_data = $meeting_data;

    }

    public function build()
    {



        $this->set_merge_fields('meetlink_merge_fields', $this->meeting_data['meetingData']->id);
        $this->set_merge_fields('leads_merge_fields', $this->meeting_data['lead_id']);
        $this->to($this->meeting_data['send_email'])
        ->set_rel_id($this->meeting_data['meetingData']->id);
    }
}
