<?php
defined('BASEPATH') or exit('No direct script access allowed');
if(!$CI->db->table_exists(db_prefix() . 'si_custom_status')) {	
	$CI->db->query('CREATE TABLE `' . db_prefix() . "si_custom_status` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(50) NOT NULL DEFAULT '',
	`order` int(11) NOT NULL DEFAULT '0',
	`color` varchar(7) NOT NULL DEFAULT '#757575',
	`filter_default` int(11) NOT NULL DEFAULT '0',
	`relto` varchar(20) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
	$CI->db->query('ALTER TABLE `' . db_prefix() . 'si_custom_status` AUTO_INCREMENT=200');
}
