<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'mailflow_newsletter_history')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "mailflow_newsletter_history` (
  `id` int(11) NOT NULL,
  `sent_by` text,
  `email_subject` text,
  `email_content` text,
  `sms_content` text,
  `total_emails_to_send` text,
  `total_sms_to_send` text,
  `email_list` text,
  `sms_list` text,
  `emails_sent` text,
  `sms_sent` text,
  `emails_failed` text,
  `sms_failed` text,
  `created_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mailflow_newsletter_history`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mailflow_newsletter_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'mailflow_email_templates')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "mailflow_email_templates` (
  `id` int(11) NOT NULL,
  `template_name` text,
  `template_subject` text,
  `template_content` text,
  `created_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mailflow_email_templates`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mailflow_email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'mailflow_unsubscribed_emails')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "mailflow_unsubscribed_emails` (
  `id` int(11) NOT NULL,
  `email` text,
  `created_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mailflow_unsubscribed_emails`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mailflow_unsubscribed_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'mailflow_smtp_integrations')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "mailflow_smtp_integrations` (
  `id` int(11) NOT NULL,
  `name` text,
  `email_encryption` text,
  `smtp_host` text,
  `smtp_port` text,
  `email` text,
  `smtp_username` text,
  `smtp_password` text,
  `email_charset` text,
  `bcc_all_emails_to` text,
  `can_delete` int default 1,
  `created_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mailflow_smtp_integrations`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mailflow_smtp_integrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'mailflow_scheduled_campaigns')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "mailflow_scheduled_campaigns` (
  `id` int(11) NOT NULL,
  `scheduled_by` text,
  `email_subject` text,
  `email_content` text,
  `email_list` text,
  `email_smtp` text,
  `sms_content` text,
  `sms_list` text,
  `scheduled_to` text,
  `campaign_status` int default 0,
  `scheduled_at` datetime,
  `created_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mailflow_scheduled_campaigns`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'mailflow_scheduled_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

$CI->db->query("INSERT INTO `".db_prefix()."mailflow_smtp_integrations` (`name`, `email_encryption`, `smtp_host`, `smtp_port`, `email`, `smtp_username`, `smtp_password`, `email_charset`, `bcc_all_emails_to`, `can_delete`, `created_at`) VALUES ('Amazon SES', 'tls','email-smtp.us-east-1.amazonaws.com','587','Amazon SES Secret ID', 'Amazon SES Secret ID', null, 'utf-8', null, 1, '2024-09-14 15:38:34');");
$CI->db->query("INSERT INTO `".db_prefix()."mailflow_smtp_integrations` (`name`, `email_encryption`, `smtp_host`, `smtp_port`, `email`, `smtp_username`, `smtp_password`, `email_charset`, `bcc_all_emails_to`, `can_delete`, `created_at`) VALUES ('Mailchimp', 'tls','smtp.mandrillapp.com','587','smtp@domain.com', 'smtp@domain.com', null, 'utf-8', null, 1, '2024-09-14 15:38:34');");
$CI->db->query("INSERT INTO `".db_prefix()."mailflow_smtp_integrations` (`name`, `email_encryption`, `smtp_host`, `smtp_port`, `email`, `smtp_username`, `smtp_password`, `email_charset`, `bcc_all_emails_to`, `can_delete`, `created_at`) VALUES ('Sendgrid', 'tls','smtp.sendgrid.net','587','apikey', 'apikey', null, 'utf-8', null, 1, '2024-09-14 15:38:34');");
$CI->db->query("INSERT INTO `".db_prefix()."mailflow_smtp_integrations` (`name`, `email_encryption`, `smtp_host`, `smtp_port`, `email`, `smtp_username`, `smtp_password`, `email_charset`, `bcc_all_emails_to`, `can_delete`, `created_at`) VALUES ('Mailgun', 'tls','smtp.mailgun.org','587','username@domain.com', 'username@domain.com', null, 'utf-8', null, 1, '2024-09-14 15:38:34');");