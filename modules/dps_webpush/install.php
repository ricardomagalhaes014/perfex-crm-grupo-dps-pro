<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->database();

if (!$CI->db->table_exists('tbl_push_subscriptions')) {
    $CI->db->query("
        CREATE TABLE IF NOT EXISTS tbl_push_subscriptions (
            id int(11) NOT NULL AUTO_INCREMENT,
            staff_id int(11) NOT NULL DEFAULT 0,
            endpoint text NOT NULL,
            p256dh varchar(500) NOT NULL DEFAULT '',
            auth varchar(255) NOT NULL DEFAULT '',
            user_agent varchar(500) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_staff_id (staff_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

add_option('dps_webpush_enabled', '1');
add_option('dps_webpush_notify_leads', '1');
add_option('dps_webpush_notify_tasks', '1');
add_option('dps_webpush_notify_comments', '1');
add_option('dps_webpush_notify_announcements', '1');
add_option('dps_webpush_notify_tickets', '1');
add_option('dps_webpush_vapid_public', 'BAYrb9Ew6-pQn5q9R85LYqwQEbCy3fJx5SsMpqLdlUH22IraXlC8LoKzmJXOSXwBXF1bQfEsCBpYqYzon15VpeM');
add_option('dps_webpush_vapid_private', 'MHcCAQEEIGgdrR0olE0UsFPqg/dMMP+TkjwH0V9A/HYsvdWx5GsPoAoGCCqGSM49AwEHoUQDQgAEBitv0TDr6lCfmr1HzktirBARsLLd8nHlKwymot2VQfbYitpeULwugrOYlc5JfAFcXVtB8SwIGlipjOifXlWl4w==');