<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Excluir os webhooks Twilio da proteção CSRF
// (Twilio faz POST sem token CSRF)
return [
    'admin/dps_voip/twiml_outbound',
    'admin/dps_voip/twiml_inbound',
    'admin/dps_voip/call_status',
    'admin/dps_voip/add_number',
    'admin/dps_voip/update_number',
    'admin/dps_voip/make_call',
];
