<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Uma linha por comercial que ligou a conta Google.
 *
 * O refresh_token é o que importa guardar: o access_token dura uma hora e é
 * renovado sozinho a partir dele. Se o refresh_token se perder, a pessoa tem
 * de voltar a autorizar — não há forma de o recuperar.
 */
$CI = &get_instance();

if (!$CI->db->table_exists(db_prefix() . 'dps_google_contas')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_google_contas` (
        `staff_id` INT(11) NOT NULL,
        `email` VARCHAR(191) NULL,
        `access_token` TEXT NULL,
        `refresh_token` TEXT NULL,
        `expires_at` DATETIME NULL,
        `ultimo_erro` VARCHAR(400) NULL,
        `date_created` DATETIME NOT NULL,
        PRIMARY KEY (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

add_option('dps_google_client_id', '');
add_option('dps_google_client_secret', '');
