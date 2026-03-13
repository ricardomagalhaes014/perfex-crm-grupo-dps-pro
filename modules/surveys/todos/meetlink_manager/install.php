<?php

defined('BASEPATH') or exit('No direct script access allowed');


$CI->db->query('SET foreign_key_checks = 0');
if (!$CI->db->table_exists(db_prefix().'meeting_services')) {
    $CI->db->query('CREATE TABLE `'.db_prefix().'meeting_services` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `service_name` VARCHAR(100) NOT NULL,
        `service_url` VARCHAR(200) NOT NULL,
        `created_by` INT NOT NULL,
        `created_datetime` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET='.$CI->db->char_set.';');
}

if (!$CI->db->table_exists(db_prefix().'meetings')) {
    $CI->db->query('CREATE TABLE `'.db_prefix().'meetings` (
		`id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `meeting_date` DATE NOT NULL,
        `meeting_time` TIME NOT NULL,
        `service_id` INT NOT NULL,
        `meeting_url` VARCHAR(200) NOT NULL, 
        `created_by` INT NOT NULL,
        `created_datetime` DATETIME DEFAULT CURRENT_TIMESTAMP
	) ENGINE = InnoDB DEFAULT CHARSET='.$CI->db->char_set.';');
}


if (!$CI->db->table_exists(db_prefix().'meeting_participants')) {
    $CI->db->query('CREATE TABLE `'.db_prefix().'meeting_participants` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `meeting_id` INT NOT NULL,
        `participant_type` ENUM("Customer", "Lead", "Staff") NOT NULL,
        `participant_id` INT NOT NULL,
        `created_by` INT NOT NULL,
        `created_datetime` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB DEFAULT CHARSET='.$CI->db->char_set.';');
}

if (!$CI->db->table_exists(db_prefix().'meeting_invitations')) {
    $CI->db->query('CREATE TABLE `'.db_prefix().'meeting_invitations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `meeting_id` INT NOT NULL,
        `participant_id` INT NOT NULL,
        `invitation_status` ENUM("Sent", "Resent", "Failed") DEFAULT "Sent",
        `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET='.$CI->db->char_set.';');
}



$CI->db->query('SET foreign_key_checks = 1');

$email_template[0]['type']     = 'meetlink';
$email_template[0]['slug']     = 'meetlink-to-admin';
$email_template[0]['language'] = 'english';
$email_template[0]['name']     = 'Send Meeting Link For Admin';
$email_template[0]['subject']  = 'Join the Meeting';
$email_template[0]['message']  = "<em>You received a new order {order_id} with a total amount of {total} {currency}  {invoice_number}{invoice_link}</em>";
$email_template[0]['message']  = "<em>I hope you're doing well. We have scheduled a meeting to discuss [specific topic, e.g., the email-sending process or any relevant administrative subject]. Below are the meeting details:

Date: [Insert Date]
Time: [Insert Time]
Meeting Link: [Insert meeting link here, e.g., from Google Meet or Zoom]

Please join the meeting using the link provided. Let me know if you have any questions or if you're unable to attend.

Looking forward to seeing you there.</em>";

$email_template[0]['fromname'] = '{companyname}';
$email_template[0]['active']   = '1';

$email_template[1]['type']     = 'meetlink';
$email_template[1]['slug']     = 'meetlink-to-client';
$email_template[1]['language'] = 'english';
$email_template[1]['name']     = 'Send Meeting Link For Client';
$email_template[1]['subject']  = 'Join the Meeting';
$email_template[1]['message']  = "<em>I hope you're doing well. We have scheduled a meeting to discuss [specific topic, e.g., the email-sending process or any relevant administrative subject]. Below are the meeting details:

Date: [Insert Date]
Time: [Insert Time]
Meeting Link: [Insert meeting link here, e.g., from Google Meet or Zoom]

Please join the meeting using the link provided. Let me know if you have any questions or if you're unable to attend.

Looking forward to seeing you there.</em>";
$email_template[1]['fromname'] = '{companyname}';
$email_template[1]['active']   = '1';


$email_template[2]['type']     = 'meetlink';
$email_template[2]['slug']     = 'meetlink-to-staff';
$email_template[2]['language'] = 'english';
$email_template[2]['name']     = 'Send Meeting Link For staff';
$email_template[2]['subject']  = 'Join the Meeting';
$email_template[2]['message']  = "<em>I hope you're doing well. We have scheduled a meeting to discuss [specific topic, e.g., the email-sending process or any relevant administrative subject]. Below are the meeting details:

Date: [Insert Date]
Time: [Insert Time]
Meeting Link: [Insert meeting link here, e.g., from Google Meet or Zoom]

Please join the meeting using the link provided. Let me know if you have any questions or if you're unable to attend.

Looking forward to seeing you there.</em>";
$email_template[2]['fromname'] = '{companyname}';
$email_template[2]['active']   = '1';



$CI->db->where('type', 'meetlink');
$result = $CI->db->get(db_prefix().'emailtemplates')->row();
if (empty($result)) {
    $CI->db->insert_batch(db_prefix().'emailtemplates', $email_template);
}
