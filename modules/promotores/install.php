<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('promotores/promotores_model');

$table = db_prefix() . 'promotores';

if (!$CI->db->table_exists($table)) {
    $CI->db->query('CREATE TABLE `' . $table . "` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(191) NOT NULL,
        `company` VARCHAR(191) NULL,
        `phase` VARCHAR(100) NOT NULL DEFAULT 'Não contactado',
        `phone` VARCHAR(100) NULL,
        `email` VARCHAR(191) NULL,
        `website` VARCHAR(191) NULL,
        `address` TEXT NULL,
        `city` VARCHAR(100) NULL,
        `state` VARCHAR(100) NULL,
        `zip` VARCHAR(30) NULL,
        `country` INT NULL,
        `notes` LONGTEXT NULL,
        `assigned` INT NULL,
        `dateadded` DATETIME NOT NULL,
        `lastcontact` DATETIME NULL,
        `addedfrom` INT NOT NULL,
        PRIMARY KEY (`id`),
        KEY `phase` (`phase`),
        KEY `assigned` (`assigned`),
        KEY `addedfrom` (`addedfrom`),
        KEY `state` (`state`),
        KEY `city` (`city`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

$fields = $CI->db->field_data($table);
$existing = array_map(function ($f) { return $f->name; }, $fields);

if (!in_array('state', $existing, true)) {
    $CI->db->query("ALTER TABLE `{$table}` ADD `state` VARCHAR(100) NULL AFTER `city`");
}
if (!in_array('city', $existing, true)) {
    $CI->db->query("ALTER TABLE `{$table}` ADD `city` VARCHAR(100) NULL AFTER `address`");
}

$seedFile = __DIR__ . '/assets/data/promotores_seed.csv';
if (file_exists($seedFile)) {
    $CI->promotores_model->import_csv($seedFile, true);
}
