<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Tabela de equipas
if (!$CI->db->table_exists(db_prefix() . 'dps_teams')) {
    $CI->db->query("CREATE TABLE `" . db_prefix() . "dps_teams` (
        `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name`        VARCHAR(100)     NOT NULL,
        `area`        ENUM('dental','imo','media') NOT NULL,
        `description` TEXT             NULL,
        `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// Tabela de membros das equipas
if (!$CI->db->table_exists(db_prefix() . 'dps_team_members')) {
    $CI->db->query("CREATE TABLE `" . db_prefix() . "dps_team_members` (
        `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `team_id`    INT(11) UNSIGNED NOT NULL,
        `staff_id`   INT(11) UNSIGNED NOT NULL,
        `role`       ENUM('manager','commercial') NOT NULL DEFAULT 'commercial',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_team_staff` (`team_id`,`staff_id`),
        KEY `fk_tm_team` (`team_id`),
        KEY `fk_tm_staff` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// Inserir as 3 equipas base se ainda não existirem
$teams = [
    ['name' => 'Equipa Dentária',    'area' => 'dental'],
    ['name' => 'Equipa Imobiliário', 'area' => 'imo'],
    ['name' => 'Equipa Media',       'area' => 'media'],
];
foreach ($teams as $team) {
    $exists = $CI->db->where('area', $team['area'])->count_all_results(db_prefix() . 'dps_teams');
    if ($exists == 0) {
        $CI->db->insert(db_prefix() . 'dps_teams', [
            'name'       => $team['name'],
            'area'       => $team['area'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
