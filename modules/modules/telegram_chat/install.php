<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'telegram')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'telegram` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bot_token` varchar(255) NOT NULL,
  `chat_id` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'telegram`
  ADD PRIMARY KEY (`id`);');

$CI->db->query('ALTER TABLE `' . db_prefix() . 'telegram`

MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');
   
}


